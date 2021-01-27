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
        if ( is_this_dt() ) {
            add_menu_page( 'Extensions (DT)', 'Extensions (DT)', 'manage_dt', 'dt_extensions', [ $this, 'extensions_menu' ], 'dashicons-admin-generic', 59 );
            add_submenu_page( 'dt_extensions', 'Webform', 'Webform', 'manage_dt', $this->token, [ $this, 'tab_setup' ] );
        }
        else if ( ! $is_site_set ) {
            add_menu_page( 'Webform (DT)', 'Webform (DT)', 'manage_dt', $this->token, [ $this, 'initialize_plugin_state' ], 'dashicons-admin-links', 99 );
        }
        else {
            add_menu_page( 'Webform (DT)', 'Webform (DT)', 'manage_dt', $this->token, [ $this, 'tab_setup' ], 'dashicons-admin-links', 99 );
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
            <h2>DISCIPLE TOOLS - WEBFORM</h2>

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
            wp_die( esc_html( 'You do not have sufficient permissions to access this page.' ) );
        }

        $title = 'DISCIPLE TOOLS - WEBFORM';

        $link = 'admin.php?page=' . $this->token . '&tab=';

        $tab_bar = [
            [
                'key' => 'forms',
                'label' => 'Forms',
            ],
            [
                'key' => 'settings',
                'label' => 'Settings',
            ],
            [
                'key' => 'help',
                'label' => 'Help',
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

                case "help":
                    $this->tab_help();
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

        if ( ! is_this_dt() ) {
            $this->metabox_select_site();
        }

        $this->metabox_fail_email();

        $this->metabox_dt_fields();

        // begin right column template
        $this->template( 'right_column' );
        // end columns template
        $this->template( 'end' );
    }

    public function tab_help() {
        // begin columns template
        $this->template( 'begin' );

        $this->metabox_help();

        // begin right column template
        $this->template( 'right_column' );

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
                            <?php if ( $selected_site ){ ?>
                                <br>
                                <span>
                                    <?php esc_html_e( 'Status:' ) ?>
                                    <strong>
                                        <span id="<?php echo esc_attr( md5( $selected_site ) ); ?>-status">
                                            <?php esc_html_e( 'Checking Status' ) ?>
                                        </span>
                                    </strong>
                                </span>
                                <?php $site_link = Site_Link_System::get_site_connection_vars( $selected_site );
                                if ( ! is_wp_error( $site_link ) && isset( $site_link["url"] )){

                                    echo "<script type='text/javascript'>
                                    jQuery(document).ready(function () {
                                        check_link_status('" . esc_attr( $site_link["transfer_token"] ) ."', '" . esc_attr( $site_link["url"] ) . "', '" . esc_attr( md5( $selected_site ) ) . "')
                                    })

                                    function check_link_status( transfer_token, url, id ) {

                                        let linked = '" . esc_attr__( 'Linked' ) . "';
                                        let not_linked = '" . esc_attr__( 'Not Linked' ) . "';
                                        let not_found = '" . esc_attr__( 'Failed to connect with the URL provided.' ) . "';
                                        let no_ssl = '" . esc_attr__( 'Linked, but insecurely. The webform may not work.' ) . "';

                                        return jQuery.ajax({
                                            type: 'POST',
                                            data: JSON.stringify({ \"transfer_token\": transfer_token } ),
                                            contentType: 'application/json; charset=utf-8',
                                            dataType: 'json',
                                            url: 'https://' + url + '/wp-json/dt-public/v1/sites/site_link_check',
                                        })
                                        .done(function (data) {
                                            if( data ) {
                                                jQuery('#' + id + '-status').html( linked ).attr('class', 'success-green')
                                            } else {
                                                jQuery('#' + id + '-status').html( not_linked ).attr('class', 'fail-red');
                                            }
                                        })
                                        .fail(function (err) {
                                            //try non https on failure
                                            jQuery.ajax({
                                                type: 'POST',
                                                data: JSON.stringify({ \"transfer_token\": transfer_token } ),
                                                contentType: 'application/json; charset=utf-8',
                                                dataType: 'json',
                                                url: 'http://' + url + '/wp-json/dt-public/v1/sites/site_link_check',
                                            }).done(data=>{
                                                if( data ) {
                                                    jQuery('#' + id + '-status').html( no_ssl ).attr('class', 'fail-red')
                                                } else {
                                                    jQuery('#' + id + '-status').html( not_linked ).attr('class', 'fail-red');
                                                }
                                            }).fail(function(err) {
                                                 jQuery( document ).ajaxError(function( event, request, settings ) {
                                                     if( request.status === 0 ) {
                                                        jQuery('#' + id + '-status').html( not_found ).attr('class', 'fail-red')
                                                     } else {
                                                        jQuery('#' + id + '-status').html( JSON.stringify( request.statusText ) ).attr('class', 'fail-red')
                                                     }
                                                });
                                            })
                                        });
                                    }
                                    </script>
                                    <style type='text/css'>
                                        .success-green { color: limegreen;}
                                        .fail-red { color: red;}
                                    </style>
                                    ";

                                }
                            } ?>

                        <?php } ?>

                    </td>
                </tr>

                </tbody>
            </table>

        </form>

        <br>
        <?php
    }

    public function metabox_help() {
        ?>
        <form method="post">
            <table class="widefat striped">
                <thead>
                <tr><th>Documentation</th></tr>
                </thead>
                <tbody>
                    <tr id="assign-to-user">
                        <td>
                            <a href="https://github.com/DiscipleTools/disciple-tools-webform/wiki">Documentation</a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>
        <br>
        <?php
    }

    public function metabox_help_menu() {
        ?>
        <?php
    }

    public function metabox_fail_email() {
        if ( isset( $_POST['fail_email_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fail_email_nonce'] ) ), 'fail_email' ) && isset( $_POST['action-button'] ) ) {
            $button = sanitize_text_field( wp_unslash( $_POST['action-button'] ) );
            if ( ! isset( $_POST['fail_email'] ) ) {
                delete_option( 'dt_webform_admin_fail_email' );
            }
            else if ( 'delete' === $button ) {
                delete_option( 'dt_webform_admin_fail_email' );
            }
            else if ( 'save' === $button ) {
                $email = sanitize_email( wp_unslash( $_POST['fail_email'] ) );
                update_option( 'dt_webform_admin_fail_email', $email, false );
            }
        }
        $email = get_option( 'dt_webform_admin_fail_email' );
        ?>
        <form method="post">
            <table class="widefat striped">
                <thead>
                <tr><th>Email to send failed forms</th></tr>
                </thead>
                <tbody>
                <tr>
                    <td>If a form is filled out, but he system fails to create a record in Disciple Tools, the failed form can be emailed to an administrator. </td>
                </tr>
                <tr>
                    <td>
                        <?php wp_nonce_field( 'fail_email', 'fail_email_nonce' ) ?>
                        <input type="text" name="fail_email" value="<?php echo esc_attr( $email ) ?>" class="regular-text" />
                        <?php if ( $email ): ?>
                            <button class="button" name="action-button" value="delete" type="submit">Delete</button>
                        <?php else : ?>
                            <button class="button" name="action-button" value="save" type="submit">Save</button>
                        <?php endif; ?>
                    </td>
                    <td></td>
                </tr>
                </tbody>
            </table>
        </form>
        <br>
        <?php
    }

    public function metabox_dt_fields() {
        $contact_defaults = DT_Webform_Utilities::get_contact_defaults( true );
        if ( is_wp_error( $contact_defaults ) ) {
            return;
        }
        $object = DT_Webform_Active_Form_Post_Type::instance();
        $fields = $object->filtered_contact_fields( $contact_defaults );
        ?>
        <table class="widefat striped">
            <thead>
            <tr>
                <td colspan="2">
                    <strong>DT Fields Available</strong><br>
                </td>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <?php
                    foreach ($fields as $key => $value) {
                        echo '<strong>' . esc_attr( $key ) . '</strong> (' . esc_attr( $value['type'] ) . ')<br>';
                        if ( ! empty( $value['default'] ) && is_array( $value['default'] ) ) {
                            foreach ( $value['default'] as $k => $v) {
                                if ('connection_types' === $key) {
                                    echo ' &nbsp;&nbsp; ' . esc_html( $v ) . '<br>';
                                } else {
                                    echo ' &nbsp;&nbsp; ' . esc_html( $k ) . '<br>';
                                }
                            }
                        }
                    }
                    ?>
                </td>
            </tr>

            </tbody>
        </table>
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
                        <div id="poststuf">
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
                        <div id="poststuf">
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
