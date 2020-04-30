<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly
/**
 * DT_Webform_Remote_Endpoints
 *
 * @class      DT_Webform_Remote_Endpoints
 * @since      0.1.0
 * @package    DT_Webform
 */



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
        error_log( 'here' );

    }

    /**
     * Form Submit
     *
     * @param \WP_REST_Request $request
     *
     * @return bool|\WP_Error
     */
    public function form_submit( WP_REST_Request $request ){
        dt_write_log( __METHOD__ );
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
        }

        // Insert new lead
        $status = DT_Webform_Utilities::insert_post( $params );

        // Handle error and add form title
        if ( is_wp_error( $status ) ) {
            return $status;
        } else {

            // Increment the lead for for receiving
            DT_Webform_Active_Form_Post_Type::increment_lead_received( $form_id );
            return 1;
        }
    }


}
/**
 * Initialize instance
 */
DT_Webform_Remote_Endpoints::instance();
