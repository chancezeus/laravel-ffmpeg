<?php

namespace Pbmedia\LaravelFFMpeg;

class SegmentedExporter extends MediaExporter
{
    /** @var SegmentedFilter */
    protected $filter;

    /** @var string */
    protected $playlistPath;

    /** @var int */
    protected $segmentLength = 10;

    /** @var string */
    protected $saveMethod = 'saveStream';

    /** @var string|string */
    private $targetPath;

    /** @var string|string */
    private $targetName;

    /** @var string|string */
    private $playlistInfo;

    /**
     * @param Media $media
     * @param string|null $targetPath
     * @param string|null $targetName
     * @param string|null $playlistInfo
     */
    public function __construct(Media $media, string $targetPath = null, string $targetName = null, string $playlistInfo = null)
    {
        parent::__construct($media);

        $this->targetPath = $targetPath;
        $this->targetName = $targetName;
        $this->playlistInfo = $playlistInfo;
    }

    /**
     * @return string|null
     */
    public function getPlayListInfo()
    {
        return $this->playlistInfo;
    }

    /**
     * @param string $playlistPath
     * @return static
     */
    public function setPlaylistPath(string $playlistPath): MediaExporter
    {
        $this->playlistPath = $playlistPath;

        return $this;
    }

    /**
     * @param int $segmentLength
     * @return static
     */
    public function setSegmentLength(int $segmentLength): MediaExporter
    {
        $this->segmentLength = $segmentLength;

        return $this;
    }

    /**
     * @return SegmentedFilter
     */
    public function getFilter(): SegmentedFilter
    {
        if ($this->filter) {
            return $this->filter;
        }

        return $this->filter = new SegmentedFilter(
            $this->getPlaylistFullPath(),
            $this->segmentLength
        );
    }

    /**
     * @param string $playlistPath
     * @return static
     */
    public function saveStream(string $playlistPath): MediaExporter
    {
        $this->setPlaylistPath($playlistPath);

        $this->media->addFilter(
            $this->getFilter()
        );

        $this->media->save(
            $this->getFormat(),
            $this->getSegmentFullPath()
        );

        return $this;
    }

    /**
     * @param string $filename
     * @return string
     */
    private function getFullPathForFilename(string $filename)
    {
        $path = rtrim(pathinfo($this->playlistPath, PATHINFO_DIRNAME), DIRECTORY_SEPARATOR);

        if ($this->targetPath) {
            $path = $path . DIRECTORY_SEPARATOR . trim($this->targetPath, DIRECTORY_SEPARATOR);
        }

        return $path . DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * @return string
     */
    public function getPlaylistFullPath(): string
    {
        return $this->getFullPathForFilename(
            $this->getPlaylistFilename()
        );
    }

    /**
     * @return string
     */
    public function getPlaylistPath(): string
    {
        if ($this->targetPath) {
            return rtrim($this->targetPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $this->getPlaylistFilename();
        }

        return $this->getPlaylistFilename();
    }

    /**
     * @return string
     */
    public function getSegmentFullPath(): string
    {
        return $this->getFullPathForFilename(
            $this->getSegmentFilename()
        );
    }

    /**
     * @return string
     */
    public function getPlaylistName(): string
    {
        return pathinfo($this->playlistPath, PATHINFO_FILENAME);
    }

    /**
     * @return string
     */
    public function getPlaylistFilename(): string
    {
        return $this->getFormattedFilename('.m3u8');
    }

    /**
     * @return string
     */
    public function getSegmentFilename(): string
    {
        return $this->getFormattedFilename('_%05d.ts');
    }

    /**
     * @param string $suffix
     * @return string
     */
    protected function getFormattedFilename(string $suffix = ''): string
    {
        if ($this->targetName) {
            return $this->targetName . $suffix;
        }

        return implode([
                $this->getPlaylistName(),
                '_',
                $this->getFormat()->getKiloBitrate(),
            ]) . $suffix;
    }
}
