<?php
/**
 * DT_Webform_Active_Form_Post_Type
 *
 * @todo
 * 1. Store details about different types of forms created and their unique customization, including additional collection fields
 *
 */

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
            global $pagenow;

            add_action( 'admin_menu', [ $this, 'meta_box_setup' ], 20 );
            add_action( 'save_post', [ $this, 'meta_box_save' ] );
            add_action( 'admin_head', [ $this, 'scripts' ], 20 );

            if ( $pagenow == 'edit.php' && isset( $_GET['post_type'] ) && esc_attr( sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) ) == $this->post_type ) {
            }
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
        add_meta_box( $this->post_type . '_embed', __( 'Embed Code', 'dt_webform' ), [ $this, 'load_embed_meta_box' ], $this->post_type, 'normal', 'high' );
        add_meta_box( $this->post_type . '_demo', __( 'Demo', 'dt_webform' ), [ $this, 'load_demo_meta_box' ], $this->post_type, 'normal', 'high' );

    }

    /**
     * Load type metabox
     */
    public function load_info_meta_box()
    {
        $this->meta_box_content( 'info' ); // prints
    }


    /**
     * Load embed metabox
     */
    public function load_embed_meta_box( $post )
    {
        global $pagenow;

        if ( 'post-new.php' == $pagenow ) {
            echo 'Embed code will display after you save the new form';
            return;
        }

        $width = get_metadata( 'post', $post->ID, 'width', true );
        $height = get_metadata( 'post', $post->ID, 'height', true );
        $token = get_metadata( 'post', $post->ID, 'token', true );
        $site = dt_webform()->public_uri;

        ?>
        <label for="embed-code">Copy and Paste this embed code</label><br>
        <textarea cols="60" rows="5"><iframe src="<?php echo esc_attr( $site ) ?>form.html?token=<?php echo esc_attr( $token )
            ?>" width="<?php echo esc_attr( $width ) ?>px" height="<?php echo esc_attr( $height ) ?>px"></iframe>

        </textarea>
        <?php
    }

    /**
     * Load demo metabox
     */
    public function load_demo_meta_box( $post )
    {
        global $pagenow;

        if ( 'post-new.php' == $pagenow ) {
            echo 'Embed code will display after you save the new form';
            return;
        }

        $width = get_metadata( 'post', $post->ID, 'width', true );
        $height = get_metadata( 'post', $post->ID, 'height', true );
        $token = get_metadata( 'post', $post->ID, 'token', true );
        $site = dt_webform()->public_uri;

        ?>
        <iframe src="<?php echo esc_attr( $site ) ?>form.html?token=<?php echo esc_attr( $token )
        ?>" width="<?php echo esc_attr( $width ) ?>px" height="<?php echo esc_attr( $height ) ?>px"></iframe>
        <?php
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

        echo '<input type="hidden" name="dt_' . esc_attr( $this->post_type ) . '_noonce" id="dt_' . esc_attr( $this->post_type ) . '_noonce" value="' . esc_attr( wp_create_nonce( 'update_dt_groups' ) ) . '" />';

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
                            echo '<tr valign="top"><th scope="row"><label for="' . esc_attr( $k ) . '">' . esc_attr( $v['name'] ) . '</label></th><td><textarea name="' . esc_attr( $k ) . '" type="text" id="' . esc_attr( $k ) . '" class="regular-text" />' . esc_attr( $data ) . '</textarea>' . "\n";
                            echo '<p class="description">' . esc_html( $v['description'] ) . '</p>' . "\n";
                            echo '</td><tr/>' . "\n";
                            break;
                        case 'display_only':
                            echo '<tr valign="top"><th scope="row"><label for="' . esc_attr( $k ) . '">' . esc_attr( $v['name'] ) . '</label></th><td>' . esc_attr( $data )  . "\n";
                            echo '<input name="' . esc_attr( $k ) . '" type="hidden" id="' . esc_attr( $k ) . '" class="regular-text" value="' . esc_attr( $data ) . '" />';
                            echo '<p class="description">' . esc_html( $v['description'] ) . '</p>' . "\n";
                            echo '</td><tr/>' . "\n";
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
        $nonce_key = 'dt_' . $this->post_type . '_noonce';
        if ( isset( $_POST[ $nonce_key ] ) && !wp_verify_nonce( sanitize_key( $_POST[ $nonce_key ] ), 'update_dt_groups' ) ) {
            return $post_id;
        }

        if ( isset( $_POST['post_type'] ) && 'page' == esc_attr( sanitize_text_field( wp_unslash( $_POST['post_type'] ) ) ) ) {
            if ( !current_user_can( 'edit_page', $post_id ) ) {
                return $post_id;
            }
        } else {
            if ( !current_user_can( 'edit_post', $post_id ) ) {
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
                throw new Exception( "Expected field $f to exist" );
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

        $fields['width'] = [
        'name'        => __( 'Width', 'dt_webform' ),
        'description' => 'pixels',
        'type'        => 'text',
        'default'     => '300',
        'section'     => 'info',
        ];
        $fields['height'] = [
        'name'        => __( 'Height', 'dt_webform' ),
        'description' => 'pixels',
        'type'        => 'text',
        'default'     => '300',
        'section'     => 'info',
        ];
        $fields['theme'] = [
            'name'        => __( 'Theme', 'dt_webform' ),
            'description' => '',
            'type'        => 'key_select',
            'default'     => [
                'simple' => __( 'Simple', 'dt_webform' ),
                'heavy'   => __( 'Heavy', 'dt_webform' ),
            ],
            'section'     => 'info',
        ];
        $fields['token'] = [
        'name'        => __( 'Token', 'dt_webform' ),
        'description' => '',
        'type'        => 'display_only',
        'default'     => DT_Webform_Api_Keys::generate_token( 16 ),
        'section'     => 'info',
        ];



        return apply_filters( 'dt_custom_fields_settings', $fields );
    } // End get_custom_fields_settings()

    public function scripts() {
        global $pagenow;
        if ( get_current_screen()->post_type == $this->post_type ) {
            echo '<script type="text/javascript">
                    jQuery(document).ready( function($) {
                        
                        $("#toplevel_page_dt_webform").addClass("current wp-has-current-submenu wp-menu-open");
                        $("h1.wp-heading-inline").append(\' <a href="'.esc_attr( admin_url() ).'admin.php?page=dt_webform&tab=remote_forms" class="page-title-action">Return to List</a>\');
                    
                    });
                </script>';
        }
        // Catches post delete redirect to standard custom post type list, and redirects to the form list in the plugin.
        if ( $pagenow == 'edit.php' && isset( $_GET['post_type'] ) && esc_attr( sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) ) == $this->post_type ) {
            echo '<script type="text/javascript">
                    window.location.href = "'.esc_attr( admin_url() ).'admin.php?page=dt_webform&tab=remote_forms";
                </script>';
        }
    }



}
DT_Webform_Active_Form_Post_Type::instance();
