<?php

if ( ! function_exists( 'thistle_widget_categories_current_category' ) ) {
	/**
	 * Defines the current category within the Categories widget
	 * when we are on a single post.
	 *
	 * @param array $cat_args An array of Categories widget options.
	 * @return array
	 */
	function thistle_widget_categories_current_category( $cat_args ) {
		if ( is_singular( 'post' ) ) {
			$categories = get_the_category();

			$cat_args['current_category'] = $categories[0]->term_id;
			$cat_args['selected'] = $categories[0]->term_id;
		}

		return $cat_args;
	}
}
add_filter( 'widget_categories_args', 'thistle_widget_categories_current_category' );
add_filter( 'widget_categories_dropdown_args', 'thistle_widget_categories_current_category' );
