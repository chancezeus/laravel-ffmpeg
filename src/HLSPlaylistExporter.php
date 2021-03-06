<?php

namespace Pbmedia\LaravelFFMpeg;

use FFMpeg\Format\VideoInterface;
use Pbmedia\LaravelFFMpeg\SegmentedExporter;

class HLSPlaylistExporter extends MediaExporter
{
    protected $formats = [];

    protected $playlistPath;

    protected $segmentLength = 10;

    protected $segmentedExporters;

    protected $saveMethod = 'savePlaylist';

    public function addFormat(VideoInterface $format, ...$streamFilters): MediaExporter
    {
        $this->formats[] = [$format, $streamFilters];

        return $this;
    }

    public function getFormatsSorted(): array
    {
        usort($this->formats, function ($formatA, $formatB) {
            return $formatA[0]->getKiloBitrate() <=> $formatB[0]->getKiloBitrate();
        });

        return $this->formats;
    }

    public function setPlaylistPath(string $playlistPath): MediaExporter
    {
        $this->playlistPath = $playlistPath;

        return $this;
    }

    public function setSegmentLength(int $segmentLength): MediaExporter
    {
        $this->segmentLength = $segmentLength;

        return $this;
    }

    protected function getSegmentedExporterFromFormat(array $format): SegmentedExporter
    {
        $media = clone $this->media;

        if (!empty($format[1])) {
            foreach($format[1] as $streamFormat) {
                $media->addFilter($streamFormat);
            }
        }

        return (new SegmentedExporter($media))
            ->inFormat($format[0])
            ->setPlaylistPath($this->playlistPath)
            ->setSegmentLength($this->segmentLength);
    }

    public function getSegmentedExporters(): array
    {
        if ($this->segmentedExporters) {
            return $this->segmentedExporters;
        }

        return $this->segmentedExporters = array_map(function ($format) {
            return $this->getSegmentedExporterFromFormat($format);
        }, $this->getFormatsSorted());
    }

    protected function exportStreams()
    {
        foreach ($this->getSegmentedExporters() as $segmentedExporter) {
            $segmentedExporter->saveStream($this->playlistPath);
        }
    }

    protected function getMasterPlaylistContents(): string
    {
        $lines = ['#EXTM3U'];

        foreach ($this->getSegmentedExporters() as $segmentedExporter) {
            $bitrate = $segmentedExporter->getFormat()->getKiloBitrate() * 1000;

            $lines[] = '#EXT-X-STREAM-INF:BANDWIDTH=' . $bitrate;
            $lines[] = $segmentedExporter->getPlaylistFilename();
        }

        return implode(PHP_EOL, $lines);
    }

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
