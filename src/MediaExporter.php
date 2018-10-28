<?php

namespace Pbmedia\LaravelFFMpeg;

use FFMpeg\Format\FormatInterface;

class MediaExporter
{
    /** @var Media */
    protected $media;

    /** @var Disk */
    protected $disk;

    /** @var FormatInterface */
    protected $format;

    /** @var string */
    protected $visibility;

    /** @var string */
    protected $saveMethod = 'saveAudioOrVideo';

    /**
     * @param Media $media
     */
    public function __construct(Media $media)
    {
        $this->media = $media;

        $this->disk = $media->getFile()->getDisk();
    }

    /**
     * @return Media
     */
    public function getMedia(): Media
    {
        return $this->media;
    }

    /**
     * @return FormatInterface|\FFMpeg\Format\Video\DefaultVideo
     */
    public function getFormat(): FormatInterface
    {
        return $this->format;
    }

    /**
     * @param FormatInterface $format
     * @return static
     */
    public function inFormat(FormatInterface $format): MediaExporter
    {
        $this->format = $format;

        return $this;
    }

    /**
     * @return Disk
     */
    protected function getDisk(): Disk
    {
        return $this->disk;
    }

    /**
     * @param Disk|string $diskOrName
     * @return static
     */
    public function toDisk($diskOrName): MediaExporter
    {
        if ($diskOrName instanceof Disk) {
            $this->disk = $diskOrName;
        } else {
            $this->disk = Disk::fromName($diskOrName);
        }

        return $this;
    }

    /**
     * @param string|null $visibility
     * @return static
     */
    public function withVisibility(string $visibility = null): MediaExporter
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * @param string $path
     * @return Media
     */
    public function save(string $path): Media
    {
        $disk = $this->getDisk();
        $file = $disk->newFile($path);

        $destinationPath = $this->getDestinationPathForSaving($file);

        $this->createDestinationPathForSaving($file);

        $this->{$this->saveMethod}($destinationPath);

        if (!$disk->isLocal()) {
            $this->moveSavedFileToRemoteDisk($destinationPath, $file);
        }

        if ($this->visibility !== null) {
            $disk->setVisibility($path, $this->visibility);
        }

        return $this->media;
    }

    /**
     * @param string $localSourcePath
     * @param File $fileOnRemoteDisk
     * @return bool
     */
    protected function moveSavedFileToRemoteDisk($localSourcePath, File $fileOnRemoteDisk): bool
    {
        return $fileOnRemoteDisk->put($localSourcePath) && @unlink($localSourcePath);
    }

    /**
     * @param File $file
     * @return string
     */
    private function getDestinationPathForSaving(File $file): string
    {
        if (!$file->getDisk()->isLocal()) {
            $tempName = FFMpeg::newTemporaryFile();

            return $tempName . '.' . $file->getExtension();
        }

        return $file->getFullPath();
    }

    /**
     * @param File $file
     * @return bool
     */
    private function createDestinationPathForSaving(File $file)
    {
        if (!$file->getDisk()->isLocal()) {
            return false;
        }

        $directory = pathinfo($file->getPath(), PATHINFO_DIRNAME);

        return $file->getDisk()->createDirectory($directory);
    }

    /**
     * @param string $fullPath
     * @return static
     */
    private function saveAudioOrVideo(string $fullPath): MediaExporter
    {
        $this->media->save($this->getFormat(), $fullPath);

        return $this;
    }
}
