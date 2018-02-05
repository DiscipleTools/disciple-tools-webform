<?php
/**
 * DT_Webform_Remote_Endpoints
 *
 * @class      DT_Webform_Remote_Endpoints
 * @since      0.1.0
 * @package    DT_Webform
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Initialize instance
 */
DT_Webform_Remote_Endpoints::instance();
/**
 * Class DT_Webform_Remote_Endpoints
 */
class DT_Webform_Remote_Endpoints
{
    /**
     * DT_Webform_Remote_Endpoints The single instance of DT_Webform_Remote_Endpoints.
     *
     * @var     object
     * @access    private
     * @since     0.1.0
     */
    private static $_instance = null;

    /**
     * Main DT_Webform_Remote_Endpoints Instance
     * Ensures only one instance of DT_Webform_Remote_Endpoints is loaded or can be loaded.
     *
     * @since 0.1.0
     * @static
     * @return DT_Webform_Remote_Endpoints instance
     */
    public static function instance()
    {
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
    public function __construct()
    {
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
    } // End __construct()

    public function add_api_routes()
    {
        $version = '1';
        $namespace = 'dt-public/v' . $version;

        register_rest_route(
            $namespace, '/webform/site_link_check', [
                [
                    'methods'  => WP_REST_Server::CREATABLE,
                    'callback' => [ $this, 'site_link_check' ],
                ],
            ]
        );
        register_rest_route(
            $namespace, '/webform/form_submit', [
                [
                    'methods'  => WP_REST_Server::CREATABLE,
                    'callback' => [ $this, 'form_submit' ],
                ],
            ]
        );
        register_rest_route(
            $namespace, '/webform/get_collection', [
                [
                    'methods'  => WP_REST_Server::READABLE,
                    'callback' => [ $this, 'get_collection' ],
                ],
            ]
        );
        register_rest_route(
            $namespace, '/webform/delete_confirmation', [
                [
                    'methods'  => WP_REST_Server::READABLE,
                    'callback' => [ $this, 'delete_confirmation' ],
                ],
            ]
        );
    }


    /**
     * Verify is site is linked
     * @todo identical function hosted in home-endpoints.php. Reevaluate if this is the DRYest option.
     *
     * @param  WP_REST_Request $request
     *
     * @access public
     * @since  0.1.0
     * @return string|WP_Error|array The contact on success
     */
    public function site_link_check( WP_REST_Request $request )
    {
        $params = $request->get_params();

        $prefix = 'dt_webform_site';

        if ( isset( $params['token'] ) ) {
            return DT_Webform_Api_Keys::check_api_key( $params['id'], $params['token'], $prefix );
        } else {
            return new WP_Error( "site_check_error", "Malformed request", [ 'status' => 400 ] );
        }
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return bool|\WP_Error
     */
    public function form_submit( WP_REST_Request $request )
    {
        $params = $request->get_params();

        // Token Validation
        if ( ! isset( $params['token'] ) || empty( $params['token'] ) ) { // @todo Need to add actual token checking
            return new WP_Error( "token_failure", "Token missing.", [ 'status' => 400 ] );
        } else {
            $token = sanitize_text_field( wp_unslash( $params['token'] ) );
            $form_id = DT_Webform_Active_Form_Post_Type::check_if_valid_token( $token );

            if ( ! $form_id ) { // if token is not valid, then error
                return new WP_Error( "token_failure", "Token not valid.", [ 'status' => 401 ] );
            }
        }

        $status = DT_Webform_New_Leads_Post_Type::insert_post( $params );

        if ( is_wp_error( $status ) ) {
            return $status;
        } else {
            // Add the form title to the record.
            $form_title = DT_Webform_Active_Form_Post_Type::get_form_title_by_token( $params['token'] );
            update_post_meta( $status, 'form_title', $form_title );
            // Increment the lead for for receiving
            DT_Webform_Active_Form_Post_Type::increment_lead_received( $form_id );
            return 1;
        }
    }

    public function get_collection( WP_REST_Request $request )
    {
        $params = $request->get_params();

        if ( isset( $params['id'] ) && isset( $params['token'] ) ) {
            // check id
            $id_decrypted = DT_Webform_Api_Keys::check_one_hour_encryption( 'id', $params['id'] );
            if ( is_wp_error( $id_decrypted ) || ! $id_decrypted ) {
                return new WP_Error( "site_check_error_1", "Malformed request", [ 'status' => 400 ] );
            }

            // check token
            $token_result = DT_Webform_API_Keys::check_token( $id_decrypted, $params['token'] );
            if ( is_wp_error( $token_result ) || ! $token_result ) {
                return new WP_Error( "site_check_error_2", "Malformed request", [ 'status' => 400 ] );
            } else {
                // call async process to schedule collection
                dt_write_log( 'remote collection end point ' );

                if ( isset( $params['get_all'] ) && $params['get_all'] ) {
                    // get requested records
                    $transfer_data = [];
                    $results = new WP_Query(['post_type' => 'dt_webform_new_leads', 'post_status' => 'publish, draft']);
                    if( $results->post_count > 0 ) {
                        foreach( $results->posts as $result) {
                            $meta = dt_get_simple_post_meta($result->ID);
                            $meta['ID'] = $result->ID;
                            unset( $meta['scheduled_for_transfer'] );
                            $transfer_data[] = $meta;
                        }
                    }

                    dt_write_log( $meta );

                    // prepare and send back requested records
                    return $transfer_data;
                } elseif ( isset( $params['selected_records'] ) && ! empty( $params['selected_records'] ) ) {
                    // get requested records
                    $transfer_data = [];
                    $results = new WP_Query(
                    [
                        'post_type' => 'dt_webform_new_leads',
                        'post_status' => 'publish, draft',
                        'post__in'  => array_map( 'sanitize_key', $params['selected_records'] ),
                    ]);
                    if( $results->post_count > 0 ) {
                        foreach( $results->posts as $result) {
                            $meta = dt_get_simple_post_meta($result->ID);
                            $meta['ID'] = $result->ID;
                            unset( $meta['scheduled_for_transfer'] );
                            $transfer_data[] = $meta;
                        }
                    }

                    dt_write_log( $meta );

                    // prepare and send back requested records
                    return $transfer_data;
                } else {
                    return new WP_Error( "missing_params", "Missing either the `get_all` or the `selected_records` parameters.", [ 'status' => 400 ] );
                }
            }
        } else {
            return new WP_Error( "site_check_error_3", "Malformed request", [ 'status' => 400 ] );
        }
    }


    public function delete_confirmation( WP_REST_Request $request )
    {
        $params = $request->get_params();
        $test = $this->verify_param_id_and_token( $params );

        if( ! is_wp_error( $test) && $test ) {
            dt_write_log( $params );

            foreach( $params['delete_records'] as $records ) {
                $deleted_status[] = wp_delete_post( $records, true );
            }

            dt_write_log('Deleted: ' );
            dt_write_log( $deleted_status );

            if( is_wp_error( $deleted_status ) ) {
                return new WP_Error( 'failed_to_delete', 'Not all records deleted' );
            } else {
                return 1;
            }

        } else {
            return new WP_Error( $test->get_error_code(), $test->get_error_message() );
        }

    }


    /**
     * Verify the token and id of a REST request
     * @param $params
     *
     * @return bool|\WP_Error
     */
    protected function verify_param_id_and_token( $params ) {
        if ( isset( $params['id'] ) && isset( $params['token'] ) ) {
            // check id
            $id_decrypted = DT_Webform_Api_Keys::check_one_hour_encryption( 'id', $params['id'] );
            if ( is_wp_error( $id_decrypted ) || ! $id_decrypted ) {
                return new WP_Error( "site_check_error_1", "Malformed request", [ 'status' => 400 ] );
            }

            // check token
            $token_result = DT_Webform_API_Keys::check_token( $id_decrypted, $params['token'] );
            if ( is_wp_error( $token_result ) || ! $token_result ) {
                return new WP_Error( "site_check_error_2", "Malformed request", [ 'status' => 400 ] );
            } else {
                return true;
            }
        } else {
            return new WP_Error( "site_check_error_3", "Malformed request", [ 'status' => 400 ] );
        }
    }
}

