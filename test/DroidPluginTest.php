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
                'Droid\Plugin\Apache\Command\ApacheSiteDisableCommand',
                'Droid\Plugin\Apache\Command\ApacheSiteEnableCommand',
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
