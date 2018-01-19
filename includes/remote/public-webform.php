<?php
/**
 * DT_Webform_Public_Webform
 *
 * @todo
 * 1. Captures the URL and rewrite to publish the embeddable webform
 * 2. Creates the HTML of the webform
 * 3. Handles the javascript form validation
 * 4. Submits the form to be saved
 *
 */

/**
 * Class DT_Webform_Public_Webform
 */
class DT_Webform_Public_Webform
{
    /**
     * DT_Webform_Public_Webform The single instance of DT_Webform_Public_Webform.
     *
     * @var    object
     * @access private
     * @since  0.1.0
     */
    private static $_instance = null;

    /**
     * Main DT_Webform_Public_Webform Instance
     * Ensures only one instance of DT_Webform_Public_Webform is loaded or can be loaded.
     *
     * @since  0.1.0
     * @static
     * @return DT_Webform_Public_Webform instance
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