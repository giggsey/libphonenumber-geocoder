<?php
/**
 * Created by PhpStorm.
 * User: giggsey
 * Date: 7/27/13
 * Time: 11:53 PM
 */

namespace libphonenumber\geocoding;


use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberUtil;

class AreaCodeMap
{
    private $areaCodeMapStorage = array();
    /**
     * @var PhoneNumberUtil
     */
    private $phoneUtil;

    public function __construct($map)
    {
        $this->areaCodeMapStorage = $map;
        $this->phoneUtil = PhoneNumberUtil::getInstance();
    }

    /**
     * Returns the description of the geographical area the {@code number} corresponds to. This method
     * distinguishes the case of an invalid prefix and a prefix for which the name is not available in
     * the current language. If the description is not available in the current language an empty
     * string is returned. If no description was found for the provided number, null is returned.
     *
     * @internal param \libphonenumber\PhoneNumber $number the phone number to look up
     * @param \libphonenumber\PhoneNumber $phoneNumber
     * @return string|null bla the description of the geographical area
     */
    public function lookup(PhoneNumber $phoneNumber)
    {
        if (count($this->areaCodeMapStorage) == 0) {
            return null;
        }

        $phonePrefix = $phoneNumber->getCountryCode() . $this->phoneUtil->getNationalSignificantNumber($phoneNumber);

        while (strlen($phonePrefix) > 0) {
            if (array_key_exists($phonePrefix, $this->areaCodeMapStorage)) {
                return $this->areaCodeMapStorage[$phonePrefix];
            }

            $phonePrefix = substr($phonePrefix, 0, -1);
        }

        return null;
    }

} 