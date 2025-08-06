<?php

namespace kivweb;

use kivweb\Controllers\IController;
use kivweb\Models\DatabaseConnection;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("vendor/autoload.php");
require_once("myAutoloader.inc.php");
require_once("settings.php");

$app = new \kivweb\ApplicationStart();
$app->appStart();

