<?php
namespace App\Background\Helpers;


class CsvHelper
{
    public static function arrayFromCSV($file, $hasFieldNames = false, $delimiter = ',', $enclosure = '"')
    {
        $result = Array();
        $size = filesize($file) + 1;
        $file = fopen($file, 'r');
        #TO DO: There must be a better way of finding out the size of the longest row... until then
        if ($hasFieldNames) {
            $keys = fgetcsv($file, $size, $delimiter, $enclosure);
        }
        while ($row = fgetcsv($file, $size, $delimiter, $enclosure)) {
            $n = count($row);
            $res = array();
            for ($i = 0; $i < $n; $i++) {
                $idx = ($hasFieldNames) ? $keys[$i] : $i;
                $res[$idx] = $row[$i];
            }
            $result[] = $res;
        }
        fclose($file);
        return $result;
    }
}
