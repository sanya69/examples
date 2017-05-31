<?php

$db_params = array(
    'host'    => 'localhost',
    'dbname'  => 'catalog',
    'user'    => 'root',
    'pass'    => '123321',
    'charset' => 'utf8'
);

$dsn = 'mysql:host='.$db_params['host'].';dbname='.$db_params['dbname'].';charset='.$db_params['charset'];

try {
    $db_connect = new PDO($dsn, $db_params['user'], $db_params['pass']);
} catch (PDOException $e) {
    echo 'Подключение к БД не удалось: '.$e->getMessage().'<br>';
    exit;
}