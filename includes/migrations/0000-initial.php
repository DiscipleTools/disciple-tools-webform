<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Legacy migration placeholder.
 */

require_once( 'abstract.php' );

/**
 * Class DT_Webform_Migration_0000
 */
class DT_Webform_Migration_0000 extends DT_Webform_Migration {

    /**
     * @throws \Exception  Got error when creating table $name.
     */
    public function up() {
        global $wpdb;
        $results = $wpdb->get_col( "SELECT ID from $wpdb->posts WHERE post_type = 'dt_webform_forms'" );

        if ( count( $results ) > 0 ) {
            require_once( plugin_dir_path( __DIR__ ) . '/post-type-active-forms.php' );
            require_once( plugin_dir_path( __DIR__ ) . '/utilities.php' );

            foreach ( $results as $post_id ) {
                $post_id = (int) $post_id;
                // upgrade custom fields

                // get all meta for ID
                $meta  = dt_get_simple_post_meta( $post_id );
                if ( empty( $meta ) ) {
                    continue;
                }

                foreach ( $meta as $key => $value ) {
                    // filter for custom fields
                    if ( substr( $key, 0, 5 ) !== 'field' ) {
                        continue;
                    }

                    // upgrade arrays
                    $value = maybe_unserialize( $value );
                    $new = [
                        'key' => $key,
                        'order' => 1,
                        'required' => $value['required'] ?? 'no',
                        'type' => 'text',
                        'labels' => $value['label'] ?? '',
                        'values' => '',
                        'dt_field' => '',
                    ];

                    // re save new arrays
                    update_post_meta( $post_id, $key, $new );
                }

                // upgrade core fields
                $core_fields = DT_Webform_Active_Form_Post_Type::instance()->get_core_fields( $post_id );
                foreach ( $core_fields as $key => $value ) {
                    if ( ! isset( $meta[$key] ) && empty( $meta[$key] ) ) {
                        update_post_meta( $post_id, $key, $value );
                    }
                }
            }
        }

    }

    /**
     * @throws \Exception  Got error when dropping table $name.
     */
    public function down() {
    }

    /**
     * @return array
     */
    public function get_expected_tables(): array {
        return [];
    }

    /**
     * Test function
     */
    public function test() {
    }

}
