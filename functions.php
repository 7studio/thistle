<?php

define( 'THISTLE_TEXT_DOMAIN', 'thistle' );
define( 'THISTLE_URI',  get_template_directory_uri() );
define( 'THISTLE_PATH', get_template_directory() );

/**
 * Thistle only works in WordPress 4.5 or later.
 */
if ( version_compare( $GLOBALS['wp_version'], '4.5', '<' ) ) {
	require 'includes/back-compat.php';
}

if ( ! function_exists( 'thistle_disable_wporg_theme_update' ) ) {
    /**
     * Disables requests to wp.org repository to check if an update is available
     * for a theme with "Thistle" as name.
     *
     * We need to do this because an outdated theme has the same name
     * and its version number is greater than the Thistle one.
     *
     * @link https://wordpress.org/themes/search/thistle/
     * @link https://wordpress.org/themes/thistle/
     *
     * @param array  $r   An array of HTTP request arguments.
     * @param string $url The request URL.
     * @return array
     */
    function thistle_disable_wporg_theme_update( $r, $url ) {

        // If it's not a theme update request, bail.
        if ( strpos( $url, 'https://api.wordpress.org/themes/update-check/1.1/' ) !== 0 ) {
            return $r;
        }

        // Decode the JSON response
        $themes = json_decode( $r['body']['themes'] );

        // Remove the active parent and child themes from the check
        $parent = get_option( 'template' );
        $child = get_option( 'stylesheet' );

        unset( $themes->themes->$parent, $themes->themes->$child );

        // Encode the updated JSON response
        $r['body']['themes'] = json_encode( $themes );

        return $r;
    }
}
add_filter( 'http_request_args', 'thistle_disable_wporg_theme_update', 5, 2 );

if ( ! function_exists( 'thistle_active_theme' ) ) {
	/**
	 * Sets up some default options and remove "Hello Dolly plugin.
	 *
	 * Note that this function is hooked into the `after_switch_theme` hook,
	 * which runs on the first WP load after a theme switch.
	 *
	 * @global WP_Rewrite $wp_rewrite
	 */
	function thistle_active_theme() {
		global $wp_rewrite;

		$options = array(
			'timezone_string'         => 'Europe/Paris',
			'date_format'             => 'j F Y',
			'time_format'             => 'G \h i \m\i\n',
			'start_of_week'           => 1,
			'permalink_structure'     => '/%category%/%postname%/',
			'category_base'           => 'categorie',
			'tag_base'                => 'tag',
			'image_default_link_type' => 'none',
			'users_can_register'      => 0,
			'admin_email'             => 'xavier@7studio.fr',
            'avatar_default'          => 'blank',
            'rss_use_excerpt'         => 1
		);

		foreach ( $options as $option => $newvalue ) {
			update_option( $option, $newvalue );
		}

        // Updates rules for permalink structure.
		$wp_rewrite->flush_rules();

		$hello = WP_PLUGIN_DIR . '/hello.php';
		if ( file_exists( $hello ) ) {
			@unlink( $hello );
		}
	}
}
add_action( 'after_switch_theme', 'thistle_active_theme' );

if ( ! function_exists( 'thistle_setup_theme' ) ) {
	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 *
	 * Note that this function is hooked into the `after_setup_theme` hook,
	 * which runs before the init hook. The init hook is too late for some features,
	 * such as indicating support for post thumbnails.
	 */
	function thistle_setup_theme() {
		/*
		 * Makes theme available for translation.
		 * Translations can be filed in the `/languages/` directory.
		 */
		load_theme_textdomain( THISTLE_TEXT_DOMAIN, THISTLE_PATH . '/languages' );

		//
		add_filter( 'xmlrpc_enabled', '__return_false' );

		// Disables the WP-API version 2.x
		add_filter('rest_enabled', '__return_false');
		add_filter('rest_jsonp_enabled', '__return_false');

		// Switches default core markup for gallery and caption to output valid HTML5.
		add_theme_support( 'html5', array(
			'gallery',
			'caption'
		) );

		/*
		 * Enables support for Post Thumbnails on posts and pages.
		 * Post Thumbnail on pages will be used to construct Open Graph.
		 */
		add_theme_support( 'post-thumbnails', array( 'post', 'page' ) );

		/*
		 * Enables support for Excerpt on pages.
		 * Excerpt on pages will be used to construct Open Graph and
		 * `<meta name="description">`.
		 */
		add_post_type_support( 'page', 'excerpt' );

		// Adds default posts and comments RSS feed links to head.
		add_theme_support( 'automatic-feed-links' );

		/*
		 * Lets WordPress manage the document title.
		 * By adding theme support, we declare that this theme does not
		 * use a hard-coded `<title>` tag in the document head, and expect
		 * WordPress to provide it for us.
		 */
		add_theme_support( 'title-tag' );

		/*
		 * Adds a stylesheet to have the same styles between the site
		 * and the visual editor (TinyMCE) when writing.
		 *
		 * This file should contain at least: https://codex.wordpress.org/CSS
		 */
        $min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		add_editor_style( array( get_stylesheet_directory_uri() . '/assets/styles/editor-style' . $min . '.css' ) );

		/*
		 * Enables support for some specific features of Thistle.
		 *
		 * - `thistle-social-meta-tags` ouputs Open Graph and Twitter Card
		 * for post, page, attachment and author.
		 * - `thistle-description-meta-tag` outputs description `<meta>` tag
		 * for the current page with the help of `post_excerpt` and a new field
		 * added into the general options panel and the customizer for home page.
		 *
		 * @see includes/share.php
		 * @see includes/seo.php
		 */
		add_theme_support( 'thistle-social-meta-tags' );
		add_theme_support( 'thistle-description-meta-tag' );
	}
}
add_action( 'after_setup_theme', 'thistle_setup_theme' );

if ( ! function_exists( 'thistle_remove_generator' ) ) {
	/**
	 * Removes WordPress version from `<head>`, RSS.
	 *
	 * The WordPress version can be found into readme.html. To prevent someone
	 * from looking at this information, the access to this file should be denied
	 * with the help of an .htaccess file.
	 */
	function thistle_remove_generator() {
		// <meta name="generator" />
		remove_action( 'wp_head', 'wp_generator' );

		// RSS: <generator>http://wordpress.org/?v=x.x.x</generator>
		add_filter( 'the_generator', '__return_empty_string' );
	}
}
add_action( 'init', 'thistle_remove_generator' );

if ( ! function_exists( 'thistle_remove_ver' ) ) {
	/**
	 * Removes WordPress version from assets URLs.
	 *
	 * @param string $src The source URL of the enqueued asset.
	 * @return string
	 */
	function thistle_remove_ver( $src ) {
	    if ( mb_strpos( $src, 'ver=' . get_bloginfo( 'version' ) ) !== false ) {
	        $src = remove_query_arg( 'ver', $src );
	    }

	    return $src;
	}
}
add_filter( 'style_loader_src', 'thistle_remove_ver' );
add_filter( 'script_loader_src', 'thistle_remove_ver' );

if ( ! function_exists( 'thistle_javascript_detection' ) ) {
	/**
	 * Handles JavaScript detection.
	 *
	 * Replaces the HTML class `no-js` by `js` on the <html> element when
	 * JavaScript is detected.
	 */
	function thistle_javascript_detection() {
		echo '<script>(function(html){html.className=html.className.replace(/\bno-js\b/,"js")})(document.documentElement)</script>' . "\n";
	}
}
add_action( 'wp_head', 'thistle_javascript_detection', 0 );

if ( ! function_exists( 'thistle_clean_head' ) ) {
	/**
	 * Cleans `<head>` HTML element.
	 */
	function thistle_clean_head() {
		/*
		 * Hides the shortlink for a post, page, attachment, or blog.
		 * Default shortlink support is limited to providing `?p=` style links for posts.
		 * It's possible to short-circuit this function via the `pre_get_shortlink` filter.
		 */
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );

		/*
		 * Hides the link to the Windows Live Writer manifest file.
		 *
		 * @link http://msdn.microsoft.com/en-us/library/bb463265.aspx
		 */
		remove_action( 'wp_head', 'wlwmanifest_link' );

		/*
		 * Hides the link to the Really Simple Discovery service endpoint.
		 *
		 * @link http://archipelago.phrasewise.com/rsd
		 */
		remove_action( 'wp_head', 'rsd_link' );

		// Hides the REST API link tag into page header.
		remove_action( 'wp_head', 'rest_output_link_wp_head' );

		// Hides the oEmbed discovery links.
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	}
}
add_action( 'init', 'thistle_clean_head' );

if ( ! function_exists( 'thistle_disable_emoji' ) ) {
	/**
	 * Disables the emoji's feature which is enabled by default since WordPress 4.2.
	 */
	function thistle_disable_emoji() {
	    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	    remove_action( 'wp_print_styles', 'print_emoji_styles' );
	    remove_action( 'admin_print_styles', 'print_emoji_styles' );
	    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );

        add_filter( 'emoji_svg_url', '__return_empty_string' );

	    add_filter( 'tiny_mce_plugins', function ( $plugins ) { return array_diff( $plugins, array( 'wpemoji' ) ); } );
	}
}
add_action( 'init', 'thistle_disable_emoji' );

if ( ! function_exists( 'thistle_disable_opensans' ) ) {
	/**
	 * Removes "Open Sans" webfont loaded via Google Fonts from WP core.
	 *
	 * @link http://fontfeed.com/archives/google-webfonts-the-spy-inside/
	 */
	function thistle_disable_opensans() {
		wp_deregister_style( 'open-sans' );

		// Allows styles which have `open-sans` as dependancy to be loaded.
		wp_register_style( 'open-sans', false );
	}
}
add_action( 'admin_enqueue_scripts', 'thistle_disable_opensans' ); // Admin
add_action( 'login_init', 'thistle_disable_opensans' ); // Login
add_action( 'wp_enqueue_scripts', 'thistle_disable_opensans' ); // Admin Toolbar when watching site

if ( ! function_exists( 'thistle_remove_postcustom_support' ) ) {
    /**
     * Removes support for Custom Fields on posts.
     *
     * IMHO, this WordPress feature is not a good idea because you can
     * modify the key of the post meta by error and break the behaviour.
     */
    function thistle_remove_postcustom_support() {
        remove_post_type_support( 'post', 'custom-fields' );
    }
}
add_action( 'init', 'thistle_remove_postcustom_support' );

if ( ! function_exists( 'thistle_register_svgxuse' ) ) {
    /**
     * Registers script to use svgxuse.
     *
     * svgxuse is a simple polyfill that fetches external SVGs referenced
     * in use elements when the browser itself fails to do so.
     *
     * @link https://github.com/Keyamoon/svgxuse
     */
    function thistle_register_svgxuse() {
        $min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

        wp_register_script( 'thistle-svgxuse', get_stylesheet_directory_uri() . '/assets/scripts/vendor/svgxuse/svgxuse' . $min . '.js', array(), false, true );
    }
}
add_action( 'init', 'thistle_register_svgxuse' );

if ( ! function_exists( 'thistle_add_svgxuse_defer_attribute' ) ) {
    /**
     * Adds the `defer` attribute on the `<script>` HTML element for svgxuse.
     *
     * @param string $tag    The `<script>` tag for the enqueued script.
     * @param string $handle The script's registered handle.
     * @param string $src    The script's source URL.
     * @return
     */
    function thistle_add_svgxuse_defer_attribute( $tag, $handle, $src ) {
        if ( $handle == 'thistle-svgxuse' ) {
            $tag = str_replace( ' src', ' defer src', $tag );
        }

        return $tag;
    }
}
add_action( 'script_loader_tag', 'thistle_add_svgxuse_defer_attribute', 10, 3 );

/**
 * Disables the option to publish on your blog using email because
 * this functionality is deprecated and will be removed in an upcoming release.
 */
add_filter( 'enable_post_by_email_configuration', '__return_false' );

if ( ! function_exists( 'thistle_sanitize_option' ) ) {
	/**
	 * Sanitises various option values based on the nature of the option.
	 *
	 * This function is the sucession of `sanitize_option`. It will be used
	 * to check new option in `register_setting` and `$wp_customize->add_setting` methods.
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param string $value          The sanitized option value.
	 * @param string $option         The option name.
	 * @param string $original_value The original value passed to the function.
	 * @return string Sanitized value.
	 */
	function thistle_sanitize_option( $value, $option, $original_value ) {
		global $wpdb;

		$error = '';

		switch ( $option ) {
			case 'thistle_blogdescription' :
				$value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );
				if ( is_wp_error( $value ) ) {
					$error = $value->get_error_message();
				} else {
					$value = wp_kses_post( $value );
					$value = esc_html( $value );
				}
				break;
		}

		if ( ! empty( $error ) ) {
			$value = get_option( $option );
			if ( function_exists( 'add_settings_error' ) ) {
				add_settings_error( $option, 'invalid_' . $option, $error );
			}
		}

		return $value;
	}
}

require_once THISTLE_PATH . '/includes/attachment.php';
require_once THISTLE_PATH . '/includes/author.php';
require_once THISTLE_PATH . '/includes/category.php';
require_once THISTLE_PATH . '/includes/editor.php';
require_once THISTLE_PATH . '/includes/embed.php';
require_once THISTLE_PATH . '/includes/excerpt.php';
require_once THISTLE_PATH . '/includes/gallery-shortcode.php';
require_once THISTLE_PATH . '/includes/search.php';
require_once THISTLE_PATH . '/includes/seo.php';
require_once THISTLE_PATH . '/includes/share.php';
require_once THISTLE_PATH . '/includes/thumbnail.php';
require_once THISTLE_PATH . '/includes/users.php';
require_once THISTLE_PATH . '/includes/admin.php';
