<?php

namespace Droid\Plugin\Apache\Command;

use RuntimeException;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Droid\Lib\Plugin\Command\CheckableTrait;

class ApacheModuleEnableCommand extends AbstractApacheCommand
{
    use CheckableTrait;

    protected $enabledDir = 'mods-enabled';
    protected $availableDir = 'mods-available';

    public function configure()
    {
        $this
            ->setName('apache:enmod')
            ->setDescription('Enable Apache modules.')
            ->addArgument(
                'module-name',
                InputArgument::REQUIRED,
                'Enable the named module.'
            )
        ;
        $this->configureCheckMode();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->activateCheckMode($input);

        $confname = $this->getConfName($input->getArgument('module-name'));
        $confFilename = $this->getConfFilename($input->getArgument('module-name'));

        if (! $this->available($confFilename)) {
            throw new RuntimeException(
                sprintf('I am not aware of a module named "%s".', $confname)
            );
        }

        if ($this->enabled($confFilename)) {
            $output->writeLn(
                sprintf(
                    'The module "%s" is already enabled. Nothing to do.',
                    $confname
                )
            );
            $this->reportChange($output);
            return 0;
        }

        $this->markChange();

        if (! $this->checkMode() && ! $this->enable($confFilename)) {
            throw new RuntimeException(
                sprintf('I cannot enable module "%s".', $confname)
            );
        }

        $output->writeLn(
            sprintf(
                'I %s "%s".',
                $this->checkMode() ? 'would enable' : 'have enabled',
                $confname
            )
        );

        $this->reportChange($output);
        return 0;
    }

    protected function getAvailableDir()
    {
        return $this->availableDir;
    }

    protected function getEnabledDir()
    {
        return $this->enabledDir;
    }
}
