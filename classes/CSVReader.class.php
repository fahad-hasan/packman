<?php
/**
 * Created by PhpStorm.
 * User: floyd
 * Date: 28/11/15
 * Time: 7:45 AM
 *
 * This class reads the supplied CSV file and returns a list of items
 *
 * Public: ::read()
 *
 */

namespace App\Classes;

class CSVReader {

    /*
     * Reads the CSV file from $url and returns all items
     * Returns: array()
     */
    public static function read($url) {
        $fp = fopen($url, "r");
        $items = array();
        while(!feof($fp))
        {
            $data = fgetcsv($fp);
            if (!empty($data[0]) && $data[1] >= 0 && $data[2] >= 0)
            $item = array(
                'name' => $data[0],
                'price' => $data[1],
                'weight' => $data[2]
            );
            $items[] = $item;
        }
        return $items;
    }

}