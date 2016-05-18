<?php

/**
 * Does not replace double line-breaks with paragraph elements into the excerpt output.
 * Unwraps the excerpt output from paragraph element.
 */
remove_filter( 'the_excerpt', 'wpautop' );

if ( ! function_exists( 'thistle_excerpt_more' ) ) {
	/**
	 * Replaces the string in the "more" link displayed after a trimmed excerpt.
	 *
	 * The default value is " […]".
	 *
	 * @param string $more_string The string shown within the more link.
	 * @return string
	 */
	function thistle_excerpt_more( $more_string ) {
		return '&hellip;';
	}
}
add_filter( 'excerpt_more', 'thistle_excerpt_more' );

if ( ! function_exists( 'thistle_excerpt_length' ) ) {
	/**
	 * Defines the number of words in an excerpt.
	 *
	 * The default value is 55.
	 *
	 * @param int $number The number of words.
	 * @return int
	 */
	function thistle_excerpt_length( $length ) {
		return 55;
	}
}
add_filter( 'excerpt_length', 'thistle_excerpt_length' );

if ( ! function_exists( 'thistle_trim_excerpt' ) ) {
	/**
	 * Applies the same treatment to the excerpt if it comes from
	 * the `post_excerpt` field or if it picks up from the `post_content` one.
	 *
	 * @see wp-includes/default-filters.php#144
 	 * @see wp-includes/formatting.php#L2897
	 *
	 * @param string $text        The trimmed text.
	 * @param string $raw_excerpt The text prior to trimming.
	 */
	function thistle_trim_excerpt( $text, $raw_text ) {
		// Excerpts come from `post_content`.
		if ( $raw_text == '' ) {
			$text = get_the_content('');
		}

		$excerpt_length = apply_filters( 'excerpt_length', 55 );
		$excerpt_more = apply_filters( 'excerpt_more', ' ' . '[&hellip;]' );

		$text = strip_shortcodes( $text );
		$text = strip_tags( wp_kses_no_null( trim( $text ) ) );
		$text = wp_trim_words( $text, $excerpt_length, $excerpt_more );

		return $text;
	}
}
add_filter( 'wp_trim_excerpt', 'thistle_trim_excerpt', 10, 2 );

/**
 * Moves the excerpt meta box above the editor.
 *
 * In fact, we can't add metaboxes after the title by default in WP. We will register
 * our own meta box position `after_title` onto which we will add our new meta boxes and
 * calling them in the `edit_form_after_title` hook which is run after the post tile box is displayed.
 *
 * @link https://ozthegreat.io/wordpress/wordpress-how-to-move-the-excerpt-meta-box-above-the-editor
 */
if ( ! function_exists( 'thistle_remove_postexcerpt_meta_box' ) ) {
	/**
	 * Removes the regular excerpt metabox and resets the order of metaboxes
	 * on editing pages.
	 *
	 * Defining metabox in another context is not enough if it's already saved
	 * somewherelse before. We need to remove the metabox from its old context
	 * and let WordPress autosave the new order and context of each metaboxes.
	 * WordPress will update the order when the users drag and drop meta boxes.
	 */
	function thistle_remove_postexcerpt_meta_box() {
		$post_types = get_post_types( array( 'public' => true ), 'names' );
		$user_id = get_current_user_id();

		foreach ( $post_types as $post_type ) {
			if ( post_type_supports( $post_type, 'excerpt' ) ) {
				remove_meta_box( 'postexcerpt' , $post_type, 'normal' );

				$meta_key = 'meta-box-order_' . $post_type;
				$meta_box_order = get_user_meta( $user_id, $meta_key, true );

				if ( $meta_box_order != false && ! isset( $meta_box_order['after_title'] ) ) {
					$meta_box_order = array_map( function( $item ) { return trim( preg_replace( '/,?postexcerpt/', '', $item ), ',' );  }, $meta_box_order );
			        update_user_meta( $user_id, $meta_key, $meta_box_order );
			    }
			}
		}
	}
}
add_action( 'admin_menu' , 'thistle_remove_postexcerpt_meta_box' );

if ( ! function_exists( 'thistle_add_postexcerpt_meta_box' ) ) {
	/**
	 * Adds the excerpt metabox back in with a custom screen location
	 *
	 * @see wp-admin/edit-form-advanced.php#L269
	 *
	 * @param string $post_type Post type.
	 */
	function thistle_add_postexcerpt_meta_box( $post_type ) {
	    if ( post_type_supports( $post_type, 'excerpt' ) ) {
	        add_meta_box(
	            'postexcerpt',
	            __( "Excerpt" ),
	            'post_excerpt_meta_box',
	            null,
	            'after_title',
				'core'
	        );
	    }
	}
}
add_action( 'add_meta_boxes', 'thistle_add_postexcerpt_meta_box' );

if ( ! function_exists( 'thistle_run_after_title_meta_boxes' ) ) {
	/**
	 * Outputs all metaboxes registered to the specific context "after_title".
	 *
	 * @global array $wp_meta_boxes
	 *
	 * @param WP_Post $post Post object.
	 */
	function thistle_run_after_title_meta_boxes( $post ) {
		global $wp_meta_boxes;

		if ( isset( $wp_meta_boxes[ $post->post_type ], $wp_meta_boxes[ $post->post_type ]['after_title'] ) ) {
			do_meta_boxes( get_current_screen(), 'after_title', $post );
		}
	}
}
add_action( 'edit_form_after_title', 'thistle_run_after_title_meta_boxes' );

if ( ! function_exists( 'thistle_postexcerpt_meta_box_assets' ) ) {
	/**
	 * Forbids the drag and drop of the excerpt metabox and adjusts some styles.
	 */
	function thistle_postexcerpt_meta_box_assets() {
	?>
	<style>
		#postexcerpt {
			margin-bottom: 0;
			margin-top: 20px;
		}
		#postexcerpt h3 {
			border-bottom: 1px solid #eee;
		}
	</style>
	<script>
		( function( window, $, undefined ) {
			if ( typeof $ !== 'undefined' ) {
				$( document ).ready( function () {
					var metabox = $( "#after_title-sortables" );

					metabox
						.removeClass( "ui-sortable" )
						.find( ".hndle" )
							.removeClass( "hndle" );
				} );
			}
		} )( window, window.jQuery );
	</script>
	<?php
	}
}
add_action( 'admin_head', 'thistle_postexcerpt_meta_box_assets' );
