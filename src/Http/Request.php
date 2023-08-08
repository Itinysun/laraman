<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Itinysun\Laraman\Http;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Workerman\Protocols\Http\Request as WorkmanRequest;

/**
 * Class Request
 * @package Webman\Http
 */
class Request extends \Symfony\Component\HttpFoundation\Request
{
    public static function createFromWorkmanRequest(WorkmanRequest $workmanRequest): \Illuminate\Http\Request
    {
        dump($workmanRequest->path());
        return new \Illuminate\Http\Request($workmanRequest->get(),$workmanRequest->post(),[],$workmanRequest->cookie(),self::resolveFiles($workmanRequest),self::resolveServerParams($workmanRequest),$workmanRequest->rawBody());
    }
    protected static function resolveServerParams(WorkmanRequest $workmanRequest): array
    {
        $server = [];
        foreach ($workmanRequest->header() as $key=>$v){
            $server['HTTP_'.strtoupper($key)]=$v;
        }
        $server['REQUEST_METHOD']=$workmanRequest->method();
        $server['DOCUMENT_ROOT']=public_path();
        $server['REMOTE_ADDR']=$workmanRequest->connection->getRemoteIp();
        $server['REMOTE_PORT']=$workmanRequest->connection->getRemotePort();
        $server['SERVER_SOFTWARE']='laraman';
        $server['SERVER_PROTOCOL']='HTTP/'.$workmanRequest->protocolVersion();
        $server['SERVER_NAME']=$workmanRequest->host();
        $server['SERVER_PORT']=substr($server['HTTP_HOST'],strpos($server['HTTP_HOST'],':'));
        $server['REQUEST_URI']=$workmanRequest->path().'?'.$workmanRequest->queryString();
        $server['SCRIPT_NAME']='/laraman';
        $server['SCRIPT_FILENAME']='/laraman';
        $server['PHP_SELF']='laraman';
        $server['REQUEST_TIME_FLOAT']=microtime(true);
        $server['REQUEST_TIME']=time();
        return $server;
    }

    protected static function resolveFiles(WorkmanRequest $workmanRequest): array
    {
        $parameters=[];
        foreach ($workmanRequest->file() as $name => $file){
            $parameters[$name]=new UploadedFile($file['tmp_name'],$file['name'],$file['type'],$file['error']);
        }
        return $parameters;
    }
}
