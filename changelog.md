# Changelog

All notable changes to `Laraman` will be documented in this file.

## version 2.0.3
feat:add support for owl-admin , try to detect owl-admin or dcat-admin and make auto support

## version 2.0.1
fix:single process arg detect for boot

## version 2.0.0 released
please force update if old version installed
refactor the start engine
fix upload file bug
add default page feature


## version 1.0.0 released
please force update if old version installed

## version 0.1.0
this is prerelease version
solved problem that can not start -d mode in linux

## version 0.0.7
- bugfix:error count config for process number
- improve:add database heartbeat config

## version 0.0.6
- improve:add database heartbeat config
- modify:split process config file to standalone

## version 0.0.5
- move methods to traits
- add event-listener mode
- add dcat-admin support
- fix bug when response give an unknown statue-code 

## Version 0.0.4

### feat
- add clean mode to support unknown app

## Version 0.0.3

### feat
- Move pid_file,status_file,log_file,event_loop,stop_timeout to server.php as public config
- fix and improve config init when bootstrap
- add support for custom protocol
- change onHttpMessage param from workerman request to laravel request

