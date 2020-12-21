<?php
/**
 * DocBlockGenerator
 *
 * This class will generate docblock outline for files/folders.
 *
 * > php docblock.php --source=file|folder [ --recursive ] [ --exclude="vendor/ tools/" ] [ --dryrun ] [ --versose ]
 *
 * Use from command line - params:
 * --source=path       - the file or folder you want to docblock (php files)
 * --recursive         - optional, recursively go through a folder
 * --exclude="p1/ p1/" - options, to exclude a series of paths
 * --functions="f1 f2" - optional, space delimited docblock only specific methods/functions
 * --dryrun            - optional, test the docblock but do not make any changes
 * --verbose           - optional, output verbose information on each file processed
 *
 * Examples:
 * > php docblock.php --sorce=target.php --functions="targetFunction"
 * > php docblock.php --source=target/dir --recursive
 * > php docblock.php --source=target/dir --recursive --dryrun
 * > php docblock.php --source=target/dir --exclude="vendor/ fa/" --recursive --dryrun
 *
 * Credit to Sean Coates for the getProtos function, modified a little.
 * http://seancoates.com/fun-with-the-tokenizer
 *
 * TODOs:
 * 1. add all proper docblock properties
 * 2. better checking for if docblock already exists
 * 3. docblocking for class properties
 * 4. try to gather more data for automatic insertion such as for @access
 *
 * @author    Anthony Gentile
 * @version   0.85
 * @link      http://agentile.com/docblock/
 *
 * @version   0.86 (2014-05-19)
 * @link      https://github.com/mbrowniebytes/PHP-Docblock-Generator
 *
 * @version   0.87 (2016-06-16)
 * @link      https://github.com/vicebas/PHP-Docblock-Generator
 *
 * @version   0.88 (2020-12-21)
 * @link      https://github.com/thewitness/PHP-Docblock-Generator
 *
 */

include('DocBlockGenerator.class.php');

use DocBlockGenerator\DocBlockGenerator;

$current_dir = getcwd();

$shortopts = 'f::s::rVvHh';

$longopts = array(
	'functions::',
	'source::',
	'exclude::',
	'verbose',
	'dryrun',
	'recursive',
	'version',
	'help'
);

$options = getopt($shortopts, $longopts);

/* Help and version output */
if (isset($options['v']) || isset($options['V']) || isset($options['version'])) {
	$dbg = new DocBlockGenerator();
	print $dbg->getVersion();

	exit(0);
}

if (isset($options['h']) || isset($options['H']) || isset($options['help'])) {
	$dbg = new DocBlockGenerator();
	print $dbg->getVersion();
	print $dbg->getHelp();

	exit(0);
}

if (sizeof($options) == 0) {
	$dbg = new DocBlockGenerator();
	print $dbg->getVersion();
	print $dbg->getHelp();

	exit(0);
}

/* Basic Pre-Checking */
$recursive = false;
$verbose   = false;
$functions = false;
$exclude   = false;
$full      = false;
$anonymous = false;

if (isset($options['r']) || isset($options['recursive'])) {
	$recursive = true;
}

if (isset($options['verbose'])) {
	$verbose = true;
}

if (isset($options['dryrun'])) {
	$dryrun = true;
}

if (isset($options['anonymous'])) {
	$anonymous = true;
}

if (isset($options['full'])) {
	$full = true;
}

if (isset($options['f']) && isset($options['functions'])) {
	print 'FATAL: You can only specify one of -f or --functions' . PHP_EOL;

	exit(1);
} else {
	if (isset($options['f'])) {
		$functions = $options['f'];
	} elseif (isset($options['functions'])) {
		$functions = $options['functions'];
	}
}

if (isset($options['s']) && isset($options['source'])) {
	print 'FATAL: You can only specify one of -s or --source' . PHP_EOL;

	exit(1);
} else {
	if (isset($options['s'])) {
		$source = $options['s'];
	} elseif (isset($options['source'])) {
		$source = $options['source'];
	} else {
		print 'FATAL: You must specify either -s or --source' . PHP_EOL;

		exit(1);
	}
}

if (isset($options['x']) && isset($options['exclude'])) {
	print 'FATAL: You can only specify one of -x or --exclude' . PHP_EOL;

	exit(1);
} else {
	if (isset($options['x'])) {
		$exclude = $options['x'];
	} elseif (isset($options['exclude'])) {
		$exclude = $options['exclude'];
	} else {
		$exclude = false;
	}
}

/* Pre-checking for source. */
if (is_dir($source) || is_file($source)) {
	if (!is_readable($source)) {
		print 'FATAL: Source ' . $source . ' is not readable!' . PHP_EOL;

		exit(1);
	}

	if (!is_writable($source)) {
		print 'FATAL: Source ' . $source . ' is not writable!' . PHP_EOL;

		exit(1);
	}
} else {
	$source = $current_dir . '/' . $source;

	if (is_dir($source) || is_file($source)) {
		if (!is_readable($source)) {
			print 'FATAL: Source ' . $source . ' is not readable!' . PHP_EOL;

			exit(1);
		}

		if (!is_writable($source)) {
			print 'FATAL: Source ' . $source . ' is not writable!' . PHP_EOL;

			exit(1);
		}
	} else {
		print 'FATAL: Source ' . $source . ' is not not either a file or directory!' . PHP_EOL;

		exit(1);
	}
}

$start = microtime(true);
$dbg   = new DocBlockGenerator($source, $functions, $recursive, $exclude, $verbose, $dryrun, $anonymous, $full);
$dbg->preface();
$dbg->start();
$dbg->result();
$end = microtime(true);

print $dbg->summary($end, $start);

exit($dbg->exitCode());
