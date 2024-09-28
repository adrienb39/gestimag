<?php
/* Copyright (C) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Xavier Dutoit        <doli@sydesy.com>
 * Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2005 	   Simon Tosser         <simon@kornog-computing.com>
 * Copyright (C) 2006 	   Andre Cianfarani     <andre.cianfarani@acdeveloppement.net>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015      Bahfir Abbes         <bafbes@gmail.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/filefunc.inc.php
 * 	\ingroup	core
 *  \brief      File that include conf.php file and commons lib like functions.lib.php
 */

if (!defined('DOL_APPLICATION_TITLE')) {
	define('DOL_APPLICATION_TITLE', 'Gestimag');
}
if (!defined('DOL_VERSION')) {
	define('DOL_VERSION', '1.0-rc1'); // a.b.c-alpha, a.b.c-beta, a.b.c-rcX or a.b.c
}

if (!defined('EURO')) {
	define('EURO', chr(128));
}

// Define syslog constants
if (!defined('LOG_DEBUG')) {
	if (!function_exists("syslog")) {
		// For PHP versions without syslog (like running on Windows OS)
		define('LOG_EMERG', 0);
		define('LOG_ALERT', 1);
		define('LOG_CRIT', 2);
		define('LOG_ERR', 3);
		define('LOG_WARNING', 4);
		define('LOG_NOTICE', 5);
		define('LOG_INFO', 6);
		define('LOG_DEBUG', 7);
	}
}

// End of common declaration part
if (defined('DOL_INC_FOR_VERSION_ERROR')) {
	return;
}


/**
 * Replace session_start()
 *
 * @return void
 */
function dol_session_start()
{
	session_start();
}

/**
 * Replace session_regenerate_id()
 *
 * @return bool True if success, false if failed
 */
function dol_session_regenerate_id()
{
	return session_regenerate_id();
}

/**
 * Destroy and recreate a new session without losing content.
 * Not used yet.
 *
 * @param  $sessionname		string		Session name
 * @return void
 */
function dol_session_rotate($sessionname = '')
{
	$oldsessionid = session_id();

	// Backup the current session
	$session_backup = $_SESSION;

	// Set current session to expire in 1 minute
	$_SESSION['OBSOLETE'] = true;
	$_SESSION['EXPIRES'] = time() + 60;

	// Close the current session
	session_write_close();

	// Set a new session id and start the session
	session_name($sessionname);
	dol_session_start();

	// Restore the previous session backup
	$_SESSION = $session_backup;

	// Clean up
	unset($session_backup);
	unset($_SESSION['OBSOLETE']);
	unset($_SESSION['EXPIRES']);

	$newsessionid = session_id();
	//var_dump("oldsessionid=".$oldsessionid." - newsessionid=".$newsessionid);
}



// Define vars
$conffiletoshowshort = "conf.php";
// Define localization of conf file
// --- Start of part replaced by Gestimag packager makepack-gestimag
$conffile = "conf/conf.php";
$conffiletoshow = "htdocs/conf/conf.php";
// For debian/redhat like systems
//$conffile = "/etc/gestimag/conf.php";
//$conffiletoshow = "/etc/gestimag/conf.php";


// Include configuration
// --- End of part replaced by Gestimag packager makepack-gestimag

// Include configuration
$result = @include_once $conffile; // Keep @ because with some error reporting mode, this breaks the redirect done when file is not found

// Disable some not used PHP stream
$listofwrappers = stream_get_wrappers();
// We need '.phar' for geoip2. TODO Replace phar in geoip with exploded files so we can disable phar by default.
// phar stream does not auto unserialize content (possible code execution) since PHP 8.1
// zip stream is necessary by excel import module
$arrayofstreamtodisable = array('compress.zlib', 'compress.bzip2', 'ftp', 'ftps', 'glob', 'data', 'expect', 'ogg', 'rar', 'zlib');
if (!empty($gestimag_main_stream_to_disable) && is_array($gestimag_main_stream_to_disable)) {
	$arrayofstreamtodisable = $gestimag_main_stream_to_disable;
}
foreach ($arrayofstreamtodisable as $streamtodisable) {
	if (!empty($listofwrappers) && in_array($streamtodisable, $listofwrappers)) {
		/*if (!empty($gestimag_main_stream_do_not_disable) && is_array($gestimag_main_stream_do_not_disable) && in_array($streamtodisable, $gestimag_main_stream_do_not_disable)) {
			continue;	// We do not disable this stream
		}*/
		stream_wrapper_unregister($streamtodisable);
	}
}

if (!$result && !empty($_SERVER["GATEWAY_INTERFACE"])) {    // If install not done and we are in a web session
	if (!empty($_SERVER["CONTEXT_PREFIX"])) {    // CONTEXT_PREFIX and CONTEXT_DOCUMENT_ROOT are not defined on all apache versions
		$path = $_SERVER["CONTEXT_PREFIX"]; // example '/gestimag/' when using an apache alias.
		if (!preg_match('/\/$/', $path)) {
			$path .= '/';
		}
	} elseif (preg_match('/index\.php/', $_SERVER['PHP_SELF'])) {
		// When we ask index.php, we MUST BE SURE that $path is '' at the end. This is required to make install process
		// when using apache alias like '/gestimag/' that point to htdocs.
		// Note: If calling page was an index.php not into htdocs (ie comm/index.php, ...), then this redirect will fails,
		// but we don't want to change this because when URL is correct, we must be sure the redirect to install/index.php will be correct.
		$path = '';
	} else {
		// If what we look is not index.php, we can try to guess location of root. May not work all the time.
		// There is no real solution, because the only way to know the apache url relative path is to have it into conf file.
		// If it fails to find correct $path, then only solution is to ask user to enter the correct URL to index.php or install/index.php
		$TDir = explode('/', $_SERVER['PHP_SELF']);
		$path = '';
		$i = count($TDir);
		while ($i--) {
			if (empty($TDir[$i]) || $TDir[$i] == 'htdocs') {
				break;
			}
			if ($TDir[$i] == 'gestimag') {
				break;
			}
			if (substr($TDir[$i], -4, 4) == '.php') {
				continue;
			}

			$path .= '../';
		}
	}

	header("Location: ".$path."install/index.php");

	/*
	echo '<br><center>';
	echo 'The conf/conf.php file was not found or is not readable by the web server. If this is your first access, <a href="'.$path.'install/index.php">click here to start the Gestimag installation process</a> to create it...';
	echo '</center><br>';
	*/

	exit;
}

// Force PHP error_reporting setup (Gestimag may report warning without this)
if (!empty($gestimag_strict_mode)) {
	error_reporting(E_ALL | E_STRICT);
} else {
	error_reporting(E_ALL & ~(E_STRICT | E_NOTICE | E_DEPRECATED));
}

// Disable php display errors
if (!empty($gestimag_main_prod)) {
	ini_set('display_errors', 'Off');
}

// Clean parameters
$gestimag_main_data_root = (empty($gestimag_main_data_root) ? '' : trim($gestimag_main_data_root));
$gestimag_main_url_root = trim(preg_replace('/\/+$/', '', empty($gestimag_main_url_root) ? '' : $gestimag_main_url_root));
$gestimag_main_url_root_alt = (empty($gestimag_main_url_root_alt) ? '' : trim($gestimag_main_url_root_alt));
$gestimag_main_document_root = (empty($gestimag_main_document_root) ? '' : trim($gestimag_main_document_root));
$gestimag_main_document_root_alt = (empty($gestimag_main_document_root_alt) ? '' : trim($gestimag_main_document_root_alt));

if (!isset($gestimag_main_db_port)) {
	$gestimag_main_db_port = 3306; // For compatibility with old configs, if not defined, we take 'mysql' type
}
if (empty($gestimag_main_db_type)) {
	$gestimag_main_db_type = 'mysqli'; // For compatibility with old configs, if not defined, we take 'mysql' type
}

// Mysql driver support has been removed in favor of mysqli
if ($gestimag_main_db_type == 'mysql') {
	$gestimag_main_db_type = 'mysqli';
}
if (empty($gestimag_main_db_prefix)) {
	$gestimag_main_db_prefix = 'llx_';
}
if (empty($gestimag_main_db_character_set)) {
	$gestimag_main_db_character_set = ($gestimag_main_db_type == 'mysqli' ? 'utf8' : ''); // Old installation
}
if (empty($gestimag_main_db_collation)) {
	$gestimag_main_db_collation = ($gestimag_main_db_type == 'mysqli' ? 'utf8_unicode_ci' : ''); // Old installation
}
if (empty($gestimag_main_db_encryption)) {
	$gestimag_main_db_encryption = 0;
}
if (empty($gestimag_main_db_cryptkey)) {
	$gestimag_main_db_cryptkey = '';
}
if (empty($gestimag_main_limit_users)) {
	$gestimag_main_limit_users = 0;
}
if (empty($gestimag_mailing_limit_sendbyweb)) {
	$gestimag_mailing_limit_sendbyweb = 0;
}
if (empty($gestimag_mailing_limit_sendbycli)) {
	$gestimag_mailing_limit_sendbycli = 0;
}
if (empty($gestimag_mailing_limit_sendbyday)) {
	$gestimag_mailing_limit_sendbyday = 0;
}
if (empty($gestimag_strict_mode)) {
	$gestimag_strict_mode = 0; // For debug in php strict mode
}

define('DOL_DOCUMENT_ROOT', $gestimag_main_document_root); // Filesystem core php (htdocs)

if (!file_exists(DOL_DOCUMENT_ROOT."/core/lib/functions.lib.php")) {
	echo "Error: Gestimag config file content seems to be not correctly defined.<br>\n";
	echo "Please run gestimag setup by calling page <b>/install</b>.<br>\n";
	exit(1);
}


// Included by default (must be before the CSRF check so wa can use the dol_syslog)
include_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
//echo memory_get_usage();


// Security: CSRF protection
// This test check if referrer ($_SERVER['HTTP_REFERER']) is same web site than Gestimag ($_SERVER['HTTP_HOST'])
// when we post forms (we allow GET and HEAD to accept direct link from a particular page).
// Note about $_SERVER[HTTP_HOST/SERVER_NAME]: http://shiflett.org/blog/2006/mar/server-name-versus-http-host
// See also CSRF protections done into main.inc.php
if (!defined('NOCSRFCHECK') && isset($gestimag_nocsrfcheck) && $gestimag_nocsrfcheck == 1) {    // If $gestimag_nocsrfcheck is 0, there is a strict CSRF test with token in main
	if (!empty($_SERVER['REQUEST_METHOD']) && !in_array($_SERVER['REQUEST_METHOD'], array('GET', 'HEAD')) && !empty($_SERVER['HTTP_HOST'])) {
		$csrfattack = false;
		if (empty($_SERVER['HTTP_REFERER'])) {
			$csrfattack = true; // An evil browser was used
		} else {
			$tmpa = parse_url($_SERVER['HTTP_HOST']);
			$tmpb = parse_url($_SERVER['HTTP_REFERER']);
			if ((empty($tmpa['host']) ? $tmpa['path'] : $tmpa['host']) != (empty($tmpb['host']) ? $tmpb['path'] : $tmpb['host'])) {
				$csrfattack = true;
			}
		}
		if ($csrfattack) {
			//echo 'NOCSRFCHECK='.defined('NOCSRFCHECK').' REQUEST_METHOD='.$_SERVER['REQUEST_METHOD'].' HTTP_HOST='.$_SERVER['HTTP_HOST'].' HTTP_REFERER='.$_SERVER['HTTP_REFERER'];
			// Note: We can't use dol_escape_htmltag here to escape output because lib functions.lib.ph is not yet loaded.
			dol_syslog("--- Access to ".(empty($_SERVER["REQUEST_METHOD"]) ? '' : $_SERVER["REQUEST_METHOD"].' ').$_SERVER["PHP_SELF"]." refused by CSRF protection (Bad referrer).", LOG_WARNING);
			echo "Access refused by CSRF protection in main.inc.php. Referrer of form (".htmlentities(empty($_SERVER['HTTP_REFERER']) ? '' : $_SERVER['HTTP_REFERER'], ENT_COMPAT, 'UTF-8').") is outside the server that serve this page (with method = ".htmlentities($_SERVER['REQUEST_METHOD'], ENT_COMPAT, 'UTF-8').").\n";
			echo "If you access your server behind a proxy using url rewriting, you might check that all HTTP headers are propagated (or add the line \$gestimag_nocsrfcheck=1 into your conf.php file to remove this security check).\n";
			die;
		}
	}
	// Another test is done later on token if option MAIN_SECURITY_CSRF_WITH_TOKEN is on.
}
if (empty($gestimag_main_db_host) && !defined('NOREQUIREDB')) {
	echo '<div class="center">Gestimag setup is not yet complete.<br><br>'."\n";
	echo '<a href="install/index.php">Click here to finish Gestimag install process</a> ...</div>'."\n";
	die;
}
if (empty($gestimag_main_url_root) && !defined('NOREQUIREVIRTUALURL')) {
	echo 'Value for parameter \'gestimag_main_url_root\' is not defined in your \'htdocs\conf\conf.php\' file.<br>'."\n";
	echo 'You must add this parameter with your full Gestimag root Url (Example: http://myvirtualdomain/ or http://mydomain/mygestimagurl/)'."\n";
	die;
}

if (empty($gestimag_main_document_root_alt)) {
	$gestimag_main_document_root_alt = $gestimag_main_document_root.'/custom';
}

if (empty($gestimag_main_data_root)) {
	// If directory not defined, we use the default hardcoded value
	$gestimag_main_data_root = str_replace("/htdocs", "", $gestimag_main_document_root);
	$gestimag_main_data_root .= "/documents";
}

// Define some constants
define('DOL_CLASS_PATH', 'class/'); // Filesystem path to class dir (defined only for some code that want to be compatible with old versions without this parameter)
define('DOL_DATA_ROOT', $gestimag_main_data_root); // Filesystem data (documents)
// Try to autodetect DOL_MAIN_URL_ROOT and DOL_URL_ROOT when root is not directly the main domain.
// Note: autodetect works only in case 1, 2, 3 and 4 of phpunit test CoreTest.php. For case 5, 6, only setting value into conf.php will works.
$tmp = '';
$found = 0;
$real_gestimag_main_document_root = str_replace('\\', '/', realpath($gestimag_main_document_root)); // A) Value found into config file, to say where are store htdocs files. Ex: C:/xxx/gestimag, C:/xxx/gestimag/htdocs
if (!empty($_SERVER["DOCUMENT_ROOT"])) {
	$pathroot = $_SERVER["DOCUMENT_ROOT"]; // B) Value reported by web server setup (not defined on CLI mode), to say where is root of web server instance. Ex: C:/xxx/gestimag, C:/xxx/gestimag/htdocs
} else {
	$pathroot = 'NOTDEFINED';
}
$paths = explode('/', str_replace('\\', '/', $_SERVER["SCRIPT_NAME"])); // C) Value reported by web server, to say full path on filesystem of a file. Ex: /gestimag/htdocs/admin/system/phpinfo.php
// Try to detect if $_SERVER["DOCUMENT_ROOT"]+start of $_SERVER["SCRIPT_NAME"] is $gestimag_main_document_root. If yes, relative url to add before dol files is this start part.
$concatpath = '';
foreach ($paths as $tmppath) {	// We check to find (B+start of C)=A
	if (empty($tmppath)) {
		continue;
	}
	$concatpath .= '/'.$tmppath;
	//if ($tmppath) $concatpath.='/'.$tmppath;
	//echo $_SERVER["SCRIPT_NAME"].'-'.$pathroot.'-'.$concatpath.'-'.$real_gestimag_main_document_root.'-'.realpath($pathroot.$concatpath).'<br>';
	if ($real_gestimag_main_document_root == @realpath($pathroot.$concatpath)) {    // @ avoid warning when safe_mode is on.
		//echo "Found relative url = ".$concatpath;
		$tmp3 = $concatpath;
		$found = 1;
		break;
	}
	//else echo "Not found yet for concatpath=".$concatpath."<br>\n";
}
//echo "found=".$found." gestimag_main_url_root=".$gestimag_main_url_root."\n";
if (!$found) {
	// There is no subdir that compose the main url root or autodetect fails (Ie: when using apache alias that point outside default DOCUMENT_ROOT).
	$tmp = $gestimag_main_url_root;
} else {
	$tmp = 'http'.((!isHTTPS() && (empty($_SERVER["SERVER_PORT"]) || $_SERVER["SERVER_PORT"] != 443)) ? '' : 's').'://'.$_SERVER["SERVER_NAME"].((empty($_SERVER["SERVER_PORT"]) || $_SERVER["SERVER_PORT"] == 80 || $_SERVER["SERVER_PORT"] == 443) ? '' : ':'.$_SERVER["SERVER_PORT"]).($tmp3 ? (preg_match('/^\//', $tmp3) ? '' : '/').$tmp3 : '');
}

//echo "tmp1=".$tmp1." tmp2=".$tmp2." tmp3=".$tmp3." tmp=".$tmp."\n";
if (!empty($gestimag_main_force_https)) {
	$tmp = preg_replace('/^http:/i', 'https:', $tmp);
}
define('DOL_MAIN_URL_ROOT', $tmp); // URL absolute root (https://sss/gestimag, ...)
$uri = preg_replace('/^http(s?):\/\//i', '', constant('DOL_MAIN_URL_ROOT')); // $uri contains url without http*
$suburi = strstr($uri, '/'); // $suburi contains url without domain:port
if (empty($suburi) || $suburi === '/') {
	$suburi = ''; // If $suburi is null or /, it is now ''
}
if (!defined('DOL_URL_ROOT')) {
	define('DOL_URL_ROOT', $suburi); // URL relative root ('', '/gestimag', ...)
}
//echo DOL_MAIN_URL_ROOT.'-'.DOL_URL_ROOT."\n";

// Define prefix MAIN_DB_PREFIX
define('MAIN_DB_PREFIX', $gestimag_main_db_prefix);


/*
 * Define PATH to external libraries
 * To use other version than embedded libraries, define here constant to path. Use '' to use include class path autodetect.
 */
// Path to root libraries
if (!defined('TCPDF_PATH')) {
	define('TCPDF_PATH', (empty($gestimag_lib_TCPDF_PATH)) ? DOL_DOCUMENT_ROOT.'/includes/tecnickcom/tcpdf/' : $gestimag_lib_TCPDF_PATH.'/');
}
if (!defined('TCPDI_PATH')) {
	define('TCPDI_PATH', (empty($gestimag_lib_TCPDI_PATH)) ? DOL_DOCUMENT_ROOT.'/includes/tcpdi/' : $gestimag_lib_TCPDI_PATH.'/');
}
if (!defined('NUSOAP_PATH')) {
	define('NUSOAP_PATH', (!isset($gestimag_lib_NUSOAP_PATH)) ? DOL_DOCUMENT_ROOT.'/includes/nusoap/lib/' : (empty($gestimag_lib_NUSOAP_PATH) ? '' : $gestimag_lib_NUSOAP_PATH.'/'));
}
if (!defined('PHPEXCELNEW_PATH')) {
	define('PHPEXCELNEW_PATH', (!isset($gestimag_lib_PHPEXCELNEW_PATH)) ? DOL_DOCUMENT_ROOT.'/includes/phpoffice/phpspreadsheet/src/PhpSpreadsheet/' : (empty($gestimag_lib_PHPEXCELNEW_PATH) ? '' : $gestimag_lib_PHPEXCELNEW_PATH.'/'));
}
if (!defined('ODTPHP_PATH')) {
	define('ODTPHP_PATH', (!isset($gestimag_lib_ODTPHP_PATH)) ? DOL_DOCUMENT_ROOT.'/includes/odtphp/' : (empty($gestimag_lib_ODTPHP_PATH) ? '' : $gestimag_lib_ODTPHP_PATH.'/'));
}
if (!defined('ODTPHP_PATHTOPCLZIP')) {
	define('ODTPHP_PATHTOPCLZIP', (!isset($gestimag_lib_ODTPHP_PATHTOPCLZIP)) ? DOL_DOCUMENT_ROOT.'/includes/odtphp/zip/pclzip/' : (empty($gestimag_lib_ODTPHP_PATHTOPCLZIP) ? '' : $gestimag_lib_ODTPHP_PATHTOPCLZIP.'/'));
}
if (!defined('JS_CKEDITOR')) {
	define('JS_CKEDITOR', (!isset($gestimag_js_CKEDITOR)) ? '' : (empty($gestimag_js_CKEDITOR) ? '' : $gestimag_js_CKEDITOR.'/'));
}
if (!defined('JS_JQUERY')) {
	define('JS_JQUERY', (!isset($gestimag_js_JQUERY)) ? '' : (empty($gestimag_js_JQUERY) ? '' : $gestimag_js_JQUERY.'/'));
}
if (!defined('JS_JQUERY_UI')) {
	define('JS_JQUERY_UI', (!isset($gestimag_js_JQUERY_UI)) ? '' : (empty($gestimag_js_JQUERY_UI) ? '' : $gestimag_js_JQUERY_UI.'/'));
}
// Other required path
if (!defined('DOL_DEFAULT_TTF')) {
	define('DOL_DEFAULT_TTF', (!isset($gestimag_font_DOL_DEFAULT_TTF)) ? DOL_DOCUMENT_ROOT.'/includes/fonts/Aerial.ttf' : (empty($gestimag_font_DOL_DEFAULT_TTF) ? '' : $gestimag_font_DOL_DEFAULT_TTF));
}
if (!defined('DOL_DEFAULT_TTF_BOLD')) {
	define('DOL_DEFAULT_TTF_BOLD', (!isset($gestimag_font_DOL_DEFAULT_TTF_BOLD)) ? DOL_DOCUMENT_ROOT.'/includes/fonts/AerialBd.ttf' : (empty($gestimag_font_DOL_DEFAULT_TTF_BOLD) ? '' : $gestimag_font_DOL_DEFAULT_TTF_BOLD));
}


/*
 * Include functions
 */

// If password is encoded, we decode it. Note: When page is called for install, $gestimag_main_db_pass may not be defined yet.
if ((!empty($gestimag_main_db_pass) && preg_match('/crypted:/i', $gestimag_main_db_pass)) || !empty($gestimag_main_db_encrypted_pass)) {
	if (!empty($gestimag_main_db_pass) && preg_match('/crypted:/i', $gestimag_main_db_pass)) {
		$gestimag_main_db_pass = preg_replace('/crypted:/i', '', $gestimag_main_db_pass);
		$gestimag_main_db_pass = dol_decode($gestimag_main_db_pass);
		$gestimag_main_db_encrypted_pass = $gestimag_main_db_pass; // We need to set this so we can use it later to know the password was initially encrypted
	} else {
		$gestimag_main_db_pass = dol_decode($gestimag_main_db_encrypted_pass);
	}
}
