<?php

if ( ! function_exists( 'thistle_mce_external_plugins' ) ) {
	/**
	 * Loads some more TinyMCE plugins.
	 *
	 * This hook will search into specific directory `/assets/scripts/vendor/tinymce/plugins/`
	 * and it has the same behaviour of `get_template_part()` looking first into child theme
	 * and after parent theme.
	 *
	 * @link https://codex.wordpress.org/TinyMCE
     *
     * @param array $external_plugins An array of external TinyMCE plugins.
     * @return array
	 */
	function thistle_mce_external_plugins( $external_plugins ) {
		$new_plugins = apply_filters( 'thistle_mce_external_plugins', array( 'table' ) );
		$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		foreach ( $new_plugins as $P ) {
			$file = '/assets/scripts/vendor/tinymce/plugins/' . $P . '/plugin' . $min . '.js';

			// Searches in the child theme.
			if ( file_exists( (get_stylesheet_directory() . $file) ) ) {
				$external_plugins[ $P ] = get_stylesheet_directory_uri() . $file;

			// Searches in the parent theme.
			} elseif ( file_exists( (get_template_directory() . $file) ) ) {
				$external_plugins[ $P ] = get_template_directory_uri() . $file;
			}
		}

		return $external_plugins;
	}
}
add_filter( 'mce_external_plugins', 'thistle_mce_external_plugins' );

if ( ! function_exists( 'thistle_tiny_mce_before_init' ) ) {
	/**
	 * Setups TinyMCE.
	 *
	 * @param $mceInit array An array with TinyMCE config.
	 * @return array
	 */
	function thistle_tiny_mce_before_init( $mceInit ) {
		$mceInit['body_class'] = 'Wysiwyg';
		$mceInit['toolbar1'] = 'formatselect,forecolor,|,bold,italic,strikethrough,|,alignleft,aligncenter,alignright,alignfull,|,link,unlink,anchor,|,bullist,numlist,|,blockquote,hr,|,table,|,outdent,indent,|,wp_more,|,pastetext,removeformat,|,undo,redo,wp_help';
		$mceInit['toolbar2'] = '';
		$mceInit['block_formats'] = 'Titre 2=h2;Titre 3=h3;Paragrahe=p';

		return $mceInit;
	}
}
add_filter( 'tiny_mce_before_init', 'thistle_tiny_mce_before_init' );
