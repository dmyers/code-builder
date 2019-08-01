<?php

namespace Swaggest\CodeBuilder\App;


class App
{
    protected $files = array();

    protected $storedFilesList = array();

    protected function getAbsoluteFilename($filename)
    {
        $path = [];
        foreach (explode('/', $filename) as $part) {
            // ignore parts that have no value
            if (empty($part) || $part === '.') continue;

            if ($part !== '..') {
                // cool, we found a new part
                array_push($path, $part);
            } else if (count($path) > 0) {
                // going back up? sure
                array_pop($path);
            } else {
                // now, here we don't like
                throw new \Exception('Climbing above the root is not permitted.');
            }
        }

        return (DIRECTORY_SEPARATOR === '\\' ? '' : '/') . join('/', $path);
    }

    protected function putContents($filepath, $content) {
        $filepath = $this->getAbsoluteFilename($filepath);
        $this->storedFilesList[$filepath] = $filepath;

        // Rendering content only once.
        $content = (string)$content;

        if (file_exists($filepath)) {
            $original = file_get_contents($filepath);
            if ($original == $content) {
                return;
            }
        }
        $dir = dirname($filepath);
        if (!file_exists($dir)) {
            mkdir($dir, 0750, true);
        }
        file_put_contents($filepath, $content);
    }

    public function store($path)
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $path = str_replace('\\', '/', $path);
        }

        $path = rtrim($path, '/') . '/';

        foreach ($this->files as $filepath => $contents) {
            $this->putContents($path . $filepath, $contents);
        }

        $this->clearOldFiles($path);
    }

    public function clearOldFiles($path)
    {
        if (!file_exists($path)) {
            return;
        }
        $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::UNIX_PATHS));

        $dirsToCheck = [];

        /** @var \RecursiveDirectoryIterator $file */
        foreach ($rii as $file) {
            if ($file->isDir()) {
                $dirsToCheck[dirname($file->getPathname())] = true;
                continue;
            }

            $filepath = $this->getAbsoluteFilename($file->getPathname());
            if (!isset($this->storedFilesList[$filepath])) {
                unlink($filepath);
            }
        }


        // removing empty dirs
        krsort($dirsToCheck);
        foreach ($dirsToCheck as $dir => $tmp) {
            if (!file_exists($dir)) {
                continue;
            }
            $s = scandir($dir);
            if (count($s) == 2) {
                rmdir($dir);
            }
        }
    }

}