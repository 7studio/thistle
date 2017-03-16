<?php

if ( ! function_exists( 'thistle_has_gallery_shortcode' ) ) {
	/**
	 * Checks if post has a shortcode for a gallery into its content.
	 *
	 * @param int|WP_Post $post Optional. Post ID or WP_Post object. Default is global `$post`.
	 * @return bool Whether the post content contains a shortcode for a gallery.
	 */
	function thistle_has_gallery_shortcode( $post = null ) {
		$post = get_post( $post );

		if ( ! $post ) {
			return false;
		}

		return has_shortcode( $post->post_content, 'gallery' );
	}
}

if ( ! function_exists( 'thistle_register_gallery_script' ) ) {
	/**
	 * Registers script to use PhotoSwipe with the WordPress galleries.
	 */
	function thistle_register_gallery_script() {
		$pswp_options = apply_filters( 'thitstle_pswp_options', array(
			'history' => false,
    		'shareEl' => false,
    		'fullscreenEl' => false
		) );

        $min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_register_script( 'thistle-imagegallery', THISTLE_CHILD_URI . '/assets/scripts/imagegallery' . $min . '.js', array(), null, true );
		wp_localize_script( 'thistle-imagegallery', 'PSWP_OPTIONS', $pswp_options );
	}
}
add_action( 'init', 'thistle_register_gallery_script' );


if ( ! function_exists( 'thistle_gallery_shortcode' ) ) {
	/**
	 * Builds the new Gallery shortcode output with a custom HTML markup and
	 * a handy JSON to interact easily with PhotoSwipe.
	 *
	 * It's a little bit violent to copy/past half of the `gallery_shortcode` function
	 * but any hook allows us to work just before the HTML construction :(
	 *
	 * @param string $output   The gallery output. Default empty.
	 * @param array  $attr     Attributes of the gallery shortcode.
	 * @param int    $instance Unique numeric ID of this gallery shortcode instance.
	 * @return string HTML content to display gallery.
	 */
	function thistle_gallery_shortcode( $output, $attr, $instance ) {
	 	$post = get_post();
		$atts = shortcode_atts( array(
			'order'      => 'ASC',
			'orderby'    => 'menu_order ID',
			'id'         => $post ? $post->ID : 0,
			'itemtag'    => 'figure',
			'captiontag' => 'figcaption',
			'columns'    => 3,
			'size'       => 'thumbnail',
			'include'    => '',
			'exclude'    => '',
			'link'       => ''
		), $attr, 'gallery' );

		$id = intval( $atts['id'] );

		if ( ! empty( $atts['include'] ) ) {
			$_attachments = get_posts( array( 'include' => $atts['include'], 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $atts['order'], 'orderby' => $atts['orderby'] ) );
			$attachments = array();
			foreach ( $_attachments as $key => $val ) {
				$attachments[$val->ID] = $_attachments[$key];
			}
		} elseif ( ! empty( $atts['exclude'] ) ) {
			$attachments = get_children( array( 'post_parent' => $id, 'exclude' => $atts['exclude'], 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $atts['order'], 'orderby' => $atts['orderby'] ) );
		} else {
			$attachments = get_children( array( 'post_parent' => $id, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $atts['order'], 'orderby' => $atts['orderby'] ) );
		}
		if ( empty( $attachments ) ) {
			return '';
		}
		if ( is_feed() ) {
			$output = "\n";
			foreach ( $attachments as $att_id => $attachment ) {
				$output .= wp_get_attachment_link( $att_id, $atts['size'], true ) . "\n";
			}
			return $output;
		}

		$json = array();
		$output = '<div class="ImageGallery" data-instance="' . esc_attr( $instance ) . '" data-columns="' . esc_attr( $atts['columns'] ) . '" data-size="' . esc_attr( $atts['size'] ) . '">';

		$i = 0;
		foreach ( $attachments as $id => $attachment ) {
			$image_meta  = wp_get_attachment_metadata( $id );
			$image_url = wp_get_attachment_url( $id );
			$image_thumb_url = wp_get_attachment_thumb_url( $id );

			$json[$i] = array(
				'src'  => $image_url,
				'w'    => $image_meta['width'],
				'h'    => $image_meta['height'],
				'msrc' => $image_thumb_url
			);

			$output .= '<figure class="ImageGallery-item">';
			$output .= '<a class="ImageGallery-link" href="' . $image_url . '">';
			$output .= wp_get_attachment_image( $id, $atts['size'], false, array( 'class' => 'ImageGallery-thumbnail' ) );
			$output .= '</a>';
			if ( trim( $attachment->post_excerpt ) ) {
				$caption = wptexturize( $attachment->post_excerpt );
				$json[$i]['title'] = $caption;

				$output .= '<figcaption class="ImageGallery-caption">';
				$output .= $caption;
				$output .= '</figcaption>';
			}
			$output .= "</figure>";

			$i++;
		}
		$output .= '<script type="text/pswp">' . json_encode( $json ) . '</script>';
		$output .= "</div>" . "\n";

		return $output;
	}
}
add_filter( 'post_gallery', 'thistle_gallery_shortcode', 10, 3 );

if ( ! function_exists( 'thistle_print_editor_gallery_template' ) ) {
	/**
	 * Prints the custom Backbone template for the new HTML markup
	 * of the WordPress galleries.
	 */
	function thistle_print_editor_gallery_template() {
		?>
		<script type="text/html" id="tmpl-thistle-editor-gallery">
			<# if ( data.attachments.length ) { #>
				<div class="ImageGallery" data-columns="{{ data.columns }}" data-size="{{ data.size }}">
					<# _.each( data.attachments, function( attachment, index ) { #>
						<figure class="ImageGallery-item">
							<a class="ImageGallery-link">
								<# if ( attachment.thumbnail ) { #>
									<img class="ImageGallery-thumbnail" src="{{ attachment.thumbnail.url }}" alt="" />
								<# } else { #>
									<img class="ImageGallery-thumbnail" src="{{ attachment.url }}" alt="" />
								<# } #>
							</a>
							<# if ( attachment.caption ) { #>
								<figcaption class="ImageGallery-caption">
									{{{ data.verifyHTML( attachment.caption ) }}}
								</figcaption>
							<# } #>
						</figure>
					<# } ); #>
				</div>
			<# } else { #>
				<div class="wpview-error">
					<div class="dashicons dashicons-format-gallery"></div><p><?php _e( 'No items found.' ); ?></p>
				</div>
			<# } #>
		</script>
		<?php
	}
}
add_action( 'print_media_templates', 'thistle_print_editor_gallery_template' );

if ( ! function_exists( 'thistle_mce_view_gallery' ) ) {
	/**
	 * Sets the new template for WordPress view which manages live previewing of
	 * WordPress galleries while editing a post.
	 *
	 * @link https://codex.wordpress.org/wp.mce.views
	 */
	function thistle_mce_view_gallery() {
		?>
		<script>
			( function( window, wp, $, undefined ) {
				if ( typeof wp !== 'undefined' && wp.mce && wp.media ) {
					var gallery = wp.mce.views.get( 'gallery' );

					gallery.prototype.template = wp.media.template( 'thistle-editor-gallery' );
				}
			} )( window, window.wp, window.jQuery );
		</script>
		<?php
	}
}
add_action( 'admin_print_footer_scripts', 'thistle_mce_view_gallery' );
