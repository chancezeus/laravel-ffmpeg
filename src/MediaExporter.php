<?php

namespace Pbmedia\LaravelFFMpeg;

use FFMpeg\Format\FormatInterface;
use Illuminate\Support\Facades\File as FileFacade;
use Neutron\TemporaryFilesystem\Manager as FsManager;

class MediaExporter
{
    /** @var FsManager */
    protected $fs;

    /** @var string */
    protected $tempPath;

    /** @var string */
    protected $fsId;

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
        $this->fs = FsManager::create();

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
    public function withVisibility(string $visibility = null)
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

        $this->createDestinationPathForSaving($destinationPath);

        $this->{$this->saveMethod}($destinationPath);

        if (!$disk->isLocal()) {
            $this->moveSavedFilesToRemoteDisk($destinationPath, $file);
        } else {
            $this->updateVisibilityForSavedFiles($file);
        }

        return $this->media;
    }

    /**
     * @param string $localSourcePath
     * @param File $target
     * @return bool
     */
    protected function moveSavedFilesToRemoteDisk(string $localSourcePath, File $target): bool
    {
        $localDirectory = pathinfo($localSourcePath, PATHINFO_DIRNAME);
        $disk = $target->getDisk();
        
        $regex = '#^' . preg_quote($this->tempPath, '#') . '#';
        
        foreach (FileFacade::allFiles($localDirectory) as $sourceFile) {
            $remotePath = preg_replace($regex, '', $sourceFile);
            
            if (!$disk->put($remotePath, file_get_contents($sourceFile), $this->visibility)) {
                return false;
            }
        }

        $this->fs->clean($this->fsId);
        
        return true;
    }

    /**
     * @param File $file
     */
    protected function updateVisibilityForSavedFiles(File $file)
    {
        $directory = pathinfo($file->getPath(), PATHINFO_DIRNAME);
        $disk = $file->getDisk();
        
        foreach ($disk->allFiles($directory) as $filePath) {
            $disk->setVisibility($filePath, $this->visibility);
        }
    }

    /**
     * @param File $file
     * @return string
     */
    protected function getDestinationPathForSaving(File $file): string
    {
        if (!$file->getDisk()->isLocal()) {
            $this->fsId = uniqid('laravel-ffmpeg');

            $this->tempPath = rtrim($this->fs->createTemporaryDirectory(0777, 50, $this->fsId), DIRECTORY_SEPARATOR);

            return $this->tempPath . DIRECTORY_SEPARATOR . $file->getPath();
        }

        return $file->getFullPath();
    }

    /**
     * @param string $file
     * @return bool
     */
    protected function createDestinationPathForSaving(string $file)
    {
        $directory = pathinfo($file, PATHINFO_DIRNAME);

        return FileFacade::makeDirectory($directory, 0755, true);
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
