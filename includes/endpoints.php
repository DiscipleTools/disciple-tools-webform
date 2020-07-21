<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly
/**
 * DT_Webform_Endpoints
 *
 * @class      DT_Webform_Endpoints
 * @since      0.1.0
 * @package    DT_Webform
 */



/**
 * Class DT_Webform_Endpoints
 */
class DT_Webform_Endpoints
{
    /**
     * DT_Webform_Endpoints The single instance of DT_Webform_Endpoints.
     *
     * @var     object
     * @access    private
     * @since     0.1.0
     */
    private static $_instance = null;

    /**
     * Main DT_Webform_Endpoints Instance
     * Ensures only one instance of DT_Webform_Endpoints is loaded or can be loaded.
     *
     * @since 0.1.0
     * @static
     * @return DT_Webform_Endpoints instance
     */
    public static function instance(){
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    /**
     * Constructor function.
     *
     * @access  public
     * @since   0.1.0
     */
    public function __construct(){
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );

    } // End __construct()

    public function add_api_routes(){
        $version = '1';
        $namespace = 'dt-public/v' . $version;

        register_rest_route(
            $namespace, '/webform/form_submit', [
                [
                    'methods'  => WP_REST_Server::CREATABLE,
                    'callback' => [ $this, 'form_submit' ],
                ],
            ]
        );
    }

    /**
     * Form Submit
     *
     * @param \WP_REST_Request $request
     *
     * @return bool|\WP_Error
     */
    public function form_submit( WP_REST_Request $request ){
//        dt_write_log( __METHOD__ );
        $params = $request->get_params();

        // Honeypot
        if ( isset( $params['email2'] ) && ! empty( $params['email2'] ) ) {
            return new WP_Error( "teapot_failure", "Oops. Busted you, robot. Shame, shame.", [ 'status' => 418 ] );
        } else {
            unset( $params['email2'] );
        }

        // Token Validation
        if ( ! isset( $params['token'] ) || empty( $params['token'] ) ) {
            return new WP_Error( "token_failure", "Token missing.", [ 'status' => 400 ] );
        } else {
            $token = sanitize_text_field( wp_unslash( $params['token'] ) );
            $form_id = DT_Webform_Active_Form_Post_Type::check_if_valid_token( $token );

            if ( ! $form_id ) { // if token is not valid, then error
                return new WP_Error( "token_failure", "Token not valid.", [ 'status' => 401 ] );
            }

            $params['form_title'] = get_the_title( $form_id );
        }

        // Insert new lead
        $this->filter_params( $params );
        return $this->create_contact_record( $params );
    }

    public function filter_params( &$params ) {
        // todo sanitize responses

        // remove empty
        $params = array_filter( $params );

        return $params;
    }

    /**
     * Create Contact via Disciple Tools System
     *
     * @param $new_lead_id
     *
     * @return bool|\WP_Error
     */
    public function create_contact_record( $params ) {
//        dt_write_log( __METHOD__ );
//        dt_write_log( $params );

        // set vars
        $check_permission = false;
        $fields = [];
        $notes = [];
        $new_lead_meta = $params;


        // check required fields
        if ( ! isset( $new_lead_meta['name'] ) || empty( $new_lead_meta['name'] ) ) {
            return new WP_Error( 'missing_contact_info', 'Missing name' );
        }

        // get form data: remote verse local form
        $form_meta = maybe_unserialize( DT_Webform_Utilities::get_form_meta( $params['token'] ) );

//        dt_write_log( '$form_meta' );
//        dt_write_log( $form_meta );

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
        if ( isset( $new_lead_meta['location'] ) && ! empty( $new_lead_meta['location'] ) ) {
            if ( ! ( empty( $new_lead_meta['location']['lat'] ?? null ) || empty( $new_lead_meta['location']['lat'] ?? null ) ) ) {
                $fields['location_grid_meta'] = [
                    "values" => [
                        [
                            'lat' => $new_lead_meta['location']['lat'],
                            'lng' => $new_lead_meta['location']['lng'],
                            'level' => $new_lead_meta['location']['level'] ?? 'place',
                            'label' => $new_lead_meta['location']['label'] ?? 'Unlabeled Place',
                        ]
                    ]
                ];
            }
        }

//        dt_write_log( '$new_lead_meta' );
//        dt_write_log( $new_lead_meta );

        // custom fields
        foreach ( $new_lead_meta as $lead_key => $lead_value ) {
            if ( 'field_' === substr( $lead_key, 0, 6 ) && ! empty( $lead_value ) ) {

                // unserialize post meta
                if ( isset( $form_meta[$lead_key] ) ) {
                    $field = maybe_unserialize( $form_meta[$lead_key] );
                } else {
                    continue;
                }

                if ( ! isset( $field['type'] ) ) {
                    continue;
                }

                // prepare note
                $label = ucfirst( $field['type'] );
                if ( ! is_array( $lead_value ) ) {
                    $notes[$lead_key] = $label . ': ' . $lead_value;
                }

                // prepare mapped fields
                if ( isset( $field['dt_field'] ) && ! empty( $field['dt_field'] ) ) {
                    // set field value to custom field
                    if ( 'other' === $field['dt_field'] ) {
                        // other field preparation
                        switch ( $field['type'] ) {

                            // other fields
                            case 'tel':
                                $fields['contact_phone'][] = [ "value" => $lead_value ];
                                break;
                            case 'email':
                                $fields['contact_email'][] = [ "value" => $lead_value ];
                                break;
                            case 'key_select':
                            case 'dropdown':
                            case 'multi_radio':
                            case 'multi_select':
                                if ( is_array( $lead_value ) ) {
                                    $concat_item = '';
                                    foreach ( $lead_value as $item ) {
                                        $concat_item .= $item . ' | ';
                                    }
                                    $notes[$lead_key] = $field['title'] . ': ' . esc_html( $concat_item );
                                } else {
                                    $notes[$lead_key] = $field['title'] . ': ' . esc_html( $lead_value );
                                }
                                break;
                            case 'checkbox':
                                $notes[$lead_key] = $field['labels'] . ': &#9989;';
                                break;
                            case 'text':
                            case 'note':
                                $notes[$lead_key] = $field['labels'] . ': ' . esc_html( $lead_value );
                                break;
                            default:
                                continue 2;
                                break;
                        }
                    } else {
                        // dt field preparation
                        switch ( $field['type'] ) {

                            // DT Fields
                            case 'date':
                            case 'text':
                            case 'key_select':
                                $fields[$field['dt_field']] = $lead_value;
                                break;
                            case 'multi_select':
                                if ( is_array( $lead_value ) ) {
                                    foreach ( $lead_value as $item ) {
                                        $fields[$field['dt_field']]['values'][] = [ 'value' => $item ];
                                    }
                                }
                                break;
                            default:
                                continue 2;
                                break;
                        }
                    }
                }
            }
        }

        // source
        if ( ! empty( $form_meta['source'] ) ) {
            if ( ! isset( $fields['sources'] ) ) {
                $fields['sources'] = [ "values" => [] ];
            }
            $fields['sources']['values'] = [ [ "value" => $form_meta['source'] ] ];
        }

        // ip address
        if ( ! empty( $new_lead_meta['ip_address'] ) ) {
            $notes['ip_address'] = __( 'IP Address: ', 'dt_webform' ) . $new_lead_meta['ip_address'];
        }

        // form source
        if ( ! isset( $new_lead_meta['form_title'] ) || empty( $new_lead_meta['form_title'] ) ) {
            $notes['form_title'] = __( 'Source Form: Unknown (token: ', 'dt_webform' ) . $new_lead_meta['token'] . ')';
        } else {
            $notes['form_title'] = __( 'Source Form: ', 'dt_webform' )  . $new_lead_meta['form_title'];
        }

        $fields['notes'] = $notes;

        // assign user
        if ( isset( $form_meta['assigned_to'] ) && ! empty( $form_meta['assigned_to'] ) ) {
            $fields['assigned_to'] = $form_meta['assigned_to'];
            $fields['overall_status'] = 'assigned';
        }

//        dt_write_log( 'Pre Submit Fields' );
//        dt_write_log( $fields );

        // Post to contact
        if ( is_dt() ) { // Create contact if hosted in DT

            // add required capability for retrieving defaults
            $current_user = wp_get_current_user();
            $current_user->add_cap( 'create_contacts' );
            $result = Disciple_Tools_Contacts::create_contact( $fields, $check_permission );

        } else { // Create contact if remote

            // @todo post to rest
            $site_id = dt_get_webform_site_link();
            if ( ! $site_id ) {
                return new WP_Error( __METHOD__, 'Not site link set.' );
            }
            $site = Site_Link_System::get_site_connection_vars( $site_id );
            if ( ! $site ) {
                return new WP_Error( __METHOD__, 'Missing site to site data' );
            }

            $args = [
                'method' => 'POST',
                'body' => $fields,
                'headers' => [
                    'Authorization' => 'Bearer ' . $site['transfer_token'],
                ],
            ];

            $result = wp_remote_post( 'https://' . trailingslashit( $site['url'] ) . 'wp-json/dt-posts/v2/contacts', $args );
            if ( is_wp_error( $result ) ) {
                return new WP_Error( 'failed_remote_post', $result->get_error_message() );
            }

            $body = json_decode( $result['body'], true );

//            dt_write_log( 'Post Result' );
//            dt_write_log( $body );

            if ( isset( $body['ID'] ) ) {
                return $result;
            } else {
                $email = get_option( 'dt_webform_admin_fail_email' );
                dt_write_log( 'Fail Contact Create' );
                dt_write_log( $body );
                dt_write_log( $fields );
                wp_mail( $email, 'Failed Form Post', maybe_serialize( $fields ) . '\n' . $body["message"] ?: "" );
                return new WP_Error( 'failed_remote_post', $body["message"] ?? maybe_serialize( $body ), isset( $body["data"] ) ? $body["data"] : [ "status" => 400 ] );

                // @todo slack (failed contact insert)
//                $data = array(
//                    'payload'   => json_encode( array(
//                            "channel"       =>  '#errors',
//                            "text"          =>  'Failed Coaching Request: ' . maybe_serialize( $body ) . ' --- ' . maybe_serialize( $fields ),
//                            "username"        =>  'error-bot',
//                            "icon_emoji"    =>  'ghost'
//                        )
//                    )
//                );
//                // Post our data via the slack webhook endpoint using wp_remote_post
//                $posting_to_slack = wp_remote_post( 'https://hooks.slack.com/services/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', array(
//                        'method' => 'POST',
//                        'timeout' => 30,
//                        'redirection' => 5,
//                        'httpversion' => '1.0',
//                        'blocking' => true,
//                        'headers' => array(),
//                        'body' => $data,
//                        'cookies' => array()
//                    )
//                );
            }
        }

        if ( is_wp_error( $result ) ) {
            return new WP_Error( __METHOD__, $result->get_error_message() . ' - REST failure.' );
        }

        return $result;
    }

}
/**
 * Initialize instance
 */
DT_Webform_Endpoints::instance();
