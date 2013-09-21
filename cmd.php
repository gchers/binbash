<?php
/*
	 'binbash' is a "shell-style" website
    Copyleft (c) 2013 joker__ <g.chers at gmail.com>
 
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.
 
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
 
    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
    
    This project is a fork of 'JSash' by Flavio 'darkjoker' Giobergia
/****************
 * cmd.php 		*
 * by joker__   *************
 * for a shell-like website	*
 ****************************

/* PATHS */
DEFINE("BASE_PATH", getcwd() . "/files");
/* MAX */
DEFINE("MAX_INPUT_LEN", 100);
/* STATUS CODES */
DEFINE("OK",0);
// errors
DEFINE("E_PATH_RESTRICTION", 1); 	/* no permission to walk to the path */
DEFINE("E_PATH_INVALID", 2); 		/* non existing */



/* Sanitizing stuff */
/* The core of your security...take a look,
 * and try to improve these functions ;-)
 */
function check_path($path) {
	if (!strncmp(realpath($path), BASE_PATH, strlen(BASE_PATH))) {
		return True;
	} else {
		return False;
	}
}

function sanitize_input($input) {
	$input = substr($input,0,MAX_INPUT_LEN);
	return $input;
}

function sanitize_output($output) {
	$output = str_replace(BASE_PATH,"",$output);
	$output = htmlspecialchars($output);
	$output = str_replace("\n","<br>",$output);
	//$output = str_replace(" ","&nbsp;";$output);
	return $output;
}


/* Command execution */
/* Note. All these functions return an array
 * in this form: [status,result].
 * Where status is the status code (fail, success)
 * and result is the string result.
 */
function cd($dir) {
	$code = OK;
	if (!check_path($dir)) {
		$code = E_PATH_RESTRICTION;
	} else {
		if (chdir($dir)) {
		} else {
			$code = E_PATH_INVALID;
		}
	}
	return array($code, getcwd());
}

function ls($dir) {
	$code = OK;
	$res = "";
	if (!file_exists($dir)) {
		$code = E_PATH_INVALID;
	} else {
		if (!check_path($dir)) {
			$code = E_PATH_RESTRICTION;
		} else {
			$res = implode("\n", preg_grep('/^([^.])/', scandir($dir))); /* no hidden files are shown */
		}
	}
	return array($code,$res);
}

function cat($file) {
	$code = OK;
	$res = "";
	if (!is_file($file)) {
		$code = E_PATH_INVALID;
	} else {
		if (!check_path($file)) {
			$code = E_PATH_RESTRICTION;
		}
		else {
			$res = file_get_contents($file, False, NULL, 0, 10000); /* max?? */
		}
	}
	return array($code,$res);
}

function catN($file, $lines) {
	$code = OK;
	$res = "";
	if (!is_file($file)) {
		$code = E_PATH_INVALID;
	} else {
		if (!check_path($file)) {
			$code = E_PATH_RESTRICTION;
		}
		else {
			$f = file($file);
			$res = implode("", array_slice($f,0,$lines));
		}
	}
	return array($code,$res);
}


/*
 * This function is an alternative to 'ls', and it provides,
 * where present, a description of each file.
 * The info file should be named '.$filename.info'.
 * It should be composed as follows:
 *  [INFO]
 * 	descr.
 *
 * Where the description can occupy 1-2 lines, of <=40
 * characters each one.
 */
function showinfo($dir) {
	$code = OK;
	$res = "";
	if (!file_exists($dir)) {
		$code = E_PATH_INVALID;
	} else {
		if (!check_path($dir)) {
			$code = E_PATH_RESTRICTION;
		} else {
			$files = preg_grep('/^([^.])/', scandir($dir)); /* no hidden files are shown */
			foreach ($files as $file) {
				if (file_exists("." . $file . ".info")) {
					$f = file("." . $file . ".info");
					$off = strlen($file) + 2;
					/* todo: check each line is lower than 41 characters */
					//$res = $res . "<strong>" . $file . "</strong>" . ": " . implode(str_repeat(" ",$off), array_slice($f,1,3));
					$res = $res . $file . ": " . implode(str_repeat(" ",$off), array_slice($f,1,3));
				} else {
					//$res = $res . "<strong>" . $file . "</strong>\n";
					$res = $res . $file . "\n";
				}
			}
		}
	}
	return array($code,$res);
}


function fileinfo($file) {
	$code = OK;
	$res = "";
	if (!check_path($file)) {
		$code = E_PATH_RESTRICTION;
	} else if (is_dir($file)) {
		$res = $file . ": directory";
	} else {
		switch(pathinfo($file, PATHINFO_EXTENSION)) {
			case "txt": $res = $file . ": ASCII text";
						break;
			/* working on it... */
			default: $res = "";
		}
	}
	return array($code,$res);
}

/*function grep($dir,$str) {
	$code = OK;
	$res = "";
	foreach (glob($dir) as $file) {
		/*if (!file_exists($file)) {
			//$code = E_PATH_INVALID;
		} else {
			if (!check_path($dir)) {
				//$code = E_PATH_RESTRICTION;
			} else {
				//$files = preg_grep('/^([^.])/', scandir($dir)); /* no hidden files are shown *
				//foreach ($files as $file) {
				preg_match($str,file_get_contents($file, False, NULL)) and $res += "\n" . $file;
			}
		}
		//if (file_exists($file) and check_path($file)) {
			if (preg_match($str,file_get_contents($file, False, NULL))) {
				$res += "\n" . $file;
			}
		//}
	}
	return array($code,$res);
}*/


/* we're working in BASE_PATH directory, which is saw by the user as ~ */
chdir(BASE_PATH);
isset($_GET['cwd']) and $cwd = $_GET['cwd'] or $cwd = BASE_PATH;
($cwd == '~') and $cwd = BASE_PATH;
cd(sanitize_input($cwd));

error_log(implode(" ",$_GET));

if (isset($_GET['action'])) {
	$res = NULL;
	switch ($_GET['action']) {
		case 'cd': isset($_GET['dir']) or die;
						if ($_GET['dir'] == '') {
							$res = cd(BASE_PATH);
						} else {
							$res = cd(sanitize_input($_GET['dir']));
						}
					break;
		case 'ls': if (isset($_GET['dir'])) {
						$res = ls(sanitize_input($_GET['dir']));
					}
					else {
						$res = ls('.');
					}
					break;
		case 'cat': if (isset($_GET['file'])) {
						$res = cat(sanitize_input($_GET['file']));
					}
					break;
		case 'head': if (isset($_GET['file'])) {
						isset($_GET['lines']) and $lines = sanitize_input($_GET['lines']) or $lines = 10;
						$res = catN(sanitize_input($_GET['file']),$lines);
					}
					break;
		case 'file': if (isset($_GET['file'])) {
						$res = fileinfo(sanitize_input($_GET['file']));
					}
					break;
		case 'info': if (isset($_GET['dir'])) {
						$res = showinfo(sanitize_input($_GET['dir']));
					}
					else {
						$res = showinfo('.');
					}
					break;
		/*case 'grep': if (isset($_GET['dir']) and isset($_GET['expr'])) {
						
						$res = grep($_GET['dir'],$_GET['expr']);
					}
					break;*/
	
	}
	$res[1] = sanitize_output($res[1]);
	//debug: error_log(implode(" ",$res));
	echo json_encode($res);
}
?>
