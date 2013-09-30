# vendor/bin/build.php
svn checkout http://libphonenumber.googlecode.com/svn/trunk/ libphonenumber-data-dir
vendor/bin/build.php GeneratePhonePrefixData libphonenumber-data-dir/resources/geocoding/ src/libphonenumber/geocoding/data/
vendor/bin/build.php GeneratePhonePrefixData libphonenumber-data-dir/resources/test/geocoding/ tests/data/
