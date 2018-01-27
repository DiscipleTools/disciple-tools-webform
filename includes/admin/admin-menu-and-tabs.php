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
        $this->token = DT_Webform::$token;

        add_action( "admin_menu", [ $this, "register_menu" ] );
    } // End __construct()

    /**
     * Loads the subnav page
     *
     * @since 0.1.0
     */
    public function register_menu()
    {

        // Process state change form
        if ( ( isset( $_POST['initialize_plugin_state'] ) && ! empty( $_POST['initialize_plugin_state'] ) ) && ( isset( $_POST['dt_webform_select_state_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dt_webform_select_state_nonce'] ) ), 'dt_webform_select_state' ) ) ) {
            update_option( 'dt_webform_state', sanitize_key( wp_unslash( $_POST['initialize_plugin_state'] ) ), false );
        }

        // Initialize Plugin Settings
        self::initialize_settings();

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
    public function extensions_menu()
    {
    }

    /**
     * Combined tabs preprocessor
     */
    public function combined()
    {

        if ( ! current_user_can( 'manage_dt' ) ) {
            wp_die( esc_attr__( 'You do not have sufficient permissions to access this page.' ) );
        }

        if ( isset( $_GET["tab"] ) ) {
            $active_tab = sanitize_key( wp_unslash( $_GET["tab"] ) );
        } else {
            $active_tab = 'general';
        }

        $title = __( 'DISCIPLE TOOLS - WEBFORM (COMBINED)' );

        $link = 'admin.php?page=' . $this->token . '&tab=';

        $tab_bar = [
                [
                        'key' => 'general',
                        'label' => __( 'General', 'dt_webform' ),
                ],
                [
                        'key' => 'site_links',
                        'label' => __( 'Site Links', 'dt_webform' ),
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

        $this->tab_loader( $title, $active_tab, $tab_bar, $link );
    }

    /**
     * Home tabs preprocessor
     */
    public function home()
    {

        if ( ! current_user_can( 'manage_dt' ) ) {
            wp_die( esc_attr__( 'You do not have sufficient permissions to access this page.' ) );
        }

        if ( isset( $_GET["tab"] ) ) {
            $active_tab = sanitize_key( wp_unslash( $_GET["tab"] ) );
        } else {
            $active_tab = 'general';
        }

        $title = __( 'DISCIPLE TOOLS - WEBFORM (HOME)' );

        $link = 'admin.php?page=' . $this->token . '&tab=';

        $tab_bar = [
            [
                'key' => 'general',
                'label' => __( 'General', 'dt_webform' ),
            ],
            [
                'key' => 'site_links',
                'label' => __( 'Site Links', 'dt_webform' ),
            ],
            [
                'key' => 'home_settings',
                'label' => __( 'Settings', 'dt_webform' ),
            ],
        ];

        $this->tab_loader( $title, $active_tab, $tab_bar, $link );

    }

    /**
     * Remote tabs preprocessor
     */
    public function remote()
    {

        if ( ! current_user_can( 'manage_dt' ) ) {
            wp_die( esc_attr__( 'You do not have sufficient permissions to access this page.' ) );
        }

        if ( isset( $_GET["tab"] ) ) {
            $active_tab = sanitize_key( wp_unslash( $_GET["tab"] ) );
        } else {
            $active_tab = 'remote_forms';
        }

        $title = __( 'DISCIPLE TOOLS - WEBFORM (REMOTE)' );

        $link = 'admin.php?page=' . $this->token . '&tab=';

        $tab_bar = [
            [
                'key' => 'remote_forms',
                'label' => __( 'Forms', 'dt_webform' ),
            ],
            [
                'key' => 'new_leads',
                'label' => __( 'New Leads', 'dt_webform' ),
            ],
            [
                'key' => 'remote_settings',
                'label' => __( 'Settings', 'dt_webform' ),
            ],
        ];

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
                case "general":
                    $object = new DT_Webform_Tab_General();
                    $object->content();
                    break;
                case "home_settings":
                    $object = new DT_Webform_Home_Tab_Settings();
                    $object->content();
                    break;
                case 'site_links':
                    $object = new DT_Webform_Home_Tab_Site_Links();
                    $object->content();
                    break;
                case "remote_settings":
                    $object = new DT_Webform_Remote_Tab_Settings();
                    $object->content();
                    break;
                case "remote_forms":
                    $object = new DT_Webform_Remote_Tab_Forms();
                    $object->content();
                    break;
                case "new_leads":
                    $this->new_leads_tab();
                    break;
                default:
                    break;
            }
            ?>

        </div><!-- End wrap -->

        <?php
    }

    public function initialize_plugin_state()
    {

        if ( ! current_user_can( 'manage_dt' ) ) {
            wp_die( esc_attr__( 'You do not have sufficient permissions to access this page.' ) );
        }

        ?>
        <div class="wrap">
            <h2><?php esc_attr_e( 'DISCIPLE TOOLS - WEBFORM', 'dt_webform' ) ?></h2>

            <?php DT_Webform_Page_Template::template( 'begin' ) ?>

            <?php DT_Webform_Admin::initialize_plugin_state_metabox() ?>

            <?php DT_Webform_Page_Template::template( 'right_column' ) ?>

            <?php DT_Webform_Page_Template::template( 'end' ) ?>

        </div>
        <?php
    }

    protected static function initialize_settings() // todo: I do not think this options setup is necissary.
    {
        $home = get_option( 'dt_webform_home_settings' );
        if ( ! $home ) {
            $default = [];
            update_option( 'dt_webform_home_settings', $default, false );
        }
        $remote = get_option( 'dt_webform_remote_settings' );
        if ( ! $remote ) {
            $default = [];
            update_option( 'dt_webform_remote_settings', $default, false );
        }
    }

    public function new_leads_tab() {
        // begin columns template
        DT_Webform_Page_Template::template( 'begin', 1 );

        DT_Webform_New_Leads_List::list_box();

        // end columns template
        DT_Webform_Page_Template::template( 'end', 1 );
    }
}


/**
 * Class DT_Webform_Page_Template
 */
class DT_Webform_Page_Template
{
    /**
     * @param $section
     */
    public static function template( $section, $columns = 2 )
    {
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

/**
 * Class DT_Webform_Tab_General
 */
class DT_Webform_Tab_General
{
    public function content()
    {

        // begin columns template
        DT_Webform_Page_Template::template( 'begin' );

        $this->main_column(); // main column content

        // begin right column template
        DT_Webform_Page_Template::template( 'right_column' );

        $this->right_column(); // right column content

        // end columns template
        DT_Webform_Page_Template::template( 'end' );
    }

    public function main_column()
    {
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <tr><td>Header</td></tr>
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

    public function right_column()
    {
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <tr><td>Information</td></tr>
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
class DT_Webform_Home_Tab_Settings
{
    public function content()
    {

        // begin columns template
        DT_Webform_Page_Template::template( 'begin' );

        DT_Webform_Admin::initialize_plugin_state_metabox();

        // begin right column template
        DT_Webform_Page_Template::template( 'right_column' );


        // end columns template
        DT_Webform_Page_Template::template( 'end' );
    }

}

/**
 * Class DT_Webform_Tab_Settings
 */
class DT_Webform_Remote_Tab_Settings
{
    public function content()
    {

        // begin columns template
        DT_Webform_Page_Template::template( 'begin' );

        DT_Webform_Remote::site_link_metabox();
        DT_Webform_Admin::initialize_plugin_state_metabox();

        // begin right column template
        DT_Webform_Page_Template::template( 'right_column' );


        // end columns template
        DT_Webform_Page_Template::template( 'end' );
    }
}

/**
 * Class DT_Webform_Tab_General
 *
 * This page generates the private API keys that link two sites together
 */
class DT_Webform_Home_Tab_Site_Links
{
    public function content()
    {
        // begin columns template
        DT_Webform_Page_Template::template( 'begin' );

        DT_Webform_Home::site_api_link_metabox(); // main column content

        // begin right column template
        DT_Webform_Page_Template::template( 'right_column' );


        // end columns template
        DT_Webform_Page_Template::template( 'end' );
    }
}

/**
 * Class DT_Webform_Tab_General
 */
class DT_Webform_Remote_Tab_Forms
{
    public function content()
    {

        // begin columns template
        DT_Webform_Page_Template::template( 'begin', 1 );

        DT_Webform_Forms_List::list_box();

        // end columns template
        DT_Webform_Page_Template::template( 'end', 1 );
    }

}
