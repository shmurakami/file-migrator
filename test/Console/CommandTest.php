<?php

class CommandTest extends PHPUnit_Framework_TestCase
{
    /** @var shmurakami\FileMigrator\Console\Command */
    private $command;

    protected function setUp()
    {
        $fileSystem = new \shmurakami\FileMigrator\File\Filesystem();
        $this->command = new shmurakami\FileMigrator\Console\Command($fileSystem, []);
    }

    function testParse()
    {
        $argv = [
            './bin/file_migrator', '-d', 'dir', '-n', '-f', 'file', '-o', 'output',
        ];

        $this->command->parse($argv);
        $this->assertEquals('dir', $this->command->getDirectory());
        $this->assertEquals('file', $this->command->getFile());
        $this->assertEquals('output', $this->command->getOutput());
        $this->assertFalse($this->command->isMigrateNamespace());
    }

    function testParse_emptyValue()
    {
        $argv = [
            './bin/file_migrator', '-d', '-f', 'file', '-o', 'output',
        ];

        $this->command->parse($argv);
        // -f is used as value of -d
        $this->assertEquals('-f', $this->command->getDirectory());
        $this->assertEquals('output', $this->command->getOutput());
        $this->assertTrue($this->command->isMigrateNamespace());
    }

    function testValidate()
    {
        // empty all
        $this->assertFalse($this->command->validate());

        // empty output
        $this->command->setFile(__FILE__);
        $this->assertFalse($this->command->validate());

        // valid
        $this->command->setOutput(__DIR__);
        $this->assertTrue($this->command->validate());

        // invalid to set both file and directory
        $this->command->setDirectory(__DIR__);
        $this->assertFalse($this->command->validate());
    }

    function testValidate_file()
    {
        $this->command->setOutput(__DIR__);
        $this->command->setFile('not_exist_file');
        $this->assertFalse($this->command->validate());

        $this->command->setFile(__DIR__);
        $this->assertFalse($this->command->validate());
    }

    function testValidate_directory()
    {
        $this->command->setOutput(__DIR__);
        $this->command->setDirectory('not_exist_directory');
        $this->assertFalse($this->command->validate());

        $this->command->setDirectory(__FILE__);
        $this->assertFalse($this->command->validate());
    }

    function testValidate_output()
    {
        $this->command->setOutput('not_exist_directory');
        $this->command->setDirectory(__DIR__);
        $this->assertFalse($this->command->validate());

        $this->command->setOutput(__FILE__);
        $this->assertFalse($this->command->validate());
    }

    function testMigrate_file()
    {
        $filesystem = \Mockery::spy(\shmurakami\FileMigrator\File\Filesystem::class);
        $command = new shmurakami\FileMigrator\Console\Command($filesystem, ['cmd', '-f', 'file', '-o', 'output', '-n']);

        $filesystem->shouldReceive('copy')->once()->andReturn(true);
        $this->assertEquals(shmurakami\FileMigrator\Console\Command::EXIT_CORRECTLY, $command->migrate());
        $filesystem->mockery_verify();
    }

    function testMigrate_dir()
    {
        $filesystem = \Mockery::spy(\shmurakami\FileMigrator\File\Filesystem::class);
        $command = new shmurakami\FileMigrator\Console\Command($filesystem, ['cmd', '-d', 'dir', '-o', 'output', '-n']);

        $filesystem->shouldReceive('files')->andReturn(['file1', 'file2']);
        $filesystem->shouldReceive('copy')->twice()->andReturn(true);
        $this->assertEquals(shmurakami\FileMigrator\Console\Command::EXIT_CORRECTLY, $command->migrate());
        $filesystem->mockery_verify();
    }

    /**
     * @expectedException \RuntimeException
     */
    function testMigrate_failedToCopy()
    {
        $filesystem = \Mockery::spy(\shmurakami\FileMigrator\File\Filesystem::class);
        $command = new shmurakami\FileMigrator\Console\Command($filesystem, ['cmd', '-f', 'file', '-o', 'output']);

        $filesystem->shouldReceive('copy')->andReturn(false);
        $command->migrate();
    }
}
