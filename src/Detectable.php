<?php
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
//
// @Author Karthik Tharavaad
//         karthik_tharavaad@yahoo.com
// @Contributor Maurice Svay
//              maurice@svay.Com
// @Contributor Marijn Ophorst
//              marijn@sensimedia.nl

namespace Sensi\Facial;

use Exception;
use DomainException;
use GdImage;

class Detectable
{
    /**
     * Creates a detectable. This is an image with facial data as detected by
     * the detector factory.
     *
     * @param GdImage $canvas
     * @param array $face
     * @return void
     */
    public function __construct(
        protected GdImage $canvas,
        protected ?array $face
    ) {}

    /**
     * Returns true if the canvas contains a face, else false.
     *
     * @return bool
     */
    public function hasFace() : bool
    {
        return isset($this->face) && $this->face['w'] > 0;
    }

    /**
     * Returns an image resource (GdImage) with the cropped fase.
     *
     * @param int|null $minimum_width See this so the crop will never go under
     *  a certain size (e.g., minimum display size of the image in your
     *  project).
     * @param float $leniency The cropping is by default very strict (leniency
     *  == 1) which _really_ zooms in on the face. For practical purposes, 1.5
     *  works better, which is our default.
     * @return GdImage|null
     */
    public function getFace(int $mimum_width = null, float $leniency = 1.5) :? GdImage
    {
        if (!$this->hasFace()) {
            null;
        }
        $facex = $this->face['x'];
        $facey = $this->face['y'];
        $facewidth = $this->face['w'];
        $cropsize = $facewidth * $leniency;
        if ($cropsize > $width || $cropsize > $height) {
            $cropsize = min($width, $height);
        }
        if ($minimum_width && $cropsize < $minimum_width) {
            $cropsize = min($minimum_width, $width, $height);
        }
        if ($cropsize != $facewidth) {
            $facex -= ($cropsize - $facewidth) / 2;
            $facey -= ($cropsize - $facewidth) / 2;
        }
        // Make sure it doesn't attempt to crop outside the image
        if ($facex < 0) {
            $facex = 0;
        }
        if ($facey < 0) {
            $facey = 0;
        }
        if ($facex + $cropsize > $width) {
            $facex = $width - $cropsize;
        }
        if ($facey + $cropsize > $height) {
            $facey = $height - $cropsize;
        }
        return imagecrop($img, ['x' => $facex, 'y' => $facey, 'width' => $cropsize, 'height' => $cropsize]);
    }

    /**
     * Returns the original image. Note that getFace will also do this if no
     * face was detected.
     *
     * @return GdImage
     */
    public function getSource() : GdImage
    {
        return $this->canvas;
    }
}

