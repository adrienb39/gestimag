<?php
/* Copyright (C) 2005-2020	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2007		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2007-2012	Regis Houssin			<regis.houssin@inodbox.com>
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
 *  \file       htdocs/admin/system/gestimag.php
 *  \brief      Page to show Gestimag information
 */

// Load Gestimag environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/memory.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("install", "other", "admin"));

$action = GETPOST('action', 'aZ09');

if (!$user->admin) {
	accessforbidden();
}

$sfurl = '';
$version = '0.0';


/*
 *	Actions
 */

if ($action == 'getlastversion') {
	$result = getURLContent('https://raw.githubusercontent.com/adrienb39/gestimag/main/rss');
	//var_dump($result['content']);
	if (function_exists('simplexml_load_string')) {
		if (LIBXML_VERSION < 20900) {
			// Avoid load of external entities (security problem).
			// Required only if LIBXML_VERSION < 20900
			// @phan-suppress-next-line PhanDeprecatedFunctionInternal
			libxml_disable_entity_loader(true);
		}

		$sfurl = simplexml_load_string($result['content'], 'SimpleXMLElement', LIBXML_NOCDATA|LIBXML_NONET);
	} else {
		setEventMessages($langs->trans("ErrorPHPDoesNotSupport", "xml"), null, 'errors');
	}
}


/*
 * View
 */

$form = new Form($db);

$help_url = '';
$title = $langs->trans("InfoGestimag");

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-admin page-system_gestimag');

echo load_fiche_titre($title, '', 'title_setup');

// Version
echo '<div class="div-table-responsive-no-min">';
echo '<table class="noborder centpercent">';
echo '<tr class="liste_titre"><td class="titlefieldcreate">'.$langs->trans("Version").'</td><td>'.$langs->trans("Value").'</td></tr>'."\n";
echo '<tr class="oddeven"><td>'.$langs->trans("CurrentVersion").'<br><span class="opacitymedium">('.$langs->trans("Programs").')</span></td><td>'.DOL_VERSION;
// If current version differs from last upgrade
if (!getDolGlobalString('MAIN_VERSION_LAST_UPGRADE')) {
	// Compare version with last install database version (upgrades never occurred)
	if (DOL_VERSION != $conf->global->MAIN_VERSION_LAST_INSTALL) {
		echo ' '.img_warning($langs->trans("RunningUpdateProcessMayBeRequired", DOL_VERSION, getDolGlobalString('MAIN_VERSION_LAST_INSTALL')));
	}
} else {
	// Compare version with last upgrade database version
	if (DOL_VERSION != $conf->global->MAIN_VERSION_LAST_UPGRADE) {
		echo ' '.img_warning($langs->trans("RunningUpdateProcessMayBeRequired", DOL_VERSION, getDolGlobalString('MAIN_VERSION_LAST_UPGRADE')));
	}
}

$version = DOL_VERSION;
if (preg_match('/[a-z]+/i', $version)) {
	$version = '1.0-rc1'; // If version contains text, it is not an official tagged version, so we use the full change log.
}
echo ' &nbsp; <a href="https://raw.githubusercontent.com/adrienb39/gestimag/'.$version.'/ChangeLog" target="_blank" rel="noopener noreferrer external">'.$langs->trans("SeeChangeLog").'</a>';

$newversion = '';
if (function_exists('curl_init')) {
	$conf->global->MAIN_USE_RESPONSE_TIMEOUT = 10;
	echo ' &nbsp; &nbsp; - &nbsp; &nbsp; ';
	if ($action == 'getlastversion') {
		if ($sfurl) {
			$i = 0;
			while (!empty($sfurl->channel[0]->item[$i]->title) && $i < 10000) {
				$title = $sfurl->channel[0]->item[$i]->title;
				$reg = array();
				if (preg_match('/([0-9]+\.([0-9\.]+))/', $title, $reg)) {
					$newversion = $reg[1];
					$newversionarray = explode('.', $newversion);
					$versionarray = explode('.', $version);
					//var_dump($newversionarray);var_dump($versionarray);
					if (versioncompare($newversionarray, $versionarray) > 0) {
						$version = $newversion;
					}
				}
				$i++;
			}

			// Show version
			echo $langs->trans("LastStableVersion").' : <b>'.(($version != '0.0') ? $version : $langs->trans("Unknown")).'</b>';
			if ($version != '0.0') {
				echo ' &nbsp; <a href="https://raw.githubusercontent.com/adrienb39/gestimag/'.$version.'/ChangeLog" target="_blank" rel="noopener noreferrer external">'.$langs->trans("SeeChangeLog").'</a>';
			}
		} else {
			echo $langs->trans("LastStableVersion").' : <b>'.$langs->trans("UpdateServerOffline").'</b>';
		}
	} else {
		echo $langs->trans("LastStableVersion").' : <a href="'.$_SERVER["PHP_SELF"].'?action=getlastversion" class="butAction smallpaddingimp">'.$langs->trans("Check").'</a>';
	}
}

// Now show link to the changelog
//echo ' &nbsp; &nbsp; - &nbsp; &nbsp; ';

$version = DOL_VERSION;
if (preg_match('/[a-z]+/i', $version)) {
	$version = 'develop'; // If version contains text, it is not an official tagged version, so we use the full change log.
}

echo '</td></tr>'."\n";
echo '<tr class="oddeven"><td>'.$langs->trans("VersionLastUpgrade").'<br><span class="opacitymedium">('.$langs->trans("Database").')</span></td><td>'.getDolGlobalString('MAIN_VERSION_LAST_UPGRADE').'</td></tr>'."\n";
echo '<tr class="oddeven"><td>'.$langs->trans("VersionLastInstall").'<br><span class="opacitymedium">('.$langs->trans("Database").')</span></td><td>'.getDolGlobalString('MAIN_VERSION_LAST_INSTALL').'</td></tr>'."\n";
echo '</table>';
echo '</div>';
echo '<br>';

// Session
echo '<div class="div-table-responsive-no-min">';
echo '<table class="noborder centpercent">';
echo '<tr class="liste_titre"><td class="titlefieldcreate">'.$langs->trans("Session").'</td><td>'.$langs->trans("Value").'</td></tr>'."\n";
echo '<tr class="oddeven"><td>'.$langs->trans("SessionSavePath").'</td><td>'.session_save_path().'</td></tr>'."\n";
echo '<tr class="oddeven"><td>'.$langs->trans("SessionName").'</td><td>'.session_name().'</td></tr>'."\n";
echo '<tr class="oddeven"><td>'.$langs->trans("SessionId").'</td><td>'.session_id().'</td></tr>'."\n";
echo '<tr class="oddeven"><td>';
echo $langs->trans("CurrentSessionTimeOut");
echo '</td>';
echo '<td>';
echo ini_get('session.gc_maxlifetime').' '.$langs->trans("seconds");
echo '<!-- session.gc_maxlifetime = '.ini_get("session.gc_maxlifetime").' -->'."\n";
echo '<!-- session.gc_probability = '.ini_get("session.gc_probability").' -->'."\n";
echo '<!-- session.gc_divisor = '.ini_get("session.gc_divisor").' -->'."\n";
echo $form->textwithpicto('', $langs->trans("Parameter").' <b>php.ini</b>: <b>session.gc_maxlifetime</b><br>'.$langs->trans("SessionExplanation", ini_get("session.gc_probability"), ini_get("session.gc_divisor")));
echo "</td></tr>\n";
echo '<tr class="oddeven"><td>'.$langs->trans("CurrentTheme").'</td><td>'.$conf->theme.'</td></tr>'."\n";
echo '<tr class="oddeven"><td>'.$langs->trans("CurrentMenuHandler").'</td><td>';
echo $conf->standard_menu;
echo '</td></tr>'."\n";
echo '<tr class="oddeven"><td>'.$langs->trans("Screen").'</td><td>';
echo $_SESSION['dol_screenwidth'].' x '.$_SESSION['dol_screenheight'];
echo '</td></tr>'."\n";
echo '<tr class="oddeven"><td>'.$langs->trans("Session").'</td><td class="wordbreak">';
$i = 0;
foreach ($_SESSION as $key => $val) {
	if ($i > 0) {
		echo ', ';
	}
	if (is_array($val)) {
		echo $key.' => array(...)';
	} else {
		echo $key.' => '.dol_escape_htmltag($val);
	}
	$i++;
}
echo '</td></tr>'."\n";
echo '</table>';
echo '</div>';
echo '<br>';


// Shmop
if (getDolGlobalInt('MAIN_OPTIMIZE_SPEED') & 0x02) {
	$shmoparray = dol_listshmop();

	echo '<div class="div-table-responsive-no-min">';
	echo '<table class="noborder centpercent">';
	echo '<tr class="liste_titre">';
	echo '<td class="titlefieldcreate">'.$langs->trans("LanguageFilesCachedIntoShmopSharedMemory").'</td>';
	echo '<td>'.$langs->trans("NbOfEntries").'</td>';
	echo '<td class="right">'.$langs->trans("Address").'</td>';
	echo '</tr>'."\n";

	foreach ($shmoparray as $key => $val) {
		echo '<tr class="oddeven"><td>'.$key.'</td>';
		echo '<td>'.count($val).'</td>';
		echo '<td class="right">'.dol_getshmopaddress($key).'</td>';
		echo '</tr>'."\n";
	}

	echo '</table>';
	echo '</div>';
	echo '<br>';
}


// Localisation
echo '<div class="div-table-responsive-no-min">';
echo '<table class="noborder centpercent">';
echo '<tr class="liste_titre"><td class="titlefieldcreate">'.$langs->trans("LocalisationGestimagParameters").'</td><td>'.$langs->trans("Value").'</td></tr>'."\n";
echo '<tr class="oddeven"><td>'.$langs->trans("LanguageBrowserParameter", "HTTP_ACCEPT_LANGUAGE").'</td><td>'.$_SERVER["HTTP_ACCEPT_LANGUAGE"].'</td></tr>'."\n";
echo '<tr class="oddeven"><td>'.$langs->trans("CurrentUserLanguage").'</td><td>'.$langs->getDefaultLang().'</td></tr>'."\n";
// Thousands
$thousand = $langs->transnoentitiesnoconv("SeparatorThousand");
if ($thousand == 'SeparatorThousand') {
	$thousand = ' '; // ' ' does not work on trans method
}
if ($thousand == 'None') {
	$thousand = '';
}
echo '<tr class="oddeven"><td>'.$langs->trans("CurrentValueSeparatorThousand").'</td><td>'.($thousand == ' ' ? $langs->transnoentitiesnoconv("Space") : $thousand).'</td></tr>'."\n";
// Decimals
$dec = $langs->transnoentitiesnoconv("SeparatorDecimal");
echo '<tr class="oddeven"><td>'.$langs->trans("CurrentValueSeparatorDecimal").'</td><td>'.$dec.'</td></tr>'."\n";
// Show results of functions to see if everything works
echo '<tr class="oddeven"><td>&nbsp; => price2num(1233.56+1)</td><td>'.price2num(1233.56 + 1, '2').'</td></tr>'."\n";
echo '<tr class="oddeven"><td>&nbsp; => price2num('."'1".$thousand."234".$dec."56')</td><td>".price2num("1".$thousand."234".$dec."56", '2')."</td></tr>\n";
if (($thousand != ',' && $thousand != '.') || ($thousand != ' ')) {
	echo '<tr class="oddeven"><td>&nbsp; => price2num('."'1 234.56')</td><td>".price2num("1 234.56", '2')."</td>";
	echo "</tr>\n";
}
echo '<tr class="oddeven"><td>&nbsp; => price(1234.56)</td><td>'.price(1234.56).'</td></tr>'."\n";

// Timezones

// Database timezone
if ($conf->db->type == 'mysql' || $conf->db->type == 'mysqli') {
	echo '<tr class="oddeven"><td>'.$langs->trans("MySQLTimeZone").' (database)</td><td>'; // Timezone server base
	$sql = "SHOW VARIABLES where variable_name = 'system_time_zone'";
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		echo $form->textwithtooltip($obj->Value, $langs->trans('TZHasNoEffect'), 2, 1, img_info(''));
	}
	echo '</td></tr>'."\n";
}
$txt = $langs->trans("OSTZ").' (variable system TZ): '.(!empty($_ENV["TZ"]) ? $_ENV["TZ"] : $langs->trans("NotDefined")).'<br>'."\n";
$txt .= $langs->trans("PHPTZ").' (date_default_timezone_get() / php.ini date.timezone): '.(getServerTimeZoneString()." / ".(ini_get("date.timezone") ? ini_get("date.timezone") : $langs->trans("NotDefined")))."<br>\n"; // date.timezone must be in valued defined in http://fr3.php.net/manual/en/timezones.europe.php
$txt .= $langs->trans("Gestimag constant MAIN_SERVER_TZ").': '.getDolGlobalString('MAIN_SERVER_TZ', $langs->trans("NotDefined"));
echo '<tr class="oddeven"><td>'.$langs->trans("CurrentTimeZone").'</td><td>'; // Timezone server PHP
$a = getServerTimeZoneInt('now');
$b = getServerTimeZoneInt('winter');
$c = getServerTimeZoneInt('summer');
$daylight = round($c - $b);
//echo $a." ".$b." ".$c." ".$daylight;
$val = ($a >= 0 ? '+' : '').$a;
$val .= ' ('.($a == 'unknown' ? 'unknown' : ($a >= 0 ? '+' : '').($a * 3600)).')';
$val .= ' &nbsp; &nbsp; &nbsp; '.getServerTimeZoneString();
$val .= ' &nbsp; &nbsp; &nbsp; '.$langs->trans("DaylingSavingTime").': '.((is_null($b) || is_null($c)) ? 'unknown' : ($a == $c ? yn($daylight) : yn(0).($daylight ? '  &nbsp; &nbsp; ('.$langs->trans('YesInSummer').')' : '')));
echo $form->textwithtooltip($val, $txt, 2, 1, img_info(''));
echo '</td></tr>'."\n"; // value defined in http://fr3.php.net/manual/en/timezones.europe.php
echo '<tr class="oddeven"><td>&nbsp; => '.$langs->trans("CurrentHour").'</td><td>'.dol_print_date(dol_now('gmt'), 'dayhour', 'tzserver').'</td></tr>'."\n";
echo '<tr class="oddeven"><td>&nbsp; => dol_print_date(0,"dayhourtext")</td><td>'.dol_print_date(0, "dayhourtext").'</td>';
echo '<tr class="oddeven"><td>&nbsp; => dol_get_first_day(1970,1,false)</td><td>'.dol_get_first_day(1970, 1, false).' &nbsp; &nbsp; (=> dol_print_date() or idate() of this value = '.dol_print_date(dol_get_first_day(1970, 1, false), 'dayhour').')</td>';
echo '<tr class="oddeven"><td>&nbsp; => dol_get_first_day(1970,1,true)</td><td>'.dol_get_first_day(1970, 1, true).' &nbsp; &nbsp; (=> dol_print_date() or idate() of this value = '.dol_print_date(dol_get_first_day(1970, 1, true), 'dayhour').')</td>';
// Client
$tz = (int) $_SESSION['dol_tz'] + (int) $_SESSION['dol_dst'];
echo '<tr class="oddeven"><td>'.$langs->trans("ClientTZ").'</td><td>'.($tz ? ($tz >= 0 ? '+' : '').$tz : '').' ('.($tz >= 0 ? '+' : '').($tz * 60 * 60).')';
echo ' &nbsp; &nbsp; &nbsp; '.$_SESSION['dol_tz_string'];
echo ' &nbsp; &nbsp; &nbsp; '.$langs->trans("DaylingSavingTime").': ';
if ($_SESSION['dol_dst'] > 0) {
	echo yn(1);
} else {
	echo yn(0);
}
if (!empty($_SESSION['dol_dst_first'])) {
	echo ' &nbsp; &nbsp; ('.dol_print_date(dol_stringtotime($_SESSION['dol_dst_first']), 'dayhour', 'gmt').' - '.dol_print_date(dol_stringtotime($_SESSION['dol_dst_second']), 'dayhour', 'gmt').')';
}
echo '</td></tr>'."\n";
echo '</td></tr>'."\n";
echo '<tr class="oddeven"><td>&nbsp; => '.$langs->trans("ClientHour").'</td><td>'.dol_print_date(dol_now('gmt'), 'dayhour', 'tzuser').'</td></tr>'."\n";

$filesystemencoding = ini_get("unicode.filesystem_encoding"); // Disponible avec PHP 6.0
echo '<tr class="oddeven"><td>'.$langs->trans("File encoding").' (php.ini unicode.filesystem_encoding)</td><td>'.$filesystemencoding.'</td></tr>'."\n";

$tmp = ini_get("unicode.filesystem_encoding"); // Disponible avec PHP 6.0
if (empty($tmp) && !empty($_SERVER["WINDIR"])) {
	$tmp = 'iso-8859-1'; // By default for windows
}
if (empty($tmp)) {
	$tmp = 'utf-8'; // By default for other
}
if (getDolGlobalString('MAIN_FILESYSTEM_ENCODING')) {
	$tmp = getDolGlobalString('MAIN_FILESYSTEM_ENCODING');
}
echo '<tr class="oddeven"><td>&nbsp; => '.$langs->trans("File encoding").'</td><td>'.$tmp.'</td></tr>'."\n"; // date.timezone must be in valued defined in http://fr3.php.net/manual/en/timezones.europe.php

echo '</table>';
echo '</div>';
echo '<br>';



// Parameters in conf.php file (when a parameter start with ?, it is shown only if defined)
$configfileparameters = array(
	'gestimag_main_prod' => 'Production mode (Hide all error messages)',
	'gestimag_main_instance_unique_id' => $langs->trans("InstanceUniqueID"),
	'separator0' => '',
	'gestimag_main_url_root' => $langs->trans("URLRoot"),
	'?gestimag_main_url_root_alt' => $langs->trans("URLRoot").' (alt)',
	'gestimag_main_document_root'=> $langs->trans("DocumentRootServer"),
	'?gestimag_main_document_root_alt' => $langs->trans("DocumentRootServer").' (alt)',
	'gestimag_main_data_root' => $langs->trans("DataRootServer"),
	'separator1' => '',
	'gestimag_main_db_host' => $langs->trans("DatabaseServer"),
	'gestimag_main_db_port' => $langs->trans("DatabasePort"),
	'gestimag_main_db_name' => $langs->trans("DatabaseName"),
	'gestimag_main_db_type' => $langs->trans("DriverType"),
	'gestimag_main_db_user' => $langs->trans("DatabaseUser"),
	'gestimag_main_db_pass' => $langs->trans("DatabasePassword"),
	'gestimag_main_db_character_set' => $langs->trans("DBStoringCharset"),
	'gestimag_main_db_collation' => $langs->trans("DBSortingCollation"),
	'?gestimag_main_db_prefix' => $langs->trans("DatabasePrefix"),
	'gestimag_main_db_readonly' => $langs->trans("ReadOnlyMode"),
	'separator2' => '',
	'gestimag_main_authentication' => $langs->trans("AuthenticationMode"),
	'?multicompany_transverse_mode'=>  $langs->trans("MultiCompanyMode"),
	'separator'=> '',
	'?gestimag_main_auth_ldap_login_attribute' => 'gestimag_main_auth_ldap_login_attribute',
	'?gestimag_main_auth_ldap_host' => 'gestimag_main_auth_ldap_host',
	'?gestimag_main_auth_ldap_port' => 'gestimag_main_auth_ldap_port',
	'?gestimag_main_auth_ldap_version' => 'gestimag_main_auth_ldap_version',
	'?gestimag_main_auth_ldap_dn' => 'gestimag_main_auth_ldap_dn',
	'?gestimag_main_auth_ldap_admin_login' => 'gestimag_main_auth_ldap_admin_login',
	'?gestimag_main_auth_ldap_admin_pass' => 'gestimag_main_auth_ldap_admin_pass',
	'?gestimag_main_auth_ldap_debug' => 'gestimag_main_auth_ldap_debug',
	'separator3' => '',
	'?gestimag_lib_FPDF_PATH' => 'gestimag_lib_FPDF_PATH',
	'?gestimag_lib_TCPDF_PATH' => 'gestimag_lib_TCPDF_PATH',
	'?gestimag_lib_FPDI_PATH' => 'gestimag_lib_FPDI_PATH',
	'?gestimag_lib_TCPDI_PATH' => 'gestimag_lib_TCPDI_PATH',
	'?gestimag_lib_NUSOAP_PATH' => 'gestimag_lib_NUSOAP_PATH',
	'?gestimag_lib_GEOIP_PATH' => 'gestimag_lib_GEOIP_PATH',
	'?gestimag_lib_ODTPHP_PATH' => 'gestimag_lib_ODTPHP_PATH',
	'?gestimag_lib_ODTPHP_PATHTOPCLZIP' => 'gestimag_lib_ODTPHP_PATHTOPCLZIP',
	'?gestimag_js_CKEDITOR' => 'gestimag_js_CKEDITOR',
	'?gestimag_js_JQUERY' => 'gestimag_js_JQUERY',
	'?gestimag_js_JQUERY_UI' => 'gestimag_js_JQUERY_UI',
	'?gestimag_font_DOL_DEFAULT_TTF' => 'gestimag_font_DOL_DEFAULT_TTF',
	'?gestimag_font_DOL_DEFAULT_TTF_BOLD' => 'gestimag_font_DOL_DEFAULT_TTF_BOLD',
	'separator4' => '',
	'gestimag_main_restrict_os_commands' => 'Restrict CLI commands for backups',
	'gestimag_main_restrict_ip' => 'Restrict access to some IPs only',
	'?gestimag_mailing_limit_sendbyweb' => 'Limit nb of email sent by page',
	'?gestimag_mailing_limit_sendbycli' => 'Limit nb of email sent by cli',
	'?gestimag_mailing_limit_sendbyday' => 'Limit nb of email sent per day',
	'?gestimag_strict_mode' => 'Strict mode is on/off',
	'?gestimag_nocsrfcheck' => 'Disable CSRF security checks'
);

echo '<div class="div-table-responsive-no-min">';
echo '<table class="noborder centpercent">';
echo '<tr class="liste_titre">';
echo '<td class="titlefieldcreate">'.$langs->trans("Parameters").' ';
echo $langs->trans("ConfigurationFile").' ('.$conffiletoshowshort.')';
echo '</td>';
echo '<td>'.$langs->trans("Parameter").'</td>';
echo '<td>'.$langs->trans("Value").'</td>';
echo '</tr>'."\n";

foreach ($configfileparameters as $key => $value) {
	$ignore = 0;

	if (empty($ignore)) {
		$newkey = preg_replace('/^\?/', '', $key);

		if (preg_match('/^\?/', $key) && empty(${$newkey})) {
			if ($newkey != 'multicompany_transverse_mode' || !isModEnabled('multicompany')) {
				continue; // We discard parameters starting with ?
			}
		}
		if (strpos($newkey, 'separator') !== false && $lastkeyshown == 'separator') {
			continue;
		}

		echo '<tr class="oddeven">';
		if (strpos($newkey, 'separator') !== false) {
			echo '<td colspan="3">&nbsp;</td>';
		} else {
			// Label
			echo "<td>".$value.'</td>';
			// Key
			echo '<td>'.$newkey.'</td>';
			// Value
			echo "<td>";
			if (in_array($newkey, array('gestimag_main_db_pass', 'gestimag_main_auth_ldap_admin_pass'))) {
				if (empty($gestimag_main_prod)) {
					echo '<!-- '.${$newkey}.' -->';
					echo showValueWithClipboardCPButton(${$newkey}, 0, '********');
				} else {
					echo '**********';
				}
			} elseif ($newkey == 'gestimag_main_url_root' && preg_match('/__auto__/', ${$newkey})) {
				echo ${$newkey}.' => '.constant('DOL_MAIN_URL_ROOT');
			} elseif ($newkey == 'gestimag_main_document_root_alt') {
				$tmparray = explode(',', $gestimag_main_document_root_alt);
				$i = 0;
				foreach ($tmparray as $value2) {
					if ($i > 0) {
						echo ', ';
					}
					echo $value2;
					if (!is_readable($value2)) {
						$langs->load("errors");
						echo ' '.img_warning($langs->trans("ErrorCantReadDir", $value2));
					}
					++$i;
				}
			} elseif ($newkey == 'gestimag_main_instance_unique_id') {
				//echo $conf->file->instance_unique_id;
				global $gestimag_main_cookie_cryptkey, $gestimag_main_instance_unique_id;
				$valuetoshow = $gestimag_main_instance_unique_id ? $gestimag_main_instance_unique_id : $gestimag_main_cookie_cryptkey; // Use $gestimag_main_instance_unique_id first then $gestimag_main_cookie_cryptkey
				if (empty($gestimag_main_prod)) {
					echo '<!-- '.$gestimag_main_instance_unique_id.' (this will not be visible if $gestimag_main_prod = 1 -->';
					echo showValueWithClipboardCPButton($valuetoshow, 0, '********');
					echo ' &nbsp; &nbsp; <span class="opacitymedium">'.$langs->trans("ThisValueCanBeReadBecauseInstanceIsNotInProductionMode").'</span>';
				} else {
					echo '**********';
					echo ' &nbsp; &nbsp; <span class="opacitymedium">'.$langs->trans("SeeConfFile").'</span>';
				}
				if (empty($valuetoshow)) {
					echo img_warning("EditConfigFileToAddEntry", 'gestimag_main_instance_unique_id');
				}
				echo '</td></tr>';
				echo '<tr class="oddeven"><td></td><td>&nbsp; => '.$langs->trans("HashForPing").'</td><td>'.md5('gestimag'.$valuetoshow).'</td></tr>'."\n";
			} elseif ($newkey == 'gestimag_main_prod') {
				echo ${$newkey};

				$valuetoshow = ${$newkey};
				if (empty($valuetoshow)) {
					echo img_warning($langs->trans('SwitchThisForABetterSecurity', 1));
				}
			} elseif ($newkey == 'gestimag_nocsrfcheck') {
				echo ${$newkey};

				$valuetoshow = ${$newkey};
				if (!empty($valuetoshow)) {
					echo img_warning($langs->trans('SwitchThisForABetterSecurity', 0));
				}
			} elseif ($newkey == 'gestimag_main_db_readonly') {
				echo ${$newkey};

				$valuetoshow = ${$newkey};
				if (!empty($valuetoshow)) {
					echo img_warning($langs->trans('ReadOnlyMode', 1));
				}
			} else {
				print(empty(${$newkey}) ? '' : ${$newkey});
			}
			if ($newkey == 'gestimag_main_url_root' && ${$newkey} != DOL_MAIN_URL_ROOT) {
				echo ' (currently overwritten by autodetected value: '.DOL_MAIN_URL_ROOT.')';
			}
			echo "</td>";
		}
		echo "</tr>\n";
		$lastkeyshown = $newkey;
	}
}
echo '</table>';
echo '</div>';
echo '<br>';



// Parameters in database
echo '<div class="div-table-responsive-no-min">';
echo '<table class="noborder">';
echo '<tr class="liste_titre">';
echo '<td class="titlefield">'.$langs->trans("Parameters").' '.$langs->trans("Database").'</td>';
echo '<td>'.$langs->trans("Value").'</td>';
if (!isModEnabled('multicompany') || !$user->entity) {
	echo '<td class="center width="80px"">'.$langs->trans("Entity").'</td>'; // If superadmin or multicompany disabled
}
echo "</tr>\n";

$sql = "SELECT";
$sql .= " rowid";
$sql .= ", ".$db->decrypt('name')." as name";
$sql .= ", ".$db->decrypt('value')." as value";
$sql .= ", type";
$sql .= ", note";
$sql .= ", entity";
$sql .= " FROM ".MAIN_DB_PREFIX."const";
if (!isModEnabled('multicompany')) {
	// If no multicompany mode, admins can see global and their constantes
	$sql .= " WHERE entity IN (0,".$conf->entity.")";
} else {
	// If multicompany mode, superadmin (user->entity=0) can see everything, admin are limited to their entities.
	if ($user->entity) {
		$sql .= " WHERE entity IN (".$db->sanitize($user->entity.",".$conf->entity).")";
	}
}
$sql .= " ORDER BY entity, name ASC";
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;

	while ($i < $num) {
		$obj = $db->fetch_object($resql);

		echo '<tr class="oddeven">';
		echo '<td class="tdoverflowmax600" title="'.dol_escape_htmltag($obj->name).'">'.dol_escape_htmltag($obj->name).'</td>'."\n";
		echo '<td class="tdoverflowmax300">';
		if (isASecretKey($obj->name)) {
			if (empty($gestimag_main_prod)) {
				echo '<!-- '.$obj->value.' -->';
			}
			echo '**********';
		} else {
			echo dol_escape_htmltag($obj->value);
		}
		echo '</td>'."\n";
		if (!isModEnabled('multicompany') || !$user->entity) {
			echo '<td class="center" width="80px">'.$obj->entity.'</td>'."\n"; // If superadmin or multicompany disabled
		}
		echo "</tr>\n";

		$i++;
	}
}

echo '</table>';
echo '</div>';

// End of page
llxFooter();
$db->close();
