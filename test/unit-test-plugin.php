<?php

class PluginTest extends TestCase
{
    public function test_plugin_installed() {
        activate_plugin( 'disciple-tools-plugin-starter-template/disciple-tools-webform.php' );

        $this->assertContains(
            'disciple-tools-plugin-starter-template/disciple-tools-webform.php',
            get_option( 'active_plugins' )
        );
    }
}
