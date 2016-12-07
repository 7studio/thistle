<?php

if ( ! function_exists( 'thistle_register_blogdescription_setting' ) ) {
	/**
	 * Registers a setting to allow users to describe their site/blog and use
	 * the tagline as a catchphrase in the title for example.
	 */
	function thistle_register_blogdescription_setting() {
		register_setting(
			'general',
			'thistle_blogdescription',
            // Because the filter is added with one arg but applied with three.
			function( $value ) {
                return thistle_sanitize_option( $value, 'thistle_blogdescription', get_option( 'thistle_blogdescription', '' ) );
            }
		);

		add_settings_field(
			'thistle_blogdescription',
			__( 'Description' ),
			function () {
			?>
				<textarea name="thistle_blogdescription" id="thistle_blogdescription" aria-describedby="thistle_blogdescription-description" class="large-text code" rows="3"><?php echo esc_textarea( get_option( 'thistle_blogdescription', '' ) ); ?></textarea>
				<p class="description" id="thistle_blogdescription-description"><?php _e( 'The description is used to fill the <code>&lt;meta&gt;</code> description and the Open Graph description of your front page (latest posts).', THISTLE_TEXT_DOMAIN ); ?></p>
			<?php
			},
			'general',
			'default',
			array( 'label_for' => 'thistle_blogdescription' )
		);
	}
}
add_action( 'admin_init' , 'thistle_register_blogdescription_setting' );

if ( ! function_exists( 'thistle_move_blogdescription_setting' ) ) {
	/**
	 * Moves the HTML of thistle_blogdescription setting below the "Tagline" one
	 * with the help of JavaScript because we have no other choice.
	 * It's more logical to find this setting on the top of the page than
	 * finding it below "Week Starts On".
	 */
	function thistle_move_blogdescription_setting() {
		?>
		<script>
			( function( window, $, undefined ) {
				if ( typeof $ !== 'undefined' ) {
					var ref = $( '#blogdescription' ).parents( 'tr' );

					$( '#thistle_blogdescription' )
						.parents( 'tr' )
							.insertAfter( ref );
				}
			} )( window, window.jQuery );
		</script>
		<?php
	}
}
add_action( 'admin_footer-options-general.php', 'thistle_move_blogdescription_setting' );

if ( ! function_exists( 'thistle_customize_blogdescription_setting' ) ) {
	/**
	 * Adds the thistle_blogdescription setting into the Customizer.
	 *
	 * @param WP_Customize_Manager $wp_customize WP_Customize_Manager instance.
	 */
	function thistle_customize_blogdescription_setting( $wp_customize ) {
		$wp_customize->add_setting( 'thistle_blogdescription', array(
			'default'           => get_option( 'thistle_blogdescription', '' ),
			'type'              => 'option',
			'capability'        => 'manage_options',
			// Because the filter is added with one arg but applied with three.
            'sanitize_callback' => function( $value ) {
                return thistle_sanitize_option( $value, 'thistle_blogdescription', get_option( 'thistle_blogdescription', '' ) );
            }
		) );

		$wp_customize->add_control( 'thistle_blogdescription', array(
			'label'       => __( 'Description', THISTLE_TEXT_DOMAIN ),
			'description' => __( 'The description is used to fill the <code>&lt;meta&gt;</code> description and the Open Graph description of your front page (latest posts).', THISTLE_TEXT_DOMAIN ),
			'section'     => 'title_tagline',
			'type'        => 'textarea'
		) );
	}
}
add_action( 'customize_register', 'thistle_customize_blogdescription_setting', 11 );

if ( ! function_exists( 'thistle_noindex_follow' ) ) {
	/**
	 * Does not index :
	 *
	 * - paged results
	 * - search result
	 * - date archive
	 * - 404
	 *
	 * @see wp-includes/general-template.php#L2618
	 *
	 * @global int $paged Page number of a list of posts.
	 */
	function thistle_noindex_follow() {
		global $paged;

		if ( is_date() || is_search() || is_404() || $paged >= 2 || apply_filters( 'thistle_noindex_follow', false ) ) {
			add_action( 'wp_head', 'wp_no_robots' );
		}
	}
}
add_action( 'get_header', 'thistle_noindex_follow' );

if ( ! function_exists( 'thistle_get_description_meta_tag' ) ) {
	/**
	 * Returns description `<meta>` tag for the current page.
	 * Of course, we don't create description for pages which are excluded from
	 * search results.
	 *
	 * Applied rules:
	 *
	 * - Home (page): %posts->post_excerpt%
	 * - Home (posts): %options->blogdescription%
	 * - Page (for posts): %posts->post_excerpt%
	 * - Post: %posts->post_excerpt%
	 * - Author: %usermeta->description%
	 * - Category: %term_taxonomy->description (category)%
	 * - Tag: %term_taxonomy->description (post_tag)%
	 * - Page: %posts->post_excerpt%
	 * - Media: %posts->post_content%
	 *
	 * @return string
	 */
	function thistle_get_description_meta_tag() {
		/**
		 * Filters the description `<meta>` tag before it is generated.
		 *
		 * Passing a non-empty value will short-circuit thistle_get_description_meta_tag(),
		 * returning that value instead.
		 *
		 * @param string $description The description `<meta>` tag. Default empty string.
		 */
		$description = apply_filters( 'pre_thistle_get_description_meta_tag', '' );
		if ( ! empty( $description ) ) {
			return $description;
		}

		// If on the home page
		if ( is_front_page() ) {
			$description = get_option( 'thistle_blogdescription', '' );

		// If on a single post or a single page
		} elseif ( is_home() || is_singular( array( 'post', 'page' ) ) ) {
			$post = get_queried_object();

			// Allows us to use `get_the_excerpt()` and more outside a loop
			setup_postdata( $GLOBALS['post'] =& $post );

			$description = get_the_excerpt();

			wp_reset_postdata();

		// If on a category or tag archive
		} elseif ( is_category() || is_tag() ) {
			$description = term_description();

		// If on an author archive
		} elseif ( is_author() && $author = get_queried_object() ) {
			$description = $author->description;

		// If on an attachment
		} elseif ( is_attachment() ) {
			$description = get_the_content();
		}

		$description = apply_filters( 'thistle_get_description_meta_tag', $description );

		$description = wptexturize( $description );
		$description = convert_chars( $description );
		$description = esc_html( $description );
		$description = capital_P_dangit( $description );

		return $description;
	}
}

if ( ! function_exists( '_thistle_render_description_meta_tag' ) ) {
	/**
 	 * Displays the description meta tag with content.
 	 *
 	 * @ignore
 	 * @access private
 	 */
	function _thistle_render_description_meta_tag() {
		if ( ! current_theme_supports( 'thistle-description-meta-tag' ) ) {
			return;
		}

		echo '<meta name="description" content="' . esc_attr( thistle_get_description_meta_tag() ) . '">' . "\n";
	}
}
add_action( 'wp_head', '_thistle_render_description_meta_tag', 1 );

if ( ! function_exists( 'thistle_document_title_separator' ) ) {
	/**
	 * Sets the separator for the document title.
	 * Default '-'.
	 *
	 * @param string $sep Document title separator.
	 * @return string
	 */
	function thistle_document_title_separator( $sep ) {
		return '–';
	}
}
add_filter( 'document_title_separator', 'thistle_document_title_separator' );

if ( ! function_exists( 'thistle_document_title_parts' ) ) {
	/**
	 * Returns the parts of the document title for the current page.
	 *
	 * Applied rules:
	 *
	 * - Home (posts or page): %options->blogdescription% – %options->blogname%
	 * - Page (for posts): %posts->post_title% – %options->blogname%
	 * - Post: %posts->post_title% – %options->blogname%
	 * - Author: %users->display_name% (%usermeta->first_name% %usermeta->last_name%), author at %options->blogname%
	 * - Category: ???
	 * - Tag: ???
	 * - Page: %posts->post_title% – %options->blogname%
	 * - Media: %posts->post_title% – %posts->post_mime_type% %options->blogname%
	 * - Date: ???
	 * - Search: %found_posts% result(s) have been found for "%search_query%" – %options->blogname%
	 * - 404: Oops! We can’t seem to find the page you’re looking for
	 *
	 * @param array $parts {
	 *     The document title parts.
	 *
	 *     @type string $title   Title of the viewed page.
	 *     @type string $page    Optional. Page number if paginated.
	 *     @type string $tagline Optional. Site description when on home page.
	 *     @type string $site    Optional. Site title when not on home page.
	 * }
	 * @return array
	 */
	function thistle_document_title_parts( $parts ) {
		global $wp_query;

        $parts = array(
            'title' => '',
            'site'  => get_bloginfo( 'name', 'display' )
        );

        // If on the front page, use the site title.
		if ( is_front_page() ) {
			$parts['title'] = get_bloginfo( 'description', 'display' );

		// If on the blog page that is not the homepage or a single post or a single page.
		} elseif ( is_home() || is_singular( array( 'post', 'page' ) ) ) {
			$parts['title'] = single_post_title( '', false );

		// If on a category or tag archive
		} elseif ( is_category() || is_tag() ) {
			$parts['title'] = single_term_title( '', false );

		// If on an author archive
		} elseif ( is_author() && $author = get_queried_object() ) {

			if ( ( $author->first_name . $author->last_name ) != '' && $author->display_name != ( $author->first_name . ' ' . $author->last_name ) && $author->display_name != ( $author->last_name . ' ' . $author->first_name ) ) {
				$full_name = ' (' . $author->first_name . ' ' . $author->last_name . ')';
			} else {
				$full_name = '';
			}

			$parts['title'] = sprintf( __( '%s%s, author at %s', THISTLE_TEXT_DOMAIN ), $author->display_name, $full_name, get_bloginfo( 'name', 'display' ) );
            $parts['site'] = '';

		// If on an attachment
		} elseif ( is_attachment() ) {
			$post = get_post();

			$parts['title'] = single_post_title( '', false );
			$parts['site'] = '';

			if ( mb_strpos( $post->post_mime_type, 'image' ) === 0 ) {
				$parts['site'] .= __( 'Images' ) . ' ';
			} elseif ( mb_strpos( $post->post_mime_type, 'video' ) === 0 ) {
				$parts['site'] .= __( 'Video' ) . ' ';
			} elseif ( mb_strpos( $post->post_mime_type, 'audio' ) === 0 ) {
				$parts['site'] .= __( 'Audio' ) . ' ';
			}

			$parts['site'] .= get_bloginfo( 'name', 'display' );

		// If it's a search
		} elseif ( is_search() ) {
			if ( $wp_query->found_posts ) {
				$parts['title'] = sprintf( _n( '%d result has been found for “%s”', '%d results have been found for “%s”', $wp_query->found_posts, THISTLE_TEXT_DOMAIN ), $wp_query->found_posts, get_search_query() );
			} else {
				$parts['title'] = sprintf( __( 'No results have been found for “%s”', THISTLE_TEXT_DOMAIN ), get_search_query() );
			}

		// If it's a 404 page
		} elseif ( is_404() ) {
			$parts['title'] = __( 'Oops! We can’t seem to find the page you’re looking for', THISTLE_TEXT_DOMAIN );
		}

        /**
         * Filters the parts of the document title.
         *
         * @param array $title {
         *     The document title parts.
         *
         *     @type string|array $title Title of the viewed page.
         *     @type string       $site  Site title.
         * }
         */
        $parts = apply_filters( 'thistle_document_title_parts', $parts );

        /**
         * Flattens the "title" part if it's an array to be processed
         * correctly by the `wp_get_document_title` function.
         */
        if ( is_array( $parts['title'] ) ) {
            $flattened_parts = array();

            array_walk_recursive( $parts, function ( $part ) use( &$flattened_parts ) { $flattened_parts[] = $part; } );

            $parts = $flattened_parts;
        }

		return $parts;
	}
}
add_filter( 'document_title_parts', 'thistle_document_title_parts' );

if ( ! function_exists( 'thistle_add_ga_tracking' ) ) {
    /**
     * Displays the Google Analytics tracking code except when:
     *
     * 1. The "debug" mode is activated;
     * 2. The current visitor is a logged user;
     * 3. The search engines are not allowed to index the site;
     * 4. No tracking code is defined.
     */
    function thistle_add_ga_tracking() {
        if ( WP_DEBUG || current_user_can( 'read' ) || ! get_option( 'blog_public' ) || ! defined( 'THISTLE_GA_TRACKING_ID' ) || THISTLE_GA_TRACKING_ID == '' ) {
            return;
        }
        ?>
        <script>
            (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');
            ga('create', '<?php echo THISTLE_GA_TRACKING_ID; ?>', 'auto');
            ga('send', 'pageview');
        </script>
        <?php
    }
}
add_action( 'wp_head', 'thistle_add_ga_tracking', 9999 );
