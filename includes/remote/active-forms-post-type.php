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
        add_action( 'init', [ $this, 'register_post_type' ] );
    }

    // Register Custom Post Type
    public function register_post_type() {

        $labels = array(
        'name'                  => _x( 'Forms', 'Post Type General Name', 'dt_webform' ),
        'singular_name'         => _x( 'Form', 'Post Type Singular Name', 'dt_webform' ),
        'menu_name'             => __( 'Forms', 'dt_webform' ),
        'name_admin_bar'        => __( 'Form', 'dt_webform' ),
        'archives'              => __( 'Item Archives', 'dt_webform' ),
        'attributes'            => __( 'Item Attributes', 'dt_webform' ),
        'parent_item_colon'     => __( 'Parent Item:', 'dt_webform' ),
        'all_items'             => __( 'All Items', 'dt_webform' ),
        'add_new_item'          => __( 'Add New Item', 'dt_webform' ),
        'add_new'               => __( 'Add New', 'dt_webform' ),
        'new_item'              => __( 'New Item', 'dt_webform' ),
        'edit_item'             => __( 'Edit Item', 'dt_webform' ),
        'update_item'           => __( 'Update Item', 'dt_webform' ),
        'view_item'             => __( 'View Item', 'dt_webform' ),
        'view_items'            => __( 'View Items', 'dt_webform' ),
        'search_items'          => __( 'Search Item', 'dt_webform' ),
        'not_found'             => __( 'Not found', 'dt_webform' ),
        'not_found_in_trash'    => __( 'Not found in Trash', 'dt_webform' ),
        'featured_image'        => __( 'Featured Image', 'dt_webform' ),
        'set_featured_image'    => __( 'Set featured image', 'dt_webform' ),
        'remove_featured_image' => __( 'Remove featured image', 'dt_webform' ),
        'use_featured_image'    => __( 'Use as featured image', 'dt_webform' ),
        'insert_into_item'      => __( 'Insert into item', 'dt_webform' ),
        'uploaded_to_this_item' => __( 'Uploaded to this item', 'dt_webform' ),
        'items_list'            => __( 'Items list', 'dt_webform' ),
        'items_list_navigation' => __( 'Items list navigation', 'dt_webform' ),
        'filter_items_list'     => __( 'Filter items list', 'dt_webform' ),
        );
        $args = array(
        'label'                 => __( 'Form', 'dt_webform' ),
        'description'           => __( 'DT Webform Forms', 'dt_webform' ),
        'labels'                => $labels,
        'supports'              => array( 'title', 'custom-fields' ),
        'hierarchical'          => false,
        'public'                => false,
        'show_ui'               => true,
        'show_in_menu'          => true,
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
        register_post_type( 'dt_webform_forms', $args );

    }
}
DT_Webform_Active_Form_Post_Type::instance();