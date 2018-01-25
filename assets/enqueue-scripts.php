<?php

/**
 * Loads scripts and styles for the contacts page.
 */
function dt_admin_webform_scripts()
{
    global $pagenow;

    if ( 'admin.php' === $pagenow && isset( $_GET['page'] ) && 'dt_webform' == sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
        dt_write_log( 'made it' );
        wp_enqueue_script( 'dt_webform_admin_script', dt_webform()->assets_uri . 'js/admin.js', [
            'jquery',
            'jquery-ui-core',
        ], filemtime( dt_webform()->assets_path . 'js/admin.js' ), true );

        wp_register_style( 'dt_webform_admin_css', dt_webform()->assets_uri . 'css/admin.css', [], filemtime( dt_webform()->assets_path . 'css/admin.css' ) );
        wp_enqueue_style( 'dt_webform_admin_css' );

    }
}
add_action( 'admin_enqueue_scripts', 'dt_admin_webform_scripts' );
