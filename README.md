# Rokka PHP Utils


A [PHP](http://php.net/) library to provide some utils function for [Rokka](https://rokka.io/) image service.

## About

[rokka](https://rokka.io) is digital image processing done right. Store, render and deliver images. Easy and blazingly fast. This library allows to upload and manage your image files to rokka and deliver them in the right format, as light and as fast as possible. And you only pay what you use, no upfront and fixed costs.

Free account plans are available. Just install the plugin, register and use it.

## Installation

Require the library using composer:

`composer require rokka/utils`

## Running PHP-CS-Fixer

```
curl https://cs.symfony.com/download/php-cs-fixer-v2.phar > /tmp/php-cs-fixer.phar
php /tmp/php-cs-fixer.phar  fix -v --diff --using-cache=yes src/
```

## Running phpstan

```
mkdir -p vendor/bin/
wget  -O vendor/bin/phpstan.phar https://github.com/phpstan/phpstan/releases/download/0.12.51/phpstan.phar
chmod a+x ./vendor/bin/phpstan.phar  
./vendor/bin/phpstan.phar analyze -c phpstan.neon -l 8 src/
```