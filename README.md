# PHP Face Detection

This class can detect one face in images ATM.

This is a pure PHP port of existing JS code from Karthik Tharavaad.

Since the package was abandoned by the original author, I forked it and upgraded
it to be compatible with PHP 8.2.

## Requirements
PHP8.2 or higher with GD

## License
GNU GPL v2 (See LICENSE.txt)

## Installation
Composer (recommended):

```sh
$ composer require sensimedia/facial
```

## Usage
```php
<?php

use Sensi\Facial\Detector;

$detector = new Detector;
$detectable = $detector->fromFile('/path/to/file');
var_dump($detectable->hasFace()); // true or false
if ($detectable->hasFace()) {
    $resource = $detectable->getFace();
} else {
    $resource = $detectable->getSource();
}
// Outputs either the face, or the original image if no face was found.
imagejpeg($resource);
```

Additionally, you may also use the fromResource (directly with a GdImage object)
or the fromString (e.g. if your image data is stored in a database) methods to
initialize a detector.

*NOTE:* the fromXXX methods immediately trigger the facial recognition, which -
depending on the size of the image and how much of it actually contains a face -
could be relatively slow (as in, up to a number of _seconds_). So please don't
call these "on the fly"; at the very least, cache the results, but better still
move it to a background process if possible.

