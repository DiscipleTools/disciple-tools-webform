<?php
/**
 * DT_Webform_Active_Form_Post_Type
 *
 * @todo
 * 1. Store details about different types of forms created and their unique customization, including additional collection fields
 *
 */

/**
 * Initialize class
 */
DT_Webform_Active_Form_Post_Type::instance();

/**
 * Class DT_Webform_Active_Form_Post_Type
 */
class DT_Webform_Active_Form_Post_Type
{
    public $post_type;
    /**
     * DT_Webform_Active_Form_Post_Type The single instance of DT_Webform_Active_Form_Post_Type.
     *
     * @var    object
     * @access private
     * @since  0.1.0
     */
    private static $_instance = null;

    /**
     * Main DT_Webform_Active_Form_Post_Type Instance
     * Ensures only one instance of DT_Webform_Active_Form_Post_Type is loaded or can be loaded.
     *
     * @since  0.1.0
     * @static
     * @return DT_Webform_Active_Form_Post_Type instance
     */
    public static function instance()
    {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    } // End instance()

    public function __construct()
    {
        $this->post_type = 'dt_webform_forms';

        add_action( 'init', [ $this, 'register_post_type' ] );

        if ( is_admin() ) {

            add_action( 'admin_menu', [ $this, 'meta_box_setup' ], 20 );
            add_action( 'save_post', [ $this, 'meta_box_save' ] );
            add_action( 'save_post', [ $this, 'save_extra_fields' ] );
            add_action( 'admin_head', [ $this, 'scripts' ], 20 );
        }
    }

    // Register Custom Post Type
    public function register_post_type() {

        $labels = array(
        'name'                  => _x( 'Forms', 'Post Type General Name', 'dt_webform' ),
        'singular_name'         => _x( 'Form', 'Post Type Singular Name', 'dt_webform' ),
        'menu_name'             => __( 'Forms', 'dt_webform' ),
        'name_admin_bar'        => __( 'Form', 'dt_webform' ),
        'archives'              => __( 'Form Archives', 'dt_webform' ),
        'attributes'            => __( 'Form Attributes', 'dt_webform' ),
        'parent_item_colon'     => __( 'Parent Form:', 'dt_webform' ),
        'all_items'             => __( 'All Forms', 'dt_webform' ),
        'add_new_item'          => __( 'Add New Form', 'dt_webform' ),
        'add_new'               => __( 'Add New', 'dt_webform' ),
        'new_item'              => __( 'New Form', 'dt_webform' ),
        'edit_item'             => __( 'Edit Form', 'dt_webform' ),
        'update_item'           => __( 'Update Form', 'dt_webform' ),
        'view_item'             => __( 'View Form', 'dt_webform' ),
        'view_items'            => __( 'View Forms', 'dt_webform' ),
        'search_items'          => __( 'Search Form', 'dt_webform' ),
        'not_found'             => __( 'Not found', 'dt_webform' ),
        'not_found_in_trash'    => __( 'Not found in Trash', 'dt_webform' ),
        'featured_image'        => __( 'Featured Image', 'dt_webform' ),
        'set_featured_image'    => __( 'Set featured image', 'dt_webform' ),
        'remove_featured_image' => __( 'Remove featured image', 'dt_webform' ),
        'use_featured_image'    => __( 'Use as featured image', 'dt_webform' ),
        'insert_into_item'      => __( 'Insert into item', 'dt_webform' ),
        'uploaded_to_this_item' => __( 'Uploaded to this form', 'dt_webform' ),
        'items_list'            => __( 'Forms list', 'dt_webform' ),
        'items_list_navigation' => __( 'Forms list navigation', 'dt_webform' ),
        'filter_items_list'     => __( 'Filter forms list', 'dt_webform' ),
        );
        $args = array(
        'label'                 => __( 'Form', 'dt_webform' ),
        'description'           => __( 'DT Webform Forms', 'dt_webform' ),
        'labels'                => $labels,
        'supports'              => array( 'title' ),
        'hierarchical'          => false,
        'public'                => false,
        'show_ui'               => true,
        'show_in_menu'          => false,
        'menu_position'         => 5,
        'show_in_admin_bar'     => false,
        'show_in_nav_menus'     => false,
        'can_export'            => false,
        'has_archive'           => false,
        'exclude_from_search'   => true,
        'publicly_queryable'    => false,
        'capability_type'       => 'page',
        'show_in_rest'          => false,
        );
        register_post_type( $this->post_type, $args );

    }

    /**
     * Setup the meta box.
     *
     * @access public
     * @since  0.1.0
     * @return void
     */
    public function meta_box_setup()
    {
        add_meta_box( $this->post_type . '_info', __( 'Form Details', 'dt_webform' ), [ $this, 'load_info_meta_box' ], $this->post_type, 'normal', 'high' );
        add_meta_box( $this->post_type . '_appearance', __( 'Form Appearance', 'dt_webform' ), [ $this, 'load_appearance_meta_box' ], $this->post_type, 'normal', 'high' );
        add_meta_box( $this->post_type . '_extra_fields', __( 'Extra Fields', 'dt_webform' ), [ $this, 'load_extra_fields_meta_box' ], $this->post_type, 'normal', 'high' );
        add_meta_box( $this->post_type . '_embed', __( 'Embed Code', 'dt_webform' ), [ $this, 'load_embed_meta_box' ], $this->post_type, 'normal', 'low' );
        add_meta_box( $this->post_type . '_demo', __( 'Demo', 'dt_webform' ), [ $this, 'load_demo_meta_box' ], $this->post_type, 'normal', 'low' );
        add_meta_box( $this->post_type . '_statistics', __( 'Statistics', 'dt_webform' ), [ $this, 'load_statistics_meta_box' ], $this->post_type, 'normal', 'low' );

    }

    /**
     * Load type metabox
     */
    public function load_info_meta_box()
    {
        $this->meta_box_content( 'info' ); // prints
    }

    /**
     * Load type metabox
     */
    public function load_appearance_meta_box()
    {
        global $pagenow;

        if ( 'post-new.php' == $pagenow ) {

            echo esc_attr__( 'Leads list will display after you save the new form', 'dt_webform' );
            echo '<div style="display:none;">';
            $this->meta_box_content( 'appearance' ); // prints
            echo '</div>';

        } else {

            $this->meta_box_content( 'appearance' ); // prints

        }
    }



    /**
     * Load type metabox
     */
    public function load_statistics_meta_box( $post )
    {
        global $pagenow;

        if ( 'post-new.php' == $pagenow ) {

            echo esc_attr__( 'Leads list will display after you save the new form', 'dt_webform' );

        } else {

            $received = esc_attr( get_post_meta( $post->ID, 'leads_received', true ) );
            if ( ! $received ) {
                $received = 0;
                update_post_meta( $post->ID, 'leads_received', $received );
            }
            $transferred = esc_attr( get_post_meta( $post->ID, 'leads_transferred', true ) );
            if ( ! $transferred ) {
                $transferred = 0;
                update_post_meta( $post->ID, 'leads_transferred', $transferred );
            }
            echo esc_attr__( 'Leads Received: ', 'dt_webform' ) . esc_attr( $received ) . '<br> ';
            echo esc_attr__( 'Leads Transferred: ', 'dt_webform' ) . esc_attr( $transferred ) . '<br> ';

        }
    }

    /**
     * Load type metabox
     */
    public function load_new_leads_meta_box( $post )
    {
        global $pagenow;

        if ( 'post-new.php' == $pagenow ) {
            echo esc_attr__( 'Leads list will display after you save the new form', 'dt_webform' );
        } else {
            // table of waiting leads
            $token = get_post_meta( $post->ID, 'token', true );
            $args = [
            'post_type' => 'dt_webform_new_leads',
            'meta_value' => $token,
            ];
            $results = new WP_Query( $args );

            if ( $results->found_posts > 0 && ! is_wp_error( $results ) ) {

                echo '<table class="widefat striped">';
                echo '<tr><td>Name</td><td>Phone</td><td>Email</td><td>Date</td></tr>';
                foreach ( $results->posts as $record ) {

                    echo '<tr>';
                    echo '<td>' . esc_attr( get_post_meta( $record->ID, 'name', true ) ) . '</td>';
                    echo '<td>' . esc_attr( get_post_meta( $record->ID, 'phone', true ) ) . '</td>';
                    echo '<td>' . esc_attr( get_post_meta( $record->ID, 'email', true ) ) . '</td>';
                    echo '<td>' . esc_attr( $record->post_date ) . '</td>';
                    echo '</tr>';

                }
                echo '</table>';

                echo '<p ><a href="">refresh</a></p>';

            } else {
                echo esc_attr__( 'No leads found', 'dt_webform' );
            }
        }
    }


    /**
     * Load embed metabox
     */
    public function load_embed_meta_box( $post )
    {
        global $pagenow;

        if ( 'post-new.php' == $pagenow ) {
            echo esc_attr__( 'Embed code will display after you save the new form', 'dt_webform' );
        }
        else {
            $width = get_metadata( 'post', $post->ID, 'width', true );
            $height = get_metadata( 'post', $post->ID, 'height', true );
            $token = get_metadata( 'post', $post->ID, 'token', true );
            $site = dt_webform()->public_uri;

            ?>
            <label for="embed-code">Copy and Paste this embed code</label><br>
            <textarea cols="60" rows="5"><iframe src="<?php echo esc_attr( $site ) ?>form.php?token=<?php echo esc_attr( $token )
                ?>" width="<?php echo esc_attr( $width ) ?>px" height="<?php echo esc_attr( $height ) ?>px"></iframe>

        </textarea>
            <?php
        }
    }

    /**
     * Load demo metabox
     */
    public function load_demo_meta_box( $post )
    {
        global $pagenow;

        if ( 'post-new.php' == $pagenow ) {
            echo esc_attr__( 'Embed code will display after you save the new form', 'dt_webform' );
        }
        else {
            $width = get_metadata( 'post', $post->ID, 'width', true );
            $height = get_metadata( 'post', $post->ID, 'height', true );
            $token = get_metadata( 'post', $post->ID, 'token', true );
            $site = dt_webform()->public_uri;

            ?>
            <iframe src="<?php echo esc_attr( $site ) ?>form.php?token=<?php echo esc_attr( $token )
            ?>" width="<?php echo esc_attr( $width ) ?>px" height="<?php echo esc_attr( $height ) ?>px"></iframe>
            <?php
        }
    }


    /**
     * The contents of our meta box.
     *
     * @param string $section
     */
    public function meta_box_content( $section = 'info' )
    {
        global $post_id;
        $fields = get_post_custom( $post_id );
        $field_data = $this->get_custom_fields_settings();

        echo '<input type="hidden" name="' . esc_attr( $this->post_type ) . '_noonce" id="' . esc_attr( $this->post_type ) . '_noonce" value="' . esc_attr( wp_create_nonce( 'update_dt_webforms' ) ) . '" />';

        if ( 0 < count( $field_data ) ) {
            echo '<table class="form-table">' . "\n";
            echo '<tbody>' . "\n";

            foreach ( $field_data as $k => $v ) {

                if ( $v['section'] == $section || $section == 'all' ) {

                    $data = $v['default'];

                    if ( isset( $fields[ $k ] ) && isset( $fields[ $k ][0] ) ) {
                        $data = $fields[ $k ][0];
                    }

                    $type = $v['type'];

                    switch ( $type ) {

                        case 'text':
                            echo '<tr valign="top"><th scope="row"><label for="' . esc_attr( $k ) . '">' . esc_attr( $v['name'] ) . '</label></th><td><input name="' . esc_attr( $k ) . '" type="text" id="' . esc_attr( $k ) . '" class="regular-text" value="' . esc_attr( $data ) . '" />' . "\n";
                            echo '<p class="description">' . esc_html( $v['description'] ) . '</p>' . "\n";
                            echo '</td><tr/>' . "\n";
                            break;
                        case 'textarea':
                            echo '<tr valign="top"><th scope="row"><label for="' . esc_attr( $k ) . '">' . esc_attr( $v['name'] ) . '</label></th><td><textarea name="' . esc_attr( $k ) . '" type="text" id="' . esc_attr( $k ) . '" class="regular-text" rows="5" />' . esc_attr( $data ) . '</textarea>' . "\n";
                            echo '<p class="description">' . esc_html( $v['description'] ) . '</p>' . "\n";
                            echo '</td><tr/>' . "\n";
                            break;
                        case 'display_only':
                            echo '<tr valign="top"><th scope="row"><label for="' . esc_attr( $k ) . '">' . esc_attr( $v['name'] ) . '</label></th><td>' . esc_attr( $data )  . "\n";
                            echo '<input name="' . esc_attr( $k ) . '" type="hidden" id="' . esc_attr( $k ) . '" class="regular-text" value="' . esc_attr( $data ) . '" />';
                            echo '<p class="description">' . esc_html( $v['description'] ) . '</p>' . "\n";
                            echo '</td><tr/>' . "\n";
                            break;
                        case 'hidden':
//                            echo '<input name="' . esc_attr( $k ) . '" type="hidden" id="' . esc_attr( $k ) . '" class="regular-text" value="' . esc_attr( $data ) . '" />';
                            break;
                        case 'date':
                            echo '<tr valign="top"><th scope="row"><label for="' . esc_attr( $k ) . '">' . esc_attr( $v['name'] ) . '</label></th><td>
                                    <input name="' . esc_attr( $k ) . '" class="datepicker regular-text" type="text" id="' . esc_attr( $k ) . '"  value="' . esc_attr( $data ) . '" />' . "\n";
                            echo '<p class="description">' . esc_html( $v['description'] ) . '</p>' . "\n";
                            echo '</td><tr/>' . "\n";

                            break;
                        case 'key_select':
                            echo '<tr class="' . esc_attr( $v['section'] ) . '" id="row_' . esc_attr( $k ) . '" valign="top"><th scope="row">
                                <label for="' . esc_attr( $k ) . '">' . esc_attr( $v['name'] ) . '</label></th>
                                <td>
                                <select name="' . esc_attr( $k ) . '" id="' . esc_attr( $k ) . '" class="regular-text">';
                            // Iterate the options
                            foreach ( $v['default'] as $kk => $vv ) {
                                echo '<option value="' . esc_attr( $kk ) . '" ';
                                if ( $kk == $data ) {
                                    echo 'selected';
                                }
                                echo '>' . esc_attr( $vv ) . '</option>';
                            }
                            echo '</select>' . "\n";
                            echo '<p class="description">' . esc_attr( $v['description'] ) . '</p>' . "\n";
                            echo '</td><tr/>' . "\n";
                            break;

                        default:
                            break;
                    }
                }
            }

            echo '</tbody>' . "\n";
            echo '</table>' . "\n";
        }
    } // End meta_box_content()

    /**
     * Save meta box fields.
     *
     * @param int $post_id
     *
     * @return int
     * @throws \Exception 'Expected field to exist'.
     */
    public function meta_box_save( int $post_id )
    {

        // Verify
        if ( get_post_type() != $this->post_type ) {
            return $post_id;
        }
        $nonce_key = $this->post_type . '_noonce';
        if ( isset( $_POST[ $nonce_key ] ) && !wp_verify_nonce( sanitize_key( $_POST[ $nonce_key ] ), 'update_dt_webforms' ) ) {
            return $post_id;
        }

        if ( isset( $_POST['post_type'] ) && 'page' == esc_attr( sanitize_text_field( wp_unslash( $_POST['post_type'] ) ) ) ) {
            if ( !current_user_can( 'manage_dt', $post_id ) ) {
                return $post_id;
            }
        } else {
            if ( !current_user_can( 'manage_dt', $post_id ) ) {
                return $post_id;
            }
        }

        if ( isset( $_GET['action'] ) ) {
            if ( $_GET['action'] == 'trash' || $_GET['action'] == 'untrash' || $_GET['action'] == 'delete' ) {
                return $post_id;
            }
        }

        $field_data = $this->get_custom_fields_settings();
        $fields = array_keys( $field_data );

        foreach ( $fields as $f ) {

            if ( isset( $_POST[ $f ] ) ) {

                ${$f} = strip_tags( trim( sanitize_text_field( wp_unslash( $_POST[ $f ] ) ) ) );

                if ( get_post_meta( $post_id, $f ) == '' ) {
                    add_post_meta( $post_id, $f, ${$f}, true );
                } elseif ( ${$f} == '' ) {
                    delete_post_meta( $post_id, $f, get_post_meta( $post_id, $f, true ) );
                } elseif ( ${$f} != get_post_meta( $post_id, $f, true ) ) {
                    update_post_meta( $post_id, $f, ${$f} );
                }
            } else {
                dt_write_log( "Expected field $f to exist" );
            }
        }

        return $post_id;
    } // End meta_box_save()

    /**
     * Get the settings for the custom fields.
     *
     * @return mixed
     */
    public function get_custom_fields_settings()
    {

        $fields = [];

        $fields['description'] = [
        'name'        => __( 'Description', 'dt_webform' ),
        'description' => '',
        'type'        => 'textarea',
        'default'     => '',
        'section'     => 'info',
        ];
        $fields['token'] = [
        'name'        => __( 'Token', 'dt_webform' ),
        'description' => '',
        'type'        => 'display_only',
        'default'     => DT_Webform_Api_Keys::generate_token( 16 ),
        'section'     => 'info',
        ];

        $fields['title'] = [
        'name'        => __( 'Title', 'dt_webform' ),
        'description' => '',
        'type'        => 'text',
        'default'     => __( 'Contact Us', 'dt_webform' ),
        'section'     => 'appearance',
        ];
        $fields['comments_title'] = [
        'name'        => __( 'Comment Title', 'dt_webform' ),
        'description' => '',
        'type'        => 'text',
        'default'     => __( 'Comments', 'dt_webform' ),
        'section'     => 'appearance',
        ];
        $fields['hidden_input'] = [
        'name'        => __( 'Hidden Input', 'dt_webform' ),
        'description' => __( 'This is a hidden input that will be submitted with the form and stored as a note in the contact. Useful for tags.', 'dt_webform' ),
        'type'        => 'text',
        'default'     => '',
        'section'     => 'appearance',
        ];
        $fields['width'] = [
        'name'        => __( 'Width', 'dt_webform' ),
        'description' => __( 'number of pixels', 'dt_webform' ),
        'type'        => 'text',
        'default'     => '250',
        'section'     => 'appearance',
        ];
        $fields['height'] = [
        'name'        => __( 'Height', 'dt_webform' ),
        'description' => __( 'number of pixels', 'dt_webform' ),
        'type'        => 'text',
        'default'     => '475',
        'section'     => 'appearance',
        ];
        $fields['theme'] = [
            'name'        => __( 'Theme', 'dt_webform' ),
            'description' => '',
            'type'        => 'key_select',
            'default'     => [
                'simple' => __( 'Simple', 'dt_webform' ),
                'heavy'   => __( 'Heavy', 'dt_webform' ),
                'none'   => __( 'None', 'dt_webform' ),
            ],
            'section'     => 'appearance',
        ];
        $fields['custom_css'] = [
            'name'        => __( 'Custom CSS', 'dt_webform' ),
            'description' => '#contact-form {}
                    .section {}
                    #name {}
                    #phone {}
                    #email {}
                    #comments {}
                    input.input-text {}
                    button.submit-button {}
                    p.title {}
                    label.error {}
                    .input-label {}',
            'type'        => 'textarea',
            'default'     => '',
            'section'     => 'appearance',
        ];


        return apply_filters( 'dt_custom_fields_settings', $fields );
    } // End get_custom_fields_settings()

    public function scripts() {
        global $pagenow;

        if ( get_current_screen()->post_type == $this->post_type ) {
            $state = get_option( 'dt_webform_state' );
            $label = esc_attr__( 'Return to List', 'dt_webform' );

            switch ( $state ) {
                case 'home':
                case 'combined':
                    echo '<script type="text/javascript">
                            jQuery(document).ready( function($) {
                                
                                jQuery("#toplevel_page_dt_extensions").addClass("wp-has-current-submenu wp-menu-open");
                                jQuery("li:contains(\'Webform\')").addClass("current");
                                $("h1.wp-heading-inline").append(\' <a href="'.esc_attr( admin_url() ).'admin.php?page=dt_webform&tab=remote_forms" class="page-title-action">' . esc_attr( $label ) . '</a>\');
                            
                            });
                        </script>';
                    break;
                default: // covers remote and unset states
                    echo '<script type="text/javascript">
                            jQuery(document).ready( function($) {
                                
                                $("#toplevel_page_dt_webform").addClass("current wp-has-current-submenu wp-menu-open");
                                $("h1.wp-heading-inline").append(\' <a href="'.esc_attr( admin_url() ).'admin.php?page=dt_webform&tab=remote_forms" class="page-title-action">' . esc_attr( $label ) . '</a>\');
                            
                            });
                        </script>';
                    break;
            }
        }
        // Catches post delete redirect to standard custom post type list, and redirects to the form list in the plugin.
        if ( $pagenow == 'edit.php' && isset( $_GET['post_type'] ) && esc_attr( sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) ) == $this->post_type ) {
            echo '<script type="text/javascript">
                    window.location.href = "'.esc_attr( admin_url() ).'admin.php?page=dt_webform&tab=remote_forms";
                </script>';
        }
    }

    public static function increment_lead_received( $form_id ) {
        $current_number = get_post_meta( $form_id, 'leads_received', true );
        if ( ! $current_number ) {
            $current_number = 0;
        }
        $current_number++;
        update_post_meta( $form_id, 'leads_received', (int) $current_number );
    }

    public static function increment_lead_transferred( $form_id ) {
        $current_number = get_post_meta( $form_id, 'leads_transferred', true );
        if ( ! $current_number ) {
            $current_number = 0;
        }
        $current_number++;
        update_post_meta( $form_id, 'leads_transferred', (int) $current_number );
    }

    public static function check_if_valid_token( $token ) {
        $form_object = new WP_Query( [
            'post_type' => 'dt_webform_forms',
            'meta_key' => 'token',
            'meta_value' => $token,
            'post_status' => 'draft, publish',
        ] );
        if ( is_wp_error( $form_object ) || $form_object->found_posts < 1 ) {
            return false;
        }
        return $form_object->post->ID;
    }

    public static function get_form_title_by_token( $token ) {
        $results = new WP_Query( [
            'post_type' => 'dt_webform_forms',
            'meta_value' => $token
        ] );
        if ( $results->found_posts < 0) {
            return __( 'Unknown', 'dt_webform' );
        }
        return $results->post->post_title;
    }

    public static function get_form_id_by_token( $token ) {
        $results = new WP_Query( [
            'post_type' => 'dt_webform_forms',
            'meta_value' => $token
        ] );
        if ( $results->found_posts < 0) {
            return __( 'Unknown', 'dt_webform' );
        }
        return $results->post->ID;
    }

    public static function get_extra_fields( $token ) {
        $post_id = self::get_form_id_by_token( $token );
        $fields = dt_get_simple_post_meta( $post_id );
        return self::filter_for_custom_fields( $fields );
    }

    /**
     * Load type metabox
     */
    public function load_extra_fields_meta_box( $post )
    {
        global $pagenow;

        if ( 'post-new.php' == $pagenow ) {

            echo esc_attr__( 'Extra fields will display after you save the new form', 'dt_webform' );

        } else {

            $unique_key = bin2hex( random_bytes( 10 ) );
            $fields = dt_get_simple_post_meta( $post->ID );
            $custom_fields = self::filter_for_custom_fields( $fields );

            if ( ! empty( $custom_fields ) ) {
                echo esc_attr__( 'Fields: Label, Type of Field, Is the Field Required?', 'dt_webform' );
                foreach ( $custom_fields as $key => $value ) {
                    $value = maybe_unserialize( $value );
                    ?>
                    <p id="<?php echo esc_attr( $key ) ?>">
                        <input type="hidden" name="<?php echo esc_attr( $key ) ?>[key]" placeholder="key"
                               value="<?php echo esc_attr( $value['key'] ) ?>" readonly/>&nbsp;
                        <input type="text" name="<?php echo esc_attr( $key ) ?>[label]" placeholder="label"
                               value="<?php echo esc_attr( $value['label'] ) ?>" required/>&nbsp;
                        <select name="<?php echo esc_attr( $key ) ?>[type]">
                            <option disabled><?php echo esc_attr__( 'Field Type', 'dt_webform' ) ?></option>
                            <option value="<?php echo esc_attr( $value['type'] ) ?>"><?php echo esc_attr( ucwords( $value['type'] ) ) ?></option>
                            <option disabled>---</option>
                            <option value="text"><?php echo esc_attr__( 'Text', 'dt_webform' ) ?></option>
                            <option value="tel"><?php echo esc_attr__( 'Phone', 'dt_webform' ) ?></option>
                            <option value="email"><?php echo esc_attr__( 'Email', 'dt_webform' ) ?></option>
                        </select>&nbsp;
                        <select name="<?php echo esc_attr( $key ) ?>[required]">
                            <option disabled><?php echo esc_attr__( 'Required', 'dt_webform' ) ?></option>
                            <option value="<?php echo esc_attr( $value['required'] ) ?>"><?php echo esc_attr( ucwords( $value['required'] ) ) ?></option>
                            <option disabled>---</option>
                            <option value="no"><?php echo esc_attr__( 'No', 'dt_webform' ) ?></option>
                            <option value="yes"><?php echo esc_attr__( 'Yes', 'dt_webform' ) ?></option>
                        </select>&nbsp;
                        <button type="submit"><?php echo esc_attr__( 'Update', 'dt_webform' ) ?></button>
                        <button name="<?php echo esc_attr( $key ) ?>" onclick="remove_add_custom_fields(<?php echo esc_attr( $key ) ?>)"
                                value=""><?php echo esc_attr__( 'Delete', 'dt_webform' ) ?>
                        </button>
                    </p>
                    <?php
                }
            }
            ?>


            <div id="new-fields"></div>

            <p>
                <button type="submit" class="button" onclick="add_new_custom_fields()">Add</button>
            </p>
            <script>
                function add_new_custom_fields() {
                    jQuery('#new-fields').html('<p><hr>\n' +
                        '                <input type="hidden" name="field_<?php echo esc_attr( $unique_key ) ?>[key]" placeholder="key" value="new"/>\n' +
                        '                <input type="text" name="field_<?php echo esc_attr( $unique_key ) ?>[label]" placeholder="label" required/>&nbsp;\n' +
                        '                <select name="field_<?php echo esc_attr( $unique_key ) ?>[type]">\n' +
                        '                        <option value="text"><?php echo esc_attr__( 'Field Type', 'dt_webform' ) ?></option>\n' +
                        '                        <option readonly>---</option>\n' +
                        '                        <option value="text"><?php echo esc_attr__( 'Text', 'dt_webform' ) ?></option>\n' +
                        '                        <option value="tel"><?php echo esc_attr__( 'Phone', 'dt_webform' ) ?></option>\n' +
                        '                        <option value="email"><?php echo esc_attr__( 'Email', 'dt_webform' ) ?></option>\n' +
                        '                </select>&nbsp;\n' +
                        '                <select name="field_<?php echo esc_attr( $unique_key ) ?>[required]">\n' +
                        '                        <option value="no"><?php echo esc_attr__( 'Required', 'dt_webform' ) ?></option>\n' +
                        '                        <option readonly>---</option>\n' +
                        '                        <option value="no"><?php echo esc_attr__( 'No', 'dt_webform' ) ?></option>\n' +
                        '                        <option value="yes"><?php echo esc_attr__( 'Yes', 'dt_webform' ) ?></option>\n' +
                        '                </select>&nbsp;\n' +
                        '               <button type="submit"><?php echo esc_attr__( 'Save', 'dt_webform' ) ?></button>' +
                        '               <button onclick="remove_new_custom_fields()"><?php echo esc_attr__( 'Delete', 'dt_webform' ) ?></button>' +
                        '            </p>')
                }

                function remove_new_custom_fields() {
                    jQuery('#new-fields').empty()
                }

                function remove_add_custom_fields(id) {
                    jQuery('#' + id).empty().submit()
                }
            </script>
            <?php
        }
    }

    public function save_extra_fields( $post_id ) {

        // fail process early
        if ( get_post_type() != $this->post_type ) {
            return $post_id;
        }
        $nonce_key = $this->post_type . '_noonce';
        if ( isset( $_POST[ $nonce_key ] ) && !wp_verify_nonce( sanitize_key( $_POST[ $nonce_key ] ), 'update_dt_webforms' ) ) {
            return $post_id;
        }
        if ( !current_user_can( 'manage_dt', $post_id ) ) {
            return $post_id;
        }
        if ( isset( $_GET['action'] ) ) {
            if ( $_GET['action'] == 'trash' || $_GET['action'] == 'untrash' || $_GET['action'] == 'delete' ) {
                return $post_id;
            }
        }

        $array = self::filter_for_custom_fields( $_POST );

        foreach ( $array as $key => $value ) {

            if ( ! get_post_meta( $post_id, $key ) ) {

                if ( !isset( $value['label'] ) || !isset( $value['type'] ) || !isset( $value['required'] ) ) {
                    break;
                }

                // create the key from the label
                $value['label'] = trim( $value['label'] ); // trim string
                $value['key'] = 'cf_' . sanitize_key( str_replace( ' ', '_', $value['label'] ) ); // build key

                add_post_meta( $post_id, $key, $value, true );
            } elseif ( $value == '' ) {
                delete_post_meta( $post_id, $key, get_post_meta( $post_id, $key, true ) );
            } elseif ( $value != get_post_meta( $post_id, $key, true ) ) {

                if ( ! isset( $value['label'] ) || ! isset( $value['type'] ) || ! isset( $value['required'] ) ) {
                    break;
                }

                // update the key if the label is updated
                $value['label'] = trim( $value['label'] ); // trim string
                $value['key'] = 'cf_' . sanitize_key( str_replace( ' ', '_', $value['label'] ) ); // build key

                update_post_meta( $post_id, $key, $value );
            }
        }
        return $post_id;
    }

    public static function filter_for_custom_fields( $array ) {
        return array_filter( $array, function( $key) {
            return strpos( $key, 'field_' ) === 0;
        }, ARRAY_FILTER_USE_KEY );
    }
}