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

		if ( is_date() || is_search() || is_404() || $paged >= 2 ) {
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
		if ( is_home() ) {
			$description = get_option( 'thistle_blogdescription', '' );

		// If on a single post or a single page
		} elseif ( is_singular( array( 'post', 'page' ) ) ) {
			$post = get_post();
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
			$post = get_post();
			setup_postdata( $GLOBALS['post'] =& $post );

			$description = get_the_content();

			wp_reset_postdata();
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
	 * - Home (page): %posts->post_title% – %options->blogname%
	 * - Home (posts): %options->blogdescription% – %options->blogname%
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

		unset( $parts['page'], $parts['tagline'], $parts['site'] );

		if ( is_home() ) {
			$parts['title'] = get_bloginfo( 'description', 'display' );
			$parts['site'] = get_bloginfo( 'name', 'display' );

		// If on a single post or a single page
		} elseif ( is_singular( array( 'post', 'page' ) ) ) {
			$parts['title'] = get_the_title();
			$parts['site'] = get_bloginfo( 'name', 'display' );

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

		// If on an attachment
		} elseif ( is_attachment() ) {
			$post = get_post();

			$parts['title'] = get_the_title();

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

			$parts['site'] = get_bloginfo( 'name', 'display' );

		// If it's a 404 page
		} elseif ( is_404() ) {
			$title['title'] = __( 'Oops! We can’t seem to find the page you’re looking for', THISTLE_TEXT_DOMAIN );
			$parts['site'] = get_bloginfo( 'name', 'display' );
		}

		return $parts;
	}
}
add_filter( 'document_title_parts', 'thistle_document_title_parts' );
