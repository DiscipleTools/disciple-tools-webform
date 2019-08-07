<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly
/**
 * DT_Webform_Menu class for the admin page
 *
 * @class       DT_Webform_Menu
 * @version     0.1.0
 * @since       0.1.0
 */



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
        add_action( "admin_enqueue_scripts", [ $this, 'scripts' ] );
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

    public function initialize_plugin_state() {

        if ( ! current_user_can( 'manage_dt' ) ) {
            wp_die( esc_attr__( 'You do not have sufficient permissions to access this page.' ) );
        }

        ?>
        <div class="wrap">
            <h2><?php esc_attr_e( 'DISCIPLE TOOLS - WEBFORM', 'dt_webform' ) ?></h2>

            <?php $this->template( 'begin' ) ?>

            <?php self::initialize_plugin_state_metabox() ?>

            <?php $this->template( 'right_column' ) ?>

            <?php $this->template( 'end' ) ?>

        </div>
        <?php
    }

    public function scripts() {
        if ( is_admin() ) {
            wp_enqueue_script( 'mapbox-gl', 'https://api.mapbox.com/mapbox-gl-js/v1.1.0/mapbox-gl.js', [ 'jquery','lodash' ], '1.1.0', false );
            wp_enqueue_style( 'mapbox-gl-css', 'https://api.mapbox.com/mapbox-gl-js/v1.1.0/mapbox-gl.css', [], '1.1.0' );
        }
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
        $this->template( 'begin', 1 );
        $this->metabox_select_home_site();
        $this->metabox_auto_approve();
        $this->initialize_plugin_state_metabox();
        $this->box_geocoding_source();

        $this->template( 'end', 1 );
    }

    public function tab_remote_settings() {
        // begin columns template
        $this->template( 'begin' );

        $this->metabox_select_home_site();
        $this->metabox_auto_approve();
        $this->initialize_plugin_state_metabox();

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

    public function metabox_auto_approve() {
        // Check for post
        if ( isset( $_POST['dt_webform_auto_approve_nonce'] ) && ! empty( $_POST['dt_webform_auto_approve_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dt_webform_auto_approve_nonce'] ) ), 'dt_webform_auto_approve' ) ) {

            $options = get_option( 'dt_webform_options' );
            if ( isset( $_POST['auto_approve'] ) ) {
                $options['auto_approve'] = true;
                //if the option is true then it will hide the tab "New leads"
                ?>
                <script>
                    jQuery("a:contains('New Leads')").remove();
                </script>
                <?php
            } else {
                //show the tab
                $options['auto_approve'] = false;
            }

            update_option( 'dt_webform_options', $options, false );
        }

        // Get status of auto approve
        $options = get_option( 'dt_webform_options' );
        if ( ! get_option( 'dt_webform_site_link' ) ) {
            DT_Webform::set_auto_approve_to_false();
            $options['auto_approve'] = false;
        }

        ?>
        <form method="post" action="">
            <?php wp_nonce_field( 'dt_webform_auto_approve', 'dt_webform_auto_approve_nonce', false, true ) ?>
            <!-- Box -->
            <table class="widefat striped">
                <thead>
                <th>Auto Approve</th>
                </thead>
                <tbody>
                <tr>
                    <td>
                        <label for="auto_approve">Auto Approve: </label>
                        <input type="checkbox" id="auto_approve" name="auto_approve" value="1" <?php echo ( $options['auto_approve'] ) ? 'checked' : ''; ?> />
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

    public function box_geocoding_source() {
        if ( isset( $_POST['mapbox_key'] )
             && ( isset( $_POST['geocoding_key_nonce'] )
                  && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['geocoding_key_nonce'] ) ), 'geocoding_key' . get_current_user_id() ) ) ) {

            $key = sanitize_text_field( wp_unslash( $_POST['mapbox_key'] ) );
            if ( empty( $key ) ) {
                delete_option( 'dt_mapbox_api_key' );
            } else {
                update_option( 'dt_mapbox_api_key', $key, true );
            }
        }
        $key = get_option( 'dt_mapbox_api_key' );
        $hidden_key = '**************' . substr( $key, -5, 5 );

        set_error_handler( [ $this, "warning_handler" ], E_WARNING );
        $list = file_get_contents( 'https://api.mapbox.com/geocoding/v5/mapbox.places/Denver.json?access_token=' . $key );
        restore_error_handler();

        if ( $list ) {
            $status_class = 'connected';
            $message = 'Successfully connected to selected source.';
        } else {
            $status_class = 'not-connected';
            $message = 'API NOT AVAILABLE';
        }
        ?>
        <form method="post">
            <table class="widefat striped">
                <thead>
                <tr><th>MapBox.com</th></tr>
                </thead>
                <tbody>
                <tr>
                    <td>
                        <?php wp_nonce_field( 'geocoding_key' . get_current_user_id(), 'geocoding_key_nonce' ); ?>
                        Mapbox API Token: <input type="text" class="regular-text" name="mapbox_key" value="<?php echo ( $key ) ? esc_attr( $hidden_key ) : ''; ?>" /> <button type="submit" class="button">Update</button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <p id="reachable_source" class="<?php echo esc_attr( $status_class ) ?>">
                            <?php echo esc_html( $message ); ?>
                        </p>
                    </td>
                </tr>
                </tbody>
            </table>
        </form>
        <br>

        <?php if ( empty( get_option( 'dt_mapbox_api_key' ) ) ) : ?>
            <table class="widefat striped">
                <thead>
                <tr><th>MapBox.com Instructions</th></tr>
                </thead>
                <tbody>
                <tr>
                    <td>
                        <ol>
                            <li>
                                Go to <a href="https://www.mapbox.com/">MapBox.com</a>.
                            </li>
                            <li>
                                Register for a new account (<a href="https://account.mapbox.com/auth/signup/">MapBox.com</a>)<br>
                                <em>(email required, no credit card required)</em>
                            </li>
                            <li>
                                Once registered, go to your account home page. (<a href="https://account.mapbox.com/">Account Page</a>)<br>
                            </li>
                            <li>
                                Inside the section labeled "Access Tokens", either create a new token or use the default token provided. Copy this token.
                            </li>
                            <li>
                                Paste the token into the "Mapbox API Token" field in the box above.
                            </li>
                        </ol>
                    </td>
                </tr>
                </tbody>
            </table>
            <br>
        <?php endif; ?>

        <?php if ( ! empty( get_option( 'dt_mapbox_api_key' ) ) ) : ?>
            <table class="widefat striped">
                <thead>
                <tr><th>Geocoding Test</th></tr>
                </thead>
                <tbody>

                <tr>
                    <td>
                        <!-- Geocoder Input Section -->
                        <?php // @codingStandardsIgnoreStart ?>
                        <script src='https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.4.0/mapbox-gl-geocoder.min.js'></script>
                        <link rel='stylesheet' href='https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.4.0/mapbox-gl-geocoder.css' type='text/css' />
                        <?php // @codingStandardsIgnoreEnd ?>
                        <style>
                            .mapboxgl-ctrl-geocoder {
                                min-width:100%;
                            }
                            #geocoder {
                                padding-bottom: 10px;
                            }
                            #map {
                                width:66%;
                                height:400px;
                                float:left;
                            }
                            #list {
                                width:33%;
                                float:right;
                            }
                            #selected_values {
                                width:66%;
                                float:left;
                            }
                            .result_box {
                                padding: 15px 10px;
                                border: 1px solid lightgray;
                                margin: 5px 0 0;
                                font-weight: bold;
                            }
                            .add-column {
                                width:10px;
                            }
                        </style>

                        <!-- Widget -->
                        <div id='geocoder' class='geocoder'></div>
                        <div>
                            <div id='map'></div>
                            <div id="list"></div>
                        </div>
                        <div id="selected_values"></div>

                        <!-- Mapbox script -->
                        <script>
                            mapboxgl.accessToken = '<?php echo esc_html( get_option( 'dt_mapbox_api_key' ) ) ?>';
                            var map = new mapboxgl.Map({
                                container: 'map',
                                style: 'mapbox://styles/mapbox/streets-v11',
                                center: [-20, 30],
                                zoom: 1
                            });

                            map.addControl(new mapboxgl.NavigationControl());

                            var geocoder = new MapboxGeocoder({
                                accessToken: mapboxgl.accessToken,
                                types: 'country', //'country region district postcode locality neighborhood address place',
                                marker: {color: 'orange'},
                                mapboxgl: mapboxgl
                            });

                            document.getElementById('geocoder').appendChild(geocoder.onAdd(map));

                            // After Search Result
                            geocoder.on('result', function(e) { // respond to search
                                geocoder._removeMarker()
                                console.log(e)
                            })


                            map.on('click', function (e) {
                                console.log(e)

                                let lng = e.lngLat.lng
                                let lat = e.lngLat.lat
                                window.active_lnglat = [lng,lat]

                                // add marker
                                if ( window.active_marker ) {
                                    window.active_marker.remove()
                                }
                                window.active_marker = new mapboxgl.Marker()
                                    .setLngLat(e.lngLat )
                                    .addTo(map);
                                console.log(active_marker)

                                // add polygon
                                jQuery.get('<?php echo esc_url( trailingslashit( get_template_directory_uri() ) ) . 'dt-mapping/' ?>location-grid-list-api.php',
                                    {
                                        type: 'possible_matches',
                                        longitude: lng,
                                        latitude:  lat,
                                        nonce: '<?php echo esc_html( wp_create_nonce( 'location_grid' ) ) ?>'
                                    }, null, 'json' ).done(function(data) {

                                    console.log(data)
                                    if ( data !== undefined ) {
                                        print_click_results( data )
                                    }

                                })
                            });


                            // User Personal Geocode Control
                            let userGeocode = new mapboxgl.GeolocateControl({
                                positionOptions: {
                                    enableHighAccuracy: true
                                },
                                marker: {
                                    color: 'orange'
                                },
                                trackUserLocation: false
                            })
                            map.addControl(userGeocode);
                            userGeocode.on('geolocate', function(e) { // respond to search
                                console.log(e)
                                let lat = e.coords.latitude
                                let lng = e.coords.longitude
                                window.active_lnglat = [lng,lat]

                                // add polygon
                                jQuery.get('<?php echo esc_url( trailingslashit( get_template_directory_uri() ) ) . 'dt-mapping/' ?>location-grid-list-api.php',
                                    {
                                        type: 'possible_matches',
                                        longitude: lng,
                                        latitude:  lat,
                                        nonce: '<?php echo esc_html( wp_create_nonce( 'location_grid' ) ) ?>'
                                    }, null, 'json' ).done(function(data) {
                                    console.log(data)

                                    if ( data !== undefined ) {

                                        print_click_results(data)
                                    }
                                })
                            })

                            jQuery(document).ready(function() {
                                jQuery('input.mapboxgl-ctrl-geocoder--input').attr("placeholder", "Enter Country")
                            })


                            function print_click_results( data ) {
                                if ( data !== undefined ) {

                                    // print click results
                                    window.MBresponse = data

                                    let print = jQuery('#list')
                                    print.empty();
                                    print.append('<strong>Click Results</strong><br><hr>')
                                    let table_body = ''
                                    jQuery.each( data, function(i,v) {
                                        let string = '<tr><td class="add-column">'
                                        string += '<button onclick="add_selection(' + v.grid_id +')">Add</button></td> '
                                        string += '<td><strong style="font-size:1.2em;">'+v.name+'</strong> <br>'
                                        if ( v.admin0_name !== v.name ) {
                                            string += v.admin0_name
                                        }
                                        if ( v.admin1_name !== null ) {
                                            string += ' > ' + v.admin1_name
                                        }
                                        if ( v.admin2_name !== null ) {
                                            string += ' > ' + v.admin2_name
                                        }
                                        if ( v.admin3_name !== null ) {
                                            string += ' > ' + v.admin3_name
                                        }
                                        if ( v.admin4_name !== null ) {
                                            string += ' > ' + v.admin4_name
                                        }
                                        if ( v.admin5_name !== null ) {
                                            string += ' > ' + v.admin5_name
                                        }
                                        string += '</td></tr>'
                                        table_body += string
                                    })
                                    print.append('<table>' + table_body + '</table>')
                                }
                            }

                            function add_selection( grid_id ) {
                                console.log(window.MBresponse[grid_id])

                                let div = jQuery('#selected_values')
                                let response = window.MBresponse[grid_id]

                                if ( window.selected_locations === undefined ) {
                                    window.selected_locations = []
                                }
                                window.selected_locations[grid_id] = new mapboxgl.Marker()
                                    .setLngLat( [ window.active_lnglat[0], window.active_lnglat[1] ] )
                                    .addTo(map);

                                let name = ''
                                name += response.name
                                if ( response.admin1_name !== undefined && response.level > '1' ) {
                                    name += ', ' + response.admin1_name
                                }
                                if ( response.admin0_name && response.level > '0' ) {
                                    name += ', ' + response.admin0_name
                                }

                                div.append('<div class="result_box" id="'+grid_id+'">' +
                                    '<span>'+name+'</span>' +
                                    '<span style="float:right;cursor:pointer;" onclick="remove_selection(\''+grid_id+'\')">X</span>' +
                                    '<input type="hidden" name="selected_grid_id['+grid_id+']" value="' + grid_id + '" />' +
                                    '<input type="hidden" name="selected_lnglat['+grid_id+']" value="' + window.active_lnglat[0] + ',' + window.active_lnglat[1] + '" />' +
                                    '</div>')

                            }

                            function remove_selection( grid_id ) {
                                window.selected_locations[grid_id].remove()
                                jQuery('#' + grid_id ).remove()
                            }


                        </script>
                    </td>
                </tr>
                </tbody>
            </table>
        <?php endif; ?>

        <?php
    }

    public function warning_handler( $errno, $errstr ) {
        ?>
        <div class="notice notice-error notice-dt-mapping-source" data-notice="dt-demo">
            <p><?php echo "MIRROR SOURCE NOT AVAILABLE" ?></p>
            <p><?php echo "Error Message: " . esc_attr( $errstr ) ?></p>
        </div>
        <?php
    }

    /**
     * Re-usable form to edit state of the plugin.
     */
    public static function initialize_plugin_state_metabox() {
        // Set selections
        $options = [
            [
                'key'   => 'combined',
                'label' => __( 'Combined', 'dt_webform' ),
            ],
            [
                'key'   => 'home',
                'label' => __( 'Home', 'dt_webform' ),
            ],
        ];

        // Check if Disciple Tools Theme is present. If not, limit select to remote server.
        $current_theme = get_option( 'current_theme' );
        if ( !( 'Disciple Tools' == $current_theme || 'Disciple Tools Child theme of disciple-tools-theme' == $current_theme )) {
            $options = [
                [
                    'key'   => 'remote',
                    'label' => __( 'Remote', 'dt_webform' ),
                ],
            ];
        }

        // Get current selection
        $state = get_option( 'dt_webform_state' );
        ?>
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
                            foreach ( $options as $option ) {
                                echo '<option value="' . esc_attr( $option['key'] ) . '" ';
                                if ( $option['key'] == $state ) {
                                    echo 'selected';
                                }
                                echo '>' . esc_attr( $option['label'] ) . '</option>';
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
                            Home configuration sets up just the half of the plugin that integrates with Disciple Tools.
                            Choosing this option assumes that you have a remote server running separatedly with the
                            'remote' setting on the plugin configured.
                        </p>
                        <p>
                            <strong>Remote</strong><br>
                            The 'Remote' configuration sets up only the remote webform server. Choosing this option
                            assumes that you have a Disciple Tools server running elsewhere with the Webform plugin
                            installed and configured as 'home'. If Disciple Tools Theme is not installed, Remote will be
                            the only installation option.
                        </p>
                        <p>
                            <strong>Combined</strong><br>
                            The 'Combined' configuration sets up the Webform plugin to run the webform server from the
                            same system as the Disciple Tools System. Choosing this option assumes that you have a
                            remote server running separatedly with the 'remote' setting on the plugin configured.
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
