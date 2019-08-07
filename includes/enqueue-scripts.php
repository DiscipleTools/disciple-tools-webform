<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Loads scripts and styles for the webform admin page.
 */
function dt_admin_webform_scripts() {
    global $pagenow;

    if ( 'admin.php' === $pagenow && isset( $_GET['page'] ) && 'dt_webform' == sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {

        wp_enqueue_script( 'dt_webform_admin_script', dt_webform()->includes_uri . 'admin.js', [
            'jquery',
            'jquery-ui-core',
        ], filemtime( dt_webform()->includes_path . 'admin.js' ), true );

        wp_register_style( 'dt_webform_admin_css', dt_webform()->includes_uri . 'admin.css', [], filemtime( dt_webform()->includes_path . 'admin.css' ) );
        wp_enqueue_style( 'dt_webform_admin_css' );

    }
}
add_action( 'admin_enqueue_scripts', 'dt_admin_webform_scripts' );
