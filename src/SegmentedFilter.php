<?php

namespace Pbmedia\LaravelFFMpeg;

use FFMpeg\Filters\Video\VideoFilterInterface;
use FFMpeg\Format\VideoInterface;
use FFMpeg\Media\Video;

class SegmentedFilter implements VideoFilterInterface
{
    protected $playlistPath;

    protected $segmentLength;

    protected $priority;

    /**
     * @param string $playlistPath
     * @param int $segmentLength
     * @param int $priority
     */
    public function __construct(string $playlistPath, int $segmentLength = 10, $priority = 0)
    {
        $this->playlistPath  = $playlistPath;
        $this->segmentLength = $segmentLength;
        $this->priority      = $priority;
    }

    /**
     * @return string
     */
    public function getPlaylistPath(): string
    {
        return $this->playlistPath;
    }

    /**
     * @return int
     */
    public function getSegmentLength(): int
    {
        return $this->segmentLength;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param Video $video
     * @param VideoInterface $format
     * @return array
     */
    public function apply(Video $video, VideoInterface $format)
    {
        return [
            '-map',
            '0',
            '-flags',
            '-global_header',
            '-f',
            'segment',
            '-segment_format',
            'mpeg_ts',
            '-segment_list',
            $this->getPlaylistPath(),
            '-segment_time',
            $this->getSegmentLength(),
        ];
    }
}
