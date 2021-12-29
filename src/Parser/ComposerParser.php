<?php

declare(strict_types=1);

namespace PhpAT\Parser;

use PhpAT\App\Configuration;
use PhpAT\File\FileFinder;

class ComposerParser
{
    private array $composerPackage;
    private FileFinder $finder;

    public function __construct(FileFinder $finder, Configuration $config)
    {
        $composerFilePath = realpath($config->getComposerConfiguration()['main']['json']);
        if (!is_file($composerFilePath)) {
            throw new \Exception('Composer file ' . $composerFilePath . ' not found.');
        }

        $this->finder = $finder;
        $this->composerPackage = json_decode(file_get_contents($composerFilePath), true);
    }

    /**
     * @return array<\SplFileInfo>
     */
    public function getFilesToAutoload(bool $includeDev = true): array
    {
        $filesFound = [];
        foreach ($this->composerPackage['classmap'] ?? [] as $path) {
            array_push($filesFound, ...$this->getFiles($path));
        }
        foreach ($this->composerPackage['files'] ?? [] as $path) {
            array_push($filesFound, ...$this->getFiles($path));
        }
        foreach ($this->composerPackage['autoload']['psr-0'] ?? [] as $path) {
            array_push($filesFound, ...array_values($this->getFiles($path)));
        }
        foreach ($this->composerPackage['autoload']['psr-4'] ?? [] as $path) {
            array_push($filesFound, ...array_values($this->getFiles($path)));
        }
        if ($includeDev) {
            foreach ($this->composerPackage['autoload-dev']['psr-0'] ?? [] as $path) {
                array_push($filesFound, ...array_values($this->getFiles($path)));
            }
            foreach ($this->composerPackage['autoload-dev']['psr-4'] ?? [] as $path) {
                array_push($filesFound, ...array_values($this->getFiles($path)));
            }
        }
        foreach ($this->composerPackage['exclude-from-classmap'] ?? [] as $path) {
            $filesFound = array_diff($filesFound, $this->getFiles($path));
        }

        return $filesFound;
    }

    /**
     * @return array<\SplFileInfo>
     */
    private function getFiles(string $path): array
    {
        return is_dir($path) ? $this->finder->findPhpFilesInPath($path) : [new \SplFileInfo($path)];
    }
}
