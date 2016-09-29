<?php

namespace Droid\Plugin\Apache\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Process\ProcessBuilder;

use Droid\Plugin\Apache\Util\Normaliser;

abstract class AbstractApacheCommand extends Command
{
    private $processBuilder;
    private $normaliser;
    private $confDir;

    public function __construct(
        ProcessBuilder $processBuilder,
        Normaliser $normaliser,
        $configDirectory = '/etc/apache2',
        $name = null
    ) {
        $this->processBuilder = $processBuilder;
        $this->normaliser = $normaliser;
        $this->confDir = $configDirectory;
        return parent::__construct($name);
    }

    abstract protected function getEnabledDir();
    abstract protected function getAvailableDir();

    protected function getConfName($argument)
    {
        return $this->normaliser->normaliseConfName($argument);
    }

    protected function getConfFilename($argument)
    {
        return $this->normaliser->normaliseConfFilename($argument);
    }

    protected function available($confFilename)
    {
        $p = $this->getProcess(
            array(
                'stat',
                '-c',
                '%F',
                $this->getTargetPath($confFilename)
            )
        );
        if (!$p->run()
            && preg_match('/^regular(?: empty)? file/', $p->getOutput())
        ) {
            return true;
        }
        return false;
    }

    protected function enabled($confFilename)
    {
        $p = $this->getProcess(
            array(
                'stat',
                '-c',
                '%F',
                $this->getLinkPath($confFilename)
            )
        );
        if (!$p->run()
            && substr($p->getOutput(), 0, 13) === 'symbolic link'
        ) {
            return true;
        }
        return false;
    }

    protected function enable($confFilename)
    {
        $p = $this->getProcess(
            array(
                'ln',
                '-s',
                $this->getTargetPath($confFilename),
                $this->getLinkPath($confFilename),
            )
        );
        return $p->run() === 0;
    }

    protected function disable($confFilename)
    {
        $p = $this->getProcess(
            array(
                'unlink',
                $this->getLinkPath($confFilename),
            )
        );
        return $p->run() === 0;
    }

    private function getLinkPath($confFilename)
    {
        return implode(
            DIRECTORY_SEPARATOR,
            array(
                $this->confDir,
                $this->getEnabledDir(),
                $confFilename,
            )
        );
    }

    private function getTargetPath($confFilename)
    {
        return implode(
            DIRECTORY_SEPARATOR,
            array(
                $this->confDir,
                $this->getAvailableDir(),
                $confFilename,
            )
        );
    }

    private function getProcess($arguments)
    {
        return $this
            ->processBuilder
            ->setArguments($arguments)
            ->setTimeout(0.0)
            ->getProcess()
        ;
    }
}
