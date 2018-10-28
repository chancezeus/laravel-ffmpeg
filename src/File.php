<?php

namespace Pbmedia\LaravelFFMpeg;

class File
{
    protected $disk;

    protected $path;

    /**
     * @param Disk $disk
     * @param string $path
     */
    public function __construct(Disk $disk, string $path)
    {
        $this->disk = $disk;
        $this->path = $path;
    }

    /**
     * @return Disk
     */
    public function getDisk(): Disk
    {
        return $this->disk;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getExtension(): string
    {
        return pathinfo($this->getPath(), PATHINFO_EXTENSION);
    }

    /**
     * @return string
     */
    public function getFullPath(): string
    {
        return $this->getDisk()->getPath() . $this->getPath();
    }

    /**
     * @param string $localSourcePath
     * @return bool
     */
    public function put(string $localSourcePath): bool
    {
        $resource = fopen($localSourcePath, 'r');

        return $this->getDisk()->put($this->getPath(), $resource);
    }
}
