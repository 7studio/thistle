<?php

if ( ! function_exists( 'thistle_remove_update_nag_admin_notice' ) ) {
    /**
     * Removes the WordPress update nag (that appears at the top of
     * all admin pages when a new version of WordPress is released) for
     * users who can't update the WordPress core.
     */
    function thistle_remove_update_nag_admin_notice() {
        if ( ! current_user_can( 'update_core' ) ) {
            remove_action( 'admin_notices', 'update_nag', 3 );
        }
    }
}
add_action( 'admin_init', 'thistle_remove_update_nag_admin_notice' );

if ( ! function_exists( 'thistle_dashboard_right_now_post_types' ) ) {
    /**
     * Adds custom post type counts in "Right now" Dashboard widget.
     *
     * To display the right custom post type "menu_icon" (dashicons),
     * you should pass an extra entry `menu_icon_hex` with the hexa value
     * of the icon to the `register_post_type` function.
     *
     * @param array $items Array of extra "Right now" widget items.
     * @return array
     */
    function thistle_dashboard_right_now_post_types( $elements ) {
        $post_types = get_post_types( array( 'public' => true, '_builtin' => false ), 'object' );

        /**
         * Filters the list of post types to automatically add them.
         *
         * @param array $post_types An array of registered post types
         *                          without WordPress default post types.
         */
        $post_types = apply_filters( 'thistle_dashboard_right_now_post_types', $post_types );

        foreach ( $post_types as $post_type ) {
            $num_posts = wp_count_posts( $post_type->name );

            if ( $num_posts && $num_posts->publish ) {
                $text = _n( '%s ' . $post_type->labels->singular_name, '%s ' . $post_type->labels->name, $num_posts->publish );
                $text = sprintf( $text, number_format_i18n( $num_posts->publish ) );

                $icon = isset( $post_type->menu_icon_hex ) && mb_strpos( $post_type->menu_icon_hex , 'http' ) === false ? '&#x' . $post_type->menu_icon_hex . ';' : '';

                if ( current_user_can( $post_type->cap->edit_posts ) ) {
                    $elements[] = '<a data-dashicons="' . $icon . '" href="edit.php?post_type=' . $post_type->name . '">' . $text . '</a>';
                } else {
                    $elements[] = '<span data-dashicons="' . $icon . '">' . $text . '</span>';
                }
            }
        }

        return $elements;
    }
}
add_filter( 'dashboard_glance_items', 'thistle_dashboard_right_now_post_types' );


if ( ! function_exists( 'thistle_dashboard_glance_items_style' ) ) {
    /**
     * Adds some CSS rules to deal with the WordPress's shit…
     * A default dashicon is defined with a 103 specificity which
     * can't be overriden by a single Dashicons HTML class like in
     * WP admin menu :/
     */
    function thistle_dashboard_glance_items_style() {
        ?>
        <style>
            #dashboard_right_now li a[data-dashicons]:not([data-dashicons=""]):before,
            #dashboard_right_now li > span[data-dashicons]:not([data-dashicons=""]):before {
                content: attr(data-dashicons);
            }
        </style>
        <?php
    }
}
add_action( 'admin_head-index.php', 'thistle_dashboard_glance_items_style' );


