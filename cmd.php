<?php
/* cmd.php 		*/
/* by joker__ 	*/
/* for a shell-like website */

function check_path($path) {
	global $mainDir;
	if (!strncmp(realpath($path), $mainDir, strlen($mainDir))) {
		return True;
	} else {
		return False;
	}
}

function cd($dir) {
	if (!check_path($dir)) {
		return "fail";
	}
	chdir($dir);
	return getcwd();
}

function ls($dir) {
	if (!check_path($dir)) {
		return "fail";
	}
	$res = preg_grep('/^([^.])/', scandir($dir)); /* no hidden files are shown */
	if ($res) {
		return implode("\n",$res);
	} else {
		return False;
	}	
}

function cat($file) {
	if (!check_path($file)) {
		return "fail";
	}
	if (!is_file($file)) {
		return "Not a file! You'd better look for files rather than loosing your time.";
	}
	return file_get_contents($file, False, NULL, 0, 4096); /* max?? */
}

function sanitize_output($output) {
	global $mainDir;
	$output = str_replace($mainDir,"",$output);
	$output = htmlspecialchars($output);
	$output = str_replace("\n","<br>",$output);
	return $output;
}


$mainDir = getcwd();

isset($_GET['cwd']) and $cwd = $_GET['cwd'] or $cwd = ".";
cd($cwd);

error_log(implode(" ",$_GET));

if (isset($_GET['action'])) {
	$res = NULL;
	switch ($_GET['action']) {
		case 'cd': isset($_GET['dir']) or die;
						if ($_GET['dir'] == '') {
							$res = cd($mainDir);
						} else {
							$res = cd($_GET['dir']);
						}
					break;
		case 'ls': if (isset($_GET['dir'])) {
						$res = ls($_GET['dir']);
					}
					else {
						$res = ls('.');
					}
					break;
		case 'cat': if (isset($_GET['file'])) {
						$res = cat($_GET['file']);
					}
					break;
		
	}
	$res = sanitize_output($res);
	echo $res;
}
?>
