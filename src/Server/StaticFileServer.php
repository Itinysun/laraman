<?php

namespace Itinysun\Laraman\Server;

use Fruitcake\Cors\CorsService;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Itinysun\Laraman\Console\ConsoleApp;
use Itinysun\Laraman\Http\Response;

class StaticFileServer
{
    static CorsService|null $cors;
    static array $allowed = [];
    public static bool $enabled = false;
    public static \Closure $isSafePath;

    public static array $cors_paths = [];

    public static function init(): void
    {
        $config = config('laraman.static');
        static::$isSafePath = function ($path) {
            return false;
        };

        if ($config['enable'] && !empty($config['allowed'])) {
            static::$enabled = true;
            foreach ($config['allowed'] as $v) {
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

        if ($config['cors']) {
            static::$cors = App::make(CorsService::class, $cors_config);
        } else {
            static::$cors = null;
        }
        static::$cors_paths = config('cors.paths', []);
    }

    public static function resolvePath(string $path, Request $request): bool|Response
    {
        $path = public_path($path);
        $file = realpath($path);
        clearstatcache($file);
        if (file_exists($file)) {
            $checkSafePath = static::$isSafePath;
            if ($checkSafePath($file)) {
                $cors = static::$cors;
                if ($cors !== null) {
                    if (static::hasMatchingPath($request)) {
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
                        return (new Response(200, $response->headers->all()))->withFile($file);
                    }
                }
                return (new Response(200))->withFile($file);
            } else {
                return new Response(403, []);
            }
        }
        return false;
    }


    /**
     * Get the path from the configuration to determine if the CORS service should run.
     *
     * @param Request $request
     * @return bool
     */
    protected static function hasMatchingPath(Request $request): bool
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
}
