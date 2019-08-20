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

        $results = new WP_Query( [
            'post_type' => 'dt_webform_forms',
            'posts_per_page' => -1,
            'nopaging' => true
        ] );

        if ( $results->found_posts > 0 ) {
            foreach ( $results->posts as $item ) {
                // upgrade custom fields

                // get all meta for ID
                $meta = dt_get_simple_post_meta( $item->ID );
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
                    update_post_meta( $item->ID, $key, $new );
                }

                // upgrade core fields
                require_once ( '../../post-type-active-forms.php' );
                $core_fields = DT_Webform_Active_Form_Post_Type::instance()->get_core_fields( $item->ID );
                foreach( $core_fields as $key => $value ) {
                    if ( ! isset( $meta[$key] ) && empty( $meta[$key] ) ) {
                        update_post_meta( $item->ID, $key, $value );
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
