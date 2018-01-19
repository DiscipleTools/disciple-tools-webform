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
    }

}