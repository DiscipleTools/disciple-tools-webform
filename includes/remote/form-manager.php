<?php
/**
 * DT_Webform_Form_Manager
 *
 * @todo
 * 1. Settings page to toggle various optional fields for the contact form
 * 2. Add overriding CSS to modify the form
 * 3. Adjust language used on the form for the fields (potentially)
 * 4. Creates the copy paste premade code for embedding
 *
 */

/**
 * Class DT_Webform_Form_Manager
 */
class DT_Webform_Form_Manager
{
    /**
     * DT_Webform_Form_Manager The single instance of DT_Webform_Form_Manager.
     *
     * @var    object
     * @access private
     * @since  0.1.0
     */
    private static $_instance = null;

    /**
     * Main DT_Webform_Form_Manager Instance
     * Ensures only one instance of DT_Webform_Form_Manager is loaded or can be loaded.
     *
     * @since  0.1.0
     * @static
     * @return DT_Webform_Form_Manager instance
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