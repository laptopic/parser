<?php

namespace App;


use App\Container\Container;

final class App
{
    private static Container $dic;

    public function __construct(array $config = [])
    {
        $configInitial = file_exists('./config.initial.php') ? require './config.initial.php' : [];
        $configLocal = file_exists('./config.php') ? require './config.php' : [];
        $config = array_merge($configInitial, $configLocal, $config);
        self::$dic = new Container($config);
    }

    public function run()
    {
        $arguments = (self::get('input'))->export();
        $app_path = $arguments[0] ?? '';
        $pathConfigLocal = __DIR__."/Packages/".$app_path."/config.php";
        $configLocal = file_exists($pathConfigLocal) ? require $pathConfigLocal : [];
        self::$dic->setConfig('local', $configLocal);
        if (!empty($app_path)) {
            $classname = '\\App\\Packages\\' . $app_path . '\\' . $app_path;
            if (!class_exists($classname, true)) {
                self::get('output')->writeLn("File {$app_path} does not contains class {$classname}!");
                return false;
            }
            $app = new $classname();
            $app->handle();
        }
    }

    public static function has(string $id)
    {
        return self::$dic->has($id);
    }

    public static function get(string $id)
    {
        return self::$dic->get($id);
    }

    public static function build(string $id, array $params = [])
    {
        return self::$dic->build($id, $params);
    }

    public static function setConfig(string $id, array $array = [])
    {
        return self::$dic->setConfig('local', $array);
    }


}