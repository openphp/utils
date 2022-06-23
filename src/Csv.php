<?php

namespace Openphp;

class Csv
{

    /**
     * Simple parser for CSV files
     * @param $file
     * @param string $delimiter
     * @param string $enclosure
     * @param bool $hasHeader
     * @param int $limit
     * @return array
     */
    public static function parse($file, $delimiter = ';', $enclosure = '"', $hasHeader = true, $limit = 10000000)
    {
        $result     = [];
        $headerKeys = [];
        $rowCounter = 0;
        if (($handle = \fopen($file, 'rb')) !== false) {
            while (($row = \fgetcsv($handle, $limit, $delimiter, $enclosure)) !== false) {
                $row = (array)$row;
                if ($rowCounter === 0 && $hasHeader) {
                    $headerKeys = $row;
                } elseif ($hasHeader) {
                    $assocRow = [];
                    foreach ($headerKeys as $colIndex => $colName) {
                        $colName            = (string)$colName;
                        $assocRow[$colName] = $row[$colIndex];
                    }
                    $result[] = $assocRow;
                } else {
                    $result[] = $row;
                }

                $rowCounter++;
            }
            \fclose($handle);
        }
        return $result;
    }
}