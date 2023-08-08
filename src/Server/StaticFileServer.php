<?php

namespace Itinysun\Laraman\Server;

use Exception;
use Fruitcake\Cors\CorsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Itinysun\Laraman\Http\Response;

class StaticFileServer
{
    static CorsService|null $cors;
    static array $allowed = [];
    public static bool $enabled = false;
    public static \Closure $isSafePath;

    public static array $cors_paths = [];

    public static array $config=[];

    public static function init($params): void
    {
        static::$config= $params;
        static::$isSafePath = function ($path) {
            return false;
        };

        if (static::$config['enable'] && !empty(static::$config['allowed'])) {
            static::$enabled = true;
            foreach (static::$config['allowed'] as $v) {
                static::$allowed[] = realpath($v);
            }
            if (count(static::$allowed) == 1) {
                if (static::$allowed[0] == '*')
                    static::$isSafePath = function ($path) {
                        return true;
                    };
                else {
                    $onlyPath = static::$allowed[0];
                    static::$isSafePath = function ($path) use ($onlyPath) {
                        return stripos($path,$onlyPath) == 0;
                    };
                }
            } else {
                $collect = collect(static::$allowed);
                static::$isSafePath = function ($path) use ($collect) {
                    return $collect->contains(function ($v) use ($path) {
                        return stripos($path,$v) == 0;
                    });
                };
            }
        }

        $cors_config = config('cors');
        static::$cors = App::make(CorsService::class, $cors_config);
        static::$cors_paths = config('cors.paths', []);
    }

    public static function tryServeFile(Request $request): ?Response
    {
        $file = self::resolvePath($request->path());
        if($file === null)
            return null;
        if (static::matchCorsPath($request)) {
            if (static::$cors->isPreflightRequest($request)) {
                $response = static::$cors->handlePreflightRequest($request);
                static::$cors->varyHeader($response, 'Access-Control-Request-Method');
                return new Response(200, $response->headers->all());
            }
            $response = new \Symfony\Component\HttpFoundation\Response();
            if ($request->getMethod() === 'OPTIONS') {
                static::$cors->varyHeader($response, 'Access-Control-Request-Method');
            }
            $response = static::$cors->addActualRequestHeaders($response, $request);
            return self::getResponse($file,$response->headers->all());
        }else{
            return self::getResponse($file);
        }
    }

    public static function resolvePath(string $path): bool|null|string
    {
        if (preg_match('/%[0-9a-f]{2}/i', $path)) {
            $path = urldecode($path);
        }
        $path = public_path($path);
        $file = realpath($path);
        clearstatcache($file);

        //尝试寻找默认页面
        if(is_dir($path)){
            $foundDefault = false;
            foreach (static::$config['defaultPage'] as $page){
                $defaultPageTry = $path.DIRECTORY_SEPARATOR.$page;
                if(file_exists($defaultPageTry)){
                    Log::debug('use defaultPage as static file response',compact($page,$path));
                    $file=$defaultPageTry;
                    $foundDefault = true;
                }
            }
            if(!$foundDefault){
                return null;
            }
        }

        //尝试寻找文件
        if (file_exists($file)) {
            $checkSafePath = static::$isSafePath;
            if ($checkSafePath($file)) {
                return $file;
            } else {
                return false;
            }
        }

        return null;
    }

    public static function getResponse(string|bool $file, array $headers=[]): Response
    {
        if($file===false){
            abort(404,'access deny',$headers);
        }
        if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            if(self::$config['support_php'])
                return (new Response(200, $headers,self::execPhpFile($file)));
            else
                abort(403,'not supported',$headers);
        }
        return (new Response(200,$headers))->withFile($file);
    }


    /**
     * Get the path from the configuration to determine if the CORS service should run.
     *
     * @param Request $request
     * @return bool
     */
    protected static function matchCorsPath(Request $request): bool
    {
        $paths = static::getPathsByHost($request->getHost());

        foreach ($paths as $path) {
            if ($path !== '/') {
                $path = trim($path, '/');
            }

            if ($request->fullUrlIs($path) || $request->is($path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the CORS paths for the given host.
     *
     * @param string $host
     * @return array
     */
    protected static function getPathsByHost(string $host): array
    {

        if (isset(static::$cors_paths[$host])) {
            return static::$cors_paths[$host];
        }

        return array_filter(static::$cors_paths, function ($path) {
            return is_string($path);
        });
    }

    /**
     * ExecPhpFile.
     * @param string $file
     * @return false|string
     */
    public static function execPhpFile(string $file): bool|string
    {
        ob_start();
        // Try to include php file.
        try {
            include $file;
        } catch (Exception $e) {
            echo $e;
        }
        return ob_get_clean();
    }
}
