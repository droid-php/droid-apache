<?php

namespace Droid\Test\Plugin\Apache;

use Droid\Plugin\Apache\DroidPlugin;

class DroidPluginTest extends \PHPUnit_Framework_TestCase
{
    protected $plugin;

    protected function setUp()
    {
        $this->plugin = new DroidPlugin('droid');
    }

    public function testGetCommandsReturnsAllCommands()
    {
        $this->assertSame(
            array(
                'Droid\Plugin\Apache\Command\ApacheModuleDisableCommand',
                'Droid\Plugin\Apache\Command\ApacheModuleEnableCommand',
            ),
            array_map(
                function ($x) {
                    return get_class($x);
                },
                $this->plugin->getCommands()
            )
        );
    }
}
