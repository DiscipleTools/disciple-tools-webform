<?php
/**
 * DT_Webform_Settings_Page
 *
 * @todo
 * 1. Create the DT webform menu
 * 2. Create the tabs for the settings page
 * 3. Create the options selection form
 * 4. Create the form creation process
 * 5. Create the active form display table
 *
 */

/**
 * Class DT_Webform_Settings_Page
 */
class DT_Webform_Settings_Page
{
    /**
     * DT_Webform_Settings_Page The single instance of DT_Webform_Settings_Page.
     *
     * @var    object
     * @access private
     * @since  0.1.0
     */
    private static $_instance = null;

    /**
     * Main DT_Webform_Settings_Page Instance
     * Ensures only one instance of DT_Webform_Settings_Page is loaded or can be loaded.
     *
     * @since  0.1.0
     * @static
     * @return DT_Webform_Settings_Page instance
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
    }

}