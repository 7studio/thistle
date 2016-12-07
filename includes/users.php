<?php

if ( ! function_exists( 'thistle_add_customer_role' ) ) {
    /**
     * Adds a new role to WordPress for the customers
     * between Editor and Administrator.
     *
     * This role allows cutomers to:
     *
     * - edit dashboard widgets and its settings
     * - access to Appearance Panel (widgets, menus, customize, background, header)
     * - access to Users Panel
     * - view the "Change role to..." dropdown in the admin user list.
     * - create, edit and delete other users' profiles
     *
     * in addition to the existing Editor capabilities.
     *
     * @see https://codex.wordpress.org/Roles_and_Capabilities
     *
     * @global WP_Roles $wp_roles
     */
    function thistle_add_customer_role() {
        global $wp_roles;

        if ( ! isset( $wp_roles ) ) {
            $wp_roles = new WP_Roles();
        }

        $capabilities = array(
            'edit_dashboard'     => true,
            'edit_theme_options' => true,
            'manage_options'     => true,
            'list_users'         => true,
            'promote_users'      => true,
            'create_users'       => true,
            'edit_users'         => true,
            'delete_users'       => true
        );

        $capabilities += $wp_roles->roles['editor']['capabilities'];

        add_role( 'customer', 'Administrator (customer)', $capabilities );
    }
}
add_action( 'after_switch_theme', 'thistle_add_customer_role' );

if ( ! function_exists( 'thistle_translate_customer_role' ) ) {
    /**
     * Translates the "customer" role name on demand.
     *
     * By default, WordPress does not provide any way to use a translation
     * from another text domain like for custom post type :/
     * It is not an efficient approach but unfortunately, we have no choice.
     *
     * @param string $translations Translated text.
     * @param string $text         Text to translate.
     * @param string $context      Context information for the translators.
     * @param string $domain       Text domain. Unique identifier for retrieving translated strings.
     * @return string Translated text on success, original text on failure.
     */
    function thistle_translate_customer_role( $translations, $text, $context, $domain ) {
        if ( $context == 'User role' && $text == 'Administrator (customer)' && $domain != THISTLE_TEXT_DOMAIN ) {
            return translate_with_gettext_context( $text, $context, THISTLE_TEXT_DOMAIN );
        }

        return $translations;
    }
}
add_filter( 'gettext_with_context', 'thistle_translate_customer_role', 10, 4 );

if ( ! function_exists( 'thistle_hide_administrator_role' ) ) {
    /**
     * Hides the administrator role from WordPress admin
     * for all users who have not the administrator role.
     *
     * This prevents users with the capabilities to create, edit or promote
     * users from changing their role to administrator or finding/viewing
     * administrator users.
     *
     * For the customers' enjoyment, they look at their role as "Administrator"
     * but they aren't ;)
     *
     * @global WP_Roles $wp_roles
     */
     function thistle_hide_administrator_role() {
        $wp_roles = wp_roles();

        if ( ! current_user_can( 'administrator' ) ) {
            unset( $wp_roles->role_objects['administrator'] );
            unset( $wp_roles->role_names['administrator'] );
            unset( $wp_roles->roles['administrator'] );

            $wp_roles->roles['customer']['name'] = __( 'Administrator' );
            $wp_roles->role_names['customer'] = __( 'Administrator' );
        }
     }
}
add_action( 'admin_init', 'thistle_hide_administrator_role', 9 );

if ( ! function_exists( 'thistle_update_users_list_table_views' ) ) {
    /**
     * Alters the output of available list table views for users.
     * WP returns a global number of users without taking into account
     * the available roles. "Real Administrators" appear
     * in the sum of users even if the role is unset.
     *
     * @param array $views An array of available list table views.
     * @return array
     */
    function thistle_update_users_list_table_views( $views ) {
        if ( ! current_user_can( 'administrator' ) ) {
            $count_all = 0;

            array_walk( $views, function( $value, $key ) use( &$count_all ) {
                preg_match( '/>\(([0-9]+)\)</', $value, $matches );

                if ( $key != 'all' && ! empty( $matches ) ) {
                    $count_all += intval( $matches[1] );
                }
            } );

            $views['all'] = preg_replace( '/>\([0-9]+\)</', '>(' . $count_all . ')<', $views['all'] );
        }

        return $views;
    }
}
add_filter( 'views_users', 'thistle_update_users_list_table_views' );

if ( ! function_exists( 'thistle_filter_users_list_table' ) ) {
    /**
     * Hides administrator users from the users list table
     * for all users which have not the administrator role.
     *
     * @param array $args Arguments passed to WP_User_Query to retrieve items
     *                    for the current users list table.
     * @return array
     */
     function thistle_filter_users_list_table( $args ) {
        if ( ! current_user_can( 'administrator' ) ) {
            $args['role__not_in'] = array( 'administrator' );
        }

        return $args;
     }
}
add_filter( 'users_list_table_query_args', 'thistle_filter_users_list_table' );

if ( ! function_exists( 'thistle_dont_allow_cap' ) ) {
    /**
     * Prevents users which are not administrator to edit or delete
     * other administrators.
     *
     * @param array  $caps    The user's actual capabilities.
     * @param string $cap     Capability name.
     * @param int    $user_id The user ID.
     * @param array  $args    Adds the context to the cap. Typically the object ID.
     * @return array
     */
     function thistle_dont_allow_cap( $caps, $cap, $user_id, $args ) {
         switch ( $cap ) {
            case 'edit_user':
            case 'promote_user':
                if( isset( $args[0] ) && $args[0] == $user_id ) {
                    break;
                } elseif( ! isset( $args[0] ) ) {
                    $caps[] = 'do_not_allow';
                }

                $other = new WP_User( absint( $args[0] ) );
                if ( $other->has_cap( 'administrator' ) && ! current_user_can( 'administrator' ) ) {
                    $caps[] = 'do_not_allow';
                }
                break;
            case 'delete_user':
            case 'delete_users':
                if ( ! isset( $args[0] ) ) {
                    break;
                }

                $other = new WP_User( absint( $args[0] ) );
                if ( $other->has_cap( 'administrator' ) && ! current_user_can( 'administrator' ) ) {
                    $caps[] = 'do_not_allow';
                }
                break;
        }

        return $caps;
    }
}
add_filter( 'map_meta_cap', 'thistle_dont_allow_cap', 10, 4 );

if ( ! function_exists( 'thistle_remove_admin_menu_page' ) ) {
    /**
     * Removes the "Tools" and "Settings" menu pages when
     * you are not an andministrator.
     */
    function thistle_remove_admin_menu_page() {
        if ( ! current_user_can( 'administrator' ) ) {
            remove_menu_page( 'tools.php' );
            remove_menu_page( 'options-general.php' );
        }
    }
}
add_action( 'admin_menu', 'thistle_remove_admin_menu_page', 9999 );

if ( ! function_exists( 'thistle_redirect_options_page' ) ) {
    /**
     * Kills WP execution and displays an error message when users try
     * to request directly options pages for which they have sufficient
     * capabilities but who are not administrator.
     *
     * @global string $pagenow
     */
    function thistle_redirect_options_page() {
        if ( ! current_user_can( 'administrator' ) ) {
            global $pagenow;

            $pages = array(
                'tools.php',
                'options.php',
                'options-general.php',
                'options-writing.php',
                'options-reading.php',
                'options-media.php',
                'options-permalink.php'
            );

            if ( in_array( $pagenow, $pages ) ) {
                wp_die( __( 'Sorry, you are not allowed to manage options for this site.' ) );
            }
        }
    }
}
add_action( 'admin_init', 'thistle_redirect_options_page' );

if ( ! function_exists( 'thistle_remove_staticfrontpage_customize_section' ) ) {
    /**
     * Removes the "Static Front Page" section from the WP customize manager
     * when you are not an andministrator.
     *
     * @param WP_Customize_Manager $wp_customize WP_Customize_Manager instance.
     */
    function thistle_remove_staticfrontpage_customize_section( $wp_customize ) {
        if ( ! current_user_can( 'administrator' ) ) {
            $wp_customize->remove_section( 'static_front_page' );
        }
    }
}
add_action( 'customize_register', 'thistle_remove_staticfrontpage_customize_section', 11 );

if ( ! function_exists( 'thistle_remove_slugdiv_meta_boxes' ) ) {
    /**
     * Removes the "Slug" meta box for all post types
     * when you are not an andministrator.
     *
     * It is more understandable to modify the slug into the permalink
     * below the title.
     */
    function thistle_remove_slugdiv_meta_boxes() {
        if( ! current_user_can( 'administrator' ) ) {
            $post_types = get_post_types( array( 'public' => true ), 'names' );

            foreach ( $post_types as $post_type ) {
                remove_meta_box( 'slugdiv', $post_type, 'normal' );
            }
        }
    }
}
add_action( 'admin_menu', 'thistle_remove_slugdiv_meta_boxes' );
