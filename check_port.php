<?php

$fp = fsockopen('67.231.31.142', 465, $errno, $errstr, 5);
// print_r($fp);
// die('End');
if (!$fp) {
    echo "port is closed or blocked";
    die;
} else {
    echo "port is open and available";

    fclose($fp);
}