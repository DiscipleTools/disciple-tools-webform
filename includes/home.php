<?php
/**
 * Collection of various utilities
 */

class DT_Webform_Home
{
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