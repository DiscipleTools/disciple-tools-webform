<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly
/**
 * DT_Webform_Menu class for the admin page
 *
 * @class       DT_Webform_Menu
 * @version     3.0.0
 * @since       0.1.0
 */


/**
 * Class DT_Webform_Menu
 */
DT_Webform_Menu::instance(); // Initialize class
class DT_Webform_Menu
{
    public $token = 'dt_webform';
    public $permissions = [ 'manage_dt' ];

    /**
     * DT_Webform_Menu Instance
     * Ensures only one instance of DT_Webform_Menu is loaded or can be loaded.
     *
     * @since 0.1.0
     * @static
     * @return DT_Webform_Menu instance
     */
    private static $_instance = null;
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
        if ( ! is_admin() ) {
            return;
        }
        if ( ! dt_has_permissions( $this->permissions ) ) {
            return;
        }

        global $pagenow;
        add_action( "admin_menu", [ $this, "register_menu" ] );
        if ( 'admin.php' === $pagenow && isset( $_GET['page'] ) && 'dt_webform' == sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
            add_action( "admin_enqueue_scripts", [ $this, 'scripts' ] );
            add_action( 'admin_head', [ $this, 'custom_admin_head' ] );
        }

    } // End __construct()

    /**
     * Loads the subnav page
     *
     * @since 0.1.0
     */
    public function register_menu() {
        $is_site_set = dt_get_webform_site_link();
        if ( $is_site_set ) {
            $is_site_set = $this->verify_site_active( $is_site_set );
        }

        // Check for Disciple Tools Theme. If not, then set plugin to 'remote'
        if ( is_dt() ) {
            add_menu_page( __( 'Extensions (DT)', 'disciple_tools' ), __( 'Extensions (DT)', 'disciple_tools' ), 'manage_dt', 'dt_extensions', [ $this, 'extensions_menu' ], 'dashicons-admin-generic', 59 );
            add_submenu_page( 'dt_extensions', __( 'Webform', 'dt_webform' ), __( 'Webform', 'dt_webform' ), 'manage_dt', $this->token, [ $this, 'tab_setup' ] );
        }
        else if ( ! $is_site_set ) {
            add_menu_page( __( 'Webform (DT)', 'disciple_tools' ), __( 'Webform (DT)', 'disciple_tools' ), 'manage_dt', $this->token, [ $this, 'initialize_plugin_state' ], 'dashicons-admin-links', 99 );
        }
        else {
            add_menu_page( __( 'Webform (DT)', 'disciple_tools' ), __( 'Webform (DT)', 'disciple_tools' ), 'manage_dt', $this->token, [ $this, 'tab_setup' ], 'dashicons-admin-links', 99 );
        }
    }

    /**
     * Menu stub. Replaced when Disciple Tools Theme fully loads.
     */
    public function extensions_menu() {
    }

    public function initialize_plugin_state() {

        if ( ! current_user_can( 'manage_dt' ) ) {
            wp_die( esc_attr__( 'You do not have sufficient permissions to access this page.' ) );
        }

        ?>
        <div class="wrap">
            <h2><?php esc_attr_e( 'DISCIPLE TOOLS - WEBFORM', 'dt_webform' ) ?></h2>

            <?php $this->template( 'begin' ) ?>

            <?php self::metabox_select_site(); ?>

            <?php $this->template( 'right_column' ) ?>

            <?php $this->template( 'end' ) ?>

        </div>
        <?php
    }

    public function scripts() {
        DT_Mapbox_API::load_mapbox_header_scripts();
        DT_Mapbox_API::load_mapbox_search_widget();
    }

    public function custom_admin_head() {
        /**
         * Add custom css styles for the dt_webform admin page
         */
        ?>
        <style type="text/css">
            .float-right {
                float: right;
            }
            button.button-like-link {
                background: none !important;
                color: blue;
                border: none;
                padding: 0 !important;
                font: inherit;
                /*border is optional*/
                cursor: pointer;
            }
        </style>
        <?php
    }

    public function tab_setup() {

        if ( ! current_user_can( 'manage_dt' ) ) {
            wp_die( esc_attr__( 'You do not have sufficient permissions to access this page.' ) );
        }

        $title = __( 'DISCIPLE TOOLS - WEBFORM' );

        $link = 'admin.php?page=' . $this->token . '&tab=';

        $tab_bar = [
            [
                'key' => 'forms',
                'label' => __( 'Forms', 'dt_webform' ),
            ],
            [
                'key' => 'settings',
                'label' => __( 'Settings', 'dt_webform' ),
            ],
            [
                'key' => 'tutorial',
                'label' => __( 'Tutorial', 'dt_webform' ),
            ],
        ];


        // determine active tabs
        $active_tab = 'forms';

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

                case "tutorial":
                    $this->tab_tutorial();
                    break;
                case "settings":
                    $this->tab_settings();
                    break;
                case "forms":
                    $this->tab_forms();
                    break;
                default:
                    break;
            }
            ?>

        </div><!-- End wrap -->

        <?php
    }

    public function tab_settings() {
        // begin columns template
        $this->template( 'begin' );

        DT_Mapbox_API::metabox_for_admin();

        if ( ! is_dt() ) {
            $this->metabox_select_site();
        }

        // begin right column template
        $this->template( 'right_column' );
        // end columns template
        $this->template( 'end' );
    }

    public function tab_tutorial() {
        // begin columns template
        $this->template( 'begin' );

        $this->metabox_tutorial();

        // begin right column template
        $this->template( 'right_column' );

        $this->metabox_tutorial_menu();
        // end columns template
        $this->template( 'end' );
    }

    public function tab_forms() {

        // begin columns template
        $this->template( 'begin', 1 );

        DT_Webform_Forms_List::list_box();

        // end columns template
        $this->template( 'end', 1 );
    }

    public function verify_site_active( $site_id ) : bool {
        $sites = Site_Link_System::get_list_of_sites_by_type( [ 'create_contacts', 'create_update_contacts' ], 'post_ids' );
        if ( empty( $sites ) ) {
            return false;
        }
        foreach ( $sites as $site ) {
            if ( $site == $site_id ) {
                return true;
            }
        }
        return false;
    }

    public function metabox_select_site() {

        if ( isset( $_POST['select_home_site_nonce'] ) && isset( $_POST['site-link'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['select_home_site_nonce'] ) ), 'select_home_site' . get_current_user_id() ) ) {
            $post_id = sanitize_text_field( wp_unslash( $_POST['site-link'] ) );
            if ( empty( $post_id ) ) {
                delete_option( 'dt_webform_site_link' );

            } else {
                update_option( 'dt_webform_site_link', $post_id );
            }
            ?><script>window.location.href = '<?php echo esc_url( admin_url() ) ?>admin.php?page=dt_webform&tab=settings'</script><?php
        }

        $sites = Site_Link_System::get_list_of_sites_by_type( [ 'create_contacts', 'create_update_contacts' ] );

        $selected_site = dt_get_webform_site_link();

        ?>

        <form method="post" action="">
            <?php wp_nonce_field( 'select_home_site' . get_current_user_id(), 'select_home_site_nonce' ); ?>
            <table class="widefat striped">
                <thead>
                <tr>
                    <td colspan="2">
                        <strong>Link to Disciple Tools Site</strong><br>
                    </td>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td width="100px">
                        <?php
                        if ( empty( $sites ) ) {
                            echo 'No site links found for this webform. You must create a site link to Disciple Tools to unlock the rest of this plugin. Go to <a href="'. esc_url( admin_url() ) . 'edit.php?post_type=site_link_system">Site Links</a>.';
                        } else {
                            ?>
                            You must select a site link to unlock the webform plugin.<br>
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

    public function metabox_tutorial() {
        ?>
        <form method="post">
            <table class="widefat striped">
                <thead>
                <tr><th>Tutorial</th></tr>
                </thead>
                <tbody>
                <tr id="assign-to-user">
                    <td>
                        <a name=""></a>
                        <strong>How to use "Assign to User"</strong>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong></strong>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong></strong>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong></strong>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong></strong>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong></strong>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong></strong>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong></strong>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong></strong>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong></strong>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong></strong>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong></strong>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong></strong>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong></strong>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong></strong>
                    </td>
                </tr>
                </tbody>
            </table>
        </form>
        <br>
        <?php
    }

    public function metabox_tutorial_menu() {
        ?>
        <form method="post">
            <table class="widefat striped">
                <thead>
                <tr><th>Topics</th></tr>
                </thead>
                <tbody>
                <tr>
                    <td>
                        <a href="#assign-to-user">How to use "Assign to User"</a>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong></strong>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong></strong>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong></strong>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong></strong>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong></strong>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong></strong>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong></strong>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong></strong>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong></strong>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong></strong>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong></strong>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong></strong>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong></strong>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong></strong>
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
