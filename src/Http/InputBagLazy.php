<?php

namespace Itinysun\Laraman\Http;


use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\ServerBag;
use Traversable;

class InputBagLazy implements \IteratorAggregate, \Countable
{
    protected bool $hasInitialized = false;

    protected \Workerman\Protocols\Http\Request $request;

    protected object  $bagInstance;

    protected string $bagInterface;

    protected string $schema;

    protected function initialize(): void
    {
        if($this->hasInitialized)
            return;
        $this->bagInterface = match ($this->schema){
            'file'=>FileBag::class,
            'server'=>ServerBag::class,
            'header'=>HeaderBag::class,
            default=>InputBag::class
        };
        if($this->schema==='server'){
            $parameters = $this->resolveServerParams();
        }else{
            $parameters = call_user_func([$this->request,$this->schema]) ?? [];
        }
        $this->bagInstance = new $this->bagInterface($parameters);
        $this->hasInitialized=true;
    }

    protected function resolveServerParams(): array
    {
        $server = [];
        foreach ($this->request->header() as $key=>$v){
            $server['HTTP_'.strtoupper($key)]=$v;
        }
        $server['REQUEST_METHOD']=$this->request->method();
        $server['DOCUMENT_ROOT']=public_path();
        $server['REMOTE_ADDR']=$this->request->connection->getRemoteIp();
        $server['REMOTE_PORT']=$this->request->connection->getRemotePort();
        $server['SERVER_SOFTWARE']='laraman';
        $server['SERVER_PROTOCOL']='HTTP/'.$this->request->protocolVersion();
        $server['SERVER_NAME']=$this->request->host();
        $server['SERVER_PORT']=substr($server['HTTP_HOST'],strpos($server['HTTP_HOST'],':'));
        $server['REQUEST_URI']=$this->request->path().'?'.$this->request->queryString();
        $server['SCRIPT_NAME']='/laraman';
        $server['SCRIPT_FILENAME']='/laraman';
        $server['PHP_SELF']='laraman';
        $server['REQUEST_TIME_FLOAT']=microtime(true);
        $server['REQUEST_TIME']=time();
        return $server;
    }

    public function __construct(\Workerman\Protocols\Http\Request $request,string $schema)
    {
        $this->request=$request;
        $this->schema=$schema;
        return $this;
    }

    public function __call($name, $arguments) {
        $this->initialize();
        return call_user_func([$this->bagInstance,$name],...$arguments);
    }

    public function getIterator(): Traversable
    {
        $this->initialize();
        return  $this->bagInstance->getIterator();
    }

    public function count(): int
    {
        $this->initialize();
        return  $this->bagInstance->count();
    }
}
