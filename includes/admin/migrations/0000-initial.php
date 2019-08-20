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
        // get all custom fields
        $results = new WP_Query( [
            'post_type' => 'dt_webform_forms',
            'posts_per_page' => -1,
            'nopaging' => true
        ] );



        // convert custom fields to current format

        // resave all custom fields

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
