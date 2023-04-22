<?php

/** Tests for facedetection */
return function () : Generator {
    /** An image with a face gets detected */
    yield function () {
        $detector = new Sensi\Facial\Detector;
        $result = $detector->fromFile(dirname(__DIR__).'/resources/face.jpg');
        assert($result->hasFace() === true);
    };

    /** An image without a face returns false */
    yield function () {
        $detector = new Sensi\Facial\Detector;
        $result = $detector->fromFile(dirname(__DIR__).'/resources/noface.jpg');
        assert($result->hasFace() === false);
    };
};

