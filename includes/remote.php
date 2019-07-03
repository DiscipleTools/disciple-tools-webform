<?php
/**
 * Collection of various utilities
 */

class DT_Webform_Remote
{

    /**
     * Trigger transfer of new leads
     *
     * @param array $selected_records
     *
     * @return bool|\WP_Error
     */
    public static function trigger_transfer_of_new_leads( $selected_records = [] ) {

        $transfer_records = [];
        $transfer_token = '';

        $site_transfer_post_id = get_option( 'dt_webform_site_link' );
        if ( empty( $site_transfer_post_id ) ) {
            return new WP_Error('no_site_transfer_setting', 'No site to site transfer defined.' );
        }
        $transfer_vars = Site_Link_System::get_site_connection_vars( $site_transfer_post_id );

        // get entire record from selected records
        foreach ( $selected_records as $record ) {
            array_push( $transfer_records, dt_get_simple_post_meta( $record ) );
        }

        // Send remote request
        $args = [
            'method' => 'GET',
            'body' => [
                'transfer_token' => $transfer_vars['transfer_token'],
                'selected_records' => $transfer_records,
            ]
        ];
        $result = wp_remote_get( 'https://' . $transfer_vars['url'] . '/wp-json/dt-public/v1/webform/transfer_collection', $args );
        if ( is_wp_error( $result ) ) {
            dt_write_log($result);
            return new WP_Error( 'failed_remote_get', $result->get_error_message() );
        }

        if ( isset( $result['body'] ) && ! empty( $result['body'] ) ) {
            $records = json_decode( $result['body'] );

            foreach ( $records as $record ) {
                wp_delete_post( $record, true );
            }
        }

        return true;
    }

    public static function get_custom_css( $token ) {
        global $wpdb;
        $css = $wpdb->get_var( $wpdb->prepare( "
            SELECT meta_value 
            FROM $wpdb->postmeta 
            WHERE post_id = ( SELECT post_id FROM $wpdb->postmeta WHERE meta_value = %s AND meta_key = 'token' LIMIT 1 ) 
            AND meta_key = 'custom_css' 
            LIMIT 1", $token ) );
        return $css;
    }

    public static function get_form_meta( $token ) {
        global $wpdb;
        $post_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_value = %s AND meta_key = 'token' LIMIT 1", $token ) );
        return dt_get_simple_post_meta( $post_id );
    }

    public static function get_theme( string $theme ) {

        switch ( $theme ) {
            case 'simple':
                return '
                    button.submit-button {
                        padding: .8em;
                        font-weight: bolder;
                    }
                    p.title {
                        font-size: 1.5em;
                        font-weight: bold;
                    }
                    label.error {
                        color: red;
                        font-size: .8em;
                    }
                    .input-text {
                        padding: .7em;
                        width: 200px;
                    }
                    textarea.input-text {
                        height:70px;
                        padding: .7em;
                        border: .5px solid #ccc;
                    }
                    ';
                break;
            case 'heavy':
                return '
                    #contact-form {}
                    .section {}
                    #name {}
                    #phone {}
                    #email {}
                    #comments {}
                    textarea.input-text {}
                    button.submit-button {
                        padding: 1em;
                        font-weight: bolder;
                    }
                    p.title {
                        font-size: 2em;
                        font-weight: bolder;
                        font-family: sans-serif;
                    }
                    label.error {
                        color: red;
                        font-size: .8em;
                    }
                    .input-text {
                        padding: .5em;
                        font-size: 1em;
                        width: 250px;
                    }
                    textarea.input-text {
                        height:80px;
                        padding: .5em;
                        font-size: 1.2em;
                        border: .5px solid #ccc;
                    }
                    .input-label {
                        font-size: 1.2em;
                        font-family: sans-serif;
                    }
                    ';
                break;
            default:
                return '';
                break;
        }
    }



}
