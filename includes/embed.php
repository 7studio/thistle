<?php

if ( ! function_exists( 'thistle_run_shortcode' ) ) {
    /**
     * Processes all shortcodes at the same time (after `wpautop`).
     *
     * By default, WordPress registers the [embed] shortcode twice:
     * 1. With an empty callback hook which will remove the shortcode into the content
     *    when the filter `do_shortcode` will run AFTER `wpautop`.
     * 2. With a specific callback hook BEFORE `wpautop` which will remove all existing shortcodes,
     *    register the [embed] shortcode, call do_shortcode(), and then
     *    re-register the old shortcodes.
     *
     * It seems this is the expected behaviour that the [embed] shortcode needs
     * to be run earlier than other shortcodes but in this case all `<script>`
     * tags from oEmbed are wrapped into `<p>` tag and brake the specific HTML
     * markup for the RWD.
     *
     * @global WP_Embed $wp_embed
     */
    function thistle_run_shortcode() {
        global $wp_embed;

        // Removes all filters`.
        remove_filter( 'the_content', 'do_shortcode', 11 );
        remove_filter( 'the_content', array( $wp_embed, 'run_shortcode' ), 8 );

        // Adds the [embed] shortcode filter between `wpautop` and `do_shortcode`.
        add_filter( 'the_content', array( $wp_embed, 'run_shortcode' ), 11 );

        /**
         * Restores the filter for all shortcodes after the [embed] one to do not
         * return an empty string for the [embed] shortcodes.
         */
        add_filter( 'the_content', 'do_shortcode', 12 );
    }
}
add_action( 'init', 'thistle_run_shortcode' );

if ( ! function_exists( 'thistle_get_embed' ) ) {
	/**
	 * Builds the Embed shortcode output.
	 *
	 * @param string     $html   …
	 * @param int|string $width  …
	 * @param int        $height …
	 * @return string HTML content to display embed.
	 */
	function thistle_get_embed( $html, $width, $height ) {
		/**
		 * Filters the markup of an embed shortcode before it is generated.
		 *
		 * Passing a non-empty value will short-circuit thistle_get_embed(),
		 * returning that value instead.
		 */
		$output = apply_filters( 'pre_thistle_get_embed', '', $html, $width, $height );
		if ( ! empty( $output ) ) {
			return $output;
		}

		// Because soundcloud returns `width: '100%'`
		if ( $width === '100%' ) {
			$ratio = 0;
			$max_width = $width;
		} else {
			$ratio = round( ((100 * (int) $height) / (int) $width), 4 );
			$max_width = $width . 'px';
		}

		if ( $ratio ) {
			$gcd = gmp_intval( gmp_gcd( $width, $height ) );
			$aspect_ratio = ($width / $gcd) . ":" . ($height / $gcd);

			$output  = '<div class="Embed" embed-aspectRatio="' . $aspect_ratio . '" style="max-width:' . $max_width . '">';
			$output .= '<div class="Embed-ratio" style="padding-bottom:' . $ratio . '%"></div>';
		} else {
			$output  = '<div class="Embed" style="max-width:' . $max_width . '">';
		}
		$output .= '<div class="Embed-content">' . $html . '</div>';
		$output .= '</div>';

		return $output;
	}
}

if ( ! function_exists( 'thistle_enqueue_style_wpembed' ) ) {
	function thistle_enqueue_style_wpembed() {
        $min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_style( 'thistle-embed', get_stylesheet_directory_uri() . '/assets/styles/admin/embed' . $min . '.css' );
	}
}
add_action( 'admin_enqueue_scripts', 'thistle_enqueue_style_wpembed' );

if ( ! function_exists( 'thistle_enable_media_live_embeds' ) ) {
	/**
	 * Enables the `media_live_embeds` option in TinyMCE to allow users
	 * to see a live preview of embedded video content within the editable area,
	 * rather than a placeholder image.
	 * This means that users can play a video clip, such as YouTube, within the editor.
	 *
	 * @param $mceInit array An array with TinyMCE config.
	 * @return array
	 */
	function thistle_enable_media_live_embeds( $mceInit ) {
		$mceInit['media_live_embeds'] = 'true';

		return $mceInit;
	}
}
add_filter( 'tiny_mce_before_init', 'thistle_enable_media_live_embeds' );

/**
 * Because when you paste an URL, it does not handle like when you insert
 * a media from a URL. The second one, wraps the URL between the `[embed]`
 * shortcode.
 *
 * IMHO, this WordPress feature is not a good idea because it isn't finished.
 */
if ( ! function_exists( 'thistle_remove_autoembed' ) ) {
	function thistle_remove_autoembed() {
		remove_filter( 'the_content', array( $GLOBALS['wp_embed'], 'autoembed' ), 8 );
	}
}
add_action( 'init', 'thistle_remove_autoembed' );

if ( ! function_exists( 'thistle_unregister_mce_view_embedUrl' ) ) {
	function thistle_unregister_mce_view_embedUrl() {
		?>
		<script>
			( function( window, wp, $, undefined ) {
				if ( typeof wp !== 'undefined' && wp.mce ) {
					wp.mce.views.unregister( 'embedURL' );
				}
			} )( window, window.wp, window.jQuery );
		</script>
		<?php
	}
}
add_action( 'admin_print_footer_scripts', 'thistle_unregister_mce_view_embedUrl' );

if ( ! function_exists( 'thistle_embed_defaults' ) ) {
	/**
	 * Adjusts the height of embed parameters to be the same as the width.
	 * The default height is 1.5 times the width, or 1000px, whichever is smaller.
	 *
	 * @param array  $size An array of embed width and height values
	 *                     in pixels (in that order).
	 * @param string $url  The URL that should be embedded.
	 * @return array Default embed parameters.
	 */
	function thistle_embed_defaults( $size, $url ) {
		$size['height'] = $size['width'];

		return $size;
	}
}
add_filter( 'embed_defaults', 'thistle_embed_defaults', 10, 2 );

if ( ! function_exists( 'thistle_oembed_min_max_width' ) ) {
	/**
	 * Sets the allowed maximum width for the oEmbed response
	 * with the value of the content width based on the theme's design.
	 * By default, maximum width is egal to 600.
	 *
	 * @param array $min_max_width Minimum and maximum widths for the oEmbed response.
	 * @return array
	 */
	function thistle_oembed_min_max_width( $min_max_width ) {
		if ( ! empty( $GLOBALS['content_width'] ) ) {
			$min_max_width['max'] = (int) $GLOBALS['content_width'];
		}

		return $min_max_width;
	}
}
add_filter( 'oembed_min_max_width', 'thistle_oembed_min_max_width' );

if ( ! function_exists( 'thistle_oembed_remove_maxheight' ) ) {
	/**
	 * Removes size arguments from the oEmbed URL to be fetched.
	 * That will allow external services to give us their default widget sizes.
     *
     * There are two exceptions (YouTube and Dailymotion) which need these arguments
     * to don't return very small version of embeds.
	 *
	 * @param string $provider URL of the oEmbed provider.
	 * @return string
	 */
	function thistle_oembed_remove_maxheight( $provider ) {
        if ( ! preg_match( '/http[s]?:\/\/(?:www\.)?(youtube|dailymotion)\.com/i', $provider ) ) {
		  $provider = remove_query_arg( array( 'maxwidth', 'maxheight' ), $provider );
        }

		return $provider;
	}
}
add_filter( 'oembed_fetch_url', 'thistle_oembed_remove_maxheight' );

if ( ! function_exists( 'thistle_oembed_add_facebook_provider' ) ) {
	/**
	 * Adds support for Facebook oEmbed.
	 *
	 * @link https://developers.facebook.com/docs/plugins/oembed-endpoints
	 * @link https://snippets.khromov.se/facebook-oembed-support-for-wordpress/
	 * @link https://core.trac.wordpress.org/ticket/34737
	 */
	function thistle_oembed_add_facebook_provider() {
		$endpoints = array(
			'#https?://www\.facebook\.com/video.php.*#i'      => 'https://www.facebook.com/plugins/video/oembed.json/',
			'#https?://www\.facebook\.com/.*/videos/.*#i'     => 'https://www.facebook.com/plugins/video/oembed.json/',
			'#https?://www\.facebook\.com/.*/posts/.*#i'      => 'https://www.facebook.com/plugins/post/oembed.json/',
			'#https?://www\.facebook\.com/.*/activity/.*#i'   => 'https://www.facebook.com/plugins/post/oembed.json/',
            '#https?://www\.facebook\.com/.*/photos/.*#i'     => 'https://www.facebook.com/plugins/post/oembed.json/',
			'#https?://www\.facebook\.com/photo(s/|.php).*#i' => 'https://www.facebook.com/plugins/post/oembed.json/',
			'#https?://www\.facebook\.com/permalink.php.*#i'  => 'https://www.facebook.com/plugins/post/oembed.json/',
			'#https?://www\.facebook\.com/media/.*#i'         => 'https://www.facebook.com/plugins/post/oembed.json/',
			'#https?://www\.facebook\.com/questions/.*#i'     => 'https://www.facebook.com/plugins/post/oembed.json/',
			'#https?://www\.facebook\.com/notes/.*#i'         => 'https://www.facebook.com/plugins/post/oembed.json/'
		);

		foreach ( $endpoints as $pattern => $endpoint ) {
			wp_oembed_add_provider( $pattern, $endpoint, true );
		}
	}
}
add_action( 'init', 'thistle_oembed_add_facebook_provider' );

if ( ! function_exists( 'thistle_oembed_facebook_album_url' ) ) {
    /**
     * Catchs the new photos album URL of Facebook and gives the old one to
     * the API.
     *
     * E.g.:
     * `/{username}/photos/?tab=album&album_id={set-id}` -> `/media/set?set={set-id}`
     *
     * @param string $provider URL of the oEmbed provider.
     * @param string $url      URL of the content to be embedded.
     * @return string
     */
    function thistle_oembed_facebook_album_url( $provider, $url ) {
        if ( preg_match( '/^https?:\/\/www\.facebook\.com\/(?:.*)\/photos\/\?(?:.*)album_id=([0-9]*)$/', $url, $matches ) ) {
            $url = 'https://www.facebook.com/media/set/?set=a.' . $matches[1];

            $provider = remove_query_arg( 'url', $provider );
            $provider = add_query_arg( 'url', $url, $provider );
        }

        return $provider;
    }
}
add_filter( 'oembed_fetch_url', 'thistle_oembed_facebook_album_url', 10, 2 );

if ( ! function_exists( 'thistle_oembed_dataparse' ) ) {
	/**
	 * Overrides the returned oEmbed HTML
	 *
	 * Note that we don't hook `embed_html` because we can't access to the data
	 * returned by the oEmbed provider.
	 *
	 * @param string $return The returned oEmbed HTML.
	 * @param object $data   A data object result from an oEmbed provider.
	 * @param string $url    The URL of the content to be embedded.
	 * @return string HTML needed to embed.
	 */
	function thistle_oembed_dataparse( $return, $data, $url ) {
		if ( $return === false ) {
			return $return;
		}

		/*
		 * WordPress oEmbed is a particular case because the provider is
		 * the blogname of each site. To have a similar process between
		 * all providers, we will handle all sites powered by WordPress
		 * under an unique provider name: "WordPres".
		 *
		 * The height of the object is deliberately unset because of the
		 * good behaviour of the WordPress widget in RWD mode. It does not
		 * need to be wrapped into extra HTML markup.
		 */
		if ( mb_strpos( $return, 'wp-embedded-content' ) !== false ) {
			$data->provider_name = 'WordPress';
			$data->height = 0;
		}

		return thistle_get_embed( $return, $data->width, $data->height );
	}
}
add_filter( 'oembed_dataparse', 'thistle_oembed_dataparse', 10, 3 );

if ( ! function_exists( 'thistle_embed_content' ) ) {
	/**
	 * Wraps `<iframe>` into a specific markup to be responsive :D
	 * This hook runs before `run_shortcode()` and `wpautop()` to be sure
	 * to handle only `<iframe>` into the content (not inserted by shortcodes).
	 *
	 * @param string $content Content of the current post.
	 * @return string
	 */
	function thistle_embed_content( $content ) {
		preg_match_all( '/<iframe [^>]+>*.<\/iframe>/isU', $content, $matches );

		foreach ( $matches[0] as $iframe ) {
			$width = '100%';
			$height = 0;

			if ( preg_match( '/width=["\']([0-9]+%?)["\']/', $iframe, $matches ) != false ) {
				$width = $matches[1];
			}
			if ( preg_match( '/height=["\']([0-9]+)["\']/', $iframe, $matches ) != false ) {
				$height = (int) $matches[1];
			}

			$wpembed = thistle_get_embed( $iframe, $width, $height );

			$content = str_replace( $iframe, $wpembed, $content );
		}

		return $content;
	}
}
add_filter( 'the_content', 'thistle_embed_content', 7 );

if ( ! function_exists( 'thistle_soundcloud_shortcode' ) ) {
	/**
	 * Builds the SoundCloud shortcode output.
	 *
	 * @param array  $atts {
	 *     Attributes of the SoundCloud shortcode.
	 *
	 *     @type string $url    Soundcloud URL for a track, set, group, user.
	 *     @type string $params Options for the Soundcloud player widget.
	 *     @type int    $width  Width of the embed media.
	 *     @type string $height Height of the embed media
	 *     @type string $iframe …
	 * }
	 * @return string HTML content to display the SoundCloud embed.
	 */
	function thistle_soundcloud_shortcode( $atts ) {

		/**
		 * Filters the default SoundCloud shortcode output.
		 *
		 * If the filtered output isn't empty, it will be used instead of generating
		 * the default SoundCloud embed template.
		 *
		 * @param string $output The SoundCloud embed output. Default empty.
		 * @param array  $attr   Attributes of the SoundCloud shortcode.
		 */
		$output = apply_filters( 'thistle_soundcloud_shortcode', '', $atts );
		if ( $output != '' ) {
			return $output;
		}

		$atts = shortcode_atts( array(
			'url'	  => '',
			'params'  => '',
			'width'	  => '',
			'height'  => '',
			'iframe'  => '',
		), $atts, 'soundcloud' );

		parse_str( html_entity_decode( $atts['params'] ), $atts['params'] );

		$atts['params']['url'] = $atts['url'];

		// Build URL
		$url = 'https://w.soundcloud.com/player/?' . http_build_query( $atts['params'] );

		return sprintf( '<iframe width="%s" height="%s" scrolling="no" frameborder="no" src="%s"></iframe>', $atts['width'], $atts['height'], $url );
	}
}
add_shortcode( 'soundcloud', 'thistle_soundcloud_shortcode');

if ( ! function_exists( 'thistle_slideshare_shortcode' ) ) {
	/**
	 * Builds the SlideShare shortcode output.
	 *
	 * @global WP_Embed $wp_embed
	 *
	 * @param array  $atts {
	 *     Attributes of the SlideShare shortcode.
	 *
	 *     @type string $id     ID of the slideshow to be fetched.
	 *     @type string $doc    …
	 *     @type int    $w      Width of the embed media.
	 *     @type string $h      Height of the embed media
	 * }
	 * @return string HTML content to display the SlideShare embed.
	 */
	function thistle_slideshare_shortcode( $atts ) {
		/**
		 * Transforms the stupid SlideShare shortcode parameter
		 * into an associative array of attributes
		 * as recommended by the Shortcode API.
		 */
		$str = '';

		if ( is_array( $atts ) ) {
			foreach ( array_keys( $atts ) as $key ) {
				if ( ! is_numeric( $key ) ) {
					$str = $key . '=' . $atts[$key];
				}
			}
		}

		parse_str( html_entity_decode( $str ), $atts );

		/**
		 * Filters the default SlideShare shortcode output.
		 *
		 * If the filtered output isn't empty, it will be used instead of generating
		 * the default SlideShare embed template.
		 *
		 * @param string $output The SlideShare embed output. Default empty.
		 * @param array  $attr   Attributes of the SlideShare shortcode.
		 */
		$output = apply_filters( 'thistle_slideshare_shortcode', '', $atts );
		if ( $output != '' ) {
			return $output;
		}

		$atts = shortcode_atts( array(
			'id'  => '',
			'doc' => '',
			'w'   => '',
			'h'   => ''
		), $atts, 'slideshare' );

		if ( empty( $atts ) || ! isset( $atts['id'] ) || empty( $atts['id'] ) ) {
			return '';
		}

		// Uses WP_Embed to get the HTML
		$attr = array(
			'width'  => $atts['w'],
			'height' => $atts['h']
		);
		$attr = array_filter( $attr );

		$url = 'https://www.slideshare.net/slideshow/embed_code/' . $atts['id'];

		return $GLOBALS['wp_embed']->shortcode( $attr, $url );
	}
}
add_shortcode( 'slideshare', 'thistle_slideshare_shortcode');

if ( ! function_exists( 'thistle_enqueue_wpembed_script' ) ) {
    /**
     * Loads the `wp-embed` script only when the Post content contains
     * an [embed] shortcode which is a WordPress inline HTML embed.
     *
     * This behaviour can be an issue if you display the full posts into a loop.
     *
     * @global WP_Embed $wp_embed
     */
     function thistle_enqueue_wpembed_script() {
        global $wp_embed;

        if ( is_single() || is_page() ) {
            $post = get_post( null );

            if ( $post && has_shortcode( $post->post_content, 'embed' ) ) {
                 $content = $wp_embed->run_shortcode( get_the_content() );

                if ( mb_strpos( $content, 'wp-embedded-content' ) !== false ) {
                    wp_enqueue_script( 'wp-embed' );
                }
            }
        }
     }
}
add_action( 'wp_head', 'thistle_enqueue_wpembed_script', 11 );

if ( ! function_exists( 'thistle_dequeue_wpembed_script' ) ) {
    /**
     * Removes the enqueued script `wp-embed`.
     *
     * This JavaScript is automaticaly added by WordPress all the time
     * to work with other WordPress inline HTML embeds.
     */
    function thistle_dequeue_wpembed_script() {
        remove_action( 'wp_head', 'wp_oembed_add_host_js' );
    }
}
add_action( 'init', 'thistle_dequeue_wpembed_script' );

if ( ! function_exists( 'thistle_add_oembed_tmp_post_meta' ) ) {
    /**
     * Stores temporarily the data object result from the oEmbed provider
     * into a custom field. This meta will be used only to construct the
     * definitive custom field in another hook `oembed_result`.
     *
     * @param string $return The returned oEmbed HTML.
     * @param object $data   A data object result from an oEmbed provider.
     * @param string $url    The URL of the content to be embedded.
     * @return false|string
     */
    function thistle_add_oembed_tmp_post_meta( $return, $data, $url ) {
        $post = get_post();

        if ( ! $post ) {
            return $return;
        }

        if ( $post && $return ) {
            update_post_meta( $post->ID, '_oembed_tmp_' . md5( $url ), $data );
        }

        return $return;
    }
}
add_filter( 'oembed_dataparse', 'thistle_add_oembed_tmp_post_meta', 11, 3 );

if ( ! function_exists( 'thistle_add_oembed_data_post_meta' ) ) {
    /**
     * Stores/Caches the data object result from the oEmbed provider
     * into a custom field like `_oembed_` and `_oembed_time_`. This meta
     * could be used to display provider thumbnail, description and many
     * other things without any additional request.
     *
     * This meta will be deleted and updated at the same time
     * that the other meta about the embed.
     *
     * @param string $data The returned oEmbed HTML.
     * @param string $url  URL of the content to be embedded.
     * @param array  $args Optional arguments, usually passed from a shortcode.
     * @return false|string
     */
    function thistle_add_oembed_data_post_meta( $data, $url, $args ) {
        $post = get_post();

        if ( ! $post ) {
            return $data;
        }

        $_oembed_tmp_data = get_post_meta( $post->ID, '_oembed_tmp_' . md5( $url ), true );
        if ( ! empty( $_oembed_tmp_data ) ) {
            unset( $args['discover'] );
            $key_suffix = md5( $url . serialize( $args ) );

            update_post_meta( $post->ID, '_oembed_data_' . $key_suffix, $_oembed_tmp_data );
            delete_post_meta( $post->ID, '_oembed_tmp_' . md5( $url ) );
        }

        return $data;
    }
}

add_filter( 'oembed_result', 'thistle_add_oembed_data_post_meta', 10, 3 );
