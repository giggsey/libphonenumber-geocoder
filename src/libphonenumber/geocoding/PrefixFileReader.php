<?php

namespace libphonenumber\geocoding;

use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberType;

/**
 * A helper class doing file handling and lookup of phone number prefix mappings.
 *
 * @package libphonenumber\geocoding
 */
class PrefixFileReader
{
    private $phonePrefixDataDirectory;
    /**
     * The mappingFileProvider knows for which combination of countryCallingCode and language a phone
     * prefix mapping file is available in the file system, so that a file can be loaded when needed.
     * @var MappingFileProvider
     */
    private $mappingFileProvider;
    /**
     * A mapping from countryCallingCode_lang to the corresponding phone prefix map that has been
     * loaded.
     * @var array
     */
    private $availablePhonePrefixMaps = array();

    public function __construct($phonePrefixDataDirectory)
    {
        $this->phonePrefixDataDirectory = $phonePrefixDataDirectory;
        $this->loadMappingFileProvider();
    }

    private function loadMappingFileProvider()
    {
        $mapPath = $this->phonePrefixDataDirectory . DIRECTORY_SEPARATOR . "Map.php";
        if (!file_exists($mapPath)) {
            throw new \InvalidArgumentException("Invalid data directory");
        }

        $map = require $mapPath;

        $this->mappingFileProvider = new MappingFileProvider($map);
    }


    /**
     * @param $prefixMapKey
     * @param $language
     * @param $script
     * @param $region
     * @return AreaCodeMap|null
     */
    public function getPhonePrefixDescriptions($prefixMapKey, $language, $script, $region)
    {
        $fileName = $this->mappingFileProvider->getFileName($prefixMapKey, $language, $script, $region);
        if (strlen($fileName) == 0) {
            return null;
        }

        if (!in_array($fileName, $this->availablePhonePrefixMaps)) {
            $this->loadAreaCodeMapFromFile($fileName);
        }

        return $this->availablePhonePrefixMaps[$fileName];
    }

    private function loadAreaCodeMapFromFile($fileName)
    {
        $path = $this->phonePrefixDataDirectory . DIRECTORY_SEPARATOR . $fileName;
        if (!file_exists($path)) {
            throw new \InvalidArgumentException("Data does not exist");
        }

        $map = require $path;
        $areaCodeMap = new AreaCodeMap($map);

        $this->availablePhonePrefixMaps[$fileName] = $areaCodeMap;
    }

    public function mayFallBackToEnglish($language)
    {
        // Don't fall back to English if the requested language is among the following:
        // - Chinese
        // - Japanese
        // - Korean
        return ($language != 'zh' && $language != 'ja' && $language != 'ko');
    }

    /**
     * Returns a text description in the given language for the given phone number.
     *
     * @param PhoneNumber $number  the phone number for which we want to get a text description
     * @param string $language  two-letter lowercase ISO language codes as defined by ISO 639-1
     * @param string $script  four-letter titlecase (the first letter is uppercase and the rest of the letters
     *     are lowercase) ISO script codes as defined in ISO 15924
     * @param string $region  two-letter uppercase ISO country codes as defined by ISO 3166-1
     * @return string a text description in the given language for the given phone number, or an empty
     *     string if a description is not available
     */
    public function getDescriptionForNumber(PhoneNumber $number, $language, $script, $region) {
        $countryCallingCode = $number->getCountryCode();
        // As the NANPA data is split into multiple files covering 3-digit areas, use a phone number
        // prefix of 4 digits for NANPA instead, e.g. 1650.
        $phonePrefix = ($countryCallingCode !== 1) ? $countryCallingCode : (1000 + intval(
                $number->getNationalNumber() / 10000000
            ));
        $phonePrefixDescriptions = $this->getPhonePrefixDescriptions($phonePrefix, $language, $script, $region);

        $description = ($phonePrefixDescriptions !== null) ? $phonePrefixDescriptions->lookup($number) : null;
        // When a location is not available in the requested language, fall back to English.
        if (($description === null || strlen($description) === 0) && $this->mayFallBackToEnglish($language)) {
            $defaultMap = $this->getPhonePrefixDescriptions($phonePrefix, "en", "", "");
            if ($defaultMap === null) {
                return "";
            }
            $description = $defaultMap->lookup($number);
        }

        return ($description !== null) ? $description : "";
    }

}

/* EOF */ 