<?php

namespace Pbmedia\LaravelFFMpeg;

use FFMpeg\Format\VideoInterface;

class HLSPlaylistExporter extends MediaExporter
{
    /** @var \Pbmedia\LaravelFFMpeg\SegmentedExporter[] */
    protected $segmentedExporters = [];

    /** @var string */
    protected $playlistPath;

    /** @var int */
    protected $segmentLength = 10;

    /** @var string */
    protected $saveMethod = 'savePlaylist';

    /** @var callable */
    protected $progressCallback;

    /** @var bool */
    protected $sortFormats = true;

    /**
     * @param \FFMpeg\Format\VideoInterface $format
     * @param callable|null $callback
     * @param string|null $targetPath
     * @param string|null $playlistInfo
     * @return static
     */
    public function addFormat(VideoInterface $format, callable $callback = null, string $targetPath = null, string $playlistInfo = null): MediaExporter
    {
        $segmentedExporter = $this->getSegmentedExporterFromFormat($format, $targetPath, $playlistInfo);

        if ($callback) {
            $callback($segmentedExporter->getMedia());
        }

        $this->segmentedExporters[] = $segmentedExporter;

        return $this;
    }

    /**
     * @return static
     */
    public function dontSortFormats()
    {
        $this->sortFormats = false;

        return $this;
    }

    /**
     * @return \FFMpeg\Format\FormatInterface[]
     */
    public function getFormatsSorted(): array
    {
        return array_map(function (SegmentedExporter $exporter) {
            return $exporter->getFormat();
        }, $this->getSegmentedExportersSorted());
    }

    /**
     * @return \Pbmedia\LaravelFFMpeg\SegmentedExporter[]
     */
    public function getSegmentedExportersSorted(): array
    {
        if ($this->sortFormats) {
            usort($this->segmentedExporters, function (SegmentedExporter $exportedA, SegmentedExporter $exportedB) {
                return $exportedA->getFormat()->getKiloBitrate() <=> $exportedB->getFormat()->getKiloBitrate();
            });
        }

        return $this->segmentedExporters;
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

        foreach ($this->segmentedExporters as $segmentedExporter) {
            $segmentedExporter->setSegmentLength($segmentLength);
        }

        return $this;
    }

    /**
     * @param \FFMpeg\Format\VideoInterface $format
     * @param string|null $targetPath
     * @param string|null $targetName
     * @param string|null $playlistInfo
     * @return \Pbmedia\LaravelFFMpeg\SegmentedExporter
     */
    protected function getSegmentedExporterFromFormat(VideoInterface $format, string $targetPath = null, string $targetName = null, string $playlistInfo = null): SegmentedExporter
    {
        $media = clone $this->media;

        return (new SegmentedExporter($media, $targetPath, $targetName, $playlistInfo))
            ->inFormat($format);
    }

    /**
     * @return \Pbmedia\LaravelFFMpeg\SegmentedExporter[]
     */
    public function getSegmentedExporters(): array
    {
        return $this->segmentedExporters;
    }

    /**
     * @param callable $callback
     * @return static
     */
    public function onProgress(callable $callback)
    {
        $this->progressCallback = $callback;

        return $this;
    }

    /**
     * @param int $key
     * @return callable
     */
    private function getSegmentedProgressCallback($key): callable
    {
        return function ($video, $format, $percentage) use ($key) {
            $previousCompletedSegments = $key / count($this->segmentedExporters) * 100;

            call_user_func($this->progressCallback,
                $previousCompletedSegments + ($percentage / count($this->segmentedExporters))
            );
        };
    }

    /**
     * @return static
     */
    public function prepareSegmentedExporters()
    {
        foreach ($this->segmentedExporters as $key => $segmentedExporter) {
            if ($this->progressCallback) {
                $segmentedExporter->getFormat()->on('progress', $this->getSegmentedProgressCallback($key));
            }

            $segmentedExporter->setSegmentLength($this->segmentLength);
        }

        return $this;
    }

    protected function exportStreams()
    {
        $this->prepareSegmentedExporters();

        foreach ($this->segmentedExporters as $key => $segmentedExporter) {
            $segmentedExporter->saveStream($this->playlistPath);
        }
    }

    /**
     * @return string
     */
    protected function getMasterPlaylistContents(): string
    {
        $lines = ['#EXTM3U'];

        $segmentedExporters = $this->sortFormats ? $this->getSegmentedExportersSorted() : $this->getSegmentedExporters();

        /** @var \Pbmedia\LaravelFFMpeg\SegmentedExporter $segmentedExporter */
        foreach ($segmentedExporters as $segmentedExporter) {
            $bitrate = $segmentedExporter->getFormat()->getKiloBitrate() * 1000;

            if ($info = $segmentedExporter->getPlayListInfo()) {
                $lines[] = "#EXT-X-STREAM-INF:BANDWIDTH={$bitrate},{$info}";
            } else {
                $lines[] = "#EXT-X-STREAM-INF:BANDWIDTH={$bitrate}";
            }

            $lines[] = $segmentedExporter->getPlaylistPath();
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param string $playlistPath
     * @return static
     */
    public function savePlaylist(string $playlistPath): MediaExporter
    {
        $this->setPlaylistPath($playlistPath);
        $this->exportStreams();

        file_put_contents(
            $playlistPath,
            $this->getMasterPlaylistContents()
        );

        return $this;
    }
}
