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
