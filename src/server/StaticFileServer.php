<?php

namespace Itinysun\Laraman\server;

use Illuminate\Http\Request;
use Itinysun\Laraman\Http\Response;

class StaticFileServer
{
    public static string $public_path = '';
    public static function resolvePath(string $path,Request $request): bool|Response
    {
//        if (str_contains($path, '..') ||
//            str_contains($path, "\\") ||
//            str_contains($path, "\0")) {
//            return response('not allowed', 403);
//        }
        $path = self::$public_path.DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR);
        $file = realpath($path);
        $pub = realpath(self::$public_path);
        clearstatcache($file);
        if(file_exists($file)){
            if(stripos(realpath(self::$public_path), $file)==0){
                return (new Response())->withFile($file);
            }else{
                return new Response(403,[]);
            }
        }
        return false;
    }
}
