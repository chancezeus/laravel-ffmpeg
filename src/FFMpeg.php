<?php

namespace Pbmedia\LaravelFFMpeg;

use FFMpeg\FFMpeg as BaseFFMpeg;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Filesystem\Factory as Filesystems;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Psr\Log\LoggerInterface;

class FFMpeg
{
    /** @var Filesystems */
    protected static $filesystems;

    /** @var array */
    private static $temporaryFiles = [];

    /** @var Disk */
    protected $disk;

    /** @var BaseFFMpeg */
    protected $ffmpeg;

    public function __construct(Filesystems $filesystems, ConfigRepository $config, LoggerInterface $logger)
    {
        static::$filesystems = $filesystems;

        $ffmpegConfig = $config->get('laravel-ffmpeg');

        $this->ffmpeg = BaseFFMpeg::create([
            'ffmpeg.binaries'  => Arr::get($ffmpegConfig, 'ffmpeg.binaries'),
            'ffmpeg.threads'   => Arr::get($ffmpegConfig, 'ffmpeg.threads'),
            'ffprobe.binaries' => Arr::get($ffmpegConfig, 'ffprobe.binaries'),
            'timeout'          => Arr::get($ffmpegConfig, 'timeout'),
        ], $logger);

        $this->fromDisk(
            Arr::get($ffmpegConfig, 'default_disk', $config->get('filesystems.default'))
        );
    }

    /**
     * @return Filesystems
     */
    public static function getFilesystems(): Filesystems
    {
        return static::$filesystems;
    }

    /**
     * @return string
     */
    public static function newTemporaryFile(): string
    {
        return self::$temporaryFiles[] = tempnam(sys_get_temp_dir(), 'laravel-ffmpeg');
    }

    public function cleanupTemporaryFiles()
    {
        foreach (self::$temporaryFiles as $path) {
            @unlink($path);
        }
    }

    /**
     * @param Filesystem $filesystem
     * @return FFMpeg
     */
    public function fromFilesystem(Filesystem $filesystem): FFMpeg
    {
        $this->disk = new Disk($filesystem);

        return $this;
    }

    /**
     * @param string $diskName
     * @return FFMpeg
     */
    public function fromDisk(string $diskName): FFMpeg
    {
        $filesystem = static::getFilesystems()->disk($diskName);
        $this->disk = new Disk($filesystem);

        return $this;
    }

    /**
     * @param string $path
     * @return Media
     */
    public function open($path): Media
    {
        $file = $this->disk->newFile($path);

        if ($this->disk->isLocal()) {
            $ffmpegPathFile = $file->getFullPath();
        } else {
            $ffmpegPathFile = static::newTemporaryFile();
            file_put_contents($ffmpegPathFile, $this->disk->read($path));
        }

        $ffmpegMedia = $this->ffmpeg->open($ffmpegPathFile);

        return new Media($file, $ffmpegMedia);
    }
}
