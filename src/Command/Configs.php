<?php

namespace Itinysun\Laraman\Command;

use Exception;

/**
 * 运行时配置文件
 */
class Configs
{
    /**
     * @var array
     */
    protected static array $config = [];

    /**
     * @var string
     */
    protected static string $basePath = '';
    /**
     * @var string
     */
    protected static string $runtimePath = '';

    /**
     * @throws Exception
     */
    protected static function init(): void
    {
        if (!empty(self::$config))
            return;
        $exe =PHP_BINARY;

        $cur = self::$basePath.DIRECTORY_SEPARATOR.'artisan';
        $command = "$exe $cur laraman-config";

        $output = shell_exec($command);

        $configs = json_decode($output, true);

        if (is_array($configs)) {
            self::$config = $configs;
            self::$runtimePath = $configs['server']['runtime_path'];
        } else {
            throw new Exception('unable to get config for laraman');
        }
    }

    /**
     * 读取配置
     * @param string $name 想要读取分项的配置名称，为空读取所有
     * @throws Exception
     */
    public static function get(string $name = '')
    {
        self::init();
        if (empty($name)) {
            return self::$config;
        }
        if (array_key_exists($name, self::$config)) {
            return self::$config[$name];
        }
        throw new Exception("$name not found in laraman configs");
    }

    /**
     * 读取运行路径，路径会在初始化配置的时候完成赋值
     * @param string $name 要合并的路径名称，可为空
     * @throws Exception
     */
    public static function runtimePath(string $name = ''): string
    {
        self::init();
        if (empty($name))
            return self::$runtimePath;
        return self::$runtimePath . DIRECTORY_SEPARATOR . $name;
    }

    /**
     * 设置laravel运行的base path(根路径）
     * @param string $path
     * @return void
     */
    public static function setBasePath(string $path): void
    {
        self::$basePath = $path;
    }

    /**
     * 读取laravel运行的base path(根路径），如果没有手动设置，则自动判断(可能不准确)
     * @param string $name
     * @return string
     */
    public static function getBasePath(string $name = ''): string
    {
        if (empty(self::$basePath)) {
            self::$basePath = realpath($_SERVER['PHP_SELF']) ?? getcwd();
        }
        if (empty($name))
            return self::$basePath;
        else
            return self::$basePath . DIRECTORY_SEPARATOR . $name;
    }
}
