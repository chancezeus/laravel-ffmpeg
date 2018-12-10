<?php

namespace Pbmedia\LaravelFFMpeg;

use Illuminate\Contracts\Filesystem\Filesystem;
use League\Flysystem\Adapter\Local as LocalAdapter;

/**
 * @mixin \Illuminate\Filesystem\FilesystemAdapter
 */
class Disk
{
    /** @var Filesystem|\Illuminate\Filesystem\FilesystemAdapter */
    protected $disk;

    /**
     * @param Filesystem $disk
     */
    public function __construct(Filesystem $disk)
    {
        $this->disk = $disk;
    }

    /**
     * @param string $name
     * @return Disk
     */
    public static function fromName(string $name): Disk
    {
        $adapter = FFMpeg::getFilesystems()->disk($name);

        return new static($adapter);
    }

    /**
     * @return bool
     */
    public function isLocal(): bool
    {
        /** @var \League\Flysystem\FilesystemInterface|\League\Flysystem\Filesystem $driver */
        $driver = $this->disk->getDriver();

        $adapter = $driver->getAdapter();

        return $adapter instanceof LocalAdapter;
    }

    /**
     * @param string $path
     * @return File
     */
    public function newFile(string $path): File
    {
        return new File($this, $path);
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        /** @var \League\Flysystem\FilesystemInterface|\League\Flysystem\Filesystem $driver */
        $driver = $this->disk->getDriver();

        /** @var LocalAdapter $adapter */
        $adapter = $driver->getAdapter();

        return $adapter->getPathPrefix();
    }

    /**
     * @param string $path
     * @return bool
     */
    public function createDirectory(string $path)
    {
        return $this->disk->makeDirectory($path);
    }

    /**
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->disk, $method], $parameters);
    }
}
