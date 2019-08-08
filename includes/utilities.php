<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly


/**
 * Class DT_Webform_Utilities
 */
class DT_Webform_Utilities {

    public static function get_form_meta( $token ) {

        if ( $meta = wp_cache_get( 'get_form_meta', $token ) ) {
            return $meta;
        }

        global $wpdb;
        $post_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_value = %s AND meta_key = 'token' LIMIT 1", $token ) );
        $meta = dt_get_simple_post_meta( $post_id );

        wp_cache_set( 'get_form_meta', $meta, $token );

        return $meta;
    }

    public static function get_custom_css( $token ) {

        if ( $meta = wp_cache_get( 'get_custom_css', $token ) ) {
            return $meta;
        }

        global $wpdb;
        $css = $wpdb->get_var( $wpdb->prepare( "
            SELECT meta_value 
            FROM $wpdb->postmeta 
            WHERE post_id = ( SELECT post_id FROM $wpdb->postmeta WHERE meta_value = %s AND meta_key = 'token' LIMIT 1 ) 
            AND meta_key = 'custom_css' 
            LIMIT 1", $token ) );

        wp_cache_set( 'get_custom_css', $css, $token );

        return $css;
    }


    public static function get_theme( string $theme ) {

        // Unique styles
        switch ( $theme ) {
            case 'simple':
                $css =   '
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
                    .mapboxgl-ctrl-geocoder {
                        min-width:100%;
                    }
                    #geocoder {
                        padding-bottom: 10px;
                    }
                    #map {
                        width:66%;
                        height:400px;
                        float:left;
                    }
                    #list {
                        width:33%;
                        float:right;
                    }
                    #selected_values {
                        width:66%;
                        float:left;
                    }
                    .result_box {
                        padding: 15px 10px;
                        border: 1px solid lightgray;
                        margin: 5px 0 0;
                        font-weight: bold;
                    }
                    .add-column {
                        width:10px;
                    }
                    ';
                break;
            case 'heavy':
                $css =   '
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
                    .mapboxgl-ctrl-geocoder {
                        min-width:100%;
                    }
                    ';
                break;
            case 'wide-heavy':
                $css =  '
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
                        width: 100%;
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
                    .mapboxgl-ctrl-geocoder {
                        min-width:100%;
                    }
                    #geocoder {
                        padding-bottom: 10px;
                    }
                    #map {
                        width:66%;
                        height:400px;
                        float:left;
                    }
                    #list {
                        width:33%;
                        float:right;
                    }
                    #list tr {
                    
                    }
                    #selected_values {
                        width:66%;
                        float:left;
                    }
                    .result_box {
                        padding: 15px 10px;
                        border: 1px solid lightgray;
                        margin: 5px 0 0;
                        font-weight: bold;
                    }
                    .add-column {
                        width:10px;
                    }
                    ';
                break;
            default:
                return '';
                break;


        }

        // common styles

        return $css;
    }

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
            return new WP_Error( 'no_site_transfer_setting', 'No site to site transfer defined.' );
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
            dt_write_log( $result );
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

    /**
     * Create Contact via Disciple Tools System
     *
     * @param $new_lead_id
     *
     * @return bool|\WP_Error
     */
    public static function create_contact_record( $new_lead_id ) {

        $check_permission = false;

        $new_lead_meta = dt_get_simple_post_meta( $new_lead_id );

        // Get the id of the form source of the lead
        if ( ! isset( $new_lead_meta['token'] ) || empty( $new_lead_meta['token'] ) ) {
            return new WP_Error( 'missing_contact_info', 'Missing token' );
        }

        // Build record
        if ( ! isset( $new_lead_meta['name'] ) || empty( $new_lead_meta['name'] ) ) {
            return new WP_Error( 'missing_contact_info', 'Missing name' );
        }

        // Build extra field data
        $notes = [];
        foreach ( $new_lead_meta as $lead_key => $lead_value ) {
            if ( 'cf_' == substr( $lead_key, 0, 3 ) && !empty( $lead_value ) ) {
                $label = ucfirst( str_replace( '_', ' ', substr( $lead_key, 2 ) ) );
                $notes[$lead_key] = $label . ": " . $lead_value;
            }
        }
        if ( ! empty( $new_lead_meta['hidden_input'] ) ) {
            $notes['hidden_input'] = __( 'Hidden Input: ', 'dt_webform' ) . $new_lead_meta['hidden_input'];
        }
        if ( ! empty( $new_lead_meta['ip_address'] ) ) {
            $notes['ip_address'] = __( 'IP Address: ', 'dt_webform' ) . $new_lead_meta['ip_address'];
        }
        if ( ! isset( $new_lead_meta['form_title'] ) || empty( $new_lead_meta['form_title'] ) ) {
            $notes['source'] = __( 'Source Form: Unknown (token: ', 'dt_webform' ) . $new_lead_meta['token'] . ')';
        } else {
            $notes['source'] = __( 'Source Form: ', 'dt_webform' )  . $new_lead_meta['form_title'];
        }
        if ( ! empty( $new_lead_meta['comments'] ) ) {
            $notes['comments'] = __( 'Comments: ', 'dt_webform' ) . $new_lead_meta['comments'];
        }

        $phone = $new_lead_meta['phone'] ?? '';
        $email = $new_lead_meta['email'] ?? '';

        // Build record data
        $fields = [
            'title' => $new_lead_meta['name'],
            "contact_phone" => [
                [ "value" => $phone ], //create
            ],
            "contact_email" => [
                [ "value" => $email ], //create
            ],
            'notes' => $notes
        ];

        // Post to contact
        if ( ! class_exists( 'Disciple_Tools_Contacts' ) ) {
            return new WP_Error( 'disciple_tools_missing', 'Disciple Tools is missing.' );
        }

        // Create contact
        $result = Disciple_Tools_Contacts::create_contact( $fields, $check_permission );

        if ( is_wp_error( $result ) ) {
            return new WP_Error( 'failed_to_insert_contact', $result->get_error_message() );
        }

        // Delete new lead after success
        $delete_result = wp_delete_post( $new_lead_id, true );
        if ( is_wp_error( $delete_result ) ) {
            return new WP_Error( 'failed_to_delete_contact', $result->get_error_message() );
        }

        return $result;
    }
}
