# libphonenumber's Geocoder for PHP [![Build Status](https://travis-ci.org/giggsey/libphonenumber-geocoder.png?branch=master)](https://travis-ci.org/giggsey/libphonenumber-geocoder)

[![Total Downloads](https://poser.pugx.org/giggsey/libphonenumber-geocoder/downloads.png)](https://packagist.org/packages/giggsey/libphonenumber-geocoder)
[![Latest Stable Version](https://poser.pugx.org/giggsey/libphonenumber-geocoder/v/stable.png)](https://packagist.org/packages/giggsey/libphonenumber-geocoder)

## What is it?
A PHP library for providing geographical information for a phone number. This library is based on Google's [libphonenumber](https://code.google.com/p/libphonenumber/) and requires [libphonenumber-for-php](https://github.com/giggsey/libphonenumber-for-php) to work.

This library requires the PECL [intl](http://php.net/intl) extension to be installed.

## Installation

The library can be installed via [composer](http://getcomposer.org/). You can also use any other [PSR-0](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md) compliant autoloader.

```json
{
    "require": {
        "giggsey/libphonenumber-geocoder": "~5.8"
    }
}
```
## Online Demo
An [online demo](http://giggsey.com/libphonenumber/) is available for both [libphonenumber-for-php](https://github.com/giggsey/libphonenumber-for-php) and [libphonenumber-geocoder](https://github.com/giggsey/libphonenumber-geocoder).


## Examples

```php
$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();

$swissNumberProto = $phoneUtil->parse("044 668 18 00", "CH");
$usNumberProto = $phoneUtil->parse("+1 650 253 0000", "US");
$gbNumberProto = $phoneUtil->parse("0161 496 0000", "GB");

$geocoder = \libphonenumber\geocoding\PhoneNumberOfflineGeocoder::getInstance();

// Outputs "Zurich"
echo $geocoder->getDescriptionForNumber($swissNumberProto, "en_US") . PHP_EOL;
// Outputs "Zürich"
echo $geocoder->getDescriptionForNumber($swissNumberProto, "de_DE") . PHP_EOL;
// Outputs "Zurigo"
echo $geocoder->getDescriptionForNumber($swissNumberProto, "it_IT") . PHP_EOL;


// Outputs "Mountain View, CA"
echo $geocoder->getDescriptionForNumber($usNumberProto, "en_US") . PHP_EOL;
// Outputs "Mountain View, CA"
echo $geocoder->getDescriptionForNumber($usNumberProto, "de_DE") . PHP_EOL;
// Outputs "미국" (Korean for United States)
echo $geocoder->getDescriptionForNumber($usNumberProto, "ko-KR") . PHP_EOL;

// Outputs "Manchester"
echo $geocoder->getDescriptionForNumber($gbNumberProto, "en_GB") . PHP_EOL;
// Outputs "영국" (Korean for United Kingdom)
echo $geocoder->getDescriptionForNumber($gbNumberProto, "ko-KR") . PHP_EOL;
```