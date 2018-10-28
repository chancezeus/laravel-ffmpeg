<?php

namespace Pbmedia\LaravelFFMpeg;

class FrameExporter extends MediaExporter
{
    /** @var bool */
    protected $mustBeAccurate = false;

    /** @var string */
    protected $saveMethod = 'saveFrame';

    /**
     * @return static
     */
    public function accurate(): MediaExporter
    {
        $this->mustBeAccurate = true;

        return $this;
    }

    /**
     * @return static
     */
    public function inaccurate(): MediaExporter
    {
        $this->mustBeAccurate = false;

        return $this;
    }

    /**
     * @return bool
     */
    public function getAccuracy(): bool
    {
        return $this->mustBeAccurate;
    }

    /**
     * @param string $fullPath
     * @return static
     */
    public function saveFrame(string $fullPath): MediaExporter
    {
        $this->media->save($fullPath, $this->getAccuracy());

        return $this;
    }
}
