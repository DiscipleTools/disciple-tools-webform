<?php
/**
 * DT_Webform_Temp_Contact_Post_Type
 *
 * @todo
 * 1. Store temp contact information ( use post type or custom tables? )
 *
 */

/**
 * Class DT_Webform_Form_Manager
 */
class DT_Webform_Temp_Contact_Post_Type
{
    /**
     * DT_Webform_Temp_Contact_Post_Type The single instance of DT_Webform_Temp_Contact_Post_Type.
     *
     * @var    object
     * @access private
     * @since  0.1.0
     */
    private static $_instance = null;

    /**
     * Main DT_Webform_Temp_Contact_Post_Type Instance
     * Ensures only one instance of DT_Webform_Temp_Contact_Post_Type is loaded or can be loaded.
     *
     * @since  0.1.0
     * @static
     * @return DT_Webform_Temp_Contact_Post_Type instance
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