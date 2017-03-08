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

if ( ! function_exists( 'thistle_admin_bar_edit_archive_page' ) ) {
    /**
     * Provides a link into the admin bar to edit the page which could share
     * the same slug as a post type archive when you browse your website.
     *
     * By default, WP does it for the posts page when it isn't the front page.
     *
     * @global WP_Query $wp_the_query
     *
     * @param WP_Admin_Bar $wp_admin_bar (passed by reference).
     */
    function thistle_admin_bar_edit_archive_page( $wp_admin_bar ) {
        global $wp_the_query;

        if ( ! is_admin() ) {
            $current_object = $wp_the_query->get_queried_object();

            if ( empty( $current_object ) )
                return;

            if ( is_a( $current_object, 'WP_Post_Type' )
                && ( $post_type_archive_link = get_post_type_archive_link( $current_object->name ) )
                && ( $page_object = get_page_by_path( parse_url( $post_type_archive_link, PHP_URL_PATH ) ) )
                && ( $page_type_object = get_post_type_object( $page_object->post_type ) )
                && current_user_can( 'edit_post', $page_object->ID )
                && $page_type_object->show_in_admin_bar
                && ( $edit_post_link = get_edit_post_link( $page_object->ID ) ) )
            {
                $wp_admin_bar->add_menu( array(
                    'id'    => 'edit',
                    'title' => $page_type_object->labels->edit_item,
                    'href'  => $edit_post_link
                ) );
            }
        }
    }
}
add_action( 'admin_bar_menu', 'thistle_admin_bar_edit_archive_page', 80 );

if ( ! function_exists( 'thistle_admin_bar_view_archive_page' ) ) {
    /**
     * Provides a link into the admin bar to go to the archive page when
     * you are on a custom post type listing in the WP administration.
     * This idea comes from the "SF Archiver" plugin but it uses the default WP UI.
     *
     * The conditions to display the link voluntarily omit the "public"
     * property from the post type object to offer the possibility to go
     * to an archive page of a post type which hasn't single view.
     *
     * @global WP_Query $wp_the_query
     * @global WP_Rewrite $wp_rewrite
     *
     * @param WP_Admin_Bar $wp_admin_bar (passed by reference).
     */
    function thistle_admin_bar_view_archive_page( $wp_admin_bar ) {
        global $wp_the_query, $wp_rewrite;

        if ( is_admin() ) {
            $current_screen = get_current_screen();

            if ( $current_screen->base == 'edit'
                && ! empty( $current_screen->post_type )
                && ( $post_type_object = get_post_type_object( $current_screen->post_type ) )
                && $post_type_object->show_in_admin_bar
                && $post_type_object->labels->view_items
                && ( $post_type_archive_link = get_post_type_archive_link( $post_type_object->name ) ) )
            {
                $wp_admin_bar->add_menu( array(
                    'id'    => 'view',
                    'title' => $post_type_object->labels->view_items,
                    'href'  => $post_type_archive_link
                ) );
            }
        }
    }
}
add_action( 'admin_bar_menu', 'thistle_admin_bar_view_archive_page', 80 );

if ( ! function_exists( 'thistle_hide_default_post_format_option' ) ) {
    /**
     * Hides the "Default Post Format" option in the "Writing Settings" page
     * if the current theme doesn't support the feature.
     */
    function thistle_hide_default_post_format_option() {
        if ( ! current_theme_supports( 'post-formats' ) ) {
        ?>
        <script>
            ( function( window, $, undefined ) {
                if ( typeof $ !== 'undefined' ) {
                    $( document ).ready( function () {
                        var option = $( '#default_post_format' );

                        option
                            .parents('tr')
                                .addClass( 'hidden' );
                    } );
                }
            } )( window, window.jQuery );
        </script>
        <?php
        }
    }
}
add_action( 'admin_head-options-writing.php', 'thistle_hide_default_post_format_option' );

if ( ! function_exists( 'thistle_login_headerurl' ) ) {
    /**
     * Sets the link URL of the header logo above login form with
     * the Site Address. By default WordPress uses `https://wordpress.org/`.
     *
     * @param string $login_header_url Login header logo URL.
     * @return string
     */
    function thistle_login_headerurl( $url ) {
        return get_home_url( '/' );
    }
}
add_filter( 'login_headerurl', 'thistle_login_headerurl' );

if ( ! function_exists( 'thistle_login_headertitle' ) ) {
    /**
     * Sets the title attribute of the header logo above login form with
     * the Site Title. By default WordPress uses "Powered by WordPress".
     *
     * @param string $login_header_title Login header logo title attribute.
     * @return string
     */
    function thistle_login_headertitle( $title ) {
        return get_bloginfo( 'name', 'display' );
    }
}
add_filter( 'login_headertitle', 'thistle_login_headertitle' );

if ( ! function_exists( 'thistle_maintenance_mode' ) ) {
    /**
     * Dies with a maintenance message without the `.maintenance` behaviour.
     *
     * By default, WordPress doesn't allow us to activate a maintenance
     * mode easily and especially without a f***ing plugin.
     * Plus, when this mode is enable, any user can access to the admin panel
     * to do something (maybe it's safer for special actions).
     *
     * The Thistle maintenance mode is softer than the WordPress one
     * by allowing you to switch in maintenance with a simple constant
     * and to log in to admin panel by reaching directly `wp-login.php`.
     *
     * The default message can be replaced by using a drop-in
     * (`maintenance.php` in the `wp-content` directory).
     */
    function thistle_soft_maintenance_mode() {
        global $pagenow;

        if ( ! file_exists( ABSPATH . '.maintenance' )
            && defined( 'THISTLE_MAINTENANCE' ) && THISTLE_MAINTENANCE
            && $pagenow !== 'wp-login.php'
            && ! is_user_logged_in() )
        {
            wp_load_translations_early();

            $server_protocol = wp_get_server_protocol();

            header( "$server_protocol 503 Service Unavailable", true, 503 );
            header( "Content-Type: text/html; charset=utf-8" );

            if ( file_exists( WP_CONTENT_DIR . '/maintenance.php' ) ) {
                include( WP_CONTENT_DIR . '/maintenance.php' );
            } else {
            ?>
                <!DOCTYPE html>
                <meta charset="<?php bloginfo( 'charset' ); ?>">
                <meta http-equiv="x-ua-compatible" content="ie=edge">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title><?php _e( 'Maintenance' ); ?></title>
                <h1 lang="<?php bloginfo( 'language' ); ?>"><?php _e( 'Briefly unavailable for scheduled maintenance. Check back in a minute.' ); ?></h1>
            <?php
            }

            die;
        }
    }
}
add_action( 'init', 'thistle_soft_maintenance_mode' );

if ( ! function_exists( 'thistle_disable_admin_email_password_change_notification' ) ) {
    /**
     * Doesn't notify the blog admin when a user changes his password.
     */
    function thistle_disable_admin_email_password_change_notification() {
        remove_action( 'after_password_reset', 'wp_password_change_notification' );
    }
}
add_action( 'admin_init', 'thistle_disable_admin_email_password_change_notification' );

if ( ! function_exists( 'thistle_remove_admin_theme_page' ) ) {
    /**
     * Removes the "Themes" submenu pages
     * when you haven't sufficient capabilities.
     */
    function thistle_remove_admin_theme_page() {
        if ( ! current_user_can( 'install_themes' ) ) {
            remove_submenu_page( 'themes.php', 'themes.php' );
        }
    }
}
add_action( 'admin_menu', 'thistle_remove_admin_theme_page', PHP_INT_MAX );

if ( ! function_exists( 'thistle_redirect_themes_page' ) ) {
    /**
     * Kills WP execution and displays an error message when users try
     * to request directly themes page and haven't sufficient
     * capabilities.
     *
     * @global string $pagenow
     */
    function thistle_redirect_themes_page() {
        global $pagenow;

        if ( $pagenow == 'themes.php' && ! current_user_can( 'install_themes' ) ) {
            wp_die( __( 'Sorry, you are not allowed to edit theme options on this site.' ), 403 );
        }
    }
}
add_action( 'admin_init', 'thistle_redirect_themes_page' );

if ( ! function_exists( 'thistle_clear_update_right_now_text' ) ) {
    /**
     * Hides the theme name displayed in the "Right now" Dashboard widget when
     * users haven't the capability to install themes.
     */
    function thistle_clear_update_right_now_text() {
        if ( ! current_user_can( 'install_themes' ) ) {
            add_filter( 'update_right_now_text', '__return_empty_string' );
        }
    }
}
add_action( 'admin_init', 'thistle_clear_update_right_now_text' );

if ( ! function_exists( 'thistle_redirect_wp_logout' ) ) {
    /**
     * Redirects the user to the current page or the home page on logout.
     *
     * @param string $logout_url The HTML-encoded logout URL.
     * @param string $redirect   Path to redirect to on logout.
     * @return string The logout URL. Note: HTML-encoded via esc_html() in wp_nonce_url().
     */
    function thistle_redirect_wp_logout( $logout_url, $redirect ) {
        if ( ! empty( $redirect ) ) {
            return $logout_url;
        }

        $redirect_to = is_admin() ? home_url() : home_url( add_query_arg( NULL, NULL ) );
        $redirect_to = trailingslashit( $redirect_to );
        $redirect_to = urlencode( $redirect_to );

        $logout_url = add_query_arg( 'redirect_to', $redirect_to, $logout_url );

        return $logout_url;
    }
}
add_filter( 'logout_url', 'thistle_redirect_wp_logout', 10, 2 );
