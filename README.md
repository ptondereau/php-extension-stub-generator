PHP Extension Stub Generator
===========================================================

This is an updated version with up-to-date dependencies.

PHP ReflectionExtension's Information Rewind to PHP Code As Stub.

# Purpose
Code Completion under IDE.

## USAGE

```
$ php-extension-stub-generator.phar dump-files {extension name} {dir} 
```

## USAGE Example

```
$ php-extension-stub-generator.phar dump-files ast tmp
```

```
$ php -d extension=/home/you/git/nikic_php-ast/modules/ast.so php-extension-stub-generator.phar dump-files ast tmp
```

## MOSTELY YOU DON'T NEED

  - http://stackoverflow.com/questions/30328805/phpstorm-how-to-add-method-stubs-from-a-pecl-library-that-phpstorm-doesnt-curr