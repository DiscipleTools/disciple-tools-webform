<?php
/**
 * Plugin Name: Disciple Tools - Webform
 * Plugin URI: https://github.com/DiscipleTools/disciple-tools
 * Description: Disciple Tools - Webform extends the Disciple Tools system to send and receive remote submissions from webform contacts.
 * Version:  0.1.0
 * Author URI: https://github.com/DiscipleTools
 * GitHub Plugin URI: https://github.com/DiscipleTools/disciple-tools-webform
 * Requires at least: 4.7.0
 * (Requires 4.7+ because of the integration of the REST API at 4.7 and the security requirements of this milestone version.)
 * Tested up to: 4.9
 *
 * @package Disciple_Tools
 * @link    https://github.com/DiscipleTools
 * @license GPL-2.0 or later
 *          https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Gets the instance of the `DT_Webform` class.  This function is useful for quickly grabbing data
 * used throughout the plugin.
 *
 * @since  0.1
 * @access public
 * @return object
 */
function dt_webform() {
    $current_theme = get_option( 'current_theme' );
    $state = get_option( 'dt_webform_state' );

    if ( ( 'combined' == $state || 'home' == $state ) && ! 'Disciple Tools' == $current_theme ) {
        add_action( 'admin_notices', 'dt_webform_no_disciple_tools_theme_found' );
        return new WP_Error( 'current_theme_not_dt', 'Disciple Tools Theme not active.' );
    }

    return DT_Webform::get_instance();

}
add_action( 'plugins_loaded', 'dt_webform' );


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

            /**
             * Determine state of the plugin
             *
             * The state of the plugin can be 'combined', 'home', and 'remote'.
             * 'Home' enables just the resources needed for the home server of the webform plugin
             * 'Remote' enables just the resources needed for the remote webform server of the webform plugin
             * 'Combined' enables both the 'home' and 'remote' servers within the plugin.
             */
            $state = get_option( 'dt_webform_state' );
            switch ( $state ) {
                case 'combined':
                    $instance->includes();
                    $instance->home();
                    $instance->remote();
                    break;
                case 'home':
                    $instance->includes();
                    $instance->home();
                    break;
                case 'remote':
                    $instance->includes();
                    $instance->remote();
                    break;
                default: // if no option exists, then the plugin is forced to selection screen.
                    $instance->initialize_plugin_state();
                    break;
            }

            $instance->setup_actions();
        }
        return $instance;
    }

    /**
     * State initialization of the plugin
     *
     * @access private
     * @return void
     */
    private function initialize_plugin_state() {

        if ( is_admin() ) {
            // Admin and tabs menu
            require_once( 'includes/admin/admin-menu-and-tabs.php' );
        }
    }

    /**
     * File dependencies exlusively for the HOME SERVER
     *
     * @access private
     * @return void
     */
    private function home() {

        require_once( 'includes/home/home-endpoints.php' );
        require_once( 'includes/home/home.php' );

    }

    /**
     * File dependencies exlusively for the REMOTE SERVER
     *
     * @access private
     * @return void
     */
    private function remote() {

        require_once( 'includes/remote/remote-endpoints.php' );
        require_once( 'includes/remote/remote.php' );

    }

    /**
     * File dependencies shared by the entire Webform system
     *
     * @since  0.1
     * @access public
     * @return void
     */
    private function includes() {

        require_once( 'includes/admin/api-keys.php' ); // api key service
        require_once( 'includes/admin/admin.php' );
        require_once( 'assets/enqueue-scripts.php' ); // enqueue scripts and styles
        require_once( 'includes/remote/active-forms-post-type.php' );
        require_once( 'includes/remote/new-leads-post-type.php' ); // post type for the new leads post type
        require_once( 'includes/admin/tables.php' );

        // @todo evaluate what needs to be in the is_admin. Issue is how much is needed to be available for the public REST API and CRON sync and UI interactions.
        if ( is_admin() ) {
            // Admin and tabs menu
            require_once( 'includes/admin/admin-menu-and-tabs.php' ); // main wp-admin menu and ui
        }
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
        $this->assets_path        = trailingslashit( $this->dir_path . 'assets' );

        // Plugin directory URIs.
        $this->assets_uri   = trailingslashit( $this->dir_uri . 'assets' );
        $this->public_uri   = trailingslashit( $this->dir_uri . 'public' );
        $this->img_uri      = trailingslashit( $this->assets_uri . 'img' );
        $this->js_uri       = trailingslashit( $this->assets_uri . 'js' );
        $this->css_uri      = trailingslashit( $this->assets_uri . 'css' );

        // Admin and settings variables
        $this->version             = '0.1.0';
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
            require( $this->includes_path . 'admin/libraries/plugin-update-checker/plugin-update-checker.php' );
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
}
// End of main class

// Activation and De-activation Hooks
register_activation_hook( __FILE__, [ 'DT_Webform', 'activation' ] );
register_deactivation_hook( __FILE__, [ 'DT_Webform', 'deactivation' ] );

/**
 * Admin alert for when Disciple Tools Theme is not available
 */
function dt_webform_no_disciple_tools_theme_found()
{
    ?>
    <div class="notice notice-error">
        <p><?php esc_html_e( "'Disciple Tools - Webform' plugin requires 'Disciple Tools' theme to work. Please activate 'Disciple Tools' theme or deactivate 'Disciple Tools - Webform' plugin.", "dt_webform" ); ?></p>
    </div>
    <?php
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
    function dt_write_log( $log )
    {
        if ( true === WP_DEBUG ) {
            if ( is_array( $log ) || is_object( $log ) ) {
                error_log( print_r( $log, true ) );
            } else {
                error_log( $log );
            }
        }
    }
}