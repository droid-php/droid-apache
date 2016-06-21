<?php

namespace Droid\Test\Plugin\Apache\Command;

use RuntimeException;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

use Droid\Plugin\Apache\Command\ApacheModuleDisableCommand;
use Droid\Plugin\Apache\Util\Normaliser;

class ApacheModuleDisableCommandTest extends \PHPUnit_Framework_TestCase
{
    protected $app;
    protected $tester;
    protected $process;
    protected $processBuilder;

    protected function setUp()
    {
        $this->process = $this
            ->getMockBuilder(Process::class)
            ->disableOriginalConstructor()
            ->setMethods(array('run', 'getOutput', 'getExitCode'))
            ->getMock()
        ;
        $this->processBuilder = $this
            ->getMockBuilder(ProcessBuilder::class)
            ->setMethods(array('setArguments', 'getProcess'))
            ->getMock()
        ;
        $this
            ->processBuilder
            ->method('setArguments')
            ->willReturnSelf()
        ;
        $this
            ->processBuilder
            ->method('getProcess')
            ->willReturn($this->process)
        ;

        $command = new ApacheModuleDisableCommand(
            $this->processBuilder,
            new Normaliser
        );

        $this->app = new Application;
        $this->app->add($command);

        $this->tester = new CommandTester($command);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage I am not aware of a module named "not_a_module"
     */
    public function testApacheModDisableThrowsRuntimeExceptionWithUnknownModule()
    {
        $this
            ->processBuilder
            ->expects($this->once())
            ->method('setArguments')
            ->with(
                array(
                    'stat',
                    '-c',
                    '%F',
                    '/etc/apache2/mods-available/not_a_module.conf',
                )
            )
        ;
        $this
            ->process
            ->expects($this->once())
            ->method('run')
            ->willReturn(1)
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('apache:dismod')->getName(),
            'module-name' => 'not_a_module',
        ));
    }

    public function testApacheModDisableExitsWhenModuleIsAlreadyDisabled()
    {
        $this
            ->processBuilder
            ->expects($this->exactly(2))
            ->method('setArguments')
            ->withConsecutive(
                array(array(
                    'stat',
                    '-c',
                    '%F',
                    '/etc/apache2/mods-available/a_module.conf',
                )),
                array(array(
                    'stat',
                    '-c',
                    '%F',
                    '/etc/apache2/mods-enabled/a_module.conf',
                ))
            )
        ;
        $this
            ->process
            ->expects($this->exactly(2))
            ->method('run')
            ->willReturnOnConsecutiveCalls(0, 1)
        ;
        $this
            ->process
            ->expects($this->once())
            ->method('getOutput')
            ->willReturn('regular file')
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('apache:dismod')->getName(),
            'module-name' => 'a_module',
        ));


        $this->assertRegExp(
            '/^The module "a_module" is already disabled\. Nothing to do/',
            $this->tester->getDisplay()
        );
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage I cannot disable module "a_module"
     */
    public function testApacheModDisableThrowsRuntimeExceptionWhenFailsToDisable()
    {
        $this
            ->processBuilder
            ->expects($this->exactly(3))
            ->method('setArguments')
            ->withConsecutive(
                array(array(
                    'stat',
                    '-c',
                    '%F',
                    '/etc/apache2/mods-available/a_module.conf',
                )),
                array(array(
                    'stat',
                    '-c',
                    '%F',
                    '/etc/apache2/mods-enabled/a_module.conf',
                )),
                array(array(
                    'unlink',
                    '/etc/apache2/mods-enabled/a_module.conf',
                ))
            )
        ;
        $this
            ->process
            ->expects($this->exactly(3))
            ->method('run')
            ->willReturnOnConsecutiveCalls(0, 0, 1)
        ;
        $this
            ->process
            ->expects($this->exactly(2))
            ->method('getOutput')
            ->willReturnOnConsecutiveCalls('regular file', 'symbolic link')
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('apache:dismod')->getName(),
            'module-name' => 'a_module',
        ));
    }

    public function testApacheModDisableWillDisableEnabledModule()
    {
        $this
            ->processBuilder
            ->expects($this->exactly(3))
            ->method('setArguments')
            ->withConsecutive(
                array(array(
                    'stat',
                    '-c',
                    '%F',
                    '/etc/apache2/mods-available/a_module.conf',
                )),
                array(array(
                    'stat',
                    '-c',
                    '%F',
                    '/etc/apache2/mods-enabled/a_module.conf',
                )),
                array(array(
                    'unlink',
                    '/etc/apache2/mods-enabled/a_module.conf',
                ))
            )
        ;
        $this
            ->process
            ->expects($this->exactly(3))
            ->method('run')
            ->willReturnOnConsecutiveCalls(0, 0, 0)
        ;
        $this
            ->process
            ->expects($this->exactly(2))
            ->method('getOutput')
            ->willReturnOnConsecutiveCalls('regular file', 'symbolic link')
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('apache:dismod')->getName(),
            'module-name' => 'a_module',
        ));


        $this->assertRegExp(
            '/^I have disabled "a_module"/',
            $this->tester->getDisplay()
        );
    }

    public function testApacheModDisableWillReportOnlyInCheckMode()
    {
        $this
            ->processBuilder
            ->expects($this->exactly(2))
            ->method('setArguments')
            ->withConsecutive(
                array(array(
                    'stat',
                    '-c',
                    '%F',
                    '/etc/apache2/mods-available/a_module.conf'
                )),
                array(array(
                    'stat',
                    '-c',
                    '%F',
                    '/etc/apache2/mods-enabled/a_module.conf'
                ))
            )
        ;
        $this
            ->process
            ->expects($this->exactly(2))
            ->method('run')
            ->willReturnOnConsecutiveCalls(0, 0)
        ;
        $this
            ->process
            ->expects($this->exactly(2))
            ->method('getOutput')
            ->willReturnOnConsecutiveCalls('regular file', 'symbolic link')
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('apache:dismod')->getName(),
            'module-name' => 'a_module',
            '--check' => true,
        ));


        $this->assertRegExp(
            '/^I would disable "a_module"/',
            $this->tester->getDisplay()
        );
    }
}
