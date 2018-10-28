<?php

namespace Pbmedia\LaravelFFMpeg;

use Closure;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\Filters\Audio\SimpleFilter;
use FFMpeg\Filters\FilterInterface;
use FFMpeg\Media\AbstractStreamableMedia;
use FFMpeg\Media\MediaTypeInterface;

/**
 * @method mixed save($format, $outputPathFile)
 */
class Media
{
    /** @var File */
    protected $file;

    /** @var MediaTypeInterface|\FFMpeg\Media\Video */
    protected $media;

    /**
     * @param File $file
     * @param MediaTypeInterface $media
     */
    public function __construct(File $file, MediaTypeInterface $media)
    {
        $this->file  = $file;
        $this->media = $media;
    }

    /**
     * @return bool
     */
    public function isFrame(): bool
    {
        return $this instanceof Frame;
    }

    /**
     * @return File
     */
    public function getFile(): File
    {
        return $this->file;
    }

    /**
     * @return int
     */
    public function getDurationInSeconds(): int
    {
        return $this->getDurationInMilliseconds() / 1000;
    }

    /**
     * @return \FFMpeg\FFProbe\DataMapping\Stream|null
     */
    public function getFirstStream()
    {
        $media = $this->media;

        if ($media instanceof AbstractStreamableMedia) {
            return $media->getStreams()->first();
        }

        return null;
    }

    /**
     * @return float
     */
    public function getDurationInMilliseconds(): float
    {
        $stream = $this->getFirstStream();

        if ($stream->has('duration')) {
            return $stream->get('duration') * 1000;
        }

        $format = $this->media->getFormat();

        if ($format->has('duration')) {
            return $format->get('duration') * 1000;
        }

        return 0;
    }

    /**
     * @return MediaExporter
     */
    public function export(): MediaExporter
    {
        return new MediaExporter($this);
    }

    /**
     * @return HLSPlaylistExporter
     */
    public function exportForHLS(): HLSPlaylistExporter
    {
        return new HLSPlaylistExporter($this);
    }

    /**
     * @param string $timeCode
     * @return Frame
     */
    public function getFrameFromString(string $timeCode): Frame
    {
        return $this->getFrameFromTimeCode(
            TimeCode::fromString($timeCode)
        );
    }

    /**
     * @param float $quantity
     * @return Frame
     */
    public function getFrameFromSeconds(float $quantity): Frame
    {
        return $this->getFrameFromTimeCode(
            TimeCode::fromSeconds($quantity)
        );
    }

    /**
     * @param TimeCode $timeCode
     * @return Frame
     */
    public function getFrameFromTimeCode(TimeCode $timeCode): Frame
    {
        $frame = $this->media->frame($timeCode);

        return new Frame($this->getFile(), $frame);
    }

    /**
     * @return static
     */
    public function addFilter(): Media
    {
        $arguments = func_get_args();

        if (isset($arguments[0]) && $arguments[0] instanceof Closure) {
            call_user_func_array($arguments[0], [$this->media->filters()]);
        } else if (isset($arguments[0]) && $arguments[0] instanceof FilterInterface) {
            call_user_func_array([$this->media, 'addFilter'], $arguments);
        } else if (isset($arguments[0]) && is_array($arguments[0])) {
            $this->media->addFilter(new SimpleFilter($arguments[0]));
        } else {
            $this->media->addFilter(new SimpleFilter($arguments));
        }

        return $this;
    }

    /**
     * @param $argument
     * @return static
     */
    protected function selfOrArgument($argument)
    {
        return ($argument === $this->media) ? $this : $argument;
    }

    /**
     * @return MediaTypeInterface
     */
    public function __invoke(): MediaTypeInterface
    {
        return $this->media;
    }

    public function __clone()
    {
        if ($this->media) {
            /** @var \FFMpeg\Filters\FiltersCollection $clonedFilters */
            $clonedFilters = clone $this->media->getFiltersCollection();

            $this->media = clone $this->media;

            $this->media->setFiltersCollection($clonedFilters);
        }
    }

    /**
     * @param string $method
     * @param array $parameters
     * @return static
     */
    public function __call($method, $parameters)
    {
        return $this->selfOrArgument(
            call_user_func_array([$this->media, $method], $parameters)
        );
    }
}
