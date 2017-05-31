<?php

error_reporting(E_ALL);ini_set('display_errors',1);

require('./catalogController.php');

$catalogController = new catalogController;
$catalogController->showCatalog();