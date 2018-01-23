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
    if ( 'Disciple Tools' == $current_theme ) {
        return DT_Webform::get_instance();
    }
    else {
        add_action( 'admin_notices', 'dt_webform_no_disciple_tools_theme_found' );
        return new WP_Error( 'current_theme_not_dt', 'Disciple Tools Theme not active.' );
    }

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
    public $token;
    public $version;
    public $dir_path = '';
    public $dir_uri = '';
    public $img_uri = '';
    public $includes_path;

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

                    break;
                case 'home':

                    break;
                case 'remote':

                    break;
                default: // if no option exists, then the plugin is forced to selection screen.
                    $instance->choose_state();
                    break;
            }
        }
        return $instance;
    }

    /**
     * State initialization of the plugin
     *
     * @access private
     * @return void
     */
    private function choose_state() {
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

        // HOME
        require_once( 'includes/home/rest-endpoints.php' );

        if ( is_admin() ) {
            // Admin and tabs menu
            require_once( 'includes/admin/admin-menu-and-tabs.php' );
        }
    }

    /**
     * File dependencies exlusively for the REMOTE SERVER
     *
     * @access private
     * @return void
     */
    private function remote() {

        // REMOTE
        require_once( 'includes/remote/rest-endpoints.php' );

        if ( is_admin() ) {
            // Admin and tabs menu
            require_once( 'includes/admin/admin-menu-and-tabs.php' );
        }
    }

    /**
     * File dependencies shared by the entire Webform system
     *
     * @since  0.1
     * @access public
     * @return void
     */
    private function includes() {

        if ( is_admin() ) {
            // Admin and tabs menu
            require_once( 'includes/admin/admin-menu-and-tabs.php' );
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

        // Plugin directory URIs.
        $this->img_uri      = trailingslashit( $this->dir_uri . 'img' );

        // Admin and settings variables
        $this->token             = 'dt_webform';
        $this->version             = '0.1';
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

        // Register activation hook.
        register_activation_hook( __FILE__, [ $this, 'activation' ] );
        register_deactivation_hook( __FILE__, [ $this, 'deactivation' ] );
    }

    /**
     * Method that runs only when the plugin is activated.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function activation() {
    }

    /**
     * Method that runs only when the plugin is deactivated.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function deactivation() {
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