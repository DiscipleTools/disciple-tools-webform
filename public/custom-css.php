<?php
if ( ! isset( $_SERVER['DOCUMENT_ROOT'] ) ) {
    die( 'missing server info' );
}
require( sanitize_text_field( wp_unslash( $_SERVER['DOCUMENT_ROOT'] ) ) .'/wp-load.php' );

if ( ! isset( $_GET['token'] ) ) {
    die( 'missing token' );
}
$dt_webform_token = sanitize_text_field( wp_unslash( $_GET['token'] ) );

$dt_webform_css = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM $wpdb->postmeta WHERE post_id = ( SELECT post_id FROM $wpdb->postmeta WHERE meta_value = %s AND meta_key = 'token' LIMIT 1 ) AND meta_key = 'custom_css' LIMIT 1", $dt_webform_token ) );

header( 'Content-type: text/css' );
echo esc_attr( $dt_webform_css );