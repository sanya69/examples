<?php

/**
 * Проверяет является ли переданная строка арифметической прогрессией
 * запуск из браузера:
 * http://localhost/check_progression.php?string=1,2,3
 * запуск из командной строки: 
 * php /var/www/html/check_progression.php 1,2,3
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

function checkStringOnProgression($string = '') 
{
    if (strpos($string, ',') === false) {
        return 0;
    }
    
    $array = explode(',', $string);
    if (!$array) {
        return 0;
    }
    
    $count = count($array);
    if ($count < 3) {
        return 0;
    }
    
    // находим разность прогрессии
    if (!is_numeric($array[0]) || !is_numeric($array[1])) {
        return 0;
    }
    $diff = $array[1] - $array[0];
    
    for ($i = 2; $i < $count; $i++) {
        if (!is_numeric($array[$i])) {
            return 0;
        }
        if (($array[$i] - $array[$i-1]) != $diff) {
            return 0;
        }
    }
    
    return 1;
}

if (isset($argv)) {
    if (empty($argv[1])) {
        exit("No string\n");
    }
    $string = $argv[1];
} else {
    $string = !empty($_REQUEST['string']) ? $_REQUEST['string'] : exit("No string\n");
}

$check_result = checkStringOnProgression($string);
echo ($check_result ? 'It\'s progression' : 'It isn\'t progression')."\n";