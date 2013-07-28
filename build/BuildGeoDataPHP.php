<?php

namespace libphonenumber\Build;

class BuildGeoDataPHP
{

    const HELP_MESSAGE = <<<'EOT'
  Usage:
  BuildGeoDataPHP <inputDir> <outputDir> <forTesting>

  where:
    inputDir    The input directory containing the locale/region.txt files
    outputDir    The output source directory
EOT;
    const NANPA_COUNTRY_CODE = 1;
    const DATA_FILE_EXTENSION = '.txt';
    public $inputDir;
    private $filesToIgnore = array('.', '..', '.svn', '.git');
    private $outputDir;
    private $englishMaps = array();

    public function start($argc, $argv)
    {
        if ($argc != 3) {
            echo self::HELP_MESSAGE;
            return false;
        }
        $this->inputDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $argv[1] . DIRECTORY_SEPARATOR;
        $this->outputDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $argv[2] . DIRECTORY_SEPARATOR;

        $inputOutputMappings = $this->createInputOutputMappings();
        $availableDataFiles = array();

        foreach ($inputOutputMappings as $textFile => $outputFiles) {
            self::output("Processing {$textFile}");
            $mappings = $this->readMappingsFromFile($textFile);

            $language = $this->getLanguageFromTextFile($textFile);

            $this->removeEmptyEnglishMappings($mappings, $language);
            $this->makeDataFallbackToEnglish($textFile, $mappings);
            self::output("Splitting up the mappings");
            $mappingForFiles = $this->splitMap($mappings, $outputFiles);

            foreach ($mappingForFiles as $outputFile => $value) {
                $this->writeMappingFile($language, $outputFile, $value);
                $this->addConfigurationMapping($availableDataFiles, $language, $outputFile);
            }
        }

        $this->writeConfigMap($availableDataFiles);

    }

    private function createInputOutputMappings()
    {
        $topLevel = scandir($this->inputDir);

        $mappings = array();

        foreach ($topLevel as $languageDirectory) {
            if (in_array($languageDirectory, $this->filesToIgnore)) {
                continue;
            }

            $fileLocation = $this->inputDir . DIRECTORY_SEPARATOR . $languageDirectory;

            if (is_dir($fileLocation)) {
                // Will contain files

                $countryCodeFiles = scandir($fileLocation);

                foreach ($countryCodeFiles as $countryCodeFileName) {
                    if (in_array($countryCodeFileName, $this->filesToIgnore)) {
                        continue;
                    }


                    $outputFiles = $this->createOutputFileNames(
                        $countryCodeFileName,
                        $this->getCountryCodeFromTextFileName($countryCodeFileName),
                        $languageDirectory
                    );

                    $mappings[$languageDirectory . DIRECTORY_SEPARATOR . $countryCodeFileName] = $outputFiles;
                }
            }
        }

        return $mappings;
    }

    /**
     * Method used by {@code #createInputOutputMappings()} to generate the list of output binary files
     * from the provided input text file. For the data files expected to be large (currently only
     * NANPA is supported), this method generates a list containing one output file for each area
     * code. Otherwise, a single file is added to the list.
     */

    private function createOutputFileNames($file, $countryCode, $language)
    {
        $outputFiles = array();

        if ($countryCode == self::NANPA_COUNTRY_CODE) {
            // Fetch the 4-digit prefixes stored in the file.
            $phonePrefixes = array();

            $this->parseTextFile(
                $this->getFilePathFromLanguageAndCountryCode($language, $countryCode),
                function ($prefix, $location) use (&$phonePrefixes) {
                    $phonePrefixes[] = substr($prefix, 0, 4);
                }
            );

            foreach ($phonePrefixes as $prefix) {
                $outputFiles[] = $this->generateFilename($prefix, $language);
            }
        } else {
            $outputFiles[] = $this->generateFilename($countryCode, $language);
        }

        return $outputFiles;
    }

    /**
     * Reads phone prefix data from the provides file path and invokes the given handler for each
     * mapping read.
     *
     * @param $filePath
     * @param $handler
     * @return array
     * @throws \InvalidArgumentException
     */
    private function parseTextFile($filePath, \Closure $handler)
    {

        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new \InvalidArgumentException("File '{$filePath}' does not exist");
        }

        $data = file($filePath);

        $countryData = array();

        foreach ($data as $line) {
            // Remove \n
            $line = str_replace("\n", "", $line);
            $line = str_replace("\r", "", $line);
            $line = trim($line);

            if (strlen($line) == 0 || substr($line, 0, 1) == '#') {
                continue;
            }
            if (strpos($line, '|')) {
                // Valid line
                $parts = explode('|', $line);


                $prefix = $parts[0];
                $location = $parts[1];

                $handler($prefix, $location);
            }

        }

        return $countryData;

    }

    private function getFilePathFromLanguageAndCountryCode($language, $code)
    {
        return $this->getFilePath($language . DIRECTORY_SEPARATOR . $code . self::DATA_FILE_EXTENSION);
    }

    private function getFilePath($fileName)
    {
        $path = $this->inputDir . $fileName;

        return $path;
    }

    private function generateFilename($prefix, $language)
    {
        return $language . DIRECTORY_SEPARATOR . $prefix . self::DATA_FILE_EXTENSION;
    }

    private function getCountryCodeFromTextFileName($countryCodeFileName)
    {
        return str_replace(self::DATA_FILE_EXTENSION, '', $countryCodeFileName);
    }

    public static function output($msg)
    {
        echo date('r') . ' - ' . $msg . PHP_EOL;
    }

    private function readMappingsFromFile($inputFile)
    {
        $areaCodeMap = array();

        $this->parseTextFile(
            $this->inputDir . $inputFile,
            function ($prefix, $location) use (&$areaCodeMap) {
                $areaCodeMap[$prefix] = $location;
            }
        );

        return $areaCodeMap;
    }

    private function getLanguageFromTextFile($textFile)
    {
        $parts = explode(DIRECTORY_SEPARATOR, $textFile);

        return $parts[0];
    }

    private function removeEmptyEnglishMappings(&$mappings, $language)
    {
        if ($language != "en") {
            return;
        }

        foreach ($mappings as $k => $v) {
            if ($v == "") {
                unset($mappings[$k]);
            }
        }
    }

    private function makeDataFallbackToEnglish($textFile, &$mappings)
    {
        $englishPath = $this->getEnglishDataPath($textFile);

        if ($textFile == $englishPath || !file_exists($this->getFilePath($englishPath))) {
            return;
        }

        $countryCode = $this->getCountryCodeFromTextFileName($textFile);

        if (!array_key_exists($countryCode, $this->englishMaps)) {
            $englishMap = $this->getEnglishDataFile($countryCode);

            $this->englishMaps[$countryCode] = $englishMap;
        }

        $this->compressAccordingToEnglishData($this->englishMaps[$countryCode], $mappings);
    }

    private function getEnglishDataPath($textFile)
    {
        return "en" . substr($textFile, -2);
    }

    private function getEnglishDataFile($callingCode)
    {
        return $this->readMappingFile("en", $callingCode);
    }

    private function readMappingFile($language, $code)
    {
        $path = $this->getFilePathFromLanguageAndCountryCode($language, $code);

        if ($path === null) {
            return array();
        }

    }

    private function compressAccordingToEnglishData($englishMap, &$nonEnglishMap)
    {
        foreach ($nonEnglishMap as $prefix => $value) {

            if (array_key_exists($prefix, $englishMap)) {
                $englishDescription = $englishMap[$prefix];
                if ($englishDescription == $value) {
                    if (!$this->hasOverlappingPrefix($prefix, $nonEnglishMap)) {
                        unset($nonEnglishMap[$prefix]);
                    } else {
                        $nonEnglishMap[$prefix] = "";
                    }
                }
            }
        }
    }

    private function hasOverlappingPrefix($number, $mappings)
    {
        while (strlen($number) > 0) {
            $number = substr($number, 0, -1);

            if (array_key_exists($number, $mappings)) {
                return true;
            }
        }

        return false;
    }

    private function splitMap($mappings, $outputFiles)
    {
        $mappingForFiles = array();

        foreach ($mappings as $prefix => $location) {
            self::output("Attempting to split up {$prefix}: {$location}");
            $targetFile = null;

            foreach ($outputFiles as $k => $outputFile) {
                $outputFilePrefix = $this->getPhonePrefixLanguagePairFromFilename($outputFile)->prefix;
                if (self::startsWith($prefix, $outputFilePrefix)) {
                    $targetFile = $outputFilePrefix;
                    break;
                }
            }


            if (!array_key_exists($targetFile, $mappingForFiles)) {
                $mappingForFiles[$targetFile] = array();
            }
            $mappingForFiles[$targetFile][$prefix] = $location;
        }

        return $mappingForFiles;
    }

    /**
     * Extracts the phone prefix and the language code contained in the provided file name.
     */
    private function getPhonePrefixLanguagePairFromFilename($outputFile)
    {
        $parts = explode(DIRECTORY_SEPARATOR, $outputFile);

        $returnObj = new \stdClass();
        $returnObj->language = $parts[0];

        $returnObj->prefix = $this->getCountryCodeFromTextFileName($parts[1]);

        return $returnObj;
    }

    /**
     *
     * @link http://stackoverflow.com/a/834355/403165
     * @param $haystack
     * @param $needle
     * @return bool
     */
    private static function startsWith($haystack, $needle)
    {
        return !strncmp($haystack, $needle, strlen($needle));
    }

    private function writeMappingFile($language, $outputFile, $data)
    {

        if (!file_exists($this->outputDir . $language)) {
            mkdir($this->outputDir . $language);
        }

        $phpSource = '<?php' . PHP_EOL . 'return ' . var_export($data, true) . ';';

        $outputPath = $this->outputDir . $language . DIRECTORY_SEPARATOR . $outputFile . '.php';

        file_put_contents($outputPath, $phpSource);
    }

    private function addConfigurationMapping(&$availableDataFiles, $language, $prefix)
    {
        if (!array_key_exists($language, $availableDataFiles)) {
            $availableDataFiles[$language] = array();
        }

        $availableDataFiles[$language][] = $prefix;
    }

    private function writeConfigMap($availableDataFiles)
    {
        $phpSource = '<?php' . PHP_EOL . 'return ' . var_export($availableDataFiles, true) . ';';

        $outputPath = $this->outputDir . 'Map.php';

        file_put_contents($outputPath, $phpSource);
    }
}


$BuildGeoDataPHP = new BuildGeoDataPHP();
$BuildGeoDataPHP->start($argc, $argv);


/* EOF */