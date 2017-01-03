<?php

if ( ! function_exists( 'thistle_set_author_base' ) ) {
	/**
	 * Translates the base for the author permalink structure depending on
	 * the locale used.
	 * By default, whatever the langage, the base is `author`.
	 *
	 * @see wp-includes/class-wp-rewrite.php#L49
	 *
	 * @global WP_Rewrite $wp_rewrite
	 */
	function thistle_set_author_base() {
	    global $wp_rewrite;

	    $wp_rewrite->author_base = mb_strtolower( __( 'Author' ) );
	    $wp_rewrite->flush_rules();
	}
}
add_action( 'init', 'thistle_set_author_base' );

if ( ! function_exists( 'thistle_remove_feed_author' ) ) {
    /**
     * Returns nothing for the name of the current post author
     * in feeds if the current post type does not support this feature.
     *
     * @global WP_Post $post The global `$post` object.
     *
     * @param string|null The author's display name.
     * @return string|null
     */
    function thistle_remove_feed_author( $display_name ) {
        global $post;

        if ( is_feed() && ! post_type_supports( $post->post_type, 'author' ) ) {
            return null;
        }

        return $display_name;
    }
}
add_filter( 'the_author', 'thistle_remove_feed_author', PHP_INT_MAX );
