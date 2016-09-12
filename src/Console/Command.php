<?php
namespace shmurakami\FileMigrator\Console;

use shmurakami\FileMigrator\File\Filesystem;

class Command
{
    const EXIT_CORRECTLY = 0;

    /** @var \shmurakami\FileMigrator\File\Filesystem */
    private $filesystem;

    private $migrateNamespace = true;

    /** @var string target directory path */
    private $directory;
    /** @var string target file path */
    private $file;
    /** @var string target output directory path */
    private $output;

    /**
     * Command constructor.
     * @param array $argv
     */
    public function __construct(Filesystem $filesystem, array $argv)
    {
        $this->filesystem = $filesystem;
        $this->parse($argv);
    }

    /**
     * @param array $argv
     */
    public function parse($argv)
    {
        // dump command name
        array_shift($argv);

        while ($key = array_shift($argv)) {
            if (preg_match('/^\-([a-z])/', $key, $matches)) {
                $key = $matches[1];
                switch (true) {
                    case $key === 'd':
                        $value = array_shift($argv);
                        $this->setDirectory($value);
                        break;
                    case $key === 'f':
                        $value = array_shift($argv);
                        $this->setFile($value);
                        break;
                    case $key === 'o':
                        $value = array_shift($argv);
                        $this->setOutput($value);
                        break;
                    case $key === 'n':
                        $this->disableNamespace();
                        break;
                    default:
                        break;
                }
            }
        }
    }

    /**
     * @return bool
     */
    public function validate()
    {
        $output = $this->getOutput();
        if (!$output) {
            return false;
        }

        $directory = $this->getDirectory();
        $file = $this->getFile();
        if ((!$directory && !$file)
            || ($directory && $file)) {
            return false;
        }

        if (!$this->filesystem->exists($output) || !$this->filesystem->isDirectory($output)) {
            return false;
        }

        if ($directory && (!$this->filesystem->exists($directory) || !$this->filesystem->isDirectory($directory))) {
            return false;
        }

        if ($file && (!$this->filesystem->exists($file) || !$this->filesystem->isFile($file))) {
            return false;
        }

        return true;
    }

    /**
     * @return int
     */
    public function migrate()
    {
        // TODO support subdirectories recursively

        $file = $this->getFile();
        if ($file) {
            $files = [$file];
        } else {
            $files = $this->filesystem->files($this->getDirectory());
        }

        $output = $this->getOutput();

        $migrateNamespace = $this->isMigrateNamespace();

        foreach ($files as $file) {
            $result = $this->filesystem->copy($file, $output);
            if (!$result) {
                throw new \RuntimeException("Failed to copy $file");
            }

            if ($migrateNamespace) {
                $destFile = $output . DIRECTORY_SEPARATOR . $file;
                $this->setPsr4Namespace($destFile);
            }
        }

        return self::EXIT_CORRECTLY;
    }

    /**
     * add namespace to file based on PSR-4
     * @param string $filePath
     */
    public function setPsr4Namespace($filePath)
    {
        // TODO little hard to know having namespace or not. do with cheap way once.
        $src = $this->filesystem->get($filePath);

        $namespace = ''; // TODO ここでnamespaceを拾って

        if (preg_match('/namespace\s+?(.+?);/', $src)) {
            preg_replace('/namespace\s+?(.+?);/', $namespace, $src, 1);
        } else {
            preg_replace('<?php', "<?php\nnamespace $namespace;", $src, 1);
        }

        $this->filesystem->put($filePath, $src);
    }

    public function disableNamespace()
    {
        $this->migrateNamespace = false;
    }

    /**
     * @return boolean
     */
    public function isMigrateNamespace()
    {
        return $this->migrateNamespace;
    }

    /**
     * @return string
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * @param string $directory
     */
    public function setDirectory($directory)
    {
        $this->directory = $directory;
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param string $file
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * @return string
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @param string $output
     */
    public function setOutput($output)
    {
        $this->output = $output;
    }

}
