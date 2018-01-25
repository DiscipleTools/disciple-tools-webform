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
    public static function form_template() {
        ?>
        <html>
        <head>

        </head>
        <body>
            Sampel Form
            <form method="post">
                <table>
                    <tr>
                        <td><label for="first_name">First Name</label></td>
                        <td><input type="text" name="first_name" value="" /></td>
                    </tr>
                    <tr>
                        <td><label for="last_name">Last Name</label></td>
                        <td><input type="text" name="last_name" value="" /></td>
                    </tr>
                    <tr>
                        <td><label for="email">Email</label></td>
                        <td><input type="text" name="first_name" value="" /></td>
                    </tr>
                    <tr>
                        <td><label for="phone">Phone</label></td>
                        <td><input type="text" name="phone" value="" /></td>
                    </tr>
                    <tr>
                        <td><label for="preference">Preference</label></td>
                        <td><input type="text" name="preference" value="" /></td>
                    </tr>
                    <tr>
                        <td><label for="notes">Notes</label></td>
                        <td><input type="text" name="notes" value="" /></td>
                    </tr>
                </table>
            </form>

        </body>
        </html>
        <?php
    }

}