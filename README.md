# QR Payment

[![Latest Stable Version](https://poser.pugx.org/a-d-w-s/qr-payment/v/stable)](https://packagist.org/packages/a-d-w-s/qr-payment)
[![Total Downloads](https://poser.pugx.org/a-d-w-s/qr-payment/downloads)](https://packagist.org/packages/a-d-w-s/qr-payment)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)

Library for generating QR payments in PHP

## Installing using Composer

`composer require a-d-w-s/qr-payment`

### QR payment example

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use ADWS\QRPayment\QR;

$qr = new QR
    ->setAccount('12-3456789012/0100')
    ->setVariableSymbol('2016001234')
    ->setMessage('QR payment')
    ->setSpecificSymbol('0308')
    ->setSpecificSymbol('1234')
    ->setCurrency('CZK')
    ->setDueDate(new \DateTime());

echo $qr->getQRCodeImage();
```

A simpler notation can also be used:

```php
echo QR::create('12-3456789012/0100', 987.60)
    ->setMessage('QR payment')
    ->getQRCodeImage();
```

### Other options

Save to file
```php
// Saves a webp of size 200x200px
$qrPayment->saveQRCodeImage('qrcode', 'webp', 100);

// Saves a png of size 200x200px
$qrPayment->saveQRCodeImage('qrcode', 'png', 100);

// Saves a svg of size 200x200px with 10px margin
$qrPayment->saveQRCodeImage('qrcode', 'svg', 100, 10);
```

The current possible formats are:
* Webp
* Png
* Svg
* Pdf
* Eps
* binary

Show data-uri
```php
// data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAUAAAAFAAQMAAAD3XjfpAAAA...
echo $qrInvoice->getQRCodeImage(false);
```

## Links

- Fork from - https://github.com/bonami/qr-platba
