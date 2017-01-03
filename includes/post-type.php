<?php

/**
 * Even if the naming convention here is quite poor, the `exclude_from_search`
 * parameter should be taken into account only on search query and not
 * on taxonomy query too.
 *
 * The first issue (first link below) was closed due to backward compatibility
 * and it's really annoying but maybe the second one will be solved (one day).
 *
 * @link https://core.trac.wordpress.org/ticket/17592
 * @link https://core.trac.wordpress.org/ticket/29418
 */

if ( ! function_exists( 'thistle_include_to_taxonomy' ) ) {
    /**
     * Changes the `exclude_from_search` property value for all post types
     * which are excluded from search when the query contains taxonomy and
     * the `post_type` parameter is empty.
     * With this behaviour, the taxonomy query is able to return all posts
     * as expected.
     *
     * The right `exclude_from_search` property value
     * will be re-established quickly after to do not break other functionalities.
     *
     * @param WP_Query $wp_query The WP_Query instance (passed by reference).
     */
    function thistle_include_to_taxonomy( $wp_query ) {
        global $wp_post_types, $thistle_exclude_from_search;

        if ( $wp_query->is_tax && empty( $wp_query->query_vars['post_type'] ) ) {
            $post_types = get_post_types( array( 'exclude_from_search' => true ), 'objects' );

            foreach ( $post_types as $PT ) {
                if ( $PT->has_archive ) {
                    $wp_post_types[ $PT->name ]->exclude_from_search = false;

                    $thistle_exclude_from_search[] = $PT->name;
                }
            }
        }
    }
}
add_action( 'pre_get_posts', 'thistle_include_to_taxonomy', PHP_INT_MAX );

if ( ! function_exists( 'thistle_exclude_from_search' ) ) {
    /**
     * Re-establishes the right `exclude_from_search` property value
     * for all post types which are excluded from search.
     */
    function thistle_exclude_from_search() {
        global $wp_post_types, $thistle_exclude_from_search;

        if ( ! empty( $thistle_exclude_from_search ) ) {
            foreach ( $thistle_exclude_from_search as $PT ) {
                $wp_post_types[ $PT ]->exclude_from_search = true;
            }
        }

        unset( $thistle_exclude_from_search );
    }
}
add_action( 'posts_selection', 'thistle_exclude_from_search', PHP_INT_MAX );

if ( ! function_exists( 'thistle_post_type_achive' ) ) {
    /**
     * Removes the limit to query only post_types that are `publicly_queryable`.
     *
     * This behaviour is really helpful when you want the post type archive page
     * and don't want the single page. To work as expected, you need to set
     * the `has_archive` parameter to true and define the permalink structure slug.
     *
     * @param WP $wp Current WordPress environment instance (passed by reference).
     */
    function thistle_post_type_achive( $wp ) {
        if ( mb_strpos( $wp->matched_query, 'post_type=' ) === 0 ) {
            parse_str( $wp->matched_query );

            if ( isset( $post_type ) && post_type_exists( $post_type ) ) {
                $post_type_object = get_post_type_object( $post_type );

                if ( ! $post_type_object->publicly_queryable
                    && $post_type_object->has_archive
                    && isset( $post_type_object->rewrite['slug'] )
                    && $wp->matched_rule == $post_type_object->rewrite['slug'] . '/?$' )
                {
                    if ( isset( $wp->query_vars['post_type'] ) ) {
                        if ( is_array( $wp->query_vars['post_type'] ) ) {
                            $wp->query_vars['post_type'][] = $post_type;
                        } else {
                            $wp->query_vars['post_type'] = array( $wp->query_vars['post_type'], $post_type );
                        }
                    } else {
                        $wp->query_vars['post_type'] = $post_type;
                    }
                }
            }
        }
    }
}
add_filter( 'parse_request', 'thistle_post_type_achive', PHP_INT_MAX );
