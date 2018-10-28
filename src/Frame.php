<?php

namespace Pbmedia\LaravelFFMpeg;

use FFMpeg\Media\Frame as BaseFrame;

/**
 * @method BaseFrame save($path, $accurate = false)
 */
class Frame extends Media
{
    /**
     * @return MediaExporter
     */
    public function export(): MediaExporter
    {
        return new FrameExporter($this);
    }
}
