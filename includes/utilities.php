<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly


/**
 * Class DT_Webform_Utilities
 */
class DT_Webform_Utilities {

    public static function get_form_meta( $token ) {

        if ( empty( $token ) ) {
            return false;
        }
        if ( $meta = wp_cache_get( 'get_form_meta', $token ) ) {
            return $meta;
        }

        global $wpdb;

        $post_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_value = %s AND meta_key = 'token' LIMIT 1", $token ) );
        $meta = dt_get_simple_post_meta( $post_id );

        $meta['form_title'] = get_the_title( $post_id );

        wp_cache_set( 'get_form_meta', $meta, $token );

        return $meta;
    }

    public static function get_custom_css( $token ) {

        if ( empty( $token ) ) {
            return false;
        }

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


    public static function get_theme( string $theme, string $token ) {

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
                    ';
                break;
            default:
                return '';
                break;


        }

        $meta = self::get_form_meta( $token );

        /**
         * Location Styles
         */
        $location_styles = '';
        if ( isset( $meta['location_select'] ) && $meta['location_select'] === 'click_map') {
            $location_styles = '
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
                .selection-container {
                    padding: 15px 10px;
                    border: 1px solid lightgray;
                    margin: 5px;
                    font-weight: bold;
                    float:left;
                }
                .selection-remove {
                    padding-left:10px;
                    color: red;
                    cursor: pointer;
                }
                .results-button-column {
                    width:10px;
                    padding-right: 10px;
                    vertical-align: top;
                }
                .results-add-button {
                    
                }
                .results-title-column {}
                .results-title {
                    font-size:1.2em;
                    font-weight:bold;
                }
                .results-row {
                    padding-bottom:5px;
                }
                .results-table {
                    
                }
                .results-table td {
                    padding-bottom: 15px;
                }
            ';
        }

        /**
         * Custom CSS
         */
        $custom_css = '';
        if ( isset( $meta['custom_css'] ) && $meta['custom_css'] === 'click_map') {
            $custom_css = $meta['custom_css'];
        }


        return $location_styles . $css . $custom_css;
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

        // set vars
        $check_permission = false;
        $fields = [];
        $notes = [];
        $new_lead_meta = dt_get_simple_post_meta( $new_lead_id );

        // check required fields
        if ( ! isset( $new_lead_meta['token'] ) || empty( $new_lead_meta['token'] ) ) {
            return new WP_Error( 'missing_contact_info', 'Missing token' );
        }
        if ( ! isset( $new_lead_meta['name'] ) || empty( $new_lead_meta['name'] ) ) {
            return new WP_Error( 'missing_contact_info', 'Missing name' );
        }

        $form_meta = self::get_form_meta( $new_lead_meta['token'] );

        // name
        $fields['title'] = $new_lead_meta['name'];

        // phone
        if ( isset( $new_lead_meta['phone'] ) && ! empty( $new_lead_meta['phone'] ) ) {
            $fields['contact_phone'] = [ [ "value" => $new_lead_meta['phone'] ] ];
        }

        // email
        if ( isset( $new_lead_meta['email'] ) && ! empty( $new_lead_meta['email'] ) ) {
            $fields['contact_email'] = [ [ "value" => $new_lead_meta['email'] ] ];
        }

        // locations
        $locations = [ "values" => [] ];
        $coordinates = [ "values" => [] ];
        foreach ( $new_lead_meta as $lead_key => $lead_value ) {
            if ( 'location_lnglat_' === substr( $lead_key, 0, 16 ) ) {
                $array = explode( ',', $lead_value );

                $longitude = $array[0] ?? '';
                $latitide = $array[1] ?? '';
                $grid_id = $array[2] ?? '';

                $locations['values'][] = [
                    'value' => $grid_id
                ];

                $coordinates['values'][] = [
                    'value' => [
                        'lng' => $longitude,
                        'lat' => $latitide,
                        'grid_id' => $grid_id,
                        'level' => '',
                        'place_name' => ''
                    ]
                ];
            }
        }
        $fields['location_grid'] = $locations;
        $fields['location_lnglat'] = $coordinates;


        // custom fields
        foreach ( $new_lead_meta as $lead_key => $lead_value ) {
            if ( 'cf_' == substr( $lead_key, 0, 3 ) && !empty( $lead_value ) ) {
                $label = ucfirst( str_replace( '_', ' ', substr( $lead_key, 2 ) ) );
                $notes[$lead_key] = $label . ": " . $lead_value;
            }
        }

        // source
        if ( ! empty( $form_meta['source'] ) ) {
            if ( ! isset( $fields['sources'] ) ) {
                $fields['sources'] = [ "values" => [] ];
            }
            $fields['sources']['values'] = [ [ "value" => $form_meta['source'] ] ];
        }

        // hidden input
        if ( ! empty( $new_lead_meta['hidden_input'] ) ) {
            $notes['hidden_input'] = __( 'Hidden Input: ', 'dt_webform' ) . $new_lead_meta['hidden_input'];
        }

        // ip address
        if ( ! empty( $new_lead_meta['ip_address'] ) ) {
            $notes['ip_address'] = __( 'IP Address: ', 'dt_webform' ) . $new_lead_meta['ip_address'];
        }

        // form source
        if ( ! isset( $form_meta['form_title'] ) || empty( $form_meta['form_title'] ) ) {
            $notes['source'] = __( 'Source Form: Unknown (token: ', 'dt_webform' ) . $new_lead_meta['token'] . ')';
        } else {
            $notes['source'] = __( 'Source Form: ', 'dt_webform' )  . $form_meta['form_title'];
        }

        // comments
        if ( ! empty( $new_lead_meta['comments'] ) ) {
            $notes['comments'] = __( 'Comments: ', 'dt_webform' ) . $new_lead_meta['comments'];
        }

        $fields['notes'] = $notes;

//        $fields = [
//            'title' => $new_lead_meta['name'],
//            "contact_phone" => [
//                [ "value" => $phone ], //create
//            ],
//            "contact_email" => [
//                [ "value" => $email ], //create
//            ],
//            'notes' => $notes
//        ];

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

/**
 * This returns a simple array versus the multi dimensional array
 *
 * @return array
 */
if ( ! function_exists( 'dt_get_simple_post_meta' ) ) {
    function dt_get_simple_post_meta( $post_id ) {
        $map = [];
        if ( ! empty( $post_id ) ) {
            $map         = array_map( function( $a ) {
                return $a[0];
            }, get_post_meta( $post_id ) ); // map the post meta
            $map['ID'] = $post_id; // add the id to the array
        }

        return $map;
    }
}

if ( ! function_exists( 'dt_get_location_grid_mirror' ) ) {
    /**
     * Best way to call for the mapping polygon
     * @return array|string
     */
    function dt_get_location_grid_mirror( $url_only = false ) {
        $mirror = get_option( 'dt_location_grid_mirror' );
        if ( empty( $mirror ) ) {
            $array = [
                'key'   => 'google',
                'label' => 'Google',
                'url'   => 'https://storage.googleapis.com/location-grid-mirror/',
            ];
            update_option( 'dt_location_grid_mirror', $array, true );
            $mirror = $array;
        }

        if ( $url_only ) {
            return $mirror['url'];
        }

        return $mirror;
    }
}

if ( ! function_exists( 'dt_get_mapbox_endpoint' ) ) {
    function dt_get_mapbox_endpoint( $type = 'places' ) : string {
        switch( $type ) {
            case 'permanent':
                return 'https://api.mapbox.com/geocoding/v5/mapbox.places-permanent/';
                break;
            case 'places':
            default:
                return 'https://api.mapbox.com/geocoding/v5/mapbox.places/';
                break;
        }
    }
}
