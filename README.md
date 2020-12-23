# PHP-Docblock-Generator

This class will generate DocBlock comment outlines for files/folders.

## Usage

This class will generate docblock outline for files/folders.

```sh
php docblock.php --source=file|folder [ --recursive ] \
   [ --exclude="vendor/ tools/ ..." ] \
   [ --functions="f1 f2 ..." ] \
   [ --dryrun ] [ --versose ] \
   [ --anonymous ] [ --full ]
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

## TODOs

 1. add all proper docblock properties
 2. better checking for if docblock already exists
 3. docblocking for class properties
 4. try to gather more data for automatic insertion such as for **@access**

## DocBlock Tags

The following is a comprehensive list of tags taken from various locations for contextual references for future development and parameterization of the tool.  The sources for this information include the phpDocumentor site and various other sites.  No claims of orgininal authorship made here, Credits go to the original authors.

### Used anywhere
* **@author** — (in any place) — Defines the name or an email of the author who wrote the following code.
* **@copyright** — (in any place) — Is used to put your copyright in the code.
* **@deprecated** — (in any place) — Is a useful tag which means that this element will disappear in the next versions.
* **@example** — (in any place) — Is used for inserting a link to a file or a web page where the example of code usage is shown.
* **@ignore** — (any place) — A DocBlock with this tag won’t be processed when generating documentation, even if there are other tags.
* **@internal** — (any place) — Often used with tag **@api**, to show that the code is used by inner logic of this part of the program. Element with this tag won’t be included in the documentation.
* **@link** — (any place) — Is used for adding links but according to the documentation this tag is not fully supported.
* **@see** — (any place) — Using this tag you can insert links on external resources (just like with @link), but it also allows to put relative links to classes and methods..
* **@since** — (any place) — You can indicate the version in which the piece of code appeared.
* **@source** — (any place, except the beginning) — with the help of this tag you can place pieces of the source code in the documentation (you set the beginning and the end code line)
* **@todo** — (any place) — The most optimistic tag used by programmers as a reminder of what need to be done in a certain piece of code. IDEhave an ability to detect this tag and group all parts of the code in a separate window which is very convenient for further search. This is the working standard and is used very often.
* **@uses** — (any place) — Is used for displaying the connection between different sections of code. It is similar to **@see**. The difference is that **@see** creates unidirectional link and after you go to a new documentation page you won’t have a backward link while **@uses** gives you a backward navigation link.
* **@version** — (any place) — Denotes the current program version in which this class, method, etc. appeares.

### Used at Class Level
* **@access** — (class) — Access control for an element. **@access** private prevents documentation of the following element (if enabled).
* **@final** — (class) — Document a class method that should never be overridden in a child class.
* **@license** — (file, class) — Shows the type of license of the written code.
* @method — (class) — Is applied to the class and describes methods processed with function __call().
* **@package** — (file, class) — Divides code into logical subgroups. When you place classes in the same namespace, you indicate their functional similarity. If classes belong to different namespaces but have the same logical characteristic, they can be grouped using this tag (for example this is the case with classes that all work with customer’s cart but belong to different namespaces). But it is better to avoid such situation. For example, Symfony code style doesn’t use this tag.
* **@property-read, @property-write** — (class) — Both are similar to the previous tag but they process only one magic method __get() or __set().
* **@property** — (class) — As well as **@method** this tag is placed in the DocBlock of the class, but its function is to describe the properties accessed with the help of magic functions __get() and __set().

### Used at Method Level
* **@api** — (method) — Defines the stable public methods, which won’t change their semantics up to the next major release.
* **@param** — (method, function) — Describes the incoming function parameters. It’s worth noticing that if you describe the incoming parameters for a certain function using DocBlocks, you have to describe all parameters, not only one or two.
* **@return** — (method, function) — Is used for describing value returned by the function.
* **@static** — (method) — Document a static property or method.
* **@staticvar** — (method, function) — Document a static variable's use in a function/method
* **@throws** — (method, function) is used for specifying exceptions which can be called out by this function.

### Used at Package level
* **@category** — (packages) — Specify a category to organize the documented element's package into.
* **@subpackage** — (packages) — Specify sub-package to group classes or functions and defines into. Requires **@package** tag

### Use at File Level
* **@filesource** — (file) — Is a tag which you can place only at the very beginning of the php file because you can apply this tag only to a file and to include all code to the generated documentation.
* **@license** — (file, class) — shows the type of license of the written code.
* **@package** — (file, class) — divides code into logical subgroups. When you place classes in the same namespace, you indicate their functional similarity. If classes belong to different namespaces but have the same logical characteristic, they can be grouped using this tag (for example this is the case with classes that all work with customer’s cart but belong to different namespaces). But it is better to avoid such situation. For example, Symfony code style doesn’t use this tag.

### Used at Function Level
* **@param** — (method, function) — Describes the incoming function parameters. It’s worth noticing that if you describe the incoming parameters for a certain function using DocBlocks, you have to describe all parameters, not only one or two.
* **@return** — (method, function) — Is used for describing value returned by the function. You can specify its type and PhpStorm will pick it and give you different tips, but let’s talk about this later.
* **@staticvar** — (method, function) — Document a static variable's use in a function/method
* **@throws** — (method, function) — Is used for specifying exceptions which can be called out by this function.

### Used at Variable Level
* **@global** — (variable) — Document a global variable, or its use in a function/method.
* **@name** — (variable) — Specify an alias to use for a procedural page or global variable in displayed documentation and linking
* **@var** — (variable) — Is used to specify and to describe variables similar to those used inside the functions and for the class properties. You should distinguish this tag and **@param**. Tag **@param** is used only in DocBlocks for functions and describes the incoming parameters and **@var** is used to describe variables.

## ChangeLog

* version: 0.85, author, Anthony Gentile, link: http://agentile.com/docblock/

* version: 0.86 (2014-05-19), link: https://github.com/mbrowniebytes/PHP-Docblock-Generator

* version: 0.87 (2016-06-16), link: https://github.com/vicebas/PHP-Docblock-Generator

* version: 1.1.0 (2020-12-21), link: https://github.com/thewitness/PHP-Docblock-Generator

## Credits

Credit to Sean Coates for the getProtos function, modified a little.
https://seancoates.com/blogs/fun-with-the-tokenizer/
