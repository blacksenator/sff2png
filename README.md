# sff2png

## Purpose

This class provides functions to read structured FAX files (SFF) and convert them into PNG file(s).

## Requirements

* PHP 7.3 or higher
* Composer (follow the installation guide at <https://getcomposer.org/download/)>

## Installation

You can install it through Composer:

```js
"require": {
    "blacksenator/sff2png": "^1.0"
},
```

or

```console
git clone https://github.com/blacksenator/fritzsoap.git
```

## Usage

```PHP
<?PHP

require_once 'sffImager.php';

use blacksenator\sff\sffImager;

$rawData = file_get_contents('Test_2.sff');
$sFFile = new sffImager($rawData);

$images = $sFFile->getSFFasPNG($rawData);

foreach ($images as $number => $image) {
    file_put_contents('FAX_page_' . ($number + 1) . '.png', $image);
}
```

## License

This script is released under MIT license.

## Author

Copyright (c) 2022 Volker Püschel
