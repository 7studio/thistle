<?php

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
