<?php

if ( ! function_exists( 'thistle_attachment_link' ) ) {
	/**
	 * Retrieves permalink for attachment following an unique permalink
	 * structure: `media/%postame%`.
	 *
	 * By default, WordPress does a f***king business by providing
	 * different permalink structures for attachments:
	 *
	 * - `/%postname%/`: the media is unattached;
	 * - `/parent/%postname%/`: the media is attached
	 *                          and the `%category%` keyword is not into the permalink structure;
	 * - `/parent/attachment/%postname%/`: the media is attached and its name is numeric
	 *                                     or the `%category%` keyword is in the permalink structure;
	 *
	 * @see wp-includes/link-template.php#L374-L430
	 *
	 * @global WP_Rewrite $wp_rewrite
	 *
	 * @param string $link    The attachment's permalink.
	 * @param int    $post_id Attachment ID.
	 * @return string The attachment permalink.
	 */
	function thistle_attachment_link( $link, $post_id ) {
		global $wp_rewrite;

		if ( $wp_rewrite->using_permalinks() && (mb_strpos( $link, '/?attachment_id=') === false) ) {
			$post = get_post( $post_id );

			return home_url( user_trailingslashit( trailingslashit( 'media' ) . $post->post_name ) );
		}

		return $link;
	}
}
add_filter( 'attachment_link', 'thistle_attachment_link', 2, 10 );

if ( ! function_exists( 'thistle_attachment_rewrite' ) ) {
	/**
	 * Handles generated rewrite rules for attachments to match the new
	 * permalink structure.
	 *
	 * @global WP_Rewrite $wp_rewrite
	 *
	 * @param $rules array The compiled array of rewrite rules.
	 * @return array An associate array of matches and queries.
	 */
	function thistle_attachment_rewrite( $rules ) {
		global $wp_rewrite;

		// Searchs the index of `%postname%` into the rewrite tags array.
		$rewritecode_index = array_search( '%postname%', $wp_rewrite->rewritecode );

		// Gets the substituted regular expression.
		$rewritereplace = $wp_rewrite->rewritereplace[ $rewritecode_index ];

		$rules = array_filter( $rules, function( $value ) { return mb_strpos( $value, 'attachment' ) === false; } );

		$attachment_tag = '%ATTACHMENT_TAG%';
		$attachment_rules = array();
		$attachment_rules[ 'media/' . $attachment_tag . '/trackback/?$' ] = 'index.php?%ATTACHMENT_TAG%' . $wp_rewrite->preg_index(1) . '&tb=1';
		$attachment_rules[ 'media/' . $attachment_tag . '/embed/?$' ] = 'index.php?%ATTACHMENT_TAG%' . $wp_rewrite->preg_index(1) . '&embed=true';
		$attachment_rules += $wp_rewrite->generate_rewrite_rules( 'media/'.$attachment_tag, EP_PAGES, false, true, false, false );

		foreach ( $attachment_rules as $key => $value ) {
			$attachment_rules[ str_replace( $attachment_tag, $rewritereplace, $key ) ] = str_replace( $attachment_tag, 'attachment=', $value );

			unset( $attachment_rules[ $key ] );
		}

		return $attachment_rules + $rules;
	}
}
add_filter( 'rewrite_rules_array', 'thistle_attachment_rewrite', 1, 999 );