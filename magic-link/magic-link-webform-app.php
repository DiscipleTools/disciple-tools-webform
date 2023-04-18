<?php
if ( !defined( 'ABSPATH' ) ){
    exit;
} // Exit if accessed directly.

class Disciple_Tools_Webform_Magic_Link_App extends DT_Magic_Url_Base{

    public $page_title = 'Webform Magic Link';
    public $page_description = 'Webform Magic Link';
    public $root = 'webform'; // define the root of the url {yoursite}/root/type/key/action
    public $type = 'ml'; // define the type
    public $post_type = 'dt_webform_forms';
    private $meta_key = '';
    public $show_bulk_send = false;
    public $show_app_tile = false; // show this magic link in the Apps tile on the post record

    private static $_instance = null;
    public $meta = []; // Allows for instance specific data.

    private $dt_webform_token = null;
    private $dt_webform_campaigns = null;
    private $dt_webform_meta = null;
    private $dt_webform_core_fields = null;
    private $dt_webform_fields = null;
    private $dt_webform_public_url = null;

    public static function instance(){
        if ( is_null( self::$_instance ) ){
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct(){
        $this->meta_key = $this->root . '_' . $this->type . '_magic_key';
        parent::__construct();

        /**
         * Test if other URL...?
         */

        $url = dt_get_url_path();
        if ( strpos( $url, $this->root . '/' . $this->type ) === false ){
            return;
        }

        /**
         * Ensure a valid token has been specified and extract/set associated
         * web-form assets.
         */

        $this->dt_webform_token = $this->fetch_incoming_link_param( 'token' );
        if ( !empty( $this->dt_webform_token ) ){
            $this->page_title = DT_Webform_Active_Form_Post_Type::get_core_fields_by_token( $this->dt_webform_token )['header_title_field']['label'] ?? $this->page_title;
            $this->dt_webform_campaigns = $this->fetch_incoming_link_param( 'campaigns' ) ?? '';
            $this->dt_webform_meta = DT_Webform_Utilities::get_form_meta( $this->dt_webform_token );
            $this->dt_webform_core_fields = DT_Webform_Active_Form_Post_Type::get_core_fields_by_token( $this->dt_webform_token );
            $this->dt_webform_fields = DT_Webform_Active_Form_Post_Type::get_extra_fields( $this->dt_webform_token );
            $this->dt_webform_public_url = str_replace( 'magic-link', 'public', trailingslashit( plugin_dir_url( __FILE__ ) ) );

            // Ensure valid core fields are specified, otherwise redirect to expired landing page.
            if ( empty( $this->dt_webform_core_fields ) ){
                $this->magic->redirect_to_expired_landing_page();
            }
        } else{
            $this->magic->redirect_to_expired_landing_page();
        }

        /**
         * Register required hooks.
         */

        add_filter( 'dt_magic_url_base_allowed_css', [ $this, 'dt_magic_url_base_allowed_css' ], 10, 1 );
        add_filter( 'dt_magic_url_base_allowed_js', [ $this, 'dt_magic_url_base_allowed_js' ], 10, 1 );
        add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ], 100 );
        add_action( 'dt_blank_body', [ $this, 'body' ] );

    }

    public function dt_magic_url_base_allowed_css( $allowed_css ){
        return $allowed_css;
    }

    public function dt_magic_url_base_allowed_js( $allowed_js ){
        return $allowed_js;
    }

    public function wp_enqueue_scripts(){
    }

    /**
     * Writes custom styles to header
     *
     * @see DT_Magic_Url_Base()->header_style() for default state
     */
    public function header_style(){
        ?>
        <style>
            body {
                background-color: white;
                padding: 1em;
            }
        </style>
        <?php
    }

    /**
     * Writes javascript to the header
     *
     * @see DT_Magic_Url_Base()->header_javascript() for default state
     */
    public function header_javascript(){
        if ( !empty( $this->dt_webform_token ) ){
            DT_Webform_Utilities::echo_form_html_scripts_and_styles( $this->dt_webform_token, $this->dt_webform_meta, $this->dt_webform_fields, $this->dt_webform_public_url );
        }
    }

    public function body(){
        if ( !empty( $this->dt_webform_token ) ){
            ?>
            <div id="wrapper">
                <form id="contact-form" action="">
                    <?php DT_Webform_Utilities::echo_form_html( $this->dt_webform_token, $this->dt_webform_campaigns, $this->dt_webform_core_fields, $this->dt_webform_fields, $this->dt_webform_public_url ); ?>
                </form>

                <div id="report"></div>
                <div id="offlineWarningContainer"></div>
            </div> <!-- wrapper-->
            <?php
        } else{
            $this->magic->redirect_to_expired_landing_page();
        }
    }

    /**
     * Writes javascript to the footer
     *
     * @see DT_Magic_Url_Base()->footer_javascript() for default state
     */
    public function footer_javascript(){

    }
}

Disciple_Tools_Webform_Magic_Link_App::instance();

