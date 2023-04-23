<?php

namespace Sensi\Facial;

use DomainException;
use GdImage;

class ImageStats
{
    protected int $width;

    protected int $height;

    protected array $ii;

    protected array $ii2;

    /**
    * @param GdImage $canvas
    * @return void
    */
    public function __construct(GdImage $canvas)
    {
        $image_width = imagesx($canvas);
        $image_height = imagesy($canvas);
        $iis = $this->computeII($canvas, $image_width, $image_height);
        $this->width = $image_width;
        $this->height = $image_height;
        $this->ii = $iis['ii'];
        $this->ii2 = $iis['ii2'];
    }

    public function __get(string $prop)
    {
        return $this->$prop ?? null;
    }
    
    /**
     * Compute the "II" values for the canvas.
     *
     * @param resource $canvas
     * @param int $image_width
     * @param int $image_height
     * @return array
     */
    protected function computeII($canvas, int $image_width, int $image_height) : array
    {
        $ii_w = $image_width + 1;
        $ii_h = $image_height + 1;
        $ii = [];
        $ii2 = [];
        for ($i = 0; $i < $ii_w; $i++) {
            $ii[$i] = 0;
            $ii2[$i] = 0;
        }
        for ($i = 1; $i < $ii_h - 1; $i++) {
            $ii[$i * $ii_w] = 0;
            $ii2[$i * $ii_w] = 0;
            $rowsum = 0;
            $rowsum2 = 0;
            for ($j = 1; $j < $ii_w - 1; $j++) {
                $rgb = imagecolorat($canvas, $j, $i);
                $red = ($rgb >> 16) & 0xFF;
                $green = ($rgb >> 8) & 0xFF;
                $blue = $rgb & 0xFF;
                $grey = (round(0.2989 * $red + 0.587 * $green + 0.114 * $blue))>>0;  // this is what matlab uses
                $rowsum += $grey;
                $rowsum2 += $grey * $grey;
                $ii_above = ($i-1)*$ii_w + $j;
                $ii_this = $i * $ii_w + $j;
                $ii[$ii_this] = $ii[$ii_above] + $rowsum;
                $ii2[$ii_this] = $ii2[$ii_above] + $rowsum2;
            }
        }
        return compact('ii', 'ii2');
    }
}

