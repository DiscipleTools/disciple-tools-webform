<?php
/**
 * Collection of various utilities
 */

class DT_Webform_Remote
{
    public static function site_link_metabox()
    {
        $prefix = 'dt_webform_site';
        $keys = DT_Webform_Api_Keys::update_keys( $prefix );

        foreach ( $keys as $key ) {
            $home_link = $key;
        } // end foreach

        ?>
        <form method="post" action="">
            <?php wp_nonce_field( $prefix . '_action', $prefix . '_nonce' ); ?>
            <table class="widefat striped">
                <thead>
                <tr>
                    <td colspan="2">
                        <?php esc_html_e( 'Link to Remote Site to Home', 'dt_webform' ) ?>
                    </td>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td style="max-width:20px;">
                        <label for="id"><?php esc_html_e( 'ID', 'dt_webform' ) ?></label>
                    </td>
                    <td>
                        <input type="text" name="id" id="id" class="regular-text"
                               value="<?php echo ( isset( $home_link['id'] ) ) ? esc_attr( $home_link['id'] ) : '' ?>" required/>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="token"><?php esc_html_e( 'Token', 'dt_webform' ) ?></label>
                    </td>
                    <td>
                        <input type="text" name="token" id="token" class="regular-text"
                               value="<?php echo ( isset( $home_link['token'] ) ) ? esc_attr( $home_link['token'] ) : '' ?>" required/>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="url"><?php esc_html_e( 'Home URL', 'dt_webform' ) ?></label>
                    </td>
                    <td>
                        <input type="text" name="url" id="url" class="regular-text" placeholder="http://www.website.com"
                               value="<?php echo ( isset( $home_link['url'] ) ) ? esc_attr( $home_link['url'] ) : '' ?>" required/>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <button type="submit" class="button" name="action" value="update"><?php esc_html_e( 'Update', 'dt_webform' ) ?></button>
                        <span class="float-right">
                            <button type="submit" class="button-like-link" name="action" value="delete"><?php esc_html_e( 'Unlink Site', 'dt_webform' ) ?></button>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <span style="float:right">
                                <?php esc_html_e( 'Status:', 'dt_webform' ) ?>
                            <strong>
                                    <span id="<?php echo esc_attr( $key['id'] ); ?>-status">
                                        <?php esc_html_e( 'Checking Status', 'dt_webform' ) ?>
                                    </span>
                                </strong>
                            </span>
                        <script>
                            jQuery(document).ready(function() {
                                check_link_status( '<?php echo esc_attr( $key['id'] ); ?>', '<?php echo esc_attr( $key['token'] ); ?>', '<?php echo esc_attr( $key['url'] ); ?>' );
                            })
                        </script>
                    </td>
                </tr>
                </tbody>
            </table>
        </form>
        <br>
        <?php
    }


}


/**
 * DT_Webform_Forms_List
 * @see https://wordpress.org/plugins/custom-list-table-example/
 */
if ( !class_exists( 'WP_List_Table' )){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
class DT_Webform_Forms_List extends WP_List_Table {

    /**
     * Call this public function to embed the list on a page: DT_Webform_Forms_List::forms_list_box
     */
    public static function list_box() {
        $list_table = new DT_Webform_Forms_List();
        $list_table->prepare_items();

        ?>
        <div class="wrap">

            <a href="<?php echo esc_html( admin_url() ) ?>post-new.php?post_type=dt_webform_forms" class="page-title-action">Add New Form</a>

            <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
            <form id="movies-filter" method="get">
                <input type="hidden" name="page" value="<?php echo ( isset( $_REQUEST['page'] ) ) ? esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) ) : '' ?>" />
                <?php $list_table->display() ?>
            </form>

        </div>
        <?php
    }

    public function __construct(){
        global $status, $page;

        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'form',     //singular name of the listed records
            'plural'    => 'forms',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );

    }

    public function column_default( $item, $column_name){
        switch ($column_name){
            case 'name':
                //Build row actions
                $actions = array(
                'edit'      => sprintf( '<a href="%spost.php?post=%s&action=%s">Edit</a>', admin_url(), $item->ID, 'edit' ),
                'embed'    => sprintf( '<a href="#" onclick="jQuery(\'#embed-%s\').toggle();">Show Embed Form</a>', $item->ID ),
                );

                //Return the title contents
                return sprintf('<strong><a href="%spost.php?post=%s&action=%s">%s</a></strong> %s',
                    admin_url(),
                    $item->ID,
                    'edit',
                    $item->post_title,
                    $this->row_actions( $actions )
                );
            case 'description':
                return get_metadata( 'post', $item->ID, 'description', true );
            case 'embed':
                $width = get_metadata( 'post', $item->ID, 'width', true );
                $height = get_metadata( 'post', $item->ID, 'height', true );
                $token = get_metadata( 'post', $item->ID, 'token', true );
                $site = dt_webform()->public_uri;

                $code = '<textarea cols="30" rows="7" id="embed-'.$item->ID.'" style="display:none;"><iframe src="'. esc_attr( $site ) .'form.html?token='. esc_attr( $token )
                    .'" width="'. esc_attr( $width ) .'px" height="'. esc_attr( $height ) .'px"></iframe></textarea></span>';

                return $code;
            case 'if_coloumn_name_is_field_name':
                return $item->{$column_name};
            default:
                return print_r( $item, true ); //Show the whole array for troubleshooting purposes
        }
    }


    public function column_cb( $item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label
            /*$2%s*/ $item->ID                //The value of the checkbox should be the record's id
        );
    }

    public function get_columns(){
        $columns = array(
        'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
        'name'     => 'Name',
        'description' => 'Description',
        'embed' => '',
        );
        return $columns;
    }

    public function get_sortable_columns() {
        $sortable_columns = array(
        'name'     => array( 'name',false ),     //true means it's already sorted
        );
        return $sortable_columns;
    }

    public function get_bulk_actions() {
        $actions = array(
//        'delete'    => 'Delete'
        );
        return $actions;
    }

    public function process_bulk_action() {

        //Detect when a bulk action is being triggered...
        if ( 'delete' ===$this->current_action() ) {
            wp_die( 'Items deleted (or they would be if we had items to delete)!' );
        }

    }

    public function prepare_items() {

        global $wpdb;

        $per_page = 10;
        $order = ( !empty( $_REQUEST['order'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : 'asc'; //If no order, default to asc
        $paged = ( !empty( $_REQUEST['paged'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['paged'] ) ) : '1'; //If no order, default to asc

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array( $columns, $hidden, $sortable );
        $this->process_bulk_action();

        $data = new WP_Query(
            [
                'post_type' => 'dt_webform_forms',
                'posts_per_page' => $per_page,
                'order' => $order,
                'paged' => $paged,
            ]
        );

        $total_items = $data->found_posts;
        $this->items = $data->posts;


        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
            'total_pages' => ceil( $total_items /$per_page )   //WE have to calculate the total number of pages
        ) );
    }


}

class DT_Webform_New_Leads_List extends WP_List_Table {

    /**
     * Call this public function to embed the list on a page: DT_Webform_Forms_List::forms_list_box
     */
    public static function list_box() {
        $list_table = new DT_Webform_New_Leads_List();
        $list_table->prepare_items();

        ?>
        <div class="wrap">

            <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
            <form id="movies-filter" method="get">
                <input type="hidden" name="page" value="<?php echo ( isset( $_REQUEST['page'] ) ) ? esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) ) : '' ?>" />
                <?php $list_table->display() ?>
            </form>

        </div>
        <?php
    }

    public function __construct(){
        global $status, $page;

        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'form',     //singular name of the listed records
            'plural'    => 'forms',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );

    }

    public function column_default( $item, $column_name){
        switch ($column_name){
            case 'name':
                return $item->post_title;
            case 'data':
                $meta = get_metadata( 'post', $item->ID );
                dt_write_log( $meta ); // @todo add loop and convert array into screen.

                return print_r( $meta );

            case 'if_coloumn_name_is_field_name':
                return $item->{$column_name};
            default:
                return print_r( $item, true ); //Show the whole array for troubleshooting purposes
        }
    }


    public function column_cb( $item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label
            /*$2%s*/ $item->ID                //The value of the checkbox should be the record's id
        );
    }

    public function get_columns(){
        $columns = array(
        'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
        'name'     => 'Name',
        'data' => 'Data',
        );
        return $columns;
    }

    public function get_sortable_columns() {
        $sortable_columns = array(
        'name'     => array( 'name',false ),     //true means it's already sorted
        );
        return $sortable_columns;
    }

    public function get_bulk_actions() {
        $actions = array(
             'delete'    => 'Delete',
             'approve' => __( 'Approve', 'dt_webform' ),
        );
        return $actions;
    }

    public function process_bulk_action() {

        //Detect when a bulk action is being triggered...
        if ( 'delete' ===$this->current_action() ) {
            wp_die( 'Items deleted (or they would be if we had items to delete)!' );
        }

    }

    public function prepare_items() {

        global $wpdb;

        $per_page = 10;
        $order = ( !empty( $_REQUEST['order'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : 'asc'; //If no order, default to asc
        $paged = ( !empty( $_REQUEST['paged'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['paged'] ) ) : '1'; //If no order, default to asc

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array( $columns, $hidden, $sortable );
        $this->process_bulk_action();

        $data = new WP_Query(
            [
            'post_type' => 'dt_webform_new_leads',
            'posts_per_page' => $per_page,
            'order' => $order,
            'paged' => $paged,
            ]
        );

        $total_items = $data->found_posts;
        $this->items = $data->posts;


        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
            'total_pages' => ceil( $total_items /$per_page )   //WE have to calculate the total number of pages
        ) );
    }


}
