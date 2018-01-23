<?php
/**
 * DT_Webform_Menu class for the admin page
 *
 * @class       DT_Webform_Menu
 * @version     0.1.0
 * @since       0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}

/**
 * Class DT_Webform_Menu
 */
class DT_Webform_Menu {

    public $token = 'dt_webform';

    private static $_instance = null;

    /**
     * DT_Webform_Menu Instance
     *
     * Ensures only one instance of DT_Webform_Menu is loaded or can be loaded.
     *
     * @since 0.1.0
     * @static
     * @return DT_Webform_Menu instance
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()


    /**
     * Constructor function.
     * @access  portal
     * @since   0.1.0
     */
    public function __construct() {

        add_action( "admin_menu", array( $this, "register_menu" ) );

    } // End __construct()


    /**
     * Loads the subnav page
     * @since 0.1
     */
    public function register_menu() {

        // Process state change form
        if ( ( isset( $_POST['initialize_plugin_state'] ) && ! empty( $_POST['initialize_plugin_state'] ) ) && ( isset( $_POST['dt_webform_select_state_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dt_webform_select_state_nonce'] ) ), 'dt_webform_select_state' ) ) ) {
            update_option( 'dt_webform_state', sanitize_key( wp_unslash( $_POST['initialize_plugin_state'] ) ), false );
        }

        // Check for Disciple Tools Theme. If not, then set plugin to 'remote'
        $current_theme = get_option( 'current_theme' );
        if ( ! 'Disciple Tools' == $current_theme ) {
            update_option( 'dt_webform_state', 'remote', false );
            add_menu_page( __( 'Webform (DT)', 'disciple_tools' ), __( 'Webform (DT)', 'disciple_tools' ), 'manage_dt', $this->token, [ $this, 'remote' ], 'dashicons-admin-generic', 59 );
        } else {
            // Load menus
            add_menu_page( __( 'Extensions (DT)', 'disciple_tools' ), __( 'Extensions (DT)', 'disciple_tools' ), 'manage_dt', 'dt_extensions', [ $this, 'extensions_menu' ], 'dashicons-admin-generic', 59 );

            $state = get_option( 'dt_webform_state' );
            switch ( $state ) {
                case 'combined':
                    add_submenu_page( 'dt_extensions', __( 'Webform', 'dt_webform' ), __( 'Webform', 'dt_webform' ), 'manage_dt', $this->token, [ $this, 'combined' ] );
                    break;
                case 'home':
                    add_submenu_page( 'dt_extensions', __( 'Webform', 'dt_webform' ), __( 'Webform', 'dt_webform' ), 'manage_dt', $this->token, [ $this, 'home' ] );
                    break;
                default: // if no option exists, then the plugin is forced to selection screen.
                    add_submenu_page( 'dt_extensions', __( 'Webform', 'dt_webform' ), __( 'Webform', 'dt_webform' ), 'manage_dt', $this->token, [ $this, 'initialize_plugin_state' ] );
                    break;
            }
        }



    }

    /**
     * Menu stub. Replaced when Disciple Tools Theme fully loads.
     */
    public function extensions_menu() {
    }

    /**
     * Combined state of the plugin
     */
    public function combined() {

        if ( !current_user_can( 'manage_dt' ) ) {
            wp_die( esc_attr__( 'You do not have sufficient permissions to access this page.' ) );
        }

        if ( isset( $_GET["tab"] ) ) {
            $tab = sanitize_key( wp_unslash( $_GET["tab"] ) );
        } else {
            $tab = 'general';
        }

        $link = 'admin.php?page='.$this->token.'&tab=';

        ?>
        <div class="wrap">
            <h2><?php esc_attr_e( 'DISCIPLE TOOLS - WEBFORM (COMBINED)', 'dt_webform' ) ?></h2>
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_attr( $link ) . 'general' ?>" class="nav-tab <?php ( $tab == 'general' || ! isset( $tab ) ) ? esc_attr_e( 'nav-tab-active', 'dt_webform' ) : print ''; ?>"><?php esc_attr_e( 'General', 'dt_webform' ) ?></a>
                <a href="<?php echo esc_attr( $link ) . 'settings' ?>" class="nav-tab <?php ( $tab == 'settings' ) ? esc_attr_e( 'nav-tab-active', 'dt_webform' ) : print ''; ?>"><?php esc_attr_e( 'Settings', 'dt_webform' ) ?></a>
            </h2>

            <?php
            switch ($tab) {
                case "general":
                    $object = new DT_Webform_Tab_General();
                    $object->content();
                    break;
                case "settings":
                    $object = new DT_Webform_Tab_Settings();
                    $object->content();
                    break;
                default:
                    break;
            }
            ?>

        </div><!-- End wrap -->

        <?php

    }

    public function home() {

        if ( !current_user_can( 'manage_dt' ) ) {
            wp_die( esc_attr__( 'You do not have sufficient permissions to access this page.' ) );
        }

        if ( isset( $_GET["tab"] ) ) {
            $tab = sanitize_key( wp_unslash( $_GET["tab"] ) );
        } else {
            $tab = 'general';
        }

        $link = 'admin.php?page='.$this->token.'&tab=';

        ?>
        <div class="wrap">
            <h2><?php esc_attr_e( 'DISCIPLE TOOLS - WEBFORM (HOME)', 'dt_webform' ) ?></h2>
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_attr( $link ) . 'general' ?>" class="nav-tab <?php ( $tab == 'general' || ! isset( $tab ) ) ? esc_attr_e( 'nav-tab-active', 'dt_webform' ) : print ''; ?>"><?php esc_attr_e( 'General', 'dt_webform' ) ?></a>
                <a href="<?php echo esc_attr( $link ) . 'settings' ?>" class="nav-tab <?php ( $tab == 'settings' ) ? esc_attr_e( 'nav-tab-active', 'dt_webform' ) : print ''; ?>"><?php esc_attr_e( 'Settings', 'dt_webform' ) ?></a>
            </h2>

            <?php
            switch ($tab) {
                case "general":
                    $object = new DT_Webform_Tab_General();
                    $object->content();
                    break;
                case "settings":
                    $object = new DT_Webform_Tab_Settings();
                    $object->content();
                    break;
                default:
                    break;
            }
            ?>

        </div><!-- End wrap -->

        <?php

    }

    public function remote() {

        if ( !current_user_can( 'manage_dt' ) ) {
            wp_die( esc_attr__( 'You do not have sufficient permissions to access this page.' ) );
        }

        if ( isset( $_GET["tab"] ) ) {
            $tab = sanitize_key( wp_unslash( $_GET["tab"] ) );
        } else {
            $tab = 'general';
        }

        $link = 'admin.php?page='.$this->token.'&tab=';

        ?>
        <div class="wrap">
            <h2><?php esc_attr_e( 'DISCIPLE TOOLS - WEBFORM (REMOTE)', 'dt_webform' ) ?></h2>
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_attr( $link ) . 'general' ?>" class="nav-tab <?php ( $tab == 'general' || ! isset( $tab ) ) ? esc_attr_e( 'nav-tab-active', 'dt_webform' ) : print ''; ?>"><?php esc_attr_e( 'General', 'dt_webform' ) ?></a>
                <a href="<?php echo esc_attr( $link ) . 'settings' ?>" class="nav-tab <?php ( $tab == 'settings' ) ? esc_attr_e( 'nav-tab-active', 'dt_webform' ) : print ''; ?>"><?php esc_attr_e( 'Settings', 'dt_webform' ) ?></a>
            </h2>

            <?php
            switch ($tab) {
                case "general":
                    $object = new DT_Webform_Tab_General();
                    $object->content();
                    break;
                case "settings":
                    $object = new DT_Webform_Tab_Settings();
                    $object->content();
                    break;
                default:
                    break;
            }
            ?>

        </div><!-- End wrap -->

        <?php
    }

    public function initialize_plugin_state() {

        if ( !current_user_can( 'manage_dt' ) ) {
            wp_die( esc_attr__( 'You do not have sufficient permissions to access this page.' ) );
        }

        ?>
        <div class="wrap">
           <h2><?php esc_attr_e( 'DISCIPLE TOOLS - WEBFORM', 'dt_webform' ) ?></h2>

            <?php DT_Webform_Page_Template::template( 'begin' ) ?>

                <?php self::initialize_plugin_state_form_select() ?>

            <?php DT_Webform_Page_Template::template( 'right_column' ) ?>

            <?php DT_Webform_Page_Template::template( 'end' ) ?>

        </div>
        <?php
    }

    /**
     * Re-usable form to edit state of the plugin.
     */
    public static function initialize_plugin_state_form_select() {
        // Set selections
        $options = [
            [
                'key' => 'combined',
                'label' => __( 'Combined', 'dt_webform' ),
            ],
            [
                'key' => 'home',
                'label' => __( 'Home', 'dt_webform' ),
            ]
        ];

        // Check if Disciple Tools Theme is present. If not, limit select to remote server.
        $current_theme = get_option( 'current_theme' );
        if ( ! 'Disciple Tools' == $current_theme ) {
            $options = [
                [
                'key' => 'remote',
                'label' => __( 'Remote', 'dt_webform' ),
                ]
            ];
        }

        // Get current selection
        $state = get_option( 'dt_webform_state' );
        ?>
        <style>
            button.button-like-link {
                background: none !important;
                color: inherit;
                border: none;
                padding: 0 !important;
                font: inherit;
                /*border is optional*/
                cursor: pointer;
            }
        </style>
        <form method="post" action="">
            <?php wp_nonce_field( 'dt_webform_select_state', 'dt_webform_select_state_nonce', true, true ) ?>
            <!-- Box -->
            <table class="widefat striped">
                <thead>
                <th>Plugin State</th>
                </thead>
                <tbody>
                <tr>
                    <td>
                        <label for="initialize_plugin_state">Configure the state of the plugin: </label><br>
                        <select name="initialize_plugin_state" id="initialize_plugin_state">
                            <option value="">Select</option>
                            <option value="" disabled>---</option>
                            <?php
                            foreach ($options as $option) {
                                echo '<option value="'.esc_attr( $option['key'] ).'" ';
                                if ( $option['key'] == $state ) {
                                    echo 'selected';
                                }
                                echo '>'. esc_attr( $option['label'] ).'</option>';
                            }
                            ?>
                        </select>
                        <span><button class="button-like-link" type="button" onclick="jQuery('#state-help').toggle();">explain settings</button></span>
                    </td>
                </tr>
                <tr id="state-help" style="display: none;">
                    <td>
                        Three different configurations:
                        <p>
                            <strong>Home</strong><br>
                            Home configuration sets up just the half of the plugin that integrates with Disciple Tools. Choosing this option assumes that you have a remote server running separatedly with the 'remote' setting on the plugin configured.
                        </p>
                        <p>
                            <strong>Remote</strong><br>
                            The 'Remote' configuration sets up only the remote webform server. Choosing this option assumes that you have a Disciple Tools server running elsewhere with the Webform plugin installed and configured as 'home'. If Disciple Tools Theme is not installed, Remote will be the only installation option.
                        </p>
                        <p>
                            <strong>Combined</strong><br>
                            The 'Combined' configuration sets up the Webform plugin to run the webform server from the same system as the Disciple Tools System. Choosing this option assumes that you have a remote server running separatedly with the 'remote' setting on the plugin configured.
                        </p>

                    </td>
                </tr>
                <tr>
                    <td>
                        <button class="button" type="submit">Update</button>
                    </td>
                </tr>
                </tbody>
            </table>
            <br>
            <!-- End Box -->
        </form>
        <?php
    }
}
DT_Webform_Menu::instance();

/**
 * Class DT_Webform_Page_Template
 */
class DT_Webform_Page_Template
{
    /**
     * @param $section
     */
    public static function template( $section ) {
        switch ( $section ) {
            case 'begin':
                ?>
                <div class="wrap">
                <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                <!-- Main Column -->
                <?php
                break;
            case 'right_column':
                ?>
                <!-- End Main Column -->
                </div><!-- end post-body-content -->
                <div id="postbox-container-1" class="postbox-container">
                <!-- Right Column -->
                <?php
                break;
            case 'end':
                ?>
                </div><!-- postbox-container 1 -->
                </div><!-- post-body meta box container -->
                </div><!--poststuff end -->
                </div><!-- wrap end -->
                <?php
                break;
        }
    }
}

/**
 * Class DT_Webform_Tab_General
 */
class DT_Webform_Tab_General
{
    public function content() {

        // begin columns template
        DT_Webform_Page_Template::template( 'begin' );

            $this->main_column(); // main column content

        // begin right column template
        DT_Webform_Page_Template::template( 'right_column' );

            $this->right_column(); // right column content

        // end columns template
        DT_Webform_Page_Template::template( 'end' );

    }

    public function main_column() {
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <th>Header</th>
            </thead>
            <tbody>
            <tr>
                <td>
                    Content
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }

    public function right_column() {
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <th>Information</th>
            </thead>
            <tbody>
            <tr>
                <td>
                    Content
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }

}

/**
 * Class DT_Webform_Tab_Settings
 */
class DT_Webform_Tab_Settings
{
    public function content() {

        // begin columns template
        DT_Webform_Page_Template::template( 'begin' );

        $this->main_column(); // main column content

        // begin right column template
        DT_Webform_Page_Template::template( 'right_column' );

        DT_Webform_Menu::initialize_plugin_state_form_select();

        $this->right_column(); // right column content

        // end columns template
        DT_Webform_Page_Template::template( 'end' );

    }

    public function main_column() {
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <th>Header</th>
            </thead>
            <tbody>
            <tr>
                <td>
                    Content
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }

    public function right_column() {
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <th>Information</th>
            </thead>
            <tbody>
            <tr>
                <td>
                    Content
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }

}