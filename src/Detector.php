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

class Detector
{
    /** @var array */
    protected array $detectionData;

    /**
     * Creates a face-detector with the given configuration
     *
     * Configuration can be either passed as an array or as
     * a filepath to a serialized array file-dump
     *
     * @param string|array $detection_data
     * @return void
     * @throws Exception
     */
    public function __construct(string|array $detection_data = null)
    {
        if (is_null($detection_data)) {
            $detection_data = dirname(__DIR__).'/resources/detection.dat';
        }
        if (is_array($detection_data)) {
            $this->detectionData = $detection_data;
            return;
        }
    
        if (!is_file($detection_data)) {
            // fallback to same file in this class's directory
            $detection_data = dirname(__FILE__) . DIRECTORY_SEPARATOR . $detection_data;
            
            if (!is_file($detection_data)) {
                throw new Exception("Couldn't load detection data");
            }
        }
        
        $this->detectionData = unserialize(file_get_contents($detection_data));
    }

    /**
     * Create a detectable from a resource.
     *
     * @param GdImage $resource
     * @return Sensi\Facial\Detectable
     */
    public function fromResource(GdImage $resource) : Detectable
    {
        return new Detectable($resource, $this->detectFace($canvas));
    }

    /**
     * Create a detectable from a filename.
     *
     * @param string $file
     * @return Sensi\Facial\Detectable
     */
    public function fromFile(string $file) : Detectable
    {
        if (!is_file($file)) {
            throw new DomainException("$file is not a file");
        }
        $canvas = imagecreatefromjpeg($file);
        return new Detectable($canvas, $this->detectFace($canvas));
    }

    /**
     * Create a detectable from a (binary) string.
     *
     * @param string $string
     * @return Sensi\Facial\Detectable
     */
    public function fromString(string $string) : Detectable
    {
        $canvas = imagecreatefromstring($file);
        if (!$canvas) {
            throw new DomainException("$string does not contain a valid image");
        }
        return new Detectable($canvas, $this->detectFace($canvas));
    }

    /**
     * @param GdImage $canvas
     * @return array|null
     * @throws Exception
     */
    protected function detectFace(GdImage $canvas) :? array
    {
        if (!isset($canvas)) {
            throw new Exception("Canvas not set! Initialize with one of the fromXXX methods");
        }

        $im_width = imagesx($canvas);
        $im_height = imagesy($canvas);

        // Resample before detection?
        $diff_width = 320 - $im_width;
        $diff_height = 240 - $im_height;
        if ($diff_width > $diff_height) {
            $ratio = $im_width / 320;
        } else {
            $ratio = $im_height / 240;
        }

        if ($ratio != 0) {
            $canvas2 = imagecreatetruecolor(round($im_width / $ratio), round($im_height / $ratio));
            imagecopyresampled(
                $canvas2,
                $canvas,
                0,
                0,
                0,
                0,
                round($im_width / $ratio),
                round($im_height / $ratio),
                $im_width,
                $im_height
            );
            $canvas = $canvas2;
        }
        $stats = new ImageStats($canvas);
        $face = $this->doDetectGreedyBigToSmall(
            $stats->ii,
            $stats->ii2,
            $stats->width,
            $stats->height
        );
        if (!$face) {
            return null;
        }
        if ($face['w'] > 0) {
            $face['x'] *= $ratio;
            $face['y'] *= $ratio;
            $face['w'] *= $ratio;
        }
        return $face;
    }

    /**
     * @param array $ii
     * @param array $ii2
     * @param int $width
     * @param int $height
     * @return array|null
     */
    protected function doDetectGreedyBigToSmall(array $ii, array $ii2, int $width, int $height) :? array
    {
        $s_w = $width / 20.0;
        $s_h = $height / 20.0;
        $start_scale = $s_h < $s_w ? $s_h : $s_w;
        $scale_update = 1 / 1.2;
        for ($scale = $start_scale; $scale > 1; $scale *= $scale_update) {
            $w = round(20 * $scale) >> 0;
            $endx = $width - $w - 1;
            $endy = $height - $w - 1;
            $step = round(max($scale, 2)) >> 0;
            $inv_area = 1 / ($w*$w);
            for ($y = 0; $y < $endy; $y += $step) {
                for ($x = 0; $x < $endx; $x += $step) {
                    $passed = $this->detectOnSubImage($x, $y, $scale, $ii, $ii2, $w, $width + 1, $inv_area);
                    if ($passed) {
                        return ['x' => $x, 'y' => $y, 'w' => $w];
                    }
                } // end x
            } // end y
        }  // end scale
        return null;
    }

    /**
     * @param int $x
     * @param int $y
     * @param float $scale
     * @param array $ii
     * @param array $ii2
     * @param int $w
     * @param int $iiw
     * @param float $inv_area
     * @return bool
     */
    protected function detectOnSubImage(int $x, int $y, float $scale, array $ii, array $ii2, int $w, int $iiw, float $inv_area) : bool
    {
        $mean = ($ii[($y + $w) * $iiw + $x + $w] + $ii[$y * $iiw + $x] - $ii[($y + $w) * $iiw + $x] - $ii[$y * $iiw + $x + $w]) * $inv_area;
        $vnorm = ($ii2[($y + $w) * $iiw + $x + $w]
                  + $ii2[$y * $iiw + $x]
                  - $ii2[($y + $w) * $iiw + $x]
                  - $ii2[$y * $iiw + $x + $w]) * $inv_area - ($mean * $mean);
        $vnorm = $vnorm > 1 ? sqrt($vnorm) : 1;
        $count_data = count($this->detectionData);
        for ($i_stage = 0; $i_stage < $count_data; $i_stage++) {
            $stage = $this->detectionData[$i_stage];
            $trees = $stage[0];
            $stage_thresh = $stage[1];
            $stage_sum = 0;
            $count_trees = count($trees);
            for ($i_tree = 0; $i_tree < $count_trees; $i_tree++) {
                $tree = $trees[$i_tree];
                $current_node = $tree[0];
                $tree_sum = 0;
                while ($current_node != null) {
                    $vals = $current_node[0];
                    $node_thresh = $vals[0];
                    $leftval = $vals[1];
                    $rightval = $vals[2];
                    $leftidx = $vals[3];
                    $rightidx = $vals[4];
                    $rects = $current_node[1];

                    $rect_sum = 0;
                    $count_rects = count($rects);

                    for ($i_rect = 0; $i_rect < $count_rects; $i_rect++) {
                        $s = $scale;
                        $rect = $rects[$i_rect];
                        $rx = round($rect[0] * $s + $x) >> 0;
                        $ry = round($rect[1] * $s + $y) >> 0;
                        $rw = round($rect[2] * $s) >> 0;
                        $rh = round($rect[3] * $s) >> 0;
                        $wt = $rect[4];
                        $r_sum = ($ii[($ry + $rh) * $iiw + $rx + $rw]
                                  + $ii[$ry * $iiw + $rx]
                                  - $ii[($ry + $rh) * $iiw + $rx]
                                  - $ii[$ry * $iiw + $rx + $rw]) * $wt;
                        $rect_sum += $r_sum;
                    }
                    $rect_sum *= $inv_area;
                    $current_node = null;
                    if ($rect_sum >= $node_thresh * $vnorm) {
                        if ($rightidx == -1) {
                            $tree_sum = $rightval;
                        } else {
                            $current_node = $tree[$rightidx];
                        }
                    } else {
                        if ($leftidx == -1) {
                            $tree_sum = $leftval;
                        } else {
                            $current_node = $tree[$leftidx];
                        }
                    }
                }
                $stage_sum += $tree_sum;
            }
            if ($stage_sum < $stage_thresh) {
                return false;
            }
        }
        return true;
    }
}

