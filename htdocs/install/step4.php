<?php
/* Copyright (C) 2004       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004       Benoit Mortier          <benoit.mortier@opensides.be>
 * Copyright (C) 2004       Sebastien DiCintio      <sdicintio@ressource-toi.org>
 * Copyright (C) 2004-2008  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2015-2016  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
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
 *	\file       htdocs/install/step4.php
 *	\ingroup	install
 *	\brief      Ask login and password of Gestimag admin user
 */


include_once 'inc.php';
require_once $gestimag_main_document_root.'/core/class/conf.class.php';
require_once $gestimag_main_document_root.'/core/lib/admin.lib.php';

global $langs;

$setuplang = GETPOST('selectlang', 'aZ09', 3) ? GETPOST('selectlang', 'aZ09', 3) : (empty($argv[1]) ? 'auto' : $argv[1]);
$langs->setDefaultLang($setuplang);

$langs->loadLangs(array("admin", "install"));

// Now we load forced value from install.forced.php file.
$useforcedwizard = false;
$forcedfile = "./install.forced.php";
if ($conffile == "/etc/gestimag/conf.php") {
	$forcedfile = "/etc/gestimag/install.forced.php";
}
if (@file_exists($forcedfile)) {
	$useforcedwizard = true;
	include_once $forcedfile;
}

gestimag_install_syslog("--- step4: entering step4.php page");

$error = 0;
$ok = 0;



/*
 *	View
 */

pHeader($langs->trans("GestimagSetup").' - '.$langs->trans("AdminAccountCreation"), "step5");

// Test if we can run a first install process
if (!is_writable($conffile)) {
	echo $langs->trans("ConfFileIsNotWritable", $conffiletoshow);
	pFooter(1, $setuplang, 'jscheckparam');
	exit;
}


echo '<h3><img class="valignmiddle inline-block paddingright" src="../theme/common/octicons/build/svg/key.svg" width="20" alt="Database"> '.$langs->trans("GestimagAdminLogin").'</h3>';

echo $langs->trans("LastStepDesc").'<br><br>';


echo '<table cellspacing="0" cellpadding="2">';

$db = getDoliDBInstance($conf->db->type, $conf->db->host, $conf->db->user, $conf->db->pass, $conf->db->name, (int) $conf->db->port);

if ($db->ok) {
	echo '<tr><td><label for="login">'.$langs->trans("Login").' :</label></td><td>';
	echo '<input id="login" name="login" type="text" value="'.(GETPOSTISSET("login") ? GETPOST("login", 'alpha') : (isset($force_install_gestimaglogin) ? $force_install_gestimaglogin : '')).'"'.(@$force_install_noedit == 2 && $force_install_gestimaglogin !== null ? ' disabled' : '').' autofocus></td></tr>';
	echo '<tr><td><label for="pass">'.$langs->trans("Password").' :</label></td><td>';
	echo '<input type="password" id="pass" name="pass" autocomplete="new-password" minlength="8"></td></tr>';
	echo '<tr><td><label for="pass_verif">'.$langs->trans("PasswordRetype").' :</label></td><td>';
	echo '<input type="password" id="pass_verif" name="pass_verif" autocomplete="new-password" minlength="8"></td></tr>';
	echo '</table>';

	if (GETPOSTINT("error") == 1) {
		echo '<br>';
		echo '<div class="error">'.$langs->trans("PasswordsMismatch").'</div>';
		$error = 0; // We show button
	}

	if (GETPOSTINT("error") == 2) {
		echo '<br>';
		echo '<div class="error">';
		echo $langs->trans("PleaseTypePassword");
		echo '</div>';
		$error = 0; // We show button
	}

	if (GETPOSTINT("error") == 3) {
		echo '<br>';
		echo '<div class="error">'.$langs->trans("PleaseTypeALogin").'</div>';
		$error = 0; // We show button
	}
}

$ret = 0;
if ($error && isset($argv[1])) {
	$ret = 1;
}
gestimag_install_syslog("Exit ".$ret);

gestimag_install_syslog("--- step4: end");

pFooter($error, $setuplang);

$db->close();

// Return code if ran from command line
if ($ret) {
	exit($ret);
}
