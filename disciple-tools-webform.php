<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly
/**
 * Plugin Name: Disciple Tools - Webform
 * Plugin URI: https://github.com/DiscipleTools/disciple-tools-webform
 * Description: Disciple Tools - Webform extends the Disciple Tools system to send and receive remote submissions from webform contacts.
 * Version:  3.0
 * Author URI: https://github.com/DiscipleTools
 * GitHub Plugin URI: https://github.com/DiscipleTools/disciple-tools-webform
 * Requires at least: 4.7.0
 * (Requires 4.7+ because of the integration of the REST API at 4.7 and the security requirements of this milestone version.)
 * Tested up to: 5.4
 *
 * @package Disciple_Tools
 * @link    https://github.com/DiscipleTools
 * @license GPL-2.0 or later
 *          https://www.gnu.org/licenses/gpl-2.0.html
 */
$dt_webform_required_dt_theme_version = '0.27.0';

/*******************************************************************************************************************
 * MIGRATION ENGINE
 ******************************************************************************************************************/
require_once( 'includes/class-migration-engine.php' );
try {
    DT_Webform_Migration_Engine::migrate( DT_Webform_Migration_Engine::$migration_number );
} catch ( Throwable $e ) {
    $migration_error = new WP_Error( 'migration_error', 'Migration engine for webform failed to migrate.', [ 'error' => $e ] );
    if ( function_exists( 'dt_write_log' ) ) {
        dt_write_log( maybe_serialize( $migration_error ) );
    } else {
        error_log( 'Migration engine for webform failed to migrate.' );
    }
}
/*******************************************************************************************************************/

/**
 * Gets the instance of the `DT_Webform` class.  This function is useful for quickly grabbing data
 * used throughout the plugin.
 *
 * @since  0.1
 * @access public
 * @return object|bool
 */
function dt_webform() {
    global $dt_webform_required_dt_theme_version;

    if ( is_dt() ) {
        $wp_theme = wp_get_theme();
        $version = $wp_theme->version;
        if ( $version < $dt_webform_required_dt_theme_version ) {
            if ( ! is_multisite() ) {
                add_action( 'admin_notices', 'dt_webform_no_disciple_tools_theme_found' );
                add_action( 'wp_ajax_dismissed_notice_handler', 'dt_hook_ajax_notice_handler' );
            }
            return new WP_Error( 'current_theme_not_dt', 'Please upgrade Disciple Tools Theme to ' . $dt_webform_required_dt_theme_version . ' to use this plugin.' );
        }
    }

    $is_rest = dt_is_rest();
    if ( $is_rest && strpos( dt_get_url_path(), 'webform' ) !== false ){
        return DT_Webform::get_instance();
    }

    if ( is_admin() ) {
        return DT_Webform::get_instance();
    }

    return false;
}
add_action( 'after_setup_theme', 'dt_webform' );


/**
 * Singleton class for setting up the plugin.
 *
 * @since  0.1
 * @access public
 */
class DT_Webform {

    /**
     * Declares public variables
     *
     * @since  0.1
     * @access public
     * @return object
     */
    public static $token = 'dt_webform';
    public $version;
    public $dir_path;
    public $dir_uri;
    public $assets_uri;
    public $img_uri;
    public $js_uri;
    public $css_uri;
    public $includes_path;
    public $admin_path;
    public $home_path;
    public $remote_path;
    public $assets_path;
    public $public_uri;

    /**
     * Returns the instance.
     *
     * @since  0.1
     * @access public
     * @return object
     */
    public static function get_instance() {

        static $instance = null;

        if ( is_null( $instance ) ) {
            $instance = new DT_Webform();
            $instance->setup();
            $instance->setup_actions();
        }
        return $instance;
    }

    /**
     * Sets up globals.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    private function setup() {

        // Main plugin directory path and URI.
        $this->dir_path     = trailingslashit( plugin_dir_path( __FILE__ ) );
        $this->dir_uri      = trailingslashit( plugin_dir_url( __FILE__ ) );

        // Plugin directory paths.
        $this->includes_path      = trailingslashit( $this->dir_path . 'includes' );
        $this->admin_path         = trailingslashit( $this->includes_path . 'admin' );
        $this->home_path          = trailingslashit( $this->includes_path . 'home' );
        $this->remote_path        = trailingslashit( $this->includes_path . 'remote' );
        $this->assets_path        = trailingslashit( $this->includes_path . 'assets' );

        // Plugin directory URIs.
        $this->includes_uri = trailingslashit( $this->dir_uri . 'includes' );
        $this->assets_uri   = trailingslashit( $this->dir_uri . 'includes/assets' );
        $this->public_uri   = trailingslashit( $this->dir_uri . 'public' );
        $this->js_uri       = trailingslashit( $this->assets_uri . 'js' );
        $this->css_uri      = trailingslashit( $this->assets_uri . 'css' );

        // Admin and settings variables
        $this->version             = '3.0';

        // LOAD FILES

        require_once( 'includes/endpoints.php' );

        // Not Disciple Tools : remote support files
        if ( ! is_dt() ) {
            require_once( 'dt-mapping/geocode-api/mapbox-api.php' );
            require_once( 'includes/site-link-post-type.php' );
            Site_Link_System::instance();

            add_action( 'init', [ $this, 'dt_set_permalink_structure' ] );
            add_action( 'update_option_permalink_structure', [ $this, 'dt_permalink_structure_changed_callback' ] );
        }

        // REST support files
        $is_rest = dt_is_rest();
        if ( $is_rest || is_admin() ) {
            require_once( 'includes/utilities.php' );
            require_once( 'includes/post-type-active-forms.php' );
        }

        // Admin area
        if ( is_admin() ) {
            // Admin and tabs menu
            require_once( 'includes/tables.php' );
            require_once( 'includes/menu-and-tabs.php' ); // main wp-admin menu and ui
        }
    }

    /**
     * Sets up main plugin actions and filters.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    private function setup_actions() {

        // Check for plugin updates system
        if ( ! class_exists( 'Puc_v4_Factory' ) ) {
            require( $this->admin_path . 'libraries/plugin-update-checker/plugin-update-checker.php' );
        }
        Puc_v4_Factory::buildUpdateChecker(
            'https://raw.githubusercontent.com/DiscipleTools/disciple-tools-version-control/master/disciple-tools-webform-version-control.json',
            __FILE__,
            'disciple-tools-webform'
        );
        // End check for updates system

        // Internationalize the text strings used.
        add_action( 'plugins_loaded', array( $this, 'i18n' ), 2 );
    }

    /**
     * Method that runs only when the plugin is activated.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public static function activation() {

        // Confirm 'Administrator' has 'manage_dt' privilege. This is key in 'remote' configuration when Disciple Tools theme is not installed.
        $role = get_role( 'administrator' );
        if ( !empty( $role ) ) {
            $role->add_cap( 'manage_dt' ); // gives access to dt plugin options
        }

    }

    /**
     * Method that runs only when the plugin is deactivated.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public static function deactivation() {
        delete_option( 'dt_webform_options' );
        delete_option( 'dt_webform_state' );
        delete_option( 'external_updates-disciple-tools-webform' );
    }

    public function dt_warn_user_about_permalink_settings() {
        ?>
        <div class="error notices">
            <p>You may only set your permalink settings to "Post name"'</p>
        </div>
        <?php
    }

    /**
     * Notification that 'posttype' is the only permalink structure available.
     *
     * @param $permalink_structure
     */
    public function dt_permalink_structure_changed_callback( $permalink_structure ) {
        global $wp_rewrite;
        if ( $permalink_structure !== '/%postname%/' ) {
            add_action( 'admin_notices', [ $this, 'dt_warn_user_about_permalink_settings' ] );
        }
    }

    public function dt_set_permalink_structure() {
        global $wp_rewrite;
        $wp_rewrite->set_permalink_structure( '/%postname%/' );
        flush_rewrite_rules();
    }

    /**
     * Constructor method.
     *
     * @since  0.1
     * @access private
     * @return void
     */
    private function __construct() {
    }

    /**
     * Loads the translation files.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function i18n() {
        load_plugin_textdomain( 'dt_webform', false, trailingslashit( dirname( plugin_basename( __FILE__ ) ) ). 'languages' );
    }

    /**
     * Magic method to output a string if trying to use the object as a string.
     *
     * @since  0.1
     * @access public
     * @return string
     */
    public function __toString() {
        return 'dt_webform';
    }

    /**
     * Magic method to keep the object from being cloned.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, esc_html__( 'Whoah, partner!', 'dt_webform' ), '0.1' );
    }

    /**
     * Magic method to keep the object from being unserialized.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, esc_html__( 'Whoah, partner!', 'dt_webform' ), '0.1' );
    }

    /**
     * Magic method to prevent a fatal error when calling a method that doesn't exist.
     *
     * @since  0.1
     * @access public
     * @return null
     */
    public function __call( $method = '', $args = array() ) {
        // @codingStandardsIgnoreLine
        _doing_it_wrong( "dt_webform::{$method}", esc_html__( 'Method does not exist.', 'dt_webform' ), '0.1' );
        unset( $method, $args );
        return null;
    }

    /**
     * @return string
     */
    public static function get_real_ip_address() {
        $ip = '';
        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ))   //check ip from share internet
        {
            // @codingStandardsIgnoreLine
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ))   //to check ip is pass from proxy
        {
            // @codingStandardsIgnoreLine
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) )
        {
            // @codingStandardsIgnoreLine
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
}
// End of main class

// Activation and De-activation Hooks
register_activation_hook( __FILE__, [ 'DT_Webform', 'activation' ] );
register_deactivation_hook( __FILE__, [ 'DT_Webform', 'deactivation' ] );


/* CORE FUNCTIONS */
if ( ! function_exists( 'is_dt' ) ) {
    function is_dt(): bool
    {
        $wp_theme = wp_get_theme();

        // child theme check
        if ( get_template_directory() !== get_stylesheet_directory() ) {
            if ( 'disciple-tools-theme' == $wp_theme->get( 'Template' ) ) {
                return true;
            }
        }

        // main theme check
        $is_theme_dt = strpos( $wp_theme->get_template(), "disciple-tools-theme" ) !== false || $wp_theme->name === "Disciple Tools";
        if ($is_theme_dt) {
            return true;
        }

        return false;
    }
}

if ( ! function_exists( 'dt_is_child_theme_of_disciple_tools' ) ) {
    /**
     * Returns true if this is a child theme of Disciple Tools, and false if it is not.
     *
     * @return bool
     */
    function dt_is_child_theme_of_disciple_tools() : bool {
        if ( get_template_directory() !== get_stylesheet_directory() ) {
            $current_theme = wp_get_theme();
            if ( 'disciple-tools-theme' == $current_theme->get( 'Template' ) ) {
                return true;
            }
        }
        return false;
    }
}

/**
 * A simple function to assist with development and non-disruptive debugging.
 * -----------
 * -----------
 * REQUIREMENT:
 * WP Debug logging must be set to true in the wp-config.php file.
 * Add these definitions above the "That's all, stop editing! Happy blogging." line in wp-config.php
 * -----------
 * define( 'WP_DEBUG', true ); // Enable WP_DEBUG mode
 * define( 'WP_DEBUG_LOG', true ); // Enable Debug logging to the /wp-content/debug.log file
 * define( 'WP_DEBUG_DISPLAY', false ); // Disable display of errors and warnings
 * @ini_set( 'display_errors', 0 );
 * -----------
 * -----------
 * EXAMPLE USAGE:
 * (string)
 * write_log('THIS IS THE START OF MY CUSTOM DEBUG');
 * -----------
 * (array)
 * $an_array_of_things = ['an', 'array', 'of', 'things'];
 * write_log($an_array_of_things);
 * -----------
 * (object)
 * $an_object = new An_Object
 * write_log($an_object);
 */
if ( !function_exists( 'dt_write_log' ) ) {
    /**
     * A function to assist development only.
     * This function allows you to post a string, array, or object to the WP_DEBUG log.
     *
     * @param $log
     */
    function dt_write_log( $log ) {
        if ( true === WP_DEBUG ) {
            global $dt_write_log_microtime;
            $now = microtime( true );
            if ( $dt_write_log_microtime > 0 ) {
                $elapsed_log = sprintf( "[elapsed:%5dms]", ( $now - $dt_write_log_microtime ) * 1000 );
            } else {
                $elapsed_log = "[elapsed:-------]";
            }
            $dt_write_log_microtime = $now;
            if ( is_array( $log ) || is_object( $log ) ) {
                error_log( $elapsed_log . " " . print_r( $log, true ) );
            } else {
                error_log( "$elapsed_log $log" );
            }
        }
    }
}


if ( !function_exists( 'dt_is_rest' ) ) {
    /**
     * Checks if the current request is a WP REST API request.
     *
     * Case #1: After WP_REST_Request initialisation
     * Case #2: Support "plain" permalink settings
     * Case #3: URL Path begins with wp-json/ (your REST prefix)
     *          Also supports WP installations in subfolders
     *
     * @returns boolean
     * @author matzeeable
     */
    function dt_is_rest( $namespace = null ) {
        $prefix = rest_get_url_prefix();
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST
            || isset( $_GET['rest_route'] )
            && strpos( trim( sanitize_text_field( wp_unslash( $_GET['rest_route'] ) ), '\\/' ), $prefix, 0 ) === 0 ) {
            return true;
        }
        $rest_url    = wp_parse_url( site_url( $prefix ) );
        $current_url = wp_parse_url( add_query_arg( array() ) );
        $is_rest = strpos( $current_url['path'], $rest_url['path'], 0 ) === 0;
        if ( $namespace ){
            return $is_rest && strpos( $current_url['path'], $namespace ) != false;
        } else {
            return $is_rest;
        }
    }
}

/**
 * Admin alert for when Disciple Tools Theme is not available
 */
function dt_webform_no_disciple_tools_theme_found() {
    ?>
    <div class="notice notice-error is-dismissible">
        <p><?php esc_html_e( "'Disciple Tools - Webform' plugin requires 'Disciple Tools' theme to work. Please activate 'Disciple Tools' theme or deactivate 'Disciple Tools - Webform' plugin.", "dt_webform" ); ?></p>
    </div>
    <?php
}

if ( ! function_exists( 'dt_sanitize_array' ) ) {
    function dt_sanitize_array( &$array ) {
        foreach ($array as &$value) {
            if ( !is_array( $value ) ) {
                $value = sanitize_text_field( wp_unslash( $value ) );
            } else {
                dt_sanitize_array( $value );
            }
        }
        return $array;
    }
}

/**
 * This returns a simple array versus the multi dimensional array
 *
 * @return array
 */
if ( ! function_exists( 'dt_get_simple_post_meta' ) ) {
    function dt_get_simple_post_meta( $post_id ) {

        $map = wp_cache_get( __METHOD__, $post_id );
        if ( $map ) {
            return $map;
        }

        $map = [];
        if ( ! empty( $post_id ) ) {
            $map         = array_map( function( $a ) {
                return maybe_unserialize( $a[0] );
            }, get_post_meta( $post_id ) ); // map the post meta
            $map['ID'] = $post_id; // add the id to the array
        }

        wp_cache_set( __METHOD__, $map, $post_id );

        return $map;
    }
}

if ( ! function_exists( 'dt_get_location_grid_mirror' ) ) {
    /**
     * Best way to call for the mapping polygon
     * @return array|string
     */
    function dt_get_location_grid_mirror( $url_only = false ) {

        $mirror = wp_cache_get( __METHOD__, $url_only );
        if ( $mirror ) {
            return $url_only ? $mirror["url"] : $mirror;
        }

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

        wp_cache_set( __METHOD__, $mirror, $url_only );

        if ( $url_only ) {
            return $mirror['url'];
        }

        return $mirror;
    }
}

if ( ! function_exists( 'dt_get_mapbox_endpoint' ) ) {
    function dt_get_mapbox_endpoint( $type = 'places' ) : string {
        switch ( $type ) {
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

if ( ! function_exists( 'dt_get_webform_site_link' ) ) {
    function dt_get_webform_site_link() {
        return get_option( 'dt_webform_site_link' );
    }
}

if ( ! function_exists( 'dt_has_permissions' ) ) {
    function dt_has_permissions( array $permissions ) : bool {
        if ( count( $permissions ) > 0 ) {
            foreach ( $permissions as $permission ){
                if ( current_user_can( $permission ) ){
                    return true;
                }
            }
        }
        return false;
    }
}

if ( ! function_exists( 'dt_get_url_path' ) ) {
    function dt_get_url_path() {
        if ( isset( $_SERVER["HTTP_HOST"] ) ) {
            $url  = ( !isset( $_SERVER["HTTPS"] ) || @( $_SERVER["HTTPS"] != 'on' ) ) ? 'http://'. sanitize_text_field( wp_unslash( $_SERVER["HTTP_HOST"] ) ) : 'https://'. sanitize_text_field( wp_unslash( $_SERVER["HTTP_HOST"] ) );
            if ( isset( $_SERVER["REQUEST_URI"] ) ) {
                $url .= sanitize_text_field( wp_unslash( $_SERVER["REQUEST_URI"] ) );
            }
            return trim( str_replace( get_site_url(), "", $url ), '/' );
        }
        return '';
    }
}
