<?php

if ( ! function_exists( 'thistle_customize_theme_color' ) ) {
    /**
     * Registers control to define the default theme color
     * for an application.
     * This control will appear inside the "Site Identity" section
     * and under the "Site Icon" control.
     *
     * @param WP_Customize_Manager $wp_customize WP_Customize_Manager instance.
     */
    function thistle_customize_theme_color( $wp_customize ) {
        $wp_customize->add_setting( 'thistle_themecolor', array(
            'default'           => get_option( 'thistle_themecolor', '' ),
            'type'              => 'option',
            'capability'        => 'manage_options'
        ) );

        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'thistle_themecolor', array(
            'label'       => __( 'Color', THISTLE_TEXT_DOMAIN ),
            'description' => __( 'Defines the default theme color for an application. This sometimes affects how the application is displayed by the OS (e.g., on Android\'s task switcher, the theme color surrounds the application).', THISTLE_TEXT_DOMAIN ),
            'section'     => 'title_tagline',
            'settings'    => 'thistle_themecolor',
            'priority'    => 61,
        ) ) );
    }
}
add_action( 'customize_register', 'thistle_customize_theme_color', 11 );

if ( ! function_exists( '_thistle_render_theme_color_meta_tag' ) ) {
    /**
     * Displays the theme color meta tag with content.
     *
     * With 100 as action priority, the theme color meta markup will be
     * displayed after the favicon one.
     *
     * @ignore
     * @access private
     */
    function _thistle_render_theme_color_meta_tag() {
        $theme_color = get_option( 'thistle_themecolor', '' );
        $theme_color = apply_filters( 'thistle_theme_color_meta_tag', $theme_color );

        if ( $theme_color ) {
            echo '<meta name="theme-color" content="' . esc_attr( $theme_color ) . '">' . "\n";
            echo '<meta name="msapplication-TileColor" content="' . esc_attr( $theme_color ) . '">' . "\n";
        }
    }
}
add_action( 'wp_head', '_thistle_render_theme_color_meta_tag', 100 );

if ( ! function_exists( 'thistle_remove_customizer_css_section' ) ) {
    /**
     * Removes the additional CSS section, introduced in 4.7, from the Customizer.
     *
     * @param $wp_customize WP_Customize_Manager
     */
    function thistle_remove_customizer_css_section( $wp_customize ) {
        $wp_customize->remove_section( 'custom_css' );
    }
}
add_action( 'customize_register', 'thistle_remove_customizer_css_section', 15 );
