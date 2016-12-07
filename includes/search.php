<?php

if ( ! is_admin() ) {
	/**
	 * Retrieves search permalink following an unique permalink
	 * structure: `%search_base%/?s=%search%`.
	 *
	 * For the moment, WordPress allows two schemes:
	 *
	 * - `/?s=`
	 * - `/{$wp_rewrite->search_base}/%search%/` even if it does not work well
	 *    without some tricks: http://coffeecupweb.com/how-to-change-search-permalink-in-wordpress/
	 *
	 * Note that if `$_GET['s']` exists in the URL on any pages,
	 * this param will alter the main query which will show posts based ALSO on a keyword search.
	 * e.g.: `/category/%category%/?s=%search%` -> `new WP_Query( array( 'cat' => , 's' => ) )`
	 *
	 * Plus, WordPress does not handle correctly the document title in this particular case.
	 * e.g.: `/category/%category%/?s=%search%` -> 'Search Results for &#8220;%s&#8221;'
	 *
	 * TODO: try this point with a default WP installation
	 */

	if ( ! function_exists( 'thistle_set_search_base' ) ) {
		/**
		 * Translates the base for the search permalink structure depending on
		 * the locale used.
		 * By default, whatever the langage, the base is `search`.
		 *
		 * @see wp-includes/class-wp-rewrite.php#L85
		 *
		 * @global WP_Rewrite $wp_rewrite
		 */
		function thistle_set_search_base() {
		    global $wp_rewrite;

		    $wp_rewrite->search_base = mb_strtolower( __( 'Search' ) );
		    $wp_rewrite->flush_rules();
		}
	}
	add_action( 'init', 'thistle_set_search_base' );

	if ( ! function_exists( 'thistle_search_rewrite_rules' ) ) {
		/**
		 * Changes all rewrite rules used for search archives.
		 *
		 * @global WP_Rewrite $wp_rewrite
		 *
		 * @param array $rules The compiled array of rewrite rules.
		 * @return array An associate array of matches and queries.
		 */
		function thistle_search_rewrite_rules( $rules ) {
			global $wp_rewrite;

	        $search_rules = $wp_rewrite->generate_rewrite_rules( 'recherche', EP_SEARCH, true, true, false, false );
	        $search_rules = array_map( function( $r ) { return $r . '&s='; }, $search_rules );
	        $search_rules[ $wp_rewrite->search_base . '/?$' ] = 'index.php?s=';

			return array_merge($search_rules, $rules);
		}
	}
	add_filter( 'search_rewrite_rules', '__return_empty_array' );
	add_filter( 'rewrite_rules_array', 'thistle_search_rewrite_rules' );

	if ( ! function_exists( 'thistle_search_query_vars' ) ) {
		/**
		 * Omits to parse search query as soon as we are not
		 * on a search result page archive.
		 *
		 * @global WP_Rewrite $wp_rewrite
		 * @global WP $wp
		 *
		 * @param array $vars
		 * @return
		 */
		function thistle_search_query_vars( $vars ) {
			global $wp, $wp_rewrite;

			if ( $wp->request != $wp_rewrite->search_base ) {
				$index = array_search( 's', $vars );

				unset( $vars[ $index ] );
			}

			return $vars;
		}
	}
	add_action( 'query_vars', 'thistle_search_query_vars' );

	if ( ! function_exists( 'thistle_search_link' ) ) {
		/**
		 * Retrieves the search permalink without taking into account the
		 * old search permalink structure.
		 *
		 * @global WP_Rewrite $wp_rewrite
		 *
		 * @param string $link   Search permalink.
	 	 * @param string $search The URL-encoded search term.
		 * @return string The search permalink.
		 */
		function thistle_search_link( $link, $search ) {
			global $wp_rewrite;

			$permastruct = $wp_rewrite->get_search_permastruct();

			$link = str_replace( '%search%', '', $permastruct );
			$link = home_url( user_trailingslashit( $link, 'search' ) );
			$link = add_query_arg( 's', $search, $link );

			return $link;
		}
	}
	add_filter( 'search_link', 'thistle_search_link', 10, 2 );

	if ( ! function_exists( 'thistle_highlight_search_terms' ) ) {
		/**
		 * Highlights searched terms into title, excerpt and content of posts
		 * when we are on a search result page archive. The search terms will
		 *
		 * @global WP_Query $wp_query
		 *
		 * @param string $text The post title, excerpt or content.
		 * @return string
		 */
		function thistle_highlight_search_terms( $text ) {
			global $wp_query;

			if ( $wp_query->is_main_query() && is_search() && isset( $wp_query->query_vars['search_terms'] ) && ! empty( $wp_query->query_vars['search_terms'] ) ) {
				$search_terms = $wp_query->query_vars['search_terms'];

				return preg_replace ('/(' . implode( '|', array_map( function( $t ) { return preg_quote( $t, '/' ); }, $search_terms ) ) . ')/iu', '<mark>$0</mark>', $text );
			}

			return $text;
		}
	}
	add_filter( 'the_title', 'thistle_highlight_search_terms', 999, 1 );
	add_filter( 'the_excerpt', 'thistle_highlight_search_terms', 999 );
	add_filter( 'the_content', 'thistle_highlight_search_terms', 999 );

}
