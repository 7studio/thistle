<?php

if ( ! function_exists( '_thistle_get_social_image' ) ) {
    /**
     * Retrieves the url, width and height of the nearest image according
     * to the size selected.
     *
     * If there is no direct match, then the raw image will be returned.
     *
     * @param int $post_id Attachment ID.
     * @return array $data {
     *     Array of file url, width, and height.
     *
     *     @type string $url    Image's url
     *     @type int    $width  Width of image
     *     @type int    $height Height of image
     * }
     */
    function _thistle_get_social_image( $post_id ) {
        /**
         * Filters the image format to define which size to use for sharing.
         *
         * @param string|array $size Image size.
         */
        $image_size = apply_filters( 'thistle_social_image_size', array( 600, 315 ) );

        $image = image_get_intermediate_size( $post_id, $image_size );
        $thumbnail_url = wp_get_attachment_url( $post_id );

        if ( is_array( $image ) ) {
            $wp_upload_dir = wp_upload_dir();

            $image['url'] = str_replace( wp_basename( $thumbnail_url ), $image['file'], $thumbnail_url );
            $image['path'] = str_replace( $wp_upload_dir['baseurl'], $wp_upload_dir['basedir'], $image['url'] );

            list( $image['width'], $image['height'] ) = getimagesize( $image['path'] );
        } else {
            $metadata = wp_get_attachment_metadata( $post_id );

            $image['url'] = $thumbnail_url;
            $image['width'] = $metadata['width'];
            $image['height'] = $metadata['height'];
        }

        return $image;
    }
}

if ( ! function_exists( 'thistle_get_social_meta_tags' ) ) {
	/**
	 * Returns `<meta>` tags needed by Open Graph and Twitter Card.
	 *
	 * @see https://developers.facebook.com/docs/sharing/webmasters
	 * @see https://developers.pinterest.com/docs/rich-pins/overview/
	 * @see https://dev.twitter.com/cards/markup
	 * @see https://developers.google.com/+/web/snippet/article-rendering
	 *
	 * @global WP $wp
	 *
	 * @return array …
	 */
	function thistle_get_social_meta_tags() {
		global $wp;

		$meta = array(
			'og:type'         => 'website',
			'og:site_name'    => get_bloginfo( 'name', 'display' ),
			'og:url'          => trailingslashit( site_url( $wp->request ) ),
			'og:title'        => wp_get_document_title(),
			'og:description'  => thistle_get_description_meta_tag(),
		 // 'og:image'        => THISTLE_CHILD_URI . '/assets/images/default-image.png',
			'og:image'        => '',
			'og:image:width'  => '',
			'og:image:height' => '',
			'twitter:card'    => 'summary'
		);
		$output = '';

		$meta = apply_filters( 'pre_thistle_get_social_meta_tags', $meta );

		// If on a single post or a single page
		if ( ! is_front_page() && is_singular( array( 'post', 'page' ) ) ) {
			$post = get_post();

			if ( is_singular( 'post' ) ) {
				$meta['og:type'] = 'article';
				$meta['article:published_time'] = DateTime::createFromFormat( 'Y-m-d H:i:s', $post->post_date )->format( DateTime::ISO8601 );

				if ( $post->post_date != $post->post_modified ) {
					$meta['article:modified_time'] = DateTime::createFromFormat( 'Y-m-d H:i:s', $post->post_modified )->format( DateTime::ISO8601 );
            	}

				$post_tags = get_the_terms( $post->ID, 'post_tag' );
				if ( is_array( $post_tags ) ) {
                    $meta['article:tag'] = array();

					foreach ( $post_tags as $tag ) {
						$meta['article:tag'][] = $tag->name;
					}
				}

				$categories = get_the_terms( $post->ID, 'category' );
				if ( is_array( $categories ) ) {
                    $meta['article:section'] = array();

					foreach ( $categories as $category ) {
						$meta['article:section'][] = $category->name;
					}
				}
			}

			$meta['og:title'] = get_the_title( $post->ID );
			$meta['og:description'] = get_the_excerpt( $post->ID );
			$meta['og:url'] = get_the_permalink( $post->ID );

			if ( has_post_thumbnail() ) {
                $image = _thistle_get_social_image( get_post_thumbnail_id( $post->ID ) );

				if ( is_array( $image ) ) {
					$meta['og:image'] = $image['url'];
					$meta['og:image:width'] = $image['width'];
					$meta['og:image:height'] = $image['height'];
				}
			}

		// If on an author archive
		} elseif ( is_author() && $author = get_queried_object() ) {
			$meta['og:type'] = 'profile';

			$meta['profile:username'] = $author->display_name;
			$meta['profile:first_name'] = $author->first_name;
			$meta['profile:last_name'] = $author->last_name;

			$avatar_data = get_avatar_data( $author->ID, array( 'size' => 256, 'default' => '404', 'scheme' => 'http' ) );

			if ( $avatar_data['found_avatar'] ) {
				$meta['og:image'] = $avatar_data['url'];
				$meta['og:image:width'] = $avatar_data['width'];
				$meta['og:image:height'] = $avatar_data['height'];
			}

		// If on an attachment
		} elseif ( is_attachment() ) {
			$post = get_post();

			$meta['og:title'] = get_the_title( $post->ID );
			$meta['og:description'] = get_the_excerpt( $post->ID );
			$meta['og:url'] = get_the_permalink( $post->ID );

			if ( has_post_thumbnail() ) {
				$image = _thistle_get_social_image( get_post_thumbnail_id( $post->ID ) );

                if ( is_array( $image ) ) {
                    $meta['og:image'] = $image['url'];
                    $meta['og:image:width'] = $image['wdth'];
                    $meta['og:image:height'] = $image['height'];
                }
			}

			if ( mb_strpos( $post->post_mime_type, 'image' ) === 0 ) {
				$image = _thistle_get_social_image( $post->ID );

                if ( is_array( $image ) ) {
                    $meta['og:image'] = $image['url'];
                    $meta['og:image:width'] = $image['wdth'];
                    $meta['og:image:height'] = $image['height'];
                }
			} elseif ( $post->post_mime_type == 'video/mp4' ) {
				$metadata = wp_get_attachment_metadata( $post->ID );

				$meta['og:video'] = $post->guid;
				$meta['og:video:type'] = $post->post_mime_type;
				$meta['og:video:width'] = $metadata['width'];
				$meta['og:video:height'] = $metadata['height'];
			}
		}

		$meta = apply_filters( 'thistle_get_social_meta_tags', $meta );

		if ( ! WP_DEBUG ) {
			$meta = array_filter( $meta );
		}

		return $meta;
	}
}

if ( ! function_exists( '_thistle_render_social_meta_tags' ) ) {
	/**
 	 * Displays social meta tags with content.
 	 *
 	 * @ignore
 	 * @access private
 	 */
	function _thistle_render_social_meta_tags() {
		if ( ! current_theme_supports( 'thistle-social-meta-tags' ) ) {
			return;
		}

		$output = '';
		$social_meta_tags = thistle_get_social_meta_tags();

		array_walk( $social_meta_tags, function( $value, $key ) use( &$output ) {
            if ( is_array( $value ) ) {
                foreach ( $value as $v ) {
                    $output .= '<meta ' . (mb_strpos( $key, 'twitter:' ) !== false ? 'name' : 'property') . '="' . esc_attr( $key ) . '" content="' . esc_attr( $v ) . '">' . "\n";
                }
            } else {
                $output .= '<meta ' . (mb_strpos( $key, 'twitter:' ) !== false ? 'name' : 'property') . '="' . esc_attr( $key ) . '" content="' . esc_attr( $value ) . '">' . "\n";
            }
		} );

		echo $output;
	}
}
add_action( 'wp_head', '_thistle_render_social_meta_tags', 10 );

if ( ! function_exists( 'thistle_ogp_namespaces' ) ) {
    /**
     * Displays the right and needed Open Graph protocol namespaces
     * to help Facebook to parse our contents correctly.
     */
    function thistle_ogp_namespaces() {
        $ns = array(
            'og' => 'og: http://ogp.me/ns#',
            'fb' => 'fb: http://ogp.me/ns/fb#',
            'ot' => 'website: http://ogp.me/ns/website#'
        );

        // If on a single post
        if ( is_singular( 'post' ) ) {
            $ns['ot'] = 'article: http://ogp.me/ns/article#';

        // If on an author archive
        } elseif ( is_author() && $author = get_queried_object() ) {
            $ns['ot'] = 'profile: http://ogp.me/ns/profile#';

        // If on an attachment
        } elseif ( is_attachment() ) {
            $post = get_post();

            if ( $post->post_mime_type == 'video/mp4' ) {
                $ns['ot'] = 'video: http://ogp.me/ns/video#';
            }
        }

        $ns = apply_filters( 'thistle_ogp_namespaces', $ns );

        echo implode( ' ', array_filter( $ns ) );
    }
}

if ( ! function_exists( 'thistle_get_facebook_sharelink' ) ) {
	/**
	 * Returns the Facebook sharelink for a post, page or attachment.
	 *
	 * @see https://developers.facebook.com/docs/reference/dialogs/feed/
	 *
	 * @param int|WP_Post $id Post ID or post object. Default is 0, which means the current post
	 * @return string A sharelink or an empty string if required request parameters by Facebook are not past.
	 */
	function thistle_get_facebook_sharelink( $id = 0 ) {
		$post = get_post( $id );

		if ( ! empty( $post->ID ) ) {
			$sharelink = 'https://www.facebook.com/sharer/sharer.php?';
			$data = array(
				 'u' => get_the_permalink( $post->ID )
			);

			$data = apply_filters( 'pre_thistle_facebook_sharelink', $data );
			$data = array_filter( $data );

			if ( isset( $data['u'] ) ) {
				$sharelink .= http_build_query( $data, '', '&amp;' );

				return apply_filters( 'thistle_get_facebook_sharelink', $sharelink, $data );
			}
		}

		return '';
	}
}

if ( ! function_exists( 'thistle_the_facebook_sharelink' ) ) {
	/**
	 * Displays the Facebook sharelink for a Post.
	 *
	 * Must be called from inside "The Loop"
	 */
	function thistle_the_facebook_sharelink() {
		$sharelink = apply_filters( 'thistle_the_facebook_sharelink', thistle_get_facebook_sharelink() );

		if ( ! empty( $sharelink ) ) {
			echo esc_url( $sharelink );
		}
	}
}

if ( ! function_exists( 'thistle_get_twitter_sharelink' ) )  {
	/**
	 * Returns the Twitter sharelink for a post, page or attachment.
	 *
	 * @see https://dev.twitter.com/web/tweet-button/web-intent
	 *
	 * @param int|WP_Post $id Post ID or post object. Default is 0, which means the current post
	 * @return string A sharelink or an empty string if required request parameters by Twitter are not past.
	 */
	function thistle_get_twitter_sharelink( $id = 0 ) {
		$post = get_post( $id );

		if ( ! empty( $post->ID ) ) {
			$post_tags = get_the_terms( $post->ID, 'post_tag' );
			$sharelink = 'https://twitter.com/intent/tweet?';
			$data = array(
				'url'         => get_the_permalink( $post->ID ),
				'text'        => get_the_title( $post->ID ),
				'hastag'      => '',
				'via'         => '',
				'related'     => '',
				'in-reply-to' => ''
			);

			if ( is_array( $post_tags ) ) {
				$hashtags = array();

				foreach ( $post_tags as $tag ) {
					$hashtags[] = str_replace( ' ', '', $tag->name );
				}
				array_splice( $hashtags, 3 );
				$data['hashtags'] = implode( ',', $hashtags );
			}

			$data['text'] = html_entity_decode( $data['text'], ENT_QUOTES, 'UTF-8' );

			$data = apply_filters( 'pre_thistle_twitter_sharelink', $data );
			$data = array_filter( $data );

			if ( isset( $data['url'], $data['text'] ) ) {
				$sharelink .= http_build_query( $data, '', '&amp;' );

				return apply_filters( 'thistle_get_twitter_sharelink', $sharelink, $data );
			}
		}

		return '';
	}
}

if ( ! function_exists( 'thistle_the_twitter_sharelink' ) ) {
	/**
	 * Displays the Twitter sharelink for a Post.
	 *
	 * Must be called from inside "The Loop"
	 */
	function thistle_the_twitter_sharelink() {
		$sharelink = apply_filters( 'thistle_the_twitter_sharelink', thistle_get_twitter_sharelink() );

		if ( ! empty( $sharelink ) ) {
			echo esc_url( $sharelink );
		}
	}
}

if ( ! function_exists( 'thistle_get_googleplus_sharelink' ) ) {
	/**
	 * Returns the Google+ sharelink for a post, page or attachment.
	 *
	 * @see https://developers.google.com/+/web/share/#sharelink-endpoint
	 *
	 * @param int|WP_Post $id Post ID or post object. Default is 0, which means the current post
	 * @return string A sharelink or an empty string if required request parameters by Google+ are not past or have bad value.
	 */
	function thistle_get_googleplus_sharelink( $id = 0 ) {
		$post = get_post( $id );

		if ( ! empty( $post->ID ) ) {
			$sharelink = 'https://plus.google.com/share?';
			$data = array(
				 'url' => get_the_permalink( $post->ID ),
				 'hl'  => 'fr'
			);

			$data = apply_filters( 'pre_thistle_googleplus_sharelink', $data );
			$data = array_filter( $data );

			if ( isset( $data['url'] ) ) {
				$sharelink .= http_build_query( $data, '', '&amp;' );

				return apply_filters( 'thistle_googleplus_sharelink', $sharelink, $data );
			}
		}

		return '';
	}
}

if ( ! function_exists( 'thistle_the_googleplus_sharelink' ) ) {
	/**
	 * Displays the Google+ sharelink for a Post.
	 *
	 * Must be called from inside "The Loop"
	 */
	function thistle_the_googleplus_sharelink() {
		$sharelink = apply_filters( 'thistle_the_googleplus_sharelink', thistle_get_googleplus_sharelink() );

		if ( ! empty( $sharelink ) ) {
			echo esc_url( $sharelink );
		}
	}
}

if ( ! function_exists( 'thistle_get_linkedin_sharelink' ) ) {
	/**
	 * Returns the LinkedIn sharelink for a post, page or attachment.
	 *
	 * @see https://developer.linkedin.com/docs/share-on-linkedin
	 *
	 * @param int|WP_Post $id Post ID or post object. Default is 0, which means the current post
	 * @return string A sharelink or an empty string if required request parameters by LinkedIn are not past or have bad values.
	 */
	function thistle_get_linkedin_sharelink( $id = 0 ) {
		$post = get_post( $id );

		if ( ! empty( $post->ID ) ) {
			$sharelink = 'https://www.linkedin.com/shareArticle?';
			$data = array(
				'url'     => get_the_permalink( $post->ID ),
				'mini'    => 'true',
				'title'   => get_the_title( $post->ID ),
				'summary' => get_the_excerpt( $post->ID ),
				'source'  => get_bloginfo( 'description', 'display' )
			);

			$data = apply_filters( 'pre_thistle_linkedin_sharelink', $data );
			$data = array_filter( $data );

			if ( isset( $data['url'], $data['mini'] ) && $data['mini'] === 'true' ) {
				$sharelink .= http_build_query( $data, '', '&amp;' );

				return apply_filters( 'thistle_get_linkedin_sharelink', $sharelink, $data );
			}
		}

		return '';
	}
}

if ( ! function_exists( 'thistle_the_linkedin_sharelink' ) )  {
	/**
	 * Displays the LinkedIn sharelink for a Post.
	 *
	 * Must be called from inside "The Loop"
	 */
	function thistle_the_linkedin_sharelink() {
		$sharelink = apply_filters( 'thistle_the_linkedin_sharelink', thistle_get_linkedin_sharelink() );

		if ( ! empty( $sharelink ) ) {
			echo esc_url( $sharelink );
		}
	}
}

if ( ! function_exists( 'thistle_get_pinterest_sharelink' ) )  {
	/**
	 * Returns the Pinterest sharelink for a post, page or attachment.
	 *
	 * @see https://developers.pinterest.com/docs/widgets/pin-it/#source-settings
	 *
	 * @param int|WP_Post $id Post ID or post object. Default is 0, which means the current post
	 * @return string A sharelink or an empty string if required request parameters by Twitter are not past.
	 */
	function thistle_get_pinterest_sharelink( $id = 0 ) {
		$post = get_post( $post );

		if ( ! empty( $post->ID ) ) {
			$sharelink = 'https://www.pinterest.com/pin/create/button/?';
			$data = array(
				'url'         => get_the_permalink( $post->ID ),
				'media'       => '',
				'description' => ''
			);

			if ( has_post_thumbnail() ) {
                $post_thumbnail_id = get_post_thumbnail_id( $post->ID );
                $image = _thistle_get_social_image( $post_thumbnail_id );

                if ( is_array( $image ) ) {
					$data['media'] = $image['url'];
					$data['description'] = get_the_excerpt( $post_thumbnail_id );
				}
			}

			$data = apply_filters( 'pre_thistle_pinterest_sharelink', $data );
			$data = array_filter( $data );

			if ( isset( $data['url'] ) ) {
				$sharelink .= http_build_query( $data, '', '&amp;' );

				return apply_filters( 'thistle_get_pinterest_sharelink', $sharelink, $data );
			}
		}

		return '';
	}
}

if ( ! function_exists( 'thistle_the_pinterest_sharelink' ) ) {
	/**
	 * Displays the Pinterest sharelink for a Post.
	 *
	 * Must be called from inside "The Loop"
	 */
	function thistle_the_pinterest_sharelink() {
		$sharelink = apply_filters( 'thistle_the_pinterest_sharelink', thistle_get_pinterest_sharelink() );

		if ( ! empty( $sharelink ) ) {
			echo esc_url( $sharelink );
		}
	}
}
