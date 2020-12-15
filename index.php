<?php

use App\App;

require_once __DIR__ . '/vendor/autoload.php';

(new App())->run();
/*
$arguments = (new \App\Core\Input())->getArgument()->export();

$app_path = $arguments[0] ?? '';
//$output = new \mpr\io\output();

if (!empty($app_path)) {
    $classname = '\\App\\Packages\\'.$app_path.'\\'.$app_path;
    if (!class_exists($classname, true)) {
        //$output->writeLn("File {$app_path} does not contains class {$classname}!");
        return false;
    }
    $app = new $classname();
    $app->run();
}
*/