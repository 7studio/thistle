<?php

if ( ! function_exists( 'thistle_remove_pagination_rewrite_rules' ) ) {
    /**
     * Removes all rewrite rules which concern archive pagination.
     *
     * @param array $rules The compiled array of rewrite rules.
     * @return
     */
    function thistle_remove_pagination_rewrite_rules( $rules ) {
        return array_filter( $rules, function( $value ) { return mb_strpos( $value, '&paged=' ) === false; } );
    }
}
add_filter( 'rewrite_rules_array', 'thistle_remove_pagination_rewrite_rules', PHP_INT_MAX );

if ( ! function_exists( 'thistle_redirect_first_page' ) ) {
    /**
     * Redirects requests which are looking for the first page of results or
     * multiple pages post with the `page` or `paged` parameter
     * to their permalink.
     */
    function thistle_redirect_first_page_result() {
        if ( isset( $_GET['page'] )
            && (empty( $_GET['page'] ) || $_GET['page'] <= 1) )
        {
            $requested_url  = 'http' . ( is_ssl() ? 's' : '' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $redirect_url = remove_query_arg( array( 'page', 'paged' ) ,  $requested_url );

            wp_redirect( $redirect_url, 301 );
            die;
        }
    }
}
add_action( 'template_redirect', 'thistle_redirect_first_page_result' );

if ( ! function_exists( 'thistle_fill_paged_query_var' ) ) {
    /**
     * Fills the `paged` Query parameter (the number of the current page) with
     * the value of the `page` GET parameter on archive pages.
     *
     * Introduces a new behaviour which allows WP to return all results if
     * `$_GET['page']` is egal to `all`.
     *
     * @param WP_Query &$query The WP_Query instance (passed by reference).
     */
    function thistle_fill_paged_query_var( $query ) {
        if ( $query->is_main_query()
            && (is_home() || is_archive() || is_search())
            && (isset( $_GET['page'] ) && ! empty( $_GET['page'] )) )
        {
            $query->set( 'paged', intval( $_GET['page'] ) );
            $query->is_paged = true;

            unset( $query->query_vars['page'] );

            if ( $_GET['page'] === 'all' ) {
                $query->set( 'nopaging', true );
                $query->set( 'no_found_rows', true );
            }
        }
    }
}
add_action( 'pre_get_posts', 'thistle_fill_paged_query_var' );

if ( ! function_exists( 'thistle_avoid_pagination_redirect' ) ) {
    /**
     * Forbids WP to redirect new URLs (e.g.: `articles/?page=2`) to the old ones.
     *
     * This function doesn't redirect old URLs (e.g.: `articles/page/2/`)
     * to the new ones which are considered as 404.
     *
     * @global WP_Query $wp_query
     * @global WP_Rewrite $wp_rewrite
     *
     * @param string $redirect_url  The redirect URL.
     * @param string $requested_url The requested URL.
     * @return string The string of the URL, if redirect needed.
     */
    function thistle_avoid_pagination_redirect( $redirect_url, $requested_url ) {
        global $wp_query, $wp_rewrite;

        // If on a single post/page/etc
        if ( is_singular() && get_query_var( 'page' ) > 1 ) {
            preg_match( '/(?:\/([0-9]+))?\/?$/', $redirect_url, $matches );

            $redirect_url = str_replace( $matches[0], '', $redirect_url );
            $redirect_url = trailingslashit( $redirect_url );
            $redirect_url = add_query_arg( 'page', $matches[1], $redirect_url );

        // If on an archive page (date, category, author, etc)
        } elseif ( (is_home() || is_archive() || is_search()) && get_query_var( 'paged' ) ) {
            $redirect_url = remove_query_arg( 'page', $redirect_url );

            preg_match( '/' . $wp_rewrite->pagination_base . '\/?([0-9]{1,})\/?/', $redirect_url, $matches );

            $redirect_url = str_replace( $matches[0], '', $redirect_url );
            $redirect_url = trailingslashit( $redirect_url );
            if ( $wp_query->max_num_pages ) {
                $redirect_url = add_query_arg( 'page', $matches[1], $redirect_url );
            }
        }

        return $redirect_url;
    }
}
add_filter( 'redirect_canonical', 'thistle_avoid_pagination_redirect', 10, 2 );

if ( ! function_exists( 'thistle_get_pagenum_link' ) ) {
    /**
     * Rewrites the link URL for a page number according to the new behaviour.
     * E.g.: `/page/2/` becomes `/?page=2`.
     *
     * This function will only operate inside the `paginate_links` function.
     *
     * @global WP_Rewrite $wp_rewrite
     *
     * @param string $result The page number link.
     * @return string The link URL for the given page number.
     */
     function thistle_get_pagenum_link( $result ) {
         global $wp_rewrite;

        $pagination = preg_match( "|\/$wp_rewrite->pagination_base\/(\d+)\/?|", $result, $matches );

        $result = remove_query_arg( 'page', $result );
        $result = preg_replace( "|\/$wp_rewrite->pagination_base\/\d+\/?|", "/$2", $result );

        if ( $pagination ) {
            $result = add_query_arg( 'page', $matches[1], $result );
        }

        return $result;
     }
}
add_filter( 'get_pagenum_link', 'thistle_get_pagenum_link' );

if ( ! function_exists( 'thistle_remove_adjacent_posts_rel_link' ) ) {
    /**
     * Hides relational links for the posts adjacent to the current post
     * for single post pages because this feature doesn't care about
     * multi-pages post or page.
     */
    function thistle_remove_adjacent_posts_rel_link() {
        remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );
    }
}
add_action( 'init', 'thistle_remove_adjacent_posts_rel_link' );

if ( ! function_exists( 'thistle_adjacent_posts_rel_link' ) ) {
    /**
     * Displays relational links for the pages adjacent to the current page
     * of a post or a page.
     *
     * Can either be next or previous page for a post or a page.
     *
     * @global WP_Query $wp_query
     */
    function thistle_adjacent_posts_rel_link() {
        global $wp_query;

        if ( is_singular() ) {
            $page = $wp_query->get( 'page' );
            $numpages = _thistle_get_post_numpages( get_queried_object_id() );

            if ( $numpages ) {
                $permalink = get_permalink();

                if ( $page < $numpages ) {
                    $nextpage = $page + 1;
                    $url = add_query_arg( 'page', $nextpage, $permalink );

                    echo '<link rel="next" href="' . esc_url( $url ) . '">' . "\n";
                }
                if ( $page > 1 ) {
                    $prevpage = $page == 2 ? null : ($page - 1);
                    $url = add_query_arg( 'page', $prevpage, $permalink );

                    echo '<link rel="prev" href="' . esc_url( $url ) . '">' . "\n";
                }
            }
        }
    }
}
add_action( 'wp_head', 'thistle_adjacent_posts_rel_link' );

if ( ! function_exists( 'thistle_get_adjacent_archive_rel_links' ) ) {
    /**
     * Displays the relational links for the pages adjacent
     * to the current page of results.
     *
     * Can either be next or previous page.
     *
     * @global int $paged Page number of a list of posts.
     * @global WP_Query $wp_query
     */
    function thistle_get_adjacent_archive_rel_links() {
        global $paged, $wp_query;

        if ( ! $paged ) {
            $paged = 1;
        }

        if ( is_home() || is_archive() || is_search() ) {
            if ( (intval( $paged ) + 1) <= $wp_query->max_num_pages ) {
                echo '<link rel="next" href="' . esc_url( get_next_posts_page_link() ) . '">' . "\n";
            }
            if ( $paged > 1 ) {
                echo '<link rel="prev" href="' . esc_url( get_previous_posts_page_link() ) . '">' . "\n";
            }
        }
    }
}
add_action( 'wp_head', 'thistle_get_adjacent_archive_rel_links' );

if ( ! function_exists( 'thistle_wp_link_pages' ) ) {
    /**
     * Adds a "View All" link after other links for paginated posts to view the
     * whole post.
     *
     * This idea comes from WordPress.com : https://en.support.wordpress.com/nextpage/
     * and is enabled for all post types.
     *
     * @param string $output HTML output of paginated posts' page links.
     * @param array  $args   An array of arguments.
     * @return string
     */
    function thistle_wp_link_pages( $output, $args ) {
        if ( $output == '' ) {
            return $output;
        }

        $defaults = array(
            'before'           => '<p>' . __( 'Pages:' ),
            'after'            => '</p>',
            'link_before'      => '',
            'link_after'       => '',
            'next_or_number'   => 'number',
            'separator'        => ' ',
            'nextpagelink'     => __( 'Next page' ),
            'previouspagelink' => __( 'Previous page' ),
            'pagelink'         => '%',
            'echo'             => 1
        );
        $params = wp_parse_args( $args, $defaults );
        $r = apply_filters( 'wp_link_pages_args', $params );

        if ( $r['next_or_number'] = 'number' ) {
            $after_lenght = mb_strlen( $r['after'] );

            $output = mb_substr( $output, 0, ($after_lenght * -1) );

            $link = $r['link_before'] . __( 'View all', THISTLE_TEXT_DOMAIN ) . $r['link_after'];
            $link = _wp_link_page( 'all' ) . $link . '</a>';

            $output .= $r['separator'];
            $output .= $link;
            $output .= $r['after'];
        }

        if ( preg_match_all( '/<a href=\"(.+)\">/U', $output, $matches ) ) {
            foreach ( $matches[1] as $link ) {
                $_link = preg_replace( '/\/(?:page\/)*(\d|all)\/$/', '/?page=$1', $link );
                $output = str_replace( $link, $_link, $output );
            }
        }

        return $output;
    }
}
add_filter( 'wp_link_pages', 'thistle_wp_link_pages', 10, 2 );

if ( ! function_exists( 'thistle_post_pagination_rewrite' ) ) {
    /**
     * Updates all rewrite rules which concern post pagination.
     *
     * @param array $rules The compiled array of rewrite rules.
     * @return
     */
    function thistle_post_pagination_rewrite( $rules ) {
        $matches = array_keys( $rules );
        $queries = array_values( $rules );

        foreach ( $matches as $K => $V ) {
            if ( mb_strpos( $V, '(?:/([0-9]+))?/?$' ) !== false ) {
                $matches[ $K ] = str_replace( '(?:/([0-9]+))?/?$', '/?$', $V );
            }
        }

        foreach ( $queries as $K => $V ) {
            if ( mb_strpos( $V, '&page=' ) !== false ) {
                $queries[ $K ] = preg_replace( '/&page=\$matches\[[0-9]*\]$/', '', $V );
            }
        }

        $rules = array_combine( $matches, $queries );

        return $rules;
    }
}
add_filter( 'rewrite_rules_array', 'thistle_post_pagination_rewrite', PHP_INT_MAX );

if ( ! function_exists( 'thistle_old_slug_pagination_redirect' ) ) {
    /**
     * Modifies the old slug redirect URL to take into account
     * the new pagination structure.
     *
     * @param string $link The redirect URL.
     * @return string
     */
    function thistle_old_slug_pagination_redirect( $link ) {
        $paged = get_query_var( 'paged' );

        if ( $paged > 1 ) {
            $link = preg_replace( '/page\/?([0-9]{1,})\/?$/', '', $link );
            $link = add_query_arg( 'page', $paged, $link );
        }

        return $link;
    }
}
add_filter( 'old_slug_redirect_url', 'thistle_old_slug_pagination_redirect' );

if ( ! function_exists( 'thistle_bypass_multipage_post_content' ) ) {
    /**
     * Bypasses the multiple pages behaviour of WordPress by setting
     * the page parameter to `all` in the post's URL .
     * This is useful for SEO or to share a whole post.
     *
     * @param array   $pages Array of "pages" derived from the post content.
     *                       of `<!-- nextpage -->` tags..
     * @param WP_Post $post  Current post object.
     * @return array
     */
    function thistle_bypass_multipage_post_content( $pages, $post ) {
        if ( is_singular() && get_query_var( 'nopaging' ) ) {
            return array( $post->post_content );
        }

        return $pages;
    }
}
add_filter( 'content_pagination', 'thistle_bypass_multipage_post_content', 10, 2 );

if ( ! function_exists( 'thistle_post_multipage_rel_canonical' ) ) {
    /**
     * Outputs rel=canonical for a post splits into multiple pages.
     *
     * @global WP_Query $wp_query
     */
    function thistle_post_multipage_rel_canonical() {
        global $wp_query;

        if ( is_singular() ) {
            $page = $wp_query->get( 'page' );
            $numpages = _thistle_get_post_numpages( get_queried_object_id() );

            if ( $numpages ) {
                $url = get_permalink( $post_id );
                $url = add_query_arg( 'page', 'all', $url );

                echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
            }
        }
    }
}
add_action( 'wp_head', 'thistle_post_multipage_rel_canonical' );

/**
 * Returns the number of "pages" derived from the post content.
 *
 * @param int|WP_Post|null $post Optional. Post ID or post object. Defaults to global $post.
 * @return int Number of "pages" derived from the post content.
 */
function _thistle_get_post_numpages( $post = null ) {
    $pages = array();
    $post = get_post( $post );

    if ( ! $post ) {
        return 0;
    }

    $content = $post->post_content;

    if ( false !== strpos( $content, '<!--nextpage-->' ) ) {
        $content = str_replace( array( "\n<!--nextpage-->\n", "\n<!--nextpage-->", "<!--nextpage-->\n" ),  '<!--nextpage-->', $content );

        // Ignores nextpage at the beginning of the content.
        if ( 0 === strpos( $content, '<!--nextpage-->' ) )
            $content = substr( $content, 15 );

        $pages = explode( '<!--nextpage-->', $content );
    } else {
        $pages = array( $content );
    }

    return count( $pages );
}
