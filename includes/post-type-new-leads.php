<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly
/**
 * DT_Webform_New_Leads_Post_Type
 *
 * This is a hidden post type for temporarily storing new leads from webforms.
 */

/**
 * Class DT_Webform_New_Leads_Post_Type
 */
DT_Webform_New_Leads_Post_Type::instance(); // Initialize instance
class DT_Webform_New_Leads_Post_Type
{
    public $post_type;
    /**
     * DT_Webform_New_Leads_Post_Type The single instance of DT_Webform_New_Leads_Post_Type.
     *
     * @var    object
     * @access private
     * @since  0.1.0
     */
    private static $_instance = null;

    /**
     * Main DT_Webform_New_Leads_Post_Type Instance
     * Ensures only one instance of DT_Webform_New_Leads_Post_Type is loaded or can be loaded.
     *
     * @since  0.1.0
     * @static
     * @return DT_Webform_New_Leads_Post_Type instance
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    } // End instance()

    public function __construct() {

        $this->post_type = 'dt_webform_new_leads';

        add_action( 'init', [ $this, 'register_post_type' ] );

        add_action( 'save_post', [ $this, 'auto_accept' ], 10, 2 );

    }

    // Register Custom Post Type
    public function register_post_type() {

        $labels = array(
        'name'                  => _x( 'New Lead', 'Post Type General Name', 'dt_webform' ),
        'singular_name'         => _x( 'New Lead', 'Post Type Singular Name', 'dt_webform' ),
        'menu_name'             => __( 'New Leads', 'dt_webform' ),
        'name_admin_bar'        => __( 'New Lead', 'dt_webform' ),
        'archives'              => __( 'New Lead Archives', 'dt_webform' ),
        'attributes'            => __( 'New Lead Attributes', 'dt_webform' ),
        'parent_item_colon'     => __( 'Parent New Lead:', 'dt_webform' ),
        'all_items'             => __( 'All New Leads', 'dt_webform' ),
        'add_new_item'          => __( 'Add New Lead', 'dt_webform' ),
        'add_new'               => __( 'Add New', 'dt_webform' ),
        'new_item'              => __( 'New New Lead', 'dt_webform' ),
        'edit_item'             => __( 'Edit New Lead', 'dt_webform' ),
        'update_item'           => __( 'Update New Lead', 'dt_webform' ),
        'view_item'             => __( 'View New Lead', 'dt_webform' ),
        'view_items'            => __( 'View New Leads', 'dt_webform' ),
        'search_items'          => __( 'Search New Lead', 'dt_webform' ),
        'not_found'             => __( 'Not found', 'dt_webform' ),
        'not_found_in_trash'    => __( 'Not found in Trash', 'dt_webform' ),
        'featured_image'        => __( 'Featured Image', 'dt_webform' ),
        'set_featured_image'    => __( 'Set featured image', 'dt_webform' ),
        'remove_featured_image' => __( 'Remove featured image', 'dt_webform' ),
        'use_featured_image'    => __( 'Use as featured image', 'dt_webform' ),
        'insert_into_item'      => __( 'Insert into item', 'dt_webform' ),
        'uploaded_to_this_item' => __( 'Uploaded to this form', 'dt_webform' ),
        'items_list'            => __( 'New Lead list', 'dt_webform' ),
        'items_list_navigation' => __( 'New Leads list navigation', 'dt_webform' ),
        'filter_items_list'     => __( 'Filter New Lead list', 'dt_webform' ),
        );
        $args = array(
        'label'                 => __( 'New Lead', 'dt_webform' ),
        'description'           => __( 'New Lead', 'dt_webform' ),
        'labels'                => $labels,
        'supports'              => array( 'title' ),
        'hierarchical'          => false,
        'public'                => false,
        'show_ui'               => false,
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
     * Process lead immediately because the options are set to 'auto_approve'
     * @param $post_id
     * @param $post
     */
    public function auto_accept( $post_id, $post ) {
        if ( $post->post_type === $this->post_type ) {
            $options = get_option( 'dt_webform_options' );

            if ( isset( $options['auto_approve'] ) && $options['auto_approve'] ) { // if auto approve is set
                $state = get_option( 'dt_webform_state' );
                if ( 'remote' == $state ) {
                    $selected_records[] = $post_id;
                    DT_Webform_Utilities::trigger_transfer_of_new_leads( $selected_records );
                } else {
                    DT_Webform_Utilities::create_contact_record( $post_id );
                }
            }
        }
    }

    /**
     * Insert Post
     *
     * @return int|\WP_Error
     */
    public static function insert_post( $params ) {

        // Prepare Insert
        $args = [
            'post_type' => 'dt_webform_new_leads',
            'post_title' => sanitize_text_field( wp_unslash( $params['name'] ) ),
            'comment_status' => 'closed',
            'ping_status' => 'closed',
        ];
        dt_write_log(__METHOD__);
        dt_write_log($params);
        foreach ( $params as $key => $value ) {
            $key = sanitize_text_field( wp_unslash( $key ) );
            $value = sanitize_text_field( wp_unslash( $value ) );
            $args['meta_input'][$key] = $value;
        }
        // Add the form title to the record.
        $form_title = DT_Webform_Active_Form_Post_Type::get_form_title_by_token( $params['token'] );
        $args['form_title'] = $form_title;

        // Insert
        $status = wp_insert_post( $args, true );
        return $status;
    }

}

