svn checkout http://libphonenumber.googlecode.com/svn/trunk/ libphonenumber-data-dir
php build/BuildGeoDataPHP.php libphonenumber-data-dir/resources/geocoding src/libphonenumber/geocoding/data/
php build/BuildGeoDataPHP.php libphonenumber-data-dir/resources/test/geocoding tests/data/