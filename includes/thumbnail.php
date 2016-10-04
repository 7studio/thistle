<?php

if ( ! function_exists( 'thistle_init_column_thumbnail' ) ) {
    /**
     * Hooks functions on to a specific filter/action to handle
     * the "Thumbnail" column for all registered post types except `attachment`.
     */
    function thistle_init_column_thumbnail() {
        $post_types = get_post_types( array( 'public' => true ), 'names' );
        unset( $post_types['attachment'] );

        foreach ( $post_types as $post_type ) {
            add_filter( 'manage_' . $post_type . '_posts_columns', 'thistle_add_column_thumbnail', 5 );
            add_action( 'manage_' . $post_type . '_posts_custom_column', 'thistle_manage_column_thumbnail', 5, 2 );
        }
    }
}
add_filter( 'admin_init', 'thistle_init_column_thumbnail' );

if ( ! function_exists( 'thistle_add_column_thumbnail' ) ) {
    /**
     * Adds a "Thumbnail" column before the last one if the post type supports
     * the thumbnail feature.
     *
     * @param array $post_columns An array of column names.
     * @return array
     */
    function thistle_add_column_thumbnail( $posts_columns ) {
         $screen = get_current_screen();

        if ( post_type_supports( $screen->post_type, 'thumbnail' ) ) {
            $post_type = get_post_type_object( $screen->post_type );

            $last_post_column_key = array_pop( (array_keys( $posts_columns )) );
            $last_post_column_value = array_pop( $posts_columns );

            $posts_columns['thumbnail'] = $post_type->labels->featured_image;
            $posts_columns[ $last_post_column_key ] = $last_post_column_value;
        }

        return $posts_columns;
    }
}

if ( ! function_exists( 'thistle_manage_column_thumbnail' ) ) {
    /**
     * Handles the thumbnail column output.
     *
     * @param string $column_name The name of the column to display.
	 * @param int    $post_id     The current post ID.
     */
    function thistle_manage_column_thumbnail( $column_name, $post_id ) {
        if ( $column_name === 'thumbnail' ) {
            $thumbnail = wp_get_attachment_image( get_post_thumbnail_id( $post_id ), array( 60, 60 ), false, array( 'alt' => '' ) );

            if ( $thumbnail != '' ) {
                echo $thumbnail;
            }
        }
    }
}

if ( ! function_exists( 'thistle_set_column_thumbnail_styles' ) ) {
    /**
     * Outputs some styles for the Thumbnail" column to match WordPress admin
     * style guide.
     */
    function thistle_set_column_thumbnail_styles() {
        ?>
        <style type="text/css">
            .fixed .column-thumbnail {
                width: 10%;
            }

            @media screen and (max-width: 782px) {
        	    .fixed .column-thumbnail {
                    display: none;
                }
            }
        </style>
        <?php
    }
}
add_action( 'admin_head', 'thistle_set_column_thumbnail_styles' );

if ( ! function_exists( 'thistle_get_enclosure_metadata' ) ) {
    /**
     *
     *
     *
     */
    function thistle_get_enclosure_metadata( $post = null ) {
        $post = get_post( $post );

        if ( is_null( $post ) || ! has_post_thumbnail() ) {
            return null;
        }

        $thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id(), 'thumbnail' );
        if ( $thumbnail === false ) {
            return null;
        }

        $thumbnail_headers = wp_get_http_headers( $thumbnail[0] );
        if ( $thumbnail_headers === false || ! $thumbnail_headers['content-length'] ) {
            return null;
        }

        return array(
            'url' => $thumbnail[0],
            'length' => $thumbnail_headers['content-length'],
            'type' => $thumbnail_headers['content-type']
        );
    }
}

if ( ! function_exists( 'thistle_rss_enclosure' ) ) {
    /**
     *
     *
     *
     */
    function thistle_rss_enclosure() {
        $enclosure_metadata = thistle_get_enclosure_metadata();

        if ( is_null( $enclosure_metadata ) ) {
            return;
        }

        echo '<enclosure url="' . $enclosure_metadata['url'] . '" length="' . $enclosure_metadata['length'] . '" type="' . $enclosure_metadata['type'] . '" />' . "\n";
    }
}
add_action( 'rss2_item', 'thistle_rss_enclosure' );

if ( ! function_exists( 'thistle_rss_enclosure' ) ) {
    /**
     *
     *
     *
     */
    function thistle_atom_enclosure() {
        $enclosure_metadata = thistle_get_enclosure_metadata();

        if ( is_null( $enclosure_metadata ) ) {
            return;
        }

        echo '<link href="' . $enclosure_metadata['url'] . '" rel="enclosure" length="' . $enclosure_metadata['length'] . '" type="' . $enclosure_metadata['type'] . '" />' . "\n";
    }
}
add_action( 'atom_entry', 'thistle_atom_enclosure' );
