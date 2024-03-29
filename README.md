# PHP Compatibility Checker

When updating PHP versions in a project, vendors do not always indicate the correct PHP versions, with which their package works correctly. 

This list is compiled manually to help you update the PHP version in your project.

## Installation and usage
This package requires PHP 8.1 or higher.

### Download the latest release
[Latest release](../../releases/latest/download/compatibility-checker.phar)

### Specify the desired PHP version and the full path to the `composer.lock` file
```shell
php compatibility-checker.phar 8.0 ./composer.lock
```

## Example output

### Incompatibilities found
![](./images/incompatibilities-found.jpg)

### No incompatibilities found
![](./images/no-incompatibilities-found.jpg)
