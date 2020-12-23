<?php
namespace DocBlockGenerator;

class DocBlockGenerator {
	public $exts = array('.php', '.php4', '.php5', '.phps', '.inc');

	public $description_placeholder = 'Insert description here';  // text, if any, to add as placeholder for description

	public $target;                 // the starting point to parse
	public $functions   = array();  // array of excluded functions
	public $exclude     = array();  // array of excluded paths
	public $recursive   = false;    // recurse through all paths
	public $dryrun      = false;    // dryrun, don't make changes
	public $verbose     = false;    // print verbose output
	public $anonymous   = false;    // true, add docblock for anonymous functions; false, skip
	public $full        = false;    // true, add all docblock params; false, add minimal

	public $file_contents;
	public $log = array();

	private $version = '1.1.0';

	private $nbr_docblocks  = 0;
	private $total_files    = 0;
	private $excluded_files = 0;

	private $total_functions     = 0;
	private $converted_functions = 0;
	private $skipped_functions   = 0;

	private $total_classes       = 0;
	private $converted_classes   = 0;
	private $skipped_classes     = 0;
	private $nondb_classes       = array();
	private $db_classes          = array();

	private $exist_db_comments    = 0;
	private $exist_nondb_comments = 0;
	private $not_documented       = 0;

	private $nondb_functions      = array();
	private $db_functions         = array();

	private $skipped_files = 0;

	private $errors = 0;

	/**
	 * __construct
	 *
	 * @param $target
	 * @param $functions
	 * @param $recursive
	 * @param $exclude
	 * @param mixed $verbose
	 * @param mixed $dryrun
	 *
	 * @return void
	 *
	 * @access public
	 * @static
	 * @since 0.85
	 */
	public function __construct($target = null, $functions = null, $recursive = false, $exclude = null,
		$verbose = false, $dryrun = false, $anonymous = false, $full = false) {
		$this->target    = $target;
		$this->recursive = $recursive;
		$this->verbose   = $verbose;
		$this->dryrun    = $dryrun;

		if ($functions !== null && $functions != '') {
			$this->functions = explode(' ', $functions);
		} else {
			$this->functions = null;
		}

		if ($exclude !== null && $exclude != '') {
			$this->exclude = explode(' ', $exclude);
		} else {
			$this->exclude = null;
		}
	}

	/**
	 * result
	 * Print output to command line
	 *
	 *
	 * @return string
	 *
	 * @access public
	 * @static
	 * @since  0.85
	 */
	public function result() {
		if ($this->verbose) {
			$str = '';

			foreach ($this->log as $log_item) {
				$str .= sprintf('| %-120s|' . PHP_EOL, $log_item);
			}

			print $str;
		}
	}

	/**
	 * summary
	 * Print summary output to command line
	 *
	 *
	 * @return null
	 *
	 * @access public
	 * @static
	 * @since  0.87
	 * @param mixed $end
	 * @param mixed $start
	 */
	public function summary($end, $start) {
		printf('+' . str_repeat('-', 121) . '+' . PHP_EOL);

		printf('| %-120s|' . PHP_EOL, sprintf('DocBlock Generation took %.2f seconds.', $end - $start));
		printf('+' . str_repeat('-', 121) . '+' . PHP_EOL);
		printf('| %-120s|' . PHP_EOL, sprintf('There were %d files total scanned and %d files excluded.',
			$this->total_files, $this->excluded_files));

		printf('| %-120s|' . PHP_EOL, sprintf('There were %d functions found and %d %s converted.',
			$this->total_functions, $this->converted_functions, ($this->dryrun ? 'would be':'were')));

		printf('| %-120s|' . PHP_EOL, sprintf('There were %d classes found and %d %s converted.',
			$this->total_classes, $this->converted_classes, ($this->dryrun ? 'would be':'were')));

		printf('| %-120s|' . PHP_EOL, sprintf('There were %d functions found with no comments.',
			$this->not_documented));

		printf('| %-120s|' . PHP_EOL, sprintf('There were %d existing docblock functions found.',
			$this->exist_db_comments));

		printf('| %-120s|' . PHP_EOL, sprintf('WARNING: There were %d existing non-docblock functions found.',
			$this->exist_nondb_comments));

		if ($this->dryrun) {
			printf('| %-120s|' . PHP_EOL, sprintf('There would be %d read/write errors expected.', $this->errors));
		} else {
			printf('| %-120s|' . PHP_EOL, sprintf('There were %d read/write errors.', $this->errors));
		}

		printf('+' . str_repeat('-', 121) . '+' . PHP_EOL);
	}

	/**
	 * preface
	 * Print details about what is going to happen
	 *
	 *
	 * @return null
	 *
	 * @access public
	 * @static
	 * @since  0.87
	 * @param mixed $end
	 * @param mixed $start
	 */
	public function preface() {
		printf('+' . str_repeat('-', 121) . '+' . PHP_EOL);
		printf('| %-120s|' . PHP_EOL, sprintf('PHP DocBlock Generator - Starting %s', ($this->dryrun ? '- Dry Run Only':'')));
		printf('+' . str_repeat('-', 121) . '+' . PHP_EOL);
		printf('| %-120s|' . PHP_EOL, sprintf('Processing Starting - Settings below'),);
		printf('+' . str_repeat('-', 121) . '+' . PHP_EOL);
		printf('| %-120s|' . PHP_EOL, sprintf('Target Path: %s', $this->target));
		printf('| %-120s|' . PHP_EOL, sprintf('Recursion is: %s', $this->recursive ? 'Enabled':'Disabled'));
		printf('| %-120s|' . PHP_EOL, sprintf('File Exclusions: %s', ($this->exclude ? implode(', ', $this->exclude) : 'None Excluded')));
		printf('| %-120s|' . PHP_EOL, sprintf('Included Functions: %s', ($this->functions ? implode(', ', $this->functions) : 'All Functions')));
		printf('| %-120s|' . PHP_EOL, sprintf('Anonymous Functions: %s', ($this->anonymous ? 'Included' : 'Skipped')));
		printf('| %-120s|' . PHP_EOL, sprintf('PHPDoc Comment Style: %s', ($this->full ? 'Full' : 'Short')));

		if ($this->verbose) {
			printf('+' . str_repeat('-', 121) . '+' . PHP_EOL);
		}
	}

	/**
	 * exitCode
	 * Return the exit code
	 *
	 *
	 * @return int
	 *
	 * @access public
	 * @static
	 * @since  0.87
	 */
	public function exitCode() {
		if ($this->errors) {
			if ($this->dryrun) {
				return 3;
			} else {
				return 2;
			}
		} else {
			if ($this->exist_nondb_comments || $this->exist_nondb_classes) {
				return 1;
			} else {
				return 0;
			}
		}
	}

	/**
	 * start
	 * Begin the docblocking process, determine if a file or folder was given
	 *
	 * @return void
	 *
	 * @access public
	 * @static
	 * @since  0.85
	 */
	public function start() {
		if (is_file($this->target)) {
			$valid_file = $this->fileCheck($this->target);

			if ($valid_file == false) {
				return false;
			}

			if (!$this->excludeCheck($this->target)) {
				$this->fileDocBlock();
			}
		} elseif (is_dir($this->target)) {
			if ($this->recursive == true) {
				$files = $this->scanDirectories($this->target, true);
			} else {
				$files = $this->scanDirectories($this->target);
			}

			foreach ($files as $file) {
				if (!$this->excludeCheck($file)) {
					$this->target = $file;
					$this->fileDocBlock();
				}
			}
		} else {
			$this->log[] = 'This is not a file or folder.';

			return false;
		}
	}

	/**
	 * excludeCheck
	 * See if a file is excluded from processing
	 *
	 * @param $target
	 *
	 * @return bool
	 *
	 * @access public
	 * @static
	 * @since  0.85
	 */
	public function excludeCheck($target) {
		$this->total_files++;

		if (is_array($this->exclude) && sizeof($this->exclude)) {
			foreach ($this->exclude as $path) {
				if (strpos($target, $path) !== false) {
					//$this->log[] = "{$target} - is Excluded.";
					$this->excluded_files++;

					return true;
				}
			}
		}

		return false;
	}

	/**
	 * functionCheck
	 * See if a function is included for processing
	 *
	 * @param $target_func
	 *
	 * @return bool
	 *
	 * @access public
	 * @static
	 * @since  0.89
	 */
	public function functionCheck($target_func) {
		if (is_array($this->functions) && sizeof($this->functions)) {
			foreach ($this->functions as $func) {
				if ($target_func === $func) {
					return true;
				}
			}

			return false;
		}

		return true;
	}

	/**
	 * fileCheck
	 * Make sure we can deal with the target file
	 *
	 * @param $target
	 *
	 * @return bool
	 *
	 * @access public
	 * @static
	 * @since  0.85
	 */
	public function fileCheck($target) {
		$file_ext = strtolower(substr($target, strrpos($target, '.')));
		$bool     = true;

		if (!in_array($file_ext, $this->exts, true)) {
			$this->log[] = "{$target} is not a PHP file.";
			$bool        = false;
		}

		if (!is_readable($target)) {
			$this->errors++;
			$this->log[] = "{$target} is not readable.";
			$bool        = false;
		}

		if (!is_writable($target)) {
			$this->errors++;
			$this->log[] = "{$target} is not writeable.\nCheck file permissions";
			$bool        = false;
		}

		return $bool;
	}

	/**
	 * fileDocBlock
	 * Shell method for docblock operations, explodes file, performs docblock methods, impodes.
	 *
	 * @return void
	 *
	 * @access public
	 * @static
	 * @since  0.85
	 */
	public function fileDocBlock() {
		$this->nbr_docblocks = 0;

		$this->log[] = "NOTE: Tokenizing {$this->target}";

		$this->file_contents = file_get_contents($this->target);

		list($funcs, $classes) = $this->getProtos();

		$this->log[] = "NOTE: Opening File for Post Processing {$this->target}";

		$handle = fopen($this->target, 'r');

		if ($contents = fread($handle, filesize($this->target))) {
			$contents = explode(PHP_EOL, $contents);
			$contents = $this->docBlock($contents, $funcs, $classes, $this->functions);
			$contents = implode(PHP_EOL, $contents);

			fclose($handle);

			$this->log[] = "NOTE: Closing File after Post Processing {$this->target}";

			if ($this->nbr_docblocks == 0) {
				$this->log[] = "NOTE: Nothing to DocBlock for {$this->target}";
				$this->skipped_files++;
			} else {
				if (!$this->dryrun) {
					$handle = fopen($this->target, 'w');

					if (fwrite($handle, $contents)) {
						$this->converted_functions += $this->nbr_docblocks;

						$this->log[] = "NOTE: DocBlocked " . $this->nbr_docblocks . " in {$this->target}";
						fclose($handle);

						return;
					} else {
						fclose($handle);
						$this->log[] = "WARNING: Write error for {$this->target} - Check Permissions";
						$this->errors++;

						return;
					}
				} else {
					if (is_writable($this->target)) {
						$this->converted_functions += $this->nbr_docblocks;
					} else {
						$this->errors++;
					}
				}
			}
		} else {
			fclose($handle);
			$this->log[] = "WARNING: Read error for {$this->target} - Check Permissions";
			$this->errors++;

			return;
		}
	}

	/**
	 * getProtos
	 * This function goes through the tokens to gather the arrays of information we need
	 *
	 * @return array
	 *
	 * @access public
	 * @static
	 * @since  0.85
	 */
	public function getProtos() {
		$tokens      = token_get_all($this->file_contents);
		$funcs       = array();
		$classes     = array();
		$curr_class  = '';
		$curr_func   = '';
		$class_depth = 0;
		$count       = count($tokens);
		$comment     = false;
		$dcomment    = false;

		for ($i = 0; $i < $count; $i++) {
			if (is_array($tokens[$i]) && $tokens[$i][0] == T_COMMENT) {
				// Ignore the first comment if it includes copyright
				if (stripos($tokens[$i][1], 'copyright') === false) {
					$comment  = true;
					$dcomment = false;
				}
			}

			if (is_array($tokens[$i]) && $tokens[$i][0] == T_DOC_COMMENT) {
				// Ignore the first comment if it includes copyright
				if (stripos($tokens[$i][1], 'copyright') === false) {
					$dcomment = true;
					$comment  = false;
				}
			}

			if (is_array($tokens[$i]) && $tokens[$i][0] == T_RETURN) {
				if ($curr_func != '' && isset($funcs[$curr_func])) {
					$funcs[$curr_func]['return'] = 'returns';
				}
			}

			if (is_array($tokens[$i]) && $tokens[$i][0] == T_CLASS) {
				/* collect some statistics */
				$this->total_classes++;

				$line = $tokens[$i][2];
				++$i; // whitespace;
				$curr_class = $tokens[++$i][1];

				if ($comment) {
					$this->exist_nondb_comments++;

					if ($this->verbose) {
						$this->log[] = "WARNING: Class '{$curr_class}' found in {$this->target} is commented in a Non DocBlock fashion";
					}

					$comment  = false;
					$dcomment = false;

					$this->nondb_classes[$curr_class] = $curr_class;
				} elseif ($dcomment) {
					$this->exist_db_comments++;

					if ($this->verbose) {
						$this->log[] = "NOTE: Class '{$curr_class}' found is commented in a DocBlock fashion";
					}

					$comment  = false;
					$dcomment = false;

					$this->db_classes[$curr_class] = $curr_class;
				}

				if (!in_array(array('line' => $line, 'name' => $curr_class), $classes, true)) {
					$classes[] = array(
						'line' => $line,
						'name' => $curr_class
					);
				}

				while (true) {
					$i++;

					if ($i >= $count) {
						break;
					} elseif (isset($tokens[$i])) {
						if ($tokens[$i] == '{') {
							break;
						}
					}
				}

				++$i;
				$class_depth = 1;

				continue;
			}

			if (is_array($tokens[$i]) && $tokens[$i][0] == T_FUNCTION) {
				/* collect some statistics */
				$this->total_functions++;

				$next_by_ref = false;
				$this_func   = array();
				$func_status = array();
				$curr_func   = $tokens[$i+2][1];

				if ($comment) {
					$this->exist_nondb_comments++;

					if ($this->verbose) {
						$this->log[] = "WARNING: Function '{$curr_func}' in {$this->target} is commented in a Non DocBlock fashion";
					}
					print "WARNING: Function '{$curr_func}' in {$this->target} is commented in a Non DocBlock fashion" . PHP_EOL;

					$comment  = false;
					$dcomment = false;

					$this->nondb_functions[$curr_func] = $curr_func;
				} elseif ($dcomment) {
					$this->exist_db_comments++;

					if ($this->verbose) {
						$this->log[] = "NOTE: Function '{$curr_func}' found is commented in a DocBlock fashion";
					}

					$comment  = false;
					$dcomment = false;

					$this->db_functions[$curr_func] = $curr_func;
				} else {
					$this->not_documented++;

					if ($this->verbose) {
						$this->log[] = "WARNING: Function '{$curr_func}' found is not commented.";
					}
				}

				if (isset($tokens[$i - 2][1]) && $tokens[$i - 2][1] == 'static') {
					$func_status['static'] = true;
				} else {
					$func_status['static'] = false;
				}

				if (isset($tokens[$i - 2][1]) && $tokens[$i - 2][1] != 'static') {
					if ($tokens[$i - 2][1] == 'public' || $tokens[$i - 2][1] == 'private' || $tokens[$i - 2][1] == 'protected') {
						$func_status['access'] = $tokens[$i - 2][1];
					}
				}

				if (isset($tokens[$i - 2][1]) && $tokens[$i - 2][1] == 'static') {
					if (isset($tokens[$i - 4][1])) {
						if ($tokens[$i - 4][1] == 'public' || $tokens[$i - 4][1] == 'private' || $tokens[$i - 4][1] == 'protected') {
							$func_status['access'] = $tokens[$i - 4][1];
						}
					}
				}

				// Proceed through the function till the first bracket
				while (true) {
					$i++;

					if ($i >= $count) {
						break;
					} elseif (isset($tokens[$i])) {
						if ($tokens[$i] == '{') {
							break;
						}
					}

					if (is_array($tokens[$i]) && $tokens[$i][0] != T_WHITESPACE) {
						if (!sizeof($this_func)) {
							$curr_func = $tokens[$i][1];

							if ($curr_func == '') {
								// no name
								$this->skipped_functions++;
								continue;
							}

							if (substr($tokens[$i][1], 0, 1) == '$') {
								// anonymous function
								if (!$this->anonymous) {
									continue;
								} else {
									// go back and try to find php function being used in
									for ($j = $i; $i - $j < 10; $j--) {
										if (is_array($tokens[$j]) && $tokens[$j][0] == T_STRING) {
											$tokens[$i][1] = $tokens[$j][1];

											break;
										}
									}
								}
							}

							$this_func = array(
								'name'  => $tokens[$i][1],
								'class' => $curr_class,
								'line'  => $tokens[$i][2],
							);
						} elseif ($tokens[$i][0] == T_VARIABLE) {
							if ($this_func) {
								$this_func['params'][] = array(
									'byRef' => $next_by_ref,
									'name'  => $tokens[$i][1],
								);

								$next_by_ref = false;
							}
						}
					} elseif ($tokens[$i] == '&') {
						$next_by_ref = true;
					} elseif ($tokens[$i] == '=') {
						while (!in_array($tokens[++$i], array(')', ','), true)) {
							// default may be a negative (-) number
							if ($tokens[$i][0] != T_WHITESPACE && $tokens[$i][0] != '-') {
								break;
							}
						}

						if ($this_func) {
							if (isset($tokens[$i][1])) {
								$this_func['params'][count($this_func['params']) - 1]['default'] = $tokens[$i][1];
							}
						}
					}
				}

				if ($this_func) {
					$funcs[$curr_func] = $this_func + $func_status;
				} else {
					$this->skipped_functions++;
				}

				$comment  = false;
				$dcomment = false;
			} elseif ($tokens[$i] == '{' || $tokens[$i] == 'T_CURLY_OPEN' || $tokens[$i] == 'T_DOLLAR_OPEN_CURLY_BRACES') {
				++$class_depth;
			} elseif ($tokens[$i] == '}') {
				--$class_depth;
			}

			/* Reset the comment tracker when we get to the end of a function block */
			if (!is_array($tokens[$i])) {
				$comment  = false;
				$dcomment = false;
			}

			if ($class_depth == 0) {
				$curr_class = '';
			}
		}

		return array($funcs, $classes);
	}

	/**
	 * docBlock
	 * Main docblock function, determines if class or function docblocking is need and calls
	 * appropriate subfunction.
	 *
	 * @param $arr
	 * @param $funcs
	 * @param $classes
	 * @param $functions
	 *
	 * @return array
	 *
	 * @access public
	 * @static
	 * @since  0.85
	 */
	public function docBlock($arr, $funcs, $classes, $functions) {
		$func_lines = array();

		foreach ($funcs as $index => $func) {
			$func_lines[] = $func['line'];
		}

		$class_lines = array();

		foreach ($classes as $class) {
			$class_lines[] = $class['line'];
		}

		$class_or_func = '';

		$count = count($arr);

		for ($i = 0; $i < $count; $i++) {
			$line = $i + 1;
			$code = $arr[$i];

			if (in_array($line, $class_lines, true) && !$this->docBlockExists($arr[($i - 1)])) {
				$class_or_func = 'class';
			} elseif (in_array($line, $func_lines, true) && !$this->docBlockExists($arr[($i - 1)])) {
				$class_or_func = 'func';
			} else {
				continue;
			}

			if ($class_or_func === 'func') {
				$data = $this->getData($line, $funcs);
			} elseif ($class_or_func === 'class') {
				$data = $this->getData($line, $classes);
			}

			if (!$this->functionCheck($data['name'])) {
				$this->skipped_functions++;
				continue;
			}

			$indent = $this->getStrIndent($code);

			if ($class_or_func === 'func') {
				$doc_block = $this->functionDocBlock($indent, $data);
			} elseif ($class_or_func === 'class') {
				$doc_block = $this->classDocBlock($indent, $data);
			}

			$this->nbr_docblocks++;

			$arr[$i] = $doc_block . $arr[$i];
		}

		return $arr;
	}

	/**
	 * scanDirectories
	 * Get all specific files from a directory and if recursive, subdirectories
	 *
	 * @param $dir
	 * @param $recursive
	 * @param $data
	 *
	 * @return array
	 *
	 * @access public
	 * @static
	 * @since  0.85
	 */
	public function scanDirectories($dir, $recursive = false, $data = array()) {
		// set filenames invisible if you want
		$invisible = array('.', '..', '.htaccess', '.htpasswd');
		// run through content of root directory
		$dir_content = scandir($dir);

		foreach ($dir_content as $key => $content) {
			// filter all files not accessible
			$path = $dir . '/' . $content;

			if (!in_array($content, $invisible, true)) {
				// if content is file & readable, add to array
				if (is_file($path) && is_readable($path)) {
					// what is the ext of this file
					$file_ext = strtolower(substr($path, strrpos($path, '.')));
					// if this file ext matches the ones from our array
					if (in_array($file_ext, $this->exts, true)) {
						// save file name with path
						$data[] = $path;
					}
					// if content is a directory and readable, add path and name
				} elseif (is_dir($path) && is_readable($path)) {
					// recursive callback to open new directory
					if ($recursive == true) {
						$data = $this->scanDirectories($path, true, $data);
					}
				}
			}
		}

		return $data;
	}

	/**
	 * getData
	 * Retrieve method or class information from our arrays
	 *
	 * @param $line
	 * @param $arr
	 *
	 * @return mixed
	 *
	 * @access public
	 * @static
	 * @since  0.85
	 */
	public function getData($line, $arr) {
		foreach ($arr as $k => $v) {
			if ($line == $v['line']) {
				return $arr[$k];
			}
		}

		return false;
	}

	/**
	 * docBlockExists
	 * Primitive check to see if docblock already exists
	 *
	 * @param $line
	 *
	 * @return bool
	 *
	 * @access public
	 * @static
	 * @since  0.85
	 */
	public function docBlockExists($line) {
		// ok we are simply going to check the line above the function and look for */
		// TODO: make this a more accurate check.
		$line = ltrim($line);
		$len  = strlen($line);

		if ($len == 0) {
			return false;
		}

		$asterik = false;

		for ($i = 0; $i < $len; $i++) {
			if ($line[$i] == '*') {
				$asterik = true;
			} elseif ($line[$i] == '/' && $asterik == true) {
				return true;
			} else {
				$asterik = false;
			}
		}

		return false;
	}

	/**
	 * functionDocBlock
	 * Docblock for function
	 *
	 * @param $indent
	 * @param $data
	 *
	 * @return string
	 *
	 * @access public
	 * @static
	 * @since  0.85
	 */
	public function functionDocBlock($indent, $data) {
		$doc_block  = $indent . '/**' . PHP_EOL;
		$doc_block .= $indent . " * {$data['name']}" . PHP_EOL;

		if (!empty($this->description_placeholder)) {
			$doc_block .= $indent . ' *' . PHP_EOL;

			$doc_block .= $indent . ' * ' . $this->description_placeholder . PHP_EOL;
		}

		$doc_block .= $indent . ' *' . PHP_EOL;

		if (isset($data['params'])) {
			foreach ($data['params'] as $func_param) {
				$doc_block .= $indent . " * @param " .
					(isset($func_param['default']) ? $this->decodeType($func_param['default']) : 'type') .
					" {$func_param['name']}" . PHP_EOL;
			}
		}

		if (!empty($data['access'])) {
			$doc_block .= $indent . ' * @access ' . $data['access'] . PHP_EOL;
		}

		if ($data['static']) {
			$doc_block .= $indent . ' * @static' . PHP_EOL;
		}

		if (isset($data['return'])) {
			$doc_block .= $indent . ' *'         . PHP_EOL;
			$doc_block .= $indent . ' * @return type' . PHP_EOL;
		}

		if ($this->full) {
			$doc_block .= $indent . ' *'         . PHP_EOL;
			$doc_block .= $indent . ' * @see'    . PHP_EOL;
			$doc_block .= $indent . ' * @since'  . PHP_EOL;
		}

		$doc_block .= $indent . ' */' . PHP_EOL;

		return $doc_block;
	}

	/**
	 * Decode the parameter type
	 *
	 * @param type $type
	 *
	 * @return string
	 */
	public function decodeType($type) {
		$typeToReturn = $type;

		if ($type == "''") {
			$typeToReturn =  'string';
		}

		if (is_int($type)) {
			$typeToReturn =  'int';
		}

		if ($type === false) {
			$typeToReturn = 'bool';
		}

		if ($type === true) {
			$typeToReturn = 'bool';
		}

		return $typeToReturn;
	}

	/**
	 * classDocBlock
	 * Docblock for class
	 *
	 * @param $indent
	 * @param $data
	 *
	 * @return string
	 *
	 * @access public
	 * @static
	 * @since  0.85
	 */
	public function classDocBlock($indent, $data) {
		$doc_block  = $indent . '/**' . PHP_EOL;
		$doc_block .= $indent . " * {$data['name']}" . PHP_EOL;

		if (!empty($this->description_placeholder)) {
			$doc_block .= $indent . ' * ' . $this->description_placeholder . PHP_EOL;
		}

		$doc_block .= $indent . ' *' . PHP_EOL;
		$doc_block .= $indent . ' *' . PHP_EOL;

		if ($this->full) {
			$doc_block .= $indent . ' * @category'  . PHP_EOL;
			$doc_block .= $indent . ' * @package'   . PHP_EOL;
			$doc_block .= $indent . ' * @author'    . PHP_EOL;
			$doc_block .= $indent . ' * @copyright' . PHP_EOL;
			$doc_block .= $indent . ' * @license'   . PHP_EOL;
			$doc_block .= $indent . ' * @version'   . PHP_EOL;
			$doc_block .= $indent . ' * @link'      . PHP_EOL;
			$doc_block .= $indent . ' * @see'       . PHP_EOL;
			$doc_block .= $indent . ' * @since'     . PHP_EOL;
		}

		$doc_block .= $indent . ' */' . PHP_EOL;

		return $doc_block;
	}

	/**
	 * getStrIndent
	 * Returns indentation of a string
	 *
	 * @param $str
	 * @param $count
	 *
	 * @return int
	 *
	 * @access public
	 * @static
	 * @since  0.86
	 */
	public function getStrIndent($str) {
		// preserve tabs and spaces
		if (preg_match('/^(\s+)/', $str, $matches)) {
			$indent = $matches[1];
		} else {
			$indent = '';
		}

		return $indent;
	}

	/**
	 * getVersion
	 * Returns the current version
	 *
	 * @return string
	 *
	 * @access public
	 * @static
	 * @since  0.87
	 */
	public function getVersion() {
		return 'PHP DocBlock Generator, Version ' . $this->version . PHP_EOL;
	}

	/**
	 * getHelp
	 * Returns the current usage requirements
	 *
	 * @return string
	 *
	 * @access public
	 * @static
	 * @since  0.87
	 */
	public function getHelp() {
		$output  = PHP_EOL . 'usage: docblock.php [ -s | --source=S ] [ -r | --recursive ]' . PHP_EOL;
		$output .= '   [ -x | --exclude=S ] [ -f | --functions=S ] [ --dryrun ] [ --verbose ]' . PHP_EOL . PHP_EOL;
		$output .= '   [ --anonymous ] [ --full ]' . PHP_EOL;
		$output .= 'Utility for inserting DocBlock into PHP code' . PHP_EOL . PHP_EOL;

		$output .= 'Options:' . PHP_EOL;
		$output .= '   -s | --source        Process the source directory here' . PHP_EOL;
		$output .= '   -r | --recursive     Recursively process files in directory' . PHP_EOL;
		$output .= '   -x | --exclude       Space delimited string of paths to exclude' . PHP_EOL;
		$output .= '   -f | --functions     A space delimited set of functions to document' . PHP_EOL;
		$output .= '   --anonymous          Include Anonymous PHP function comments' . PHP_EOL;
		$output .= '   --full               Use the complete PHP DocBlock comment format' . PHP_EOL;
		$output .= '   --dryrun             Generate a log.  Do not update files' . PHP_EOL;
		$output .= '   --verbose            Print the entire log, not just summaries' . PHP_EOL . PHP_EOL;

		$output .= 'Returns:' . PHP_EOL;
		$output .= '   0 - No errors encountered, all functions correctly documented' . PHP_EOL;
		$output .= '   1 - Some functions remain undocumented, or in non-DocBlock format' . PHP_EOL;
		$output .= '   2 - General read/write error encountered' . PHP_EOL;
		$output .= '   3 - Read write errors would encounted in dryrun' . PHP_EOL . PHP_EOL;

		$output .= 'This utility will traverse a source directory for PHP files and insert' . PHP_EOL;
		$output .= 'DocBlock code for all functions and classes.  By default, it will modify' . PHP_EOL;
		$output .= 'files in place, but you may also run a --dryrun to check for undocumented' . PHP_EOL;
		$output .= 'functions.' . PHP_EOL . PHP_EOL;

		$output .= 'You may exclude files that match a pattern, for example the' . PHP_EOL;
		$output .= 'following is a valid --exclude parameter:' . PHP_EOL . PHP_EOL;
		$output .= '   --exclude="vendor/ tools/"' . PHP_EOL . PHP_EOL;

		$output .= 'You may specify specific functions by name, for example the' . PHP_EOL;
		$output .= 'following is a valid --functions parameter:' . PHP_EOL . PHP_EOL;
		$output .= '   --functions="f1 f2"' . PHP_EOL . PHP_EOL;

		$output .= 'That exclude option will exclude directories named vendor and tools' . PHP_EOL;
		$output .= 'from being scanned and altered.' . PHP_EOL . PHP_EOL;

		$output .= 'NOTE: Legacy options processing has been deprecated and removed' . PHP_EOL;
		$output .= 'from this release.' . PHP_EOL . PHP_EOL;

		return $output;
	}
}
