<?php

namespace Droid\Plugin\Apache;

use Symfony\Component\Process\ProcessBuilder;

use Droid\Plugin\Apache\Command\ApacheModuleDisableCommand;
use Droid\Plugin\Apache\Command\ApacheModuleEnableCommand;
use Droid\Plugin\Apache\Command\ApacheSiteDisableCommand;
use Droid\Plugin\Apache\Command\ApacheSiteEnableCommand;
use Droid\Plugin\Apache\Util\Normaliser;

class DroidPlugin
{
    public function __construct($droid)
    {
        $this->droid = $droid;
    }

    public function getCommands()
    {
        return array(
            new ApacheModuleDisableCommand(
                new ProcessBuilder,
                new Normaliser
            ),
            new ApacheModuleEnableCommand(
                new ProcessBuilder,
                new Normaliser
            ),
            new ApacheSiteDisableCommand(
                new ProcessBuilder,
                new Normaliser
            ),
            new ApacheSiteEnableCommand(
                new ProcessBuilder,
                new Normaliser
            ),
        );
    }
}
