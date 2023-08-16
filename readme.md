# Laraman

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]


Run laravel with workman , 1 artisan command, 10x speed up

v2.0.3 released ! 

_support dcat/admin and owl-admin now_


## Installation

Via Composer

``` bash

# install package
composer require itinysun/laraman


# install publish file
php artisan vendor:publish --tag=laraman.install

# update publish as needed

php artisan vendor:publish --tag=laraman.install --force
```

## Usage

```php

//run
php laraman

//run a custom process
php larman process {process name}


//config/laraman/server.php
//this is for auto start process name,web for inner build web server ,
// monitor for hot reload after edit ,only enable under debug mode
// see process config in process.php
    'processes'=>[
        'web','monitor'
    ]




```
## how to write a custom process
create a new class extend Itinysun\Laraman\Process
create a new config file in config/laraman/
if it needs auto start , add config name in server.php



## Change log

Please see the [changelog](changelog.md) for more information on what has changed recently.



## Contributing

Please see [contributing.md](contributing.md) for details and a todolist.

## Security

If you discover any security related issues, please email 860760361@qq.com instead of using the issue tracker.

## Credits

- [itinysun][link-author]
- [All Contributors][link-contributors]

## License

MIT

[ico-version]: https://img.shields.io/packagist/v/itinysun/laraman.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/itinysun/laraman.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/itinysun/laraman/master.svg?style=flat-square
[ico-styleci]: https://styleci.io/repos/12345678/shield

[link-packagist]: https://packagist.org/packages/itinysun/laraman
[link-downloads]: https://packagist.org/packages/itinysun/laraman
[link-travis]: https://travis-ci.org/itinysun/laraman
[link-styleci]: https://styleci.io/repos/12345678
[link-author]: https://github.com/itinysun
[link-contributors]: ../../contributors
