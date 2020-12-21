# PHP-Docblock-Generator

This class will generate DocBlock comment outlines for files/folders.

## Usage

This class will generate docblock outline for files/folders.

```sh
php docblock.php --source=file|folder [ --recursive ] \
   [ --exclude="vendor/ tools/" ] [ --dryrun ] [ --versose ]
```

## Params

Use from command line - params:

```
--source=path       - the file or folder you want to docblock (php files)
--recursive         - optional, recursively go through a folder
--exclude="p1/ p1/" - optional, to exclude a series of paths
--functions="f1 f2" - optional, space delimited docblock only specific methods/functions
--anonymous         - optional, document anonymous functions as well as normal functions
--full              - optional, use full PHPDoc comment blocks instead of short
--dryrun            - optional, test the docblock but do not make any changes
--verbose           - optional, output verbose information on each file processed
```

## Examples

```sh
php docblock.php --sorce=target.php --functions="targetFunction"
php docblock.php --source=target/dir --recursive
php docblock.php --source=target/dir --recursive --dryrun
php docblock.php --source=target/dir --exclude="vendor/ fa/" --recursive --dryrun
```

## TODOs:

 1. add all proper docblock properties
 2. better checking for if docblock already exists
 3. docblocking for class properties
 4. try to gather more data for automatic insertion such as for @access

## ChangeLog

* author    Anthony Gentile
  version   0.85
  link      http://agentile.com/docblock/

* version   0.86 (2014-05-19)
  link      https://github.com/mbrowniebytes/PHP-Docblock-Generator

* version   0.87 (2016-06-16)
  link      https://github.com/vicebas/PHP-Docblock-Generator

* version   1.1.0 (2020-12-21)
  link      https://github.com/thewitness/PHP-Docblock-Generator

## Credits

Credit to Sean Coates for the getProtos function, modified a little.
https://seancoates.com/blogs/fun-with-the-tokenizer/
