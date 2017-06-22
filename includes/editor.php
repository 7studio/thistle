<?php

if ( ! function_exists( 'thistle_mce_external_plugins' ) ) {
	/**
	 * Loads some more TinyMCE plugins.
	 *
	 * This hook will search into specific directory `/assets/vendor/tinymce/plugins/`
	 * looking first into child theme and after parent theme.
	 *
	 * @link https://codex.wordpress.org/TinyMCE
     *
     * @param array $external_plugins An array of external TinyMCE plugins.
     * @return array
	 */
	function thistle_mce_external_plugins( $external_plugins ) {
		$new_plugins = apply_filters( 'thistle_mce_external_plugins', array( 'table', 'template' ) );
		$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		foreach ( $new_plugins as $P ) {
			$file = 'assets/vendor/tinymce/plugins/' . $P . '/plugin' . $min . '.js';

			if ( file_exists( get_theme_file_path( $file ) ) ) {
				$external_plugins[ $P ] = get_theme_file_uri( $file );
            }
		}

		return $external_plugins;
	}
}
add_filter( 'mce_external_plugins', 'thistle_mce_external_plugins' );

if ( ! function_exists( 'thistle_remove_tiny_mce_colorpicker' ) ) {
    /**
     * Pulls out the colorpicker plugin from TinyMCE.
     * Without this plugin the end user cannot select a custom color.
     *
     * @link https://www.tinymce.com/docs/plugins/colorpicker/
     *
     * @param array $plugins An array of default TinyMCE plugins.
     * @return array
     */
    function thistle_remove_tiny_mce_colorpicker( $plugins ) {
        return array_diff( $plugins, array( 'colorpicker' ) );
    }
}
add_filter( 'tiny_mce_plugins', 'thistle_remove_tiny_mce_colorpicker' );

if ( ! function_exists( 'thistle_tiny_mce_textcolor_map' ) ) {
    /**
     * Specifies a map of the text colors that will appear in the grid of
     * the textcolor plugin.
     * If the map is empty, the textcolor plugin is pulled out.
     *
     * @link https://www.tinymce.com/docs/plugins/textcolor/
     *
     * @param $mceInit array An array with TinyMCE config.
     * @return array
     */
    function thistle_tiny_mce_textcolor_map( $mceInit ) {
        $textcolor_map = apply_filters( 'thistle_tiny_mce_textcolor_map', array() );

        if ( ! empty( $textcolor_map ) ) {
            $mceInit['textcolor_map'] = wp_json_encode( $textcolor_map );
        } else {
            $mceInit['plugins'] = explode( ',' , $mceInit['plugins'] );
            $mceInit['plugins'] = array_diff( $mceInit['plugins'], array( 'textcolor' ) );
            $mceInit['plugins'] = implode( ',' , $mceInit['plugins'] );
        }

        return $mceInit;
    }
}
add_filter( 'tiny_mce_before_init', 'thistle_tiny_mce_textcolor_map' );

if ( ! function_exists( 'thistle_tiny_mce_get_templates' ) ) {
    /**
     * Registers all TinyMCE templates found in the `template-parts` dir
     * with the pattern `tinymce-*.php`.
     * Each template should have a header like WordPress page template
     * (PHP comment at the top of the file) with at least
     * the `Title` header, an optional `Description` header
     * and a conditional `Post type` header. Other data headers
     * (Author, Versions) can exist but will not be used.
     *
     * If the templates list is empty, the template plugin is pulled out.
     *
     * @link https://www.tinymce.com/docs/plugins/template/
     *
     * @param $mceInit array An array with TinyMCE config.
     * @return array
     */
    function thistle_tiny_mce_get_templates( $mceInit ) {
        global $typenow;

        $templates = array();

        $pathnames = glob( THISTLE_CHILD_PATH . '/template-parts/tinymce-*.php' );
        if ( ! empty( $pathnames ) ) {
            foreach ( $pathnames as $file ) {
                $datas = get_file_data( $file, array( 'title' => 'Title', 'description' => 'Description', 'post_type' => 'Post Type' ) );
                $datas['post_type'] = array_filter( array_map( 'trim', explode( ',', $datas['post_type'] ) ) );

                if ( $datas['title'] && (in_array( $typenow, $datas['post_type'] ) || empty( $datas['post_type'] )) ) {
                    $datas['url'] = THISTLE_CHILD_URI . '/template-parts/' . basename( $file ) . '?t=' . filemtime( $file );

                    $templates[] = $datas;
                }
            }
        }

        if ( ! empty( $templates ) ) {
            $mceInit['templates'] = json_encode( $templates );
        } else {
            $mceInit['external_plugins'] = json_decode( $mceInit['external_plugins'], true );
            unset( $mceInit['external_plugins']['template'] );
            $mceInit['external_plugins'] = wp_json_encode( $mceInit['external_plugins'] );
        }

        return $mceInit;
    }
}
add_filter( 'tiny_mce_before_init', 'thistle_tiny_mce_get_templates' );

if ( ! function_exists( 'thistle_tiny_mce_before_init' ) ) {
	/**
	 * Setups TinyMCE.
	 *
	 * @param $mceInit array An array with TinyMCE config.
	 * @return array
	 */
	function thistle_tiny_mce_before_init( $mceInit ) {
        // Fix unquoted keys
        $mceInit['formats'] = preg_replace("/(?<!\"|'|\w)([a-zA-Z0-9_]+?)(?!\"|'|\w)\s?:/", "\"$1\":",  $mceInit['formats'] );

		$mceInit['body_class'] = 'Wysiwyg';
		$mceInit['toolbar1'] = 'formatselect,forecolor,|,bold,italic,strikethrough,subscript,superscript,|,alignleft,aligncenter,alignright,alignfull,|,link,unlink,anchor,|,bullist,numlist,|,blockquote,hr,|,table,template,|,outdent,indent,|,wp_more,wp_page,|,charmap,pastetext,removeformat,|,undo,redo,visualblocks,wp_help,|,dfw';
		$mceInit['toolbar2'] = '';
        $mceInit['block_formats'] = 'Titre 2=h2;Titre 3=h3;Paragraphe=p';

		return $mceInit;
	}
}
add_filter( 'tiny_mce_before_init', 'thistle_tiny_mce_before_init' );

if ( ! function_exists( 'thistle_set_wp_lang_attrs' ) ) {
    /**
     * Uses the site language instead of the user one to define
     * the `lang` attribute on the `<html>` element of TinyMCE.
     *
     * @link https://core.trac.wordpress.org/ticket/40715
     * @link https://github.com/polylang/polylang/issues/45
     *
     * @param $mceInit array An array with TinyMCE config.
     * @return array
     */
    function thistle_set_wp_lang_attrs( $mceInit ) {
        $lang = get_locale();
        $lang = str_replace( '_', '-', $lang );

        $mceInit['wp_lang_attr'] = $lang;
        $mceInit['wp_user_lang_attr'] = get_bloginfo( 'language' );
        $mceInit['setup'] = <<<JS
function( editor ) {
  editor.on( 'init', function() {
      var doc = editor.getDoc();
      var dom = editor.dom;

      dom.setAttrib( doc.documentElement, 'data-user-lang', editor.getParam( 'wp_user_lang_attr' ) );
  } );
}
JS;

        return $mceInit;
    }
}
add_filter( 'tiny_mce_before_init', 'thistle_set_wp_lang_attrs' );

if ( ! function_exists( 'thistle_filter_image_link_rel' ) ) {
    /**
     * Removes all invalid values into the rel attribute before
     * the image HTML markup will be sent to the editor.
     *
     * By default WordPress adds two invalid link types values:
     * `attachment` and `wp-att-%post_id%`.
     *
     * @param string $html The image HTML markup to send.
     * @return string
     */
    function thistle_filter_image_link_rel( $html ) {
        $allowed_rels = array( 'alternate', 'archives', 'author', 'bookmark', 'external', 'first', 'help', 'index', 'last', 'license', 'next', 'nofollow', 'noreferrer', 'prefetch', 'prev', 'search', 'sidebar', 'tag', 'up' );

        if ( preg_match('/rel="([^"]*)"/', $html, $matches ) ) {
            $rel = explode( ' ', $matches[1] );
            $rel = array_intersect( $rel, $allowed_rels );
            $rel = implode( ' ', $rel );

            if ( $rel == '' ) {
                $html = str_replace( ' ' . $matches[0], '', $html );
            } else {
                $html = str_replace( $matches[1], $rel, $html );
            }
        }

        return $html;
    }
}
add_filter( 'image_send_to_editor', 'thistle_filter_image_link_rel' );
