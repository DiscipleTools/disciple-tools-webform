<?php
/**
 * DT_Webform_Menu class for the admin page
 *
 * @class       DT_Webform_Menu
 * @version     0.1.0
 * @since       0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}


/**
 * Class DT_Webform_Menu
 */
DT_Webform_Menu::instance(); // Initialize class
class DT_Webform_Menu
{


    public $token;

    private static $_instance = null;

    /**
     * DT_Webform_Menu Instance
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
     *
     * @access  public
     * @since   0.1.0
     */
    public function __construct() {
        $this->token = DT_Webform::$token;

        add_action( "admin_menu", [ $this, "register_menu" ] );
    } // End __construct()

    /**
     * Loads the subnav page
     *
     * @since 0.1.0
     */
    public function register_menu() {

        // Process state change form
        if ( ( isset( $_POST['initialize_plugin_state'] ) && ! empty( $_POST['initialize_plugin_state'] ) ) && ( isset( $_POST['dt_webform_select_state_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dt_webform_select_state_nonce'] ) ), 'dt_webform_select_state' ) ) ) {
            update_option( 'dt_webform_state', sanitize_key( wp_unslash( $_POST['initialize_plugin_state'] ) ), true );
        }

        // Check for Disciple Tools Theme. If not, then set plugin to 'remote'
        $current_theme = get_option( 'current_theme' );
        if ( 'Disciple Tools' != $current_theme ) {
            update_option( 'dt_webform_state', 'remote', true );
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
     * Combined tabs preprocessor
     */
    public function combined() {

        if ( ! current_user_can( 'manage_dt' ) ) {
            wp_die( esc_attr__( 'You do not have sufficient permissions to access this page.' ) );
        }
        $title = __( 'DISCIPLE TOOLS - WEBFORM (COMBINED)' );

        $link = 'admin.php?page=' . $this->token . '&tab=';

        $tab_bar = [
            [
                'key'   => 'new_leads',
                'label' => __( 'New Leads', 'dt_webform' ),
            ],
            [
                'key' => 'remote_forms',
                'label' => __( 'Forms', 'dt_webform' ),
            ],
            [
                'key' => 'home_settings',
                'label' => __( 'Settings', 'dt_webform' ),
            ],
        ];

        //nonce check
        if ( isset( $_POST['dt_webform_auto_approve_nonce'] ) && ! empty( $_POST['dt_webform_auto_approve_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dt_webform_auto_approve_nonce'] ) ) ) ) {
            die( "Nonce Fail" );
        }

        // determine active tabs
        $active_tab = 'new_leads';

        $options = get_option( 'dt_webform_options' ); // if auto approve, reset tab array
        if ( isset( $options['auto_approve'] ) && $options['auto_approve'] && !isset( $_POST['dt_webform_auto_approve_nonce'] ) ) {
            unset( $tab_bar[0] );
            $active_tab = $tab_bar[1]['key'];
        }

        if ( isset( $_GET["tab"] ) ) {
            $active_tab = sanitize_key( wp_unslash( $_GET["tab"] ) );
        }

        $this->tab_loader( $title, $active_tab, $tab_bar, $link );
    }

    /**
     * Home tabs preprocessor
     */
    public function home() {

        if ( ! current_user_can( 'manage_dt' ) ) {
            wp_die( esc_attr__( 'You do not have sufficient permissions to access this page.' ) );
        }


        $title = __( 'DISCIPLE TOOLS - WEBFORM (HOME)' );

        $link = 'admin.php?page=' . $this->token . '&tab=';

        $tab_bar = [
            [
                'key'   => 'new_leads',
                'label' => __( 'New Leads', 'dt_webform' ),
            ],
            [
                'key' => 'home_settings',
                'label' => __( 'Settings', 'dt_webform' ),
            ],
        ];

        //nonce check
        if ( isset( $_POST['dt_webform_auto_approve_nonce'] ) && ! empty( $_POST['dt_webform_auto_approve_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dt_webform_auto_approve_nonce'] ) ) ) ) {
            die( "Nonce Fail" );
        }

        // determine active tabs
        $active_tab = 'home_settings';

        $options = get_option( 'dt_webform_options' ); // if auto approve, reset tab array
        if ( isset( $options['auto_approve'] ) && $options['auto_approve'] && !isset( $_POST['dt_webform_auto_approve_nonce'] ) ) {
            unset( $tab_bar[0] );
            $active_tab = $tab_bar[1]['key'];
        }

        if ( isset( $_GET["tab"] ) ) {
            $active_tab = sanitize_key( wp_unslash( $_GET["tab"] ) );
        }

        $this->tab_loader( $title, $active_tab, $tab_bar, $link );

    }

    /**
     * Remote tabs preprocessor
     */
    public function remote() {

        if ( ! current_user_can( 'manage_dt' ) ) {
            wp_die( esc_attr__( 'You do not have sufficient permissions to access this page.' ) );
        }

        $title = __( 'DISCIPLE TOOLS - WEBFORM (REMOTE)' );

        $link = 'admin.php?page=' . $this->token . '&tab=';

        $tab_bar = [
            [
                'key'   => 'new_leads',
                'label' => __( 'New Leads', 'dt_webform' ),
            ],
            [
                'key' => 'remote_forms',
                'label' => __( 'Forms', 'dt_webform' ),
            ],
            [
                'key' => 'remote_settings',
                'label' => __( 'Settings', 'dt_webform' ),
            ],
        ];

        //nonce check
        if ( isset( $_POST['dt_webform_auto_approve_nonce'] ) && ! empty( $_POST['dt_webform_auto_approve_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dt_webform_auto_approve_nonce'] ) ) ) ) {
            die( "Nonce Fail" );
        }

        // determine active tabs
        $active_tab = 'new_leads';

        $options = get_option( 'dt_webform_options' ); // if auto approve, reset tab array
        if ( isset( $options['auto_approve'] ) && $options['auto_approve'] && !isset( $_POST['dt_webform_auto_approve_nonce'] ) ) {
            unset( $tab_bar[0] );
            $active_tab = $tab_bar[1]['key'];
        }
        if ( isset( $_GET["tab"] ) ) {
            $active_tab = sanitize_key( wp_unslash( $_GET["tab"] ) );
        }

        $this->tab_loader( $title, $active_tab, $tab_bar, $link );
    }

    /**
     * Tab Loader
     *
     * @param $title
     * @param $active_tab
     * @param $tab_bar
     * @param $link
     */
    public function tab_loader( $title, $active_tab, $tab_bar, $link ) {
        ?>
        <div class="wrap">

            <h2><?php echo esc_attr( $title ) ?></h2>

            <h2 class="nav-tab-wrapper">
                <?php foreach ( $tab_bar as $tab) : ?>
                    <a href="<?php echo esc_attr( $link . $tab['key'] ) ?>"
                       class="nav-tab <?php echo ( $active_tab == $tab['key'] ) ? esc_attr( 'nav-tab-active' ) : ''; ?>">
                        <?php echo esc_attr( $tab['label'] ) ?>
                    </a>
                <?php endforeach; ?>
            </h2>

            <?php
            switch ( $active_tab ) {

                case "home_settings":
                    $this->tab_home_settings();
                    break;
                case 'site_links':
                    $this->tab_home_site_links();
                    break;
                case "remote_settings":
                    $this->tab_remote_settings();
                    break;
                case "remote_forms":
                    $this->tab_remote_forms();
                    break;
                case "new_leads":
                    $this->tab_new_leads();
                    break;
                default:
                    break;
            }
            ?>

        </div><!-- End wrap -->

        <?php
    }

    public function initialize_plugin_state() {

        if ( ! current_user_can( 'manage_dt' ) ) {
            wp_die( esc_attr__( 'You do not have sufficient permissions to access this page.' ) );
        }

        ?>
        <div class="wrap">
            <h2><?php esc_attr_e( 'DISCIPLE TOOLS - WEBFORM', 'dt_webform' ) ?></h2>

            <?php $this->template( 'begin' ) ?>

            <?php DT_Webform_Settings::initialize_plugin_state_metabox() ?>

            <?php $this->template( 'right_column' ) ?>

            <?php $this->template( 'end' ) ?>

        </div>
        <?php
    }

    public function tab_new_leads() {

        // begin columns template
        $this->template( 'begin', 1 );

        $options = get_option( 'dt_webform_options' );
        if ( isset( $options['auto_approve'] ) && $options['auto_approve'] ) {
            echo esc_attr__( 'Tab no longer valid because you have selected "Auto Approve"', 'dt_webform' );
        } else {
            DT_Webform_New_Leads_List::list_box();
        }

        // end columns template
        $this->template( 'end', 1 );
    }

    public function tab_home_settings() {
        // begin columns template
        $this->template( 'begin' );
        $this->metabox_select_home_site();
        DT_Webform_Settings::auto_approve_metabox();
        DT_Webform_Settings::initialize_plugin_state_metabox();

        // begin right column template
        $this->template( 'right_column' );
        // end columns template
        $this->template( 'end' );
    }

    public function tab_remote_settings() {
        // begin columns template
        $this->template( 'begin' );

        $this->metabox_select_home_site();
        DT_Webform_Settings::auto_approve_metabox();
        DT_Webform_Settings::initialize_plugin_state_metabox();

        // begin right column template
        $this->template( 'right_column' );
        // end columns template
        $this->template( 'end' );
    }

    public function tab_home_site_links() {
        // begin columns template
        $this->template( 'begin' );

        // begin right column template
        $this->template( 'right_column' );
        // end columns template
        $this->template( 'end' );
    }

    public function tab_remote_forms() {

        // begin columns template
        $this->template( 'begin', 1 );

        DT_Webform_Forms_List::list_box();

        // end columns template
        $this->template( 'end', 1 );
    }

    public function metabox_select_home_site() {
        if ( isset( $_POST['select_home_site_nonce'] ) && isset( $_POST['site-link'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['select_home_site_nonce'] ) ), 'select_home_site' . get_current_user_id() ) ) {
            $post_id = sanitize_text_field( wp_unslash( $_POST['site-link'] ) );
            if ( empty( $post_id ) ) {
                delete_option( 'dt_webform_site_link' );
            } else {
                update_option( 'dt_webform_site_link', $post_id );
            }
        }

        $sites = Site_Link_System::get_list_of_sites_by_type( [ 'Webform' ] );

        $selected_site = get_option( 'dt_webform_site_link' );

        ?>

        <form method="post" action="">
            <?php wp_nonce_field( 'select_home_site' . get_current_user_id(), 'select_home_site_nonce' ); ?>
            <table class="widefat striped">
                <thead>
                <tr>
                    <td colspan="2">
                        <strong>Link to Home Site</strong><br>
                    </td>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td width="100px">
                        <?php
                        if ( empty( $sites ) ) {
                            echo 'No site links found for this webform. Go to <a href="'. esc_url( admin_url() ) . 'edit.php?post_type=site_link_system">Site Links</a>.';
                        } else {
                            ?>
                            <select class="regular-text" name="site-link">
                                <?php

                                echo '<option value=""></option>';
                                foreach ( $sites as $site ) {

                                    echo '<option value="'. esc_attr( $site['id'] ).'"';

                                    if ( $site['id'] === $selected_site ) {
                                        echo ' selected';
                                    }
                                    echo '>';

                                    echo esc_html( $site['name'] );

                                    echo '</option>';
                                }

                                ?>
                            </select>
                            <button class="button" type="submit">Save</button>

                        <?php } ?>

                    </td>
                </tr>

                </tbody>
            </table>

        </form>

        <br>
        <?php
    }

    public function template( $section, $columns = 2 ) {
        switch ( $columns ) {

            case '1':
                switch ( $section ) {
                    case 'begin':
                        ?>
                        <div class="wrap">
                        <div id="poststuff">
                        <div id="post-body" class="metabox-holder columns-1">
                        <div id="post-body-content">
                        <!-- Main Column -->
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
                break;

            case '2':
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
                break;
        }
    }
}
