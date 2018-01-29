<?php
/**
 * DT_Webform_Crons
 *
 * @todo
 * 1. Register crons for home and remote
 * 2. Base to Remote check for new contacts
 * 3. Remote to Base trigger check for new contacts
 *
 */

/**
 * Class DT_Webform_Crons
 */
class DT_Webform_Crons
{
    /**
     * DT_Webform_Crons The single instance of DT_Webform_Crons.
     *
     * @var    object
     * @access private
     * @since  0.1.0
     */
    private static $_instance = null;

    /**
     * Main DT_Webform_Crons Instance
     * Ensures only one instance of DT_Webform_Crons is loaded or can be loaded.
     *
     * @since  0.1.0
     * @static
     * @return DT_Webform_Crons instance
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