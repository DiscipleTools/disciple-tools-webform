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
                    'permission_callback' => '__return_true',
                ]
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
            return new WP_Error( 'teapot_failure', 'Oops. Busted you, robot. Shame, shame.', [ 'status' => 418 ] );
        } else {
            unset( $params['email2'] );
        }

        // Token Validation
        if ( ! isset( $params['token'] ) || empty( $params['token'] ) ) {
            return new WP_Error( 'token_failure', 'Token missing.', [ 'status' => 400 ] );
        } else {
            $token = sanitize_text_field( wp_unslash( $params['token'] ) );
            $form_id = DT_Webform_Active_Form_Post_Type::check_if_valid_token( $token );

            if ( ! $form_id ) { // if token is not valid, then error
                return new WP_Error( 'token_failure', 'Token not valid.', [ 'status' => 401 ] );
            }

            $params['form_title'] = get_the_title( $form_id );
        }

        $params['referer'] = $request->get_header( 'referer' );

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
     * Create Contact via Disciple.Tools System
     *
     * @param $new_lead_id
     *
     * @return bool|\WP_Error
     */
    public function create_contact_record( $params ) {

        // set vars
        $check_permission = false;
        $create_args      = [];
        $fields           = [];
        $notes            = [];
        $new_lead_meta    = $params;


        // check required fields
        if ( ! isset( $new_lead_meta['name'] ) || empty( $new_lead_meta['name'] ) ) {
            return new WP_Error( 'missing_contact_info', 'Missing name' );
        }

        // get form data: remote verse local form
        $form_meta            = maybe_unserialize( DT_Webform_Utilities::get_form_meta( $params['token'] ) );
        $check_for_duplicates = ( isset( $form_meta['check_for_duplicates'] ) && $form_meta['check_for_duplicates'] );
        $remote_settings      = DT_Webform_Utilities::get_contact_defaults();


        // form source
        if ( ! isset( $new_lead_meta['form_title'] ) || empty( $new_lead_meta['form_title'] ) ) {
            $notes['form_title'] = __( 'Source Form: Unknown (token: ', 'dt_webform' ) . $new_lead_meta['token'] . ')';
        } else {
            $form_name = $new_lead_meta['form_title'];
            //form submitted on page
            if ( !empty( $params['referer'] ) ){
                $form_name = "[$form_name]({$params['referer']})";
            }
            $notes['form_title'] = __( 'Source Form: ', 'dt_webform' )  . $form_name;
        }


        // form description
        if ( !empty( $form_meta['form_description'] ) ) {
            $notes['form_description'] = __( 'Description', 'dt_webform' ) . ': ' . esc_html( $form_meta['form_description'] );
        }

        // name
        $fields['title'] = $new_lead_meta['name'];
        $notes['title'] = __( 'Name', 'dt_webform' ) . ': ' . $new_lead_meta['name'];

        // phone
        $create_args['check_for_duplicates'] = [];
        if ( isset( $new_lead_meta['phone'] ) && ! empty( $new_lead_meta['phone'] ) ) {
            $fields['contact_phone'] = [ [ 'value' => $new_lead_meta['phone'] ] ];
            $notes['contact_phone'] = __( 'Phone', 'dt_webform' ) . ': ' . $new_lead_meta['phone'];

            if ( $check_for_duplicates ) {
                $create_args['check_for_duplicates'][] = 'contact_phone';
            }
        }

        // email
        if ( isset( $new_lead_meta['email'] ) && ! empty( $new_lead_meta['email'] ) ) {
            $fields['contact_email'] = [ [ 'value' => $new_lead_meta['email'] ] ];
            $notes['contact_email'] = __( 'Email', 'dt_webform' ) . ': ' . $new_lead_meta['email'];

            if ( $check_for_duplicates ) {
                $create_args['check_for_duplicates'][] = 'contact_email';
            }
        }

        // locations
        if ( isset( $new_lead_meta['location'] ) && ! empty( $new_lead_meta['location'] ) ) {
            if ( ! ( empty( $new_lead_meta['location']['lat'] ?? null ) || empty( $new_lead_meta['location']['lat'] ?? null ) ) ) {
                $fields['location_grid_meta'] = [
                    'values' => [
                        [
                            'lat' => $new_lead_meta['location']['lat'],
                            'lng' => $new_lead_meta['location']['lng'],
                            'level' => $new_lead_meta['location']['level'] ?? 'place',
                            'label' => $new_lead_meta['location']['label'] ?? 'Unlabeled Place',
                        ]
                    ]
                ];
                $notes['location_grid_meta'] = __( 'Location', 'dt_webform' ) . ': ' . esc_html( $fields['location_grid_meta']['values'][0]['label'] );
            }
        }

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
                                $fields['contact_phone'][] = [ 'value' => $lead_value ];
                                break;
                            case 'email':
                                $fields['contact_email'][] = [ 'value' => $lead_value ];
                                break;
                            case 'key_select':
                            case 'dropdown':
                            case 'multi_radio':
                            case 'multi_select':
                                if ( is_array( $lead_value ) ) {
                                    $notes[$lead_key] = $field['title'] . ': ' . esc_html( join( ' | ', $lead_value ) );
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

                                // Identify corresponding value label.
                                if ( ( $field['type'] === 'key_select' ) && isset( $field['labels'], $field['values'] ) ) {
                                    foreach ( DT_Webform_Active_Form_Post_Type::match_dt_field_labels_with_values( $field['labels'], $field['values'] ) ?? [] as $list ) {
                                        if ( $list['value'] === $lead_value ){
                                            $lead_value = $list['label'];
                                        }
                                    }
                                }

                                $notes[$lead_key] = ( $field['title'] ?? $field['dt_field'] ) . ': ' . esc_html( $lead_value );
                                break;
                            case 'communication_channel':
                                if ( !isset( $fields[$field['dt_field']] ) ){
                                    $fields[$field['dt_field']] = [];
                                }
                                $fields[$field['dt_field']][] = [ 'value' => $lead_value ];
                                $notes[$lead_key] = ( $field['title'] ?? $field['dt_field'] ) . ': ' . esc_html( $lead_value );
                                break;
                            case 'multi_select':
                                if ( is_array( $lead_value ) ) {
                                    $items = [];
                                    foreach ( $lead_value as $item ) {
                                        $fields[$field['dt_field']]['values'][] = [ 'value' => $item ];
                                        $items[] = $item;
                                    }
                                    if ( !empty( $items ) ){
                                        $select_options = [];

                                        // Identify corresponding value labels.
                                        if ( isset( $field['labels'], $field['values'] ) ) {
                                            $list = DT_Webform_Active_Form_Post_Type::match_dt_field_labels_with_values( $field['labels'], $field['values'] );
                                            foreach ( $items as $item ) {
                                                foreach ( $list ?? [] as $list_element ) {
                                                    if ( $list_element['value'] === $item ){
                                                        $select_options[] = $list_element['label'];
                                                    }
                                                }
                                            }
                                        }

                                        $notes[$lead_key] = ( $field['title'] ?? $field['dt_field'] ) . ': ' . esc_html( implode( ' | ', ( ! empty( $select_options ) ) ? $select_options : $items ) );
                                    }
                                }
                                break;
                            case 'boolean':
                                $fields[$field['dt_field']] = $lead_value === 'on';
                                $notes[$lead_key] = ( $field['labels'] ?? $field['dt_field'] ) . ': ' . ( $lead_value === 'on' ? 'Yes' : 'No' );
                                break;

                            default:
                                continue 2;
                                break;
                        }
                    }
                }
            }

            if ( isset( $remote_settings[$lead_key]['type'] ) ){
                if ( is_array( $lead_value ) && in_array( $remote_settings[$lead_key]['type'], [ 'tags', 'multi_select' ], true ) ){
                    if ( !isset( $fields[$lead_key] ) ){
                        $fields[$lead_key] = [ 'values' => [] ];
                    }
                    foreach ( $lead_value as $item ){
                        $fields[$lead_key]['values'][] = [ 'value' => $item ];
                    }
                }
            }
        }

        // source
        if ( ! empty( $form_meta['source'] ) && !is_wp_error( $remote_settings ) && !empty( $remote_settings['sources']['default'] ) ) {
            if ( ! isset( $fields['sources'] ) ) {
                $fields['sources'] = [ 'values' => [] ];
            }
            $fields['sources']['values'] = [ [ 'value' => $form_meta['source'] ] ];
            $notes['sources'] = __( 'Source: ', 'dt_webform' ) . $remote_settings['sources']['default'][ $form_meta['source'] ]['label'] ?? $form_meta['source'];
        }

        // Capture metadata based sources.
        if ( isset( $new_lead_meta['meta_source'] ) && !empty( $new_lead_meta['meta_source'] ) ){
            if ( !isset( $fields['sources'] ) ){
                $fields['sources'] = [ 'values' => [] ];
            }
            $fields['sources']['values'] = [ [ 'value' => $new_lead_meta['meta_source'] ] ];
            $notes['sources_meta'] = __( 'Meta Source: ', 'dt_webform' ) . $new_lead_meta['meta_source'];
        }

        // metadata - campaigns
        if ( isset( $new_lead_meta['meta_campaigns'] ) && ! empty( $new_lead_meta['meta_campaigns'] ) ) {
            $fields['campaigns']['values'] = [ [ 'value' => $new_lead_meta['meta_campaigns'] ] ];
            $notes['campaigns'] = __( 'Campaigns: ', 'dt_webform' ) . $new_lead_meta['meta_campaigns'];
        }

        // ip address
//        if ( ! empty( $new_lead_meta['ip_address'] ) ) {
//            $notes['ip_address'] = __( 'IP Address: ', 'dt_webform' ) . $new_lead_meta['ip_address'];
//        }


        //submit extra notes as one comment
        $fields['notes'] = [ implode( "\r\n", $notes ) ];

        // assign user
        if ( isset( $form_meta['assigned_to'] ) && ! empty( $form_meta['assigned_to'] ) && $form_meta['assigned_to'] !== 'default_user' ) {
            if ( is_numeric( $form_meta['assigned_to'] ) ){
                $fields['assigned_to'] = $form_meta['assigned_to'];
            }
            $fields['overall_status'] = 'assigned';
            if ( isset( $form_meta['overall_status'] ) && !empty( $form_meta['overall_status'] ) ){
                $fields['overall_status'] = $form_meta['overall_status'];
            }
        }

        $fields = apply_filters( 'dt_webform_fields_before_submit', $fields );
        // Post to contact
        if ( is_this_dt() ) { // Create contact if hosted in DT

            // add required capability for retrieving defaults
            $current_user = wp_get_current_user();
            $current_user->add_cap( 'create_contacts' );
            $current_user->display_name = 'D.T Webform' . ( $new_lead_meta['form_title'] ? ': ' . $new_lead_meta['form_title'] : '' );
            $result = DT_Posts::create_post( 'contacts', $fields, false, false, $create_args );

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

            $check_for_duplicates = '';
            if ( ! empty( $create_args['check_for_duplicates'] ) ) {
                $check_for_duplicates = '?check_for_duplicates=' . implode( ',', $create_args['check_for_duplicates'] );
            }

            $result = wp_remote_post( 'https://' . trailingslashit( $site['url'] ) . 'wp-json/dt-posts/v2/contacts' . $check_for_duplicates, $args );

            if ( is_wp_error( $result ) ) {
                return new WP_Error( 'failed_remote_post', $result->get_error_message() );
            }

            $body = json_decode( $result['body'], true );

            if ( isset( $body['ID'] ) ) {
                return $result;
            } else {
                $email = get_option( 'dt_webform_admin_fail_email' );
                dt_write_log( 'Fail Contact Create' );
                dt_write_log( $body );
                dt_write_log( $fields );
                wp_mail( $email, 'Failed Form Post', maybe_serialize( $fields ) . '\n' . $body['message'] ?? '' );
                return new WP_Error( 'failed_remote_post', $body['message'] ?? maybe_serialize( $body ), isset( $body['data'] ) ? $body['data'] : [ 'status' => 400 ] );
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
