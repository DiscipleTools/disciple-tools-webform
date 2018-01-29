<?php

/**
 * DT_Webform_Remote_Endpoints
 *
 * @class      DT_Webform_Remote_Endpoints
 * @version    0.1.0
 * @since      0.1.0
 * @package    Disciple_Tools
 */

/**
 * @todo
 * 1. Create endpoint to deliver new contacts
 * 2. Create endpoint to confirm successful transfer of contacts
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

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

        if ( isset( $params['id'] ) && isset( $params['token'] ) ) {
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

        // Prepare Insert
        $args = [
            'post_type' => 'dt_webform_new_leads',
            'post_title' => sanitize_text_field( wp_unslash( $params['name'] ) ),
            'comment_status' => 'closed',
            'ping_status' => 'closed',
        ];
        foreach ( $params as $key => $value ) {
            $key = sanitize_text_field( wp_unslash( $key ) );
            $value = sanitize_text_field( wp_unslash( $value ) );
            $args['meta_input'][$key] = $value;
        }

        // Insert
        $status = wp_insert_post( $args, true );
        if ( is_wp_error( $status ) ) {
            return $status;
        } else {
            DT_Webform_Active_Form_Post_Type::increment_lead_received( $form_id );
            return 1;
        }
    }
}
DT_Webform_Remote_Endpoints::instance();
