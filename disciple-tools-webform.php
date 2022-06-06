<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly
/**
 *Plugin Name: Disciple.Tools - Webform
 * Plugin URI: https://github.com/DiscipleTools/disciple-tools-webform
 * Description: Disciple.Tools - Webform extends the Disciple.Tools system to send and receive remote submissions from webform contacts.
 * Version:  5.5
 * Text Domain: disciple-tools-webform
 * Domain Path: /languages
 * Author name: Disciple.Tools
 * Author URI: https://github.com/DiscipleTools
 * GitHub Plugin URI: https://github.com/DiscipleTools/disciple-tools-webform
 * Requires at least: 4.7.0
 * (Requires 4.7+ because of the integration of the REST API at 4.7 and the security requirements of this milestone version.)
 * Tested up to: 5.6
 *
 * @package Disciple_Tools
 * @link    https://github.com/DiscipleTools
 * @license GPL-2.0 or later
 *          https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @version 4.5 Bug fixes
 * @version 4.5.1 Minor updated to allow html in descriptions and headers.
 */
$dt_webform_required_dt_theme_version = '1.0';

require_once( 'includes/functions.php' );
require_once( 'includes/shortcodes.php' );

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

    if ( is_this_dt() ) {
        $wp_theme = wp_get_theme();
        $version = $wp_theme->version;
        if ( $version < $dt_webform_required_dt_theme_version ) {
            if ( ! is_multisite() ) {
                add_action( 'admin_notices', 'dt_webform_no_disciple_tools_theme_found' );
                add_action( 'wp_ajax_dismissed_notice_handler', 'dt_hook_ajax_notice_handler' );
            }
            return new WP_Error( 'current_theme_not_dt', 'Please upgrade Disciple.Tools Theme to ' . $dt_webform_required_dt_theme_version . ' to use this plugin.' );
        }
    }

    $is_rest = dt_is_rest();
    if ( $is_rest && ( strpos( dt_get_url_path(), 'webform' ) !== false || strpos( dt_get_url_path(), 'site_link_check' ) !== false ) ){
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
        $this->version             = '4.0';

        // LOAD FILES

        require_once( 'includes/create-contact.php' );

        // Not Disciple.Tools : remote support files
        if ( ! is_this_dt() ) {
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
        // Internationalize the text strings used.
        add_action( 'plugins_loaded', array( $this, 'i18n' ), 2 );

        if ( is_admin() ) {
            // adds links to the plugin description area in the plugin admin list.
            add_filter( 'plugin_row_meta', [ $this, 'plugin_description_links' ], 10, 4 );
        }
    }

    /**
     * Filters the array of row meta for each/specific plugin in the Plugins list table.
     * Appends additional links below each/specific plugin on the plugins page.
     *
     * @access  public
     * @param   array       $links_array            An array of the plugin's metadata
     * @param   string      $plugin_file_name       Path to the plugin file
     * @param   array       $plugin_data            An array of plugin data
     * @param   string      $status                 Status of the plugin
     * @return  array       $links_array
     */
    public function plugin_description_links( $links_array, $plugin_file_name, $plugin_data, $status ) {
        if ( strpos( $plugin_file_name, basename( __FILE__ ) ) ) {
            // You can still use `array_unshift()` to add links at the beginning.

            $links_array[] = '<a href="https://github.com/DiscipleTools/disciple-tools-webform">Github</a>';
            $links_array[] = '<a href="https://www.youtube.com/watch?v=lBOwgrOSUkU">Video Tutorial</a>';

            // add other links here
        }

        return $links_array;
    }

    /**
     * Method that runs only when the plugin is activated.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public static function activation() {

        // Confirm 'Administrator' has 'manage_dt' privilege. This is key in 'remote' configuration when Disciple.Tools theme is not installed.
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
        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) )   //check ip from share internet
        {
            // @codingStandardsIgnoreLine
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) )   //to check ip is pass from proxy
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


/**
 * Check for plugin updates even when the active theme is not Disciple.Tools
 *
 * Below is the publicly hosted .json file that carries the version information. This file can be hosted
 * anywhere as long as it is publicly accessible. You can download the version file listed below and use it as
 * a template.
 * Also, see the instructions for version updating to understand the steps involved.
 * @see https://github.com/DiscipleTools/disciple-tools-version-control/wiki/How-to-Update-the-Starter-Plugin
 */
add_action( 'plugins_loaded', function (){
    if ( is_admin() && !( is_multisite() && class_exists( "DT_Multisite" ) ) || wp_doing_cron() ){
        // Check for plugin updates
        if ( ! class_exists( 'Puc_v4_Factory' ) ) {
            $dir_path           = trailingslashit( plugin_dir_path( __FILE__ ) );
            $includes_path      = trailingslashit( $dir_path . 'includes' );
            $admin_path         = trailingslashit( $includes_path . 'admin' );
            if ( file_exists( $admin_path . 'libraries/plugin-update-checker/plugin-update-checker.php' ) ){
                require( $admin_path . 'libraries/plugin-update-checker/plugin-update-checker.php' );
            }
        }
        if ( class_exists( 'Puc_v4_Factory' ) ){
            Puc_v4_Factory::buildUpdateChecker(
                'https://raw.githubusercontent.com/DiscipleTools/disciple-tools-webform/master/version-control.json',
                __FILE__,
                'disciple-tools-webform'
            );

        }
    }
} );
