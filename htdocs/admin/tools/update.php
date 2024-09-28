<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2012      Juanjo Menent        <jmenent@2byte.es>
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
 *		\file 		htdocs/admin/tools/update.php
 *		\brief      Page to make a Gestimag online upgrade
 */

if (! defined('CSRFCHECK_WITH_TOKEN')) {
	define('CSRFCHECK_WITH_TOKEN', '1');		// Force use of CSRF protection with tokens even for GET
}

// Load Gestimag environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "other"));

$action = GETPOST('action', 'aZ09');

if (!$user->admin) {
	accessforbidden();
}

if (GETPOST('msg', 'alpha')) {
	setEventMessages(GETPOST('msg', 'alpha'), null, 'errors');
}


$urlgestimag = 'https://www.gestimag.org/downloads/';
$gestimagroot = preg_replace('/([\\/]+)$/i', '', DOL_DOCUMENT_ROOT);
$gestimagroot = preg_replace('/([^\\/]+)$/i', '', $gestimagroot);
$gestimagdataroot = preg_replace('/([\\/]+)$/i', '', DOL_DATA_ROOT);

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
		$sfurl = 'xml_not_available';
	}
}


/*
 * View
 */

$wikihelp = 'EN:Installation_-_Upgrade|FR:Installation_-_Mise_à_jour|ES:Instalación_-_Actualización';
llxHeader('', $langs->trans("Upgrade"), $wikihelp, '', 0, 0, '', '', '', 'mod-admin page-tools_update');

echo load_fiche_titre($langs->trans("Upgrade"), '', 'title_setup');

echo '<br>';

echo $langs->trans("CurrentVersion").' : <strong>'.DOL_VERSION.'</strong><br>';

if (function_exists('curl_init')) {
	$conf->global->MAIN_USE_RESPONSE_TIMEOUT = 10;

	if ($action == 'getlastversion') {
		if ($sfurl == 'xml_not_available') {
			$langs->load("errors");
			echo $langs->trans("LastStableVersion").' : <b class="error">'.$langs->trans("ErrorFunctionNotAvailableInPHP", 'simplexml_load_string').'</b><br>';
		} elseif ($sfurl) {
			$i = 0;
			while (!empty($sfurl->channel[0]->item[$i]->title) && $i < 10000) {
				$title = $sfurl->channel[0]->item[$i]->title;
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
			echo $langs->trans("LastStableVersion").' : <b>'.(($version != '0.0') ? $version : $langs->trans("Unknown")).'</b><br>';
		} else {
			echo $langs->trans("LastStableVersion").' : <b>'.$langs->trans("UpdateServerOffline").'</b><br>';
		}
	} else {
		echo $langs->trans("LastStableVersion").' : <a href="'.$_SERVER["PHP_SELF"].'?action=getlastversion&token='.newToken().'" class="button smallpaddingimp">'.$langs->trans("Check").'</a><br>';
	}
}

echo '<br>';
echo '<br>';

// Upgrade
echo $langs->trans("Upgrade").'<br>';
echo '<hr>';
echo $langs->trans("ThisIsProcessToFollow").'<br>';
echo '<b>'.$langs->trans("StepNb", 1).'</b>: ';
$fullurl = '<a href="'.$urlgestimag.'" target="_blank" rel="noopener noreferrer">'.$urlgestimag.'</a>';
echo str_replace('{s}', $fullurl, $langs->trans("DownloadPackageFromWebSite", '{s}')).'<br>';
echo '<b>'.$langs->trans("StepNb", 2).'</b>: ';
echo str_replace('{s}', $gestimagroot, $langs->trans("UnpackPackageInGestimagRoot", '{s}')).'<br>';
echo '<b>'.$langs->trans("StepNb", 3).'</b>: ';
echo $langs->trans("RemoveLock", $gestimagdataroot.'/install.lock').'<br>';
echo '<b>'.$langs->trans("StepNb", 4).'</b>: ';
$fullurl = '<a href="'.DOL_URL_ROOT.'/install/" target="_blank" rel="noopener noreferrer">'.DOL_URL_ROOT.'/install/</a>';
echo str_replace('{s}', $fullurl, $langs->trans("CallUpdatePage", '{s}')).'<br>';
echo '<b>'.$langs->trans("StepNb", 5).'</b>: ';
echo $langs->trans("RestoreLock", $gestimagdataroot.'/install.lock').'<br>';

echo '<br>';
echo '<br>';





echo $langs->trans("AddExtensionThemeModuleOrOther").'<br>';
echo '<hr>';
$texttoshow = $langs->trans("GoModuleSetupArea", DOL_URL_ROOT.'/admin/modules.php?mode=deploy', '{s2}');
$texttoshow = str_replace('{s2}', img_picto('', 'tools', 'class="pictofixedwidth"').$langs->transnoentities("Home").' - '.$langs->transnoentities("Setup").' - '.$langs->transnoentities("Modules"), $texttoshow);
echo $texttoshow;

// End of page
llxFooter();
$db->close();
