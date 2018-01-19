<?php
/**
 * DT_Webform_Contacts_Handler
 *
 * @todo
 * 1. Create the contact verification
 * 2. Create the contact insert into DT function
 * 3. Create the report back to remote webform server the "success" message
 *
 */

/**
 * Class DT_Webform_Settings_Page
 */
class DT_Webform_Contacts_Handler
{
    /**
     * DT_Webform_Contacts_Handler The single instance of DT_Webform_Contacts_Handler.
     *
     * @var    object
     * @access private
     * @since  0.1.0
     */
    private static $_instance = null;

    /**
     * Main DT_Webform_Contacts_Handler Instance
     * Ensures only one instance of DT_Webform_Contacts_Handler is loaded or can be loaded.
     *
     * @since  0.1.0
     * @static
     * @return DT_Webform_Contacts_Handler instance
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