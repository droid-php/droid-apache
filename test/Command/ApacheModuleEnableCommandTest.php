<?php

namespace Droid\Test\Plugin\Apache\Command;

use RuntimeException;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

use Droid\Plugin\Apache\Command\ApacheModuleEnableCommand;
use Droid\Plugin\Apache\Util\Normaliser;

class ApacheModuleEnableCommandTest extends \PHPUnit_Framework_TestCase
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
            ->setMethods(array('setArguments', 'setTimeout', 'getProcess'))
            ->getMock()
        ;
        $this
            ->processBuilder
            ->method('setArguments')
            ->willReturnSelf()
        ;
        $this
            ->processBuilder
            ->method('setTimeout')
            ->willReturnSelf()
        ;
        $this
            ->processBuilder
            ->method('getProcess')
            ->willReturn($this->process)
        ;

        $command = new ApacheModuleEnableCommand(
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
    public function testApacheModEnableThrowsRuntimeExceptionWithUnknownModule()
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
                    '/etc/apache2/mods-available/not_a_module.load'
                )
            )
        ;
        $this
            ->processBuilder
            ->expects($this->once())
            ->method('setTimeout')
            ->with($this->equalTo(0.0))
        ;
        $this
            ->process
            ->expects($this->once())
            ->method('run')
            ->willReturn(1)
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('apache:enmod')->getName(),
            'module-name' => 'not_a_module',
        ));
    }

    public function testApacheModEnableExitsWhenModuleIsAlreadyEnabled()
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
                    '/etc/apache2/mods-available/a_module.load',
                )),
                array(array(
                    'stat',
                    '-c',
                    '%F',
                    '/etc/apache2/mods-enabled/a_module.load',
                ))
            )
        ;
        $this
            ->processBuilder
            ->expects($this->exactly(2))
            ->method('setTimeout')
            ->with($this->equalTo(0.0))
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
            'command' => $this->app->find('apache:enmod')->getName(),
            'module-name' => 'a_module',
        ));


        $this->assertRegExp(
            '/^The module "a_module" is already enabled\. Nothing to do/',
            $this->tester->getDisplay()
        );
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage I cannot enable module "a_module"
     */
    public function testApacheModEnableThrowsRuntimeExceptionWhenFailsToEnable()
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
                    '/etc/apache2/mods-available/a_module.load',
                )),
                array(array(
                    'stat',
                    '-c',
                    '%F',
                    '/etc/apache2/mods-enabled/a_module.load',
                )),
                array(array(
                    'ln',
                    '-s',
                    '/etc/apache2/mods-available/a_module.load',
                    '/etc/apache2/mods-enabled/a_module.load',
                ))
            )
        ;
        $this
            ->processBuilder
            ->expects($this->exactly(3))
            ->method('setTimeout')
            ->with($this->equalTo(0.0))
        ;
        $this
            ->process
            ->expects($this->exactly(3))
            ->method('run')
            ->willReturnOnConsecutiveCalls(0, 1, 1)
        ;
        $this
            ->process
            ->expects($this->once())
            ->method('getOutput')
            ->willReturn('regular file')
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('apache:enmod')->getName(),
            'module-name' => 'a_module',
        ));
    }

    public function testApacheModEnableWillEnableDisabledModule()
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
                    '/etc/apache2/mods-available/a_module.load',
                )),
                array(array(
                    'stat',
                    '-c',
                    '%F',
                    '/etc/apache2/mods-enabled/a_module.load',
                )),
                array(array(
                    'ln',
                    '-s',
                    '/etc/apache2/mods-available/a_module.load',
                    '/etc/apache2/mods-enabled/a_module.load',
                ))
            )
        ;
        $this
            ->processBuilder
            ->expects($this->exactly(3))
            ->method('setTimeout')
            ->with($this->equalTo(0.0))
        ;
        $this
            ->process
            ->expects($this->exactly(3))
            ->method('run')
            ->willReturnOnConsecutiveCalls(0, 1, 0)
        ;
        $this
            ->process
            ->expects($this->once())
            ->method('getOutput')
            ->willReturn('regular file')
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('apache:enmod')->getName(),
            'module-name' => 'a_module',
        ));


        $this->assertRegExp(
            '/^I have enabled "a_module"/',
            $this->tester->getDisplay()
        );
    }

    public function testApacheModEnableWillReportOnlyInCheckMode()
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
                    '/etc/apache2/mods-available/a_module.load',
                )),
                array(array(
                    'stat',
                    '-c',
                    '%F',
                    '/etc/apache2/mods-enabled/a_module.load',
                ))
            )
        ;
        $this
            ->processBuilder
            ->expects($this->exactly(2))
            ->method('setTimeout')
            ->with($this->equalTo(0.0))
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
            'command' => $this->app->find('apache:enmod')->getName(),
            'module-name' => 'a_module',
            '--check' => true,
        ));


        $this->assertRegExp(
            '/^I would enable "a_module"/',
            $this->tester->getDisplay()
        );
    }
}
