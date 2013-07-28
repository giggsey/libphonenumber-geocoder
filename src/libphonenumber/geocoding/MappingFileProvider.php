<?php
/**
 * Created by PhpStorm.
 * User: giggsey
 * Date: 7/27/13
 * Time: 11:28 PM
 */

namespace libphonenumber\geocoding;


class MappingFileProvider
{

    private $map;

    public function __construct($map)
    {
        $this->map = $map;
    }

    public function getFileName($countryCallingCode, $language, $script)
    {
        if (strlen($language) == 0) {
            return "";
        }

        if ($this->inMap($language, $countryCallingCode)) {
            return $language . DIRECTORY_SEPARATOR . $countryCallingCode . '.php';
        }


        return "";
    }

    private function inMap($language, $countryCallingCode)
    {
        return (array_key_exists($language, $this->map) && in_array($countryCallingCode, $this->map[$language]));
    }

} 