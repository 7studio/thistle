<?php

if ( ! function_exists( 'thistle_remove_admin_redirections' ) ) {
    /**
     * Forbids WordPress to redirect variety of shorthand URLs
     * (`dashboard`, `admin` or `login`) to the admin.
     * When users are not logged, it can be considered as
     * a minor security issue.
     */
    function thistle_remove_admin_redirections() {
        remove_action( 'template_redirect', 'wp_redirect_admin_locations', 1000 );
    }
}
add_action( 'init', 'thistle_remove_admin_redirections' );

if ( ! function_exists( 'thistle_redirect_admin_locations' ) ) {
    /**
     * Redirects a variety of shorthand URLs to the admin only when
     * users are logged.
     *
     * If a user visits example.com/admin, they'll be redirected to /wp-admin.
     *
     * @global WP_Rewrite $wp_rewrite
     */
    function thistle_redirect_admin_locations() {
        global $wp_rewrite;

        if ( ! is_user_logged_in() ) {
            return;
        }

        if ( ! ( is_404() && $wp_rewrite->using_permalinks() ) ) {
            return;
        }

        $admins = array(
            home_url( 'wp-admin', 'relative' ),
            home_url( 'wp', 'relative' ),
            home_url( 'admin', 'relative' )
        );
        if ( in_array( untrailingslashit( $_SERVER['REQUEST_URI'] ), $admins ) ) {
            wp_redirect( admin_url() );
            exit;
        }
    }
}
add_action( 'template_redirect', 'thistle_redirect_admin_locations', 1000 );

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

        $redirect_to = is_admin() ? home_url( '/' ) : home_url( add_query_arg( NULL, NULL ) );
        $redirect_to = urlencode( $redirect_to );

        $logout_url = add_query_arg( 'redirect_to', $redirect_to, $logout_url );

        return $logout_url;
    }
}
add_filter( 'logout_url', 'thistle_redirect_wp_logout', 10, 2 );

if ( ! function_exists( 'thistle_remove_admin_bar_archive_node' ) ) {
    /**
     * Removes the default archive links ("View Posts") from the admin bar in
     * favour of the Thistle one. This choice lets us have
     * this helpful link even if the post type has not a single view.
     *
     * @link https://core.trac.wordpress.org/ticket/34113
     *
     * @global WP_Admin_Bar $wp_admin_bar
     */
    function thistle_remove_admin_bar_archive_node() {
        global $wp_admin_bar;

        $wp_admin_bar->remove_menu( 'archive' );
    }
}
add_action( 'wp_before_admin_bar_render', 'thistle_remove_admin_bar_archive_node' );

if ( ! function_exists( 'thistle_add_medium_large_size_settings' ) ) {
    /**
     * Adds a new field into the media settings page to manage
     * the fourth image size: "Medium Large".
     *
     * Introduced by WordPress 4.4, this format has by default a `768px` width
     * and is used for responsive images (through the `srcset` attribute).
     * I know that it's not a good idea to define `srcset` and `size`
     * attributes according to this site breakpoint
     * (cf.: )
     * but as WP handles this format (and uses space disc) without telling us,
     * we should be able to edit it to enjoy it even for one of our breakpoints.
     *
     * If you want to escape this format, you just have to set its width
     * and height to zero ;)
     */
    function thistle_add_medium_large_size_settings() {
        add_settings_field(
            'medium_large_size',
            __( 'Large size' ),
            '_thistle_output_medium_large_size_settings',
            'media',
            'default',
            array()
        );
    }

    function _thistle_output_medium_large_size_settings() {
        ?>
        <fieldset>
            <legend class="screen-reader-text">
                <span><?php _e( 'Large size' ); ?></span>
            </legend>
            <label for="medium_large_size_w"><?php _e( 'Max Width' ); ?></label>
            <input name="medium_large_size_w" type="number" step="1" min="0" id="medium_large_size_w" value="<?php form_option( 'medium_large_size_w' ); ?>" class="small-text" />
            <label for="medium_large_size_h"><?php _e( 'Max Height' ); ?></label>
            <input name="medium_large_size_h" type="number" step="1" min="0" id="medium_large_size_h" value="<?php form_option( 'medium_large_size_h' ); ?>" class="small-text" />
        </fieldset>
        <?php
    }
}
add_action( 'admin_init', 'thistle_add_medium_large_size_settings' );

if ( ! function_exists( 'thistle_media_whitelist_options' ) ) {
    /**
     * Adds the `medium_large_size_w` and `medium_large_size_h` options
     * to the white list to be authorised to edit them.
     *
     * @param array White list options.
     * @return array.
     */
    function thistle_media_whitelist_options( $whitelist_options ) {
        $whitelist_options['media'][] = 'medium_large_size_w';
        $whitelist_options['media'][] = 'medium_large_size_h';

        return $whitelist_options;
    }
}
add_filter( 'whitelist_options', 'thistle_media_whitelist_options' );

if ( ! function_exists( 'thistle_change_size_settings' ) ) {
    /**
     * Changes the order of the size settings to have "Medium Large" before
     * "Large" and rename "Large" label into "Extra large" because we can't
     * do it with the help of PHP.
     */
    function thistle_change_size_settings() {
        ?>
        <script>
            ( function( window, $, undefined ) {
                if ( typeof $ !== 'undefined' ) {
                    $( document ).ready( function () {
                        var $lss = $( '#large_size_w' ).parents('tr');
                        var $mlss = $( '#medium_large_size_w' ).parents('tr');

                        $lss
                            .insertAfter($mlss)
                            .find('th, legend span')
                                .html('<?php _e( 'Extra large size', THISTLE_TEXT_DOMAIN ); ?>');
                    } );
                }
            } )( window, window.jQuery );
        </script>
        <?php
    }
}
add_action( 'admin_head-options-media.php', 'thistle_change_size_settings' );

if ( ! function_exists( 'thistle_change_image_size_names' ) ) {
    /**
     * Retrieves the names and labels of the default image sizes including
     * the fourth (or fifth) one: "Medium Large".
     * Because we don't have a good translation for "Medium Large" in French,
     * I decided to rename "Large" into "Extra large" and
     * give the "Large" label to the medium_large size.
     *
     * @param array $size_names Array of image sizes and their names. Default values
     *                          include 'Thumbnail', 'Medium', 'Large', 'Full Size'.
     * @return array
     */
    function thistle_change_image_size_names( $size_names ) {
        $index = array_search( 'large' , array_keys( $size_names ) );

        $size_names = array_merge( array_slice( $size_names, 0, $index, true), array( 'medium_large' => _( 'Large' ) ), $size_names );
        $size_names['large'] = _( 'Extra large', THISTLE_TEXT_DOMAIN );

        return $size_names;
    }
}
add_filter( 'image_size_names_choose', 'thistle_change_image_size_names' );
