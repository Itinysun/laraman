<?php

namespace Itinysun\Laraman\Command;

use Exception;

class Configs
{
    protected static array $config=[];

    protected static string $basePath='';
    protected static string $runtimePath='';

    /**
     * @throws Exception
     */
    protected static function init(): void
    {
        if(!empty(self::$config))
            return;
        $command = 'php artisan laraman-config';

        $output = shell_exec($command);

        $configs = json_decode($output,true);

        if(is_array($configs)){
            self::$config=$configs;
            self::$runtimePath=$configs['server']['runtime_path'];
        }else{
            throw new Exception('unable to get config for laraman');
        }
    }

    /**
     * @throws Exception
     */
    public static function get(string $name=''){
        self::init();
        if(empty($name)){
            return self::$config;
        }
        if(array_key_exists($name,self::$config)){
            return self::$config[$name];
        }
        throw new Exception("$name not found in laraman configs");
    }

    /**
     * @throws Exception
     */
    public static function runtimePath(string $name=''): string
    {
        self::init();
        if(empty($name))
            return self::$runtimePath;
        return self::$runtimePath.PATH_SEPARATOR.$name;
    }

    public static function setBasePath($path): void
    {
        self::$basePath=$path;
    }

    public static function getBasePath(string $name='') :string{
        if(empty(self::$basePath)){
           self::$basePath=getcwd() ?? realpath($_SERVER['PHP_SELF']);
        }
        if(empty($name))
            return self::$basePath;
        else
            return self::$basePath.PATH_SEPARATOR.$name;
    }
}
