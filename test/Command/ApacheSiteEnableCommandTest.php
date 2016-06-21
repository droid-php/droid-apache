<?php

namespace Droid\Test\Plugin\Apache\Command;

use RuntimeException;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

use Droid\Plugin\Apache\Command\ApacheSiteEnableCommand;
use Droid\Plugin\Apache\Util\Normaliser;

class ApacheSiteEnableCommandTest extends \PHPUnit_Framework_TestCase
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

        $command = new ApacheSiteEnableCommand(
            $this->processBuilder,
            new Normaliser
        );

        $this->app = new Application;
        $this->app->add($command);

        $this->tester = new CommandTester($command);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage I am not aware of a site named "not_a_site"
     */
    public function testApacheSiteEnableThrowsRuntimeExceptionWithUnknownSite()
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
                    '/etc/apache2/sites-available/not_a_site.conf'
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
            'command' => $this->app->find('apache:ensite')->getName(),
            'site-name' => 'not_a_site',
        ));
    }

    public function testApacheSiteEnableExitsWhenSiteIsAlreadyEnabled()
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
                    '/etc/apache2/sites-available/a_site.conf',
                )),
                array(array(
                    'stat',
                    '-c',
                    '%F',
                    '/etc/apache2/sites-enabled/a_site.conf',
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
            'command' => $this->app->find('apache:ensite')->getName(),
            'site-name' => 'a_site',
        ));


        $this->assertRegExp(
            '/^The site "a_site" is already enabled\. Nothing to do/',
            $this->tester->getDisplay()
        );
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage I cannot enable site "a_site"
     */
    public function testApacheSiteEnableThrowsRuntimeExceptionWhenFailsToEnable()
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
                    '/etc/apache2/sites-available/a_site.conf',
                )),
                array(array(
                    'stat',
                    '-c',
                    '%F',
                    '/etc/apache2/sites-enabled/a_site.conf',
                )),
                array(array(
                    'ln',
                    '-s',
                    '/etc/apache2/sites-available/a_site.conf',
                    '/etc/apache2/sites-enabled/a_site.conf',
                ))
            )
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
            'command' => $this->app->find('apache:ensite')->getName(),
            'site-name' => 'a_site',
        ));
    }

    public function testApacheSiteEnableWillEnableDisabledSite()
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
                    '/etc/apache2/sites-available/a_site.conf',
                )),
                array(array(
                    'stat',
                    '-c',
                    '%F',
                    '/etc/apache2/sites-enabled/a_site.conf',
                )),
                array(array(
                    'ln',
                    '-s',
                    '/etc/apache2/sites-available/a_site.conf',
                    '/etc/apache2/sites-enabled/a_site.conf',
                ))
            )
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
            'command' => $this->app->find('apache:ensite')->getName(),
            'site-name' => 'a_site',
        ));


        $this->assertRegExp(
            '/^I have enabled "a_site"/',
            $this->tester->getDisplay()
        );
    }

    public function testApacheSiteEnableWillReportOnlyInCheckMode()
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
                    '/etc/apache2/sites-available/a_site.conf',
                )),
                array(array(
                    'stat',
                    '-c',
                    '%F',
                    '/etc/apache2/sites-enabled/a_site.conf',
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
            'command' => $this->app->find('apache:ensite')->getName(),
            'site-name' => 'a_site',
            '--check' => true,
        ));


        $this->assertRegExp(
            '/^I would enable "a_site"/',
            $this->tester->getDisplay()
        );
    }
}
