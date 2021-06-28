Baraja Console
==============

![Integrity check](https://github.com/baraja-core/nette-symfony-console/workflows/Integrity%20check/badge.svg)

Easy integration of Symfony Console into Nette framework.

This library provides a fully functional implementation of Symfony Console into Nette Framework including basic configuration. There is nothing to configure for use, you simply install the package, register the extension and you can start using the console commands immediately.

The main goal of this library is maximum simplicity and best compatibility. You simply install the library and you don't have to do anything.

📦 Installation
---------------

It's best to use [Composer](https://getcomposer.org) for installation, and you can also find the package on
[Packagist](https://packagist.org/packages/baraja-core/nette-symfony-console) and
[GitHub](https://github.com/baraja-core/nette-symfony-console).

To install, simply use the command:

```shell
$ composer require baraja-core/nette-symfony-console
```

You can use the package manually by creating an instance of the internal classes, or register a DIC extension to link the services directly to the Nette Framework.

How to use
----------

In your `common.neon` simply register:

```yaml
services:
    console: Baraja\Console\ConsoleExtension
```

Configuration
-------------

In your `common.neon` you can use this fields.

For example:

```yaml
console:
    url: https://baraja.cz
    name: My application
```

| Field             | Type             | Description |
|-------------------|------------------|-------------|
| `url`             | string|null      | The default absolute URL of the project (for example, `https://baraja.cz`) to use for generating links.
| `name`            | string           | Project name.
| `version`         | string|int|float | Project version (must be a number or a numeric string).
| `catchExceptions` | bool             | If Command throws an exception, should it be caught and logged?
| `autoExit`        | bool             | Should the application be automatically terminated after processing Command?
| `helperSet`       | string|object    | Helper Settings.
| `helpers`         | string[]         | Registration of classes for helpers.
| `lazy`            | bool             | Register the `CommandLoaderInterface` service and look for Commands only on the first attempt to call it.

📄 License
-----------

`baraja-core/nette-symfony-console` is licensed under the MIT license. See the [LICENSE](https://github.com/baraja-core/nette-symfony-console/blob/master/LICENSE) file for more details.
