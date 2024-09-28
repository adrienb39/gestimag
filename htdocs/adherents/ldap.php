<?php
/* Copyright (C) 2006		Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2006-2021	Regis Houssin		<regis.houssin@inodbox.com>
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
 *       \file       htdocs/adherents/ldap.php
 *       \ingroup    ldap member
 *       \brief      Page fiche LDAP adherent
 */

// Load Gestimag environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ldap.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/ldap.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';

// Load translation files required by the page
$langs->loadLangs(array("companies", "members", "ldap", "admin"));

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alphanohtml');
$action = GETPOST('action', 'aZ09');

// Protection
$socid = 0;
if ($user->socid > 0) {
	$socid = $user->socid;
}

$object = new Adherent($db);

// Fetch object
if ($id > 0 || !empty($ref)) {
	// Load member
	$result = $object->fetch($id, $ref);

	// Define variables to know what current user can do on users
	$canadduser = (!empty($user->admin) || $user->hasRight('user', 'user', 'creer'));
	// Define variables to know what current user can do on properties of user linked to edited member
	if ($object->user_id) {
		// $User is the user who edits, $object->user_id is the id of the related user in the edited member
		$caneditfielduser = ((($user->id == $object->user_id) && $user->hasRight('user', 'self', 'creer'))
			|| (($user->id != $object->user_id) && $user->hasRight('user', 'user', 'creer')));
		$caneditpassworduser = ((($user->id == $object->user_id) && $user->hasRight('user', 'self', 'password'))
			|| (($user->id != $object->user_id) && $user->hasRight('user', 'user', 'password')));
	}
}

// Define variables to determine what the current user can do on the members
$canaddmember = $user->hasRight('adherent', 'creer');
// Define variables to determine what the current user can do on the properties of a member
if ($id) {
	$caneditfieldmember = $user->hasRight('adherent', 'creer');
}

// Security check
$result = restrictedArea($user, 'adherent', $object->id, '', '', 'socid', 'rowid', 0);


/*
 * Actions
 */

if ($action == 'gestimag2ldap') {
	$ldap = new Ldap();
	$result = $ldap->connectBind();

	if ($result > 0) {
		$info = $object->_load_ldap_info();
		$dn = $object->_load_ldap_dn($info);
		$olddn = $dn; // We can say that old dn = dn as we force synchro

		$result = $ldap->update($dn, $info, $user, $olddn);
	}

	if ($result >= 0) {
		setEventMessages($langs->trans("MemberSynchronized"), null, 'mesgs');
	} else {
		setEventMessages($ldap->error, $ldap->errors, 'errors');
	}
}



/*
 *	View
 */

$form = new Form($db);

llxHeader('', $langs->trans("Member"), 'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros|DE:Modul_Mitglieder');

$head = member_prepare_head($object);

echo dol_get_fiche_head($head, 'ldap', $langs->trans("Member"), 0, 'user');

$linkback = '<a href="'.DOL_URL_ROOT.'/adherents/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

dol_banner_tab($object, 'rowid', $linkback);

echo '<div class="fichecenter">';

echo '<div class="underbanner clearboth"></div>';
echo '<table class="border centpercent tableforfield">';

// Login
echo '<tr><td class="titlefield">'.$langs->trans("Login").' / '.$langs->trans("Id").'</td><td class="valeur">'.dol_escape_htmltag($object->login).'&nbsp;</td></tr>';

// If there is a link to the unencrypted password, we show the value in database here so we can compare because it is shown nowhere else
// This is for very old situation. Password are now encrypted and $object->pass is empty.
if (getDolGlobalString('LDAP_MEMBER_FIELD_PASSWORD')) {
	echo '<tr><td>'.$langs->trans("LDAPFieldPasswordNotCrypted").'</td>';
	echo '<td class="valeur">'.dol_escape_htmltag($object->pass).'</td>';
	echo "</tr>\n";
}

$adht = new AdherentType($db);
$adht->fetch($object->typeid);

// Type
echo '<tr><td>'.$langs->trans("Type").'</td><td class="valeur">'.$adht->getNomUrl(1)."</td></tr>\n";

// LDAP DN
echo '<tr><td>LDAP '.$langs->trans("LDAPMemberDn").'</td><td class="valeur">'.getDolGlobalString('LDAP_MEMBER_DN')."</td></tr>\n";

// LDAP Cle
echo '<tr><td>LDAP '.$langs->trans("LDAPNamingAttribute").'</td><td class="valeur">'.getDolGlobalString('LDAP_KEY_MEMBERS')."</td></tr>\n";

// LDAP Server
echo '<tr><td>LDAP '.$langs->trans("Type").'</td><td class="valeur">'.getDolGlobalString('LDAP_SERVER_TYPE')."</td></tr>\n";
echo '<tr><td>LDAP '.$langs->trans("Version").'</td><td class="valeur">'.getDolGlobalString('LDAP_SERVER_PROTOCOLVERSION')."</td></tr>\n";
echo '<tr><td>LDAP '.$langs->trans("LDAPPrimaryServer").'</td><td class="valeur">'.getDolGlobalString('LDAP_SERVER_HOST')."</td></tr>\n";
echo '<tr><td>LDAP '.$langs->trans("LDAPSecondaryServer").'</td><td class="valeur">'.getDolGlobalString('LDAP_SERVER_HOST_SLAVE')."</td></tr>\n";
echo '<tr><td>LDAP '.$langs->trans("LDAPServerPort").'</td><td class="valeur">'.getDolGlobalString('LDAP_SERVER_PORT')."</td></tr>\n";

echo '</table>';

echo '</div>';

echo dol_get_fiche_end();

/*
 * Action bar
 */
echo '<div class="tabsAction">';

if (getDolGlobalString('LDAP_MEMBER_ACTIVE') && getDolGlobalString('LDAP_MEMBER_ACTIVE') != Ldap::SYNCHRO_LDAP_TO_DOLIBARR) {
	echo '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=gestimag2ldap">'.$langs->trans("ForceSynchronize").'</a></div>';
}

echo "</div>\n";

if (getDolGlobalString('LDAP_MEMBER_ACTIVE') && getDolGlobalString('LDAP_MEMBER_ACTIVE') != Ldap::SYNCHRO_LDAP_TO_DOLIBARR) {
	echo "<br>\n";
}



// Affichage attributes LDAP
echo load_fiche_titre($langs->trans("LDAPInformationsForThisMember"));

echo '<table width="100%" class="noborder">';

echo '<tr class="liste_titre">';
echo '<td>'.$langs->trans("LDAPAttributes").'</td>';
echo '<td>'.$langs->trans("Value").'</td>';
echo '</tr>';

// Lecture LDAP
$ldap = new Ldap();
$result = $ldap->connectBind();
if ($result > 0) {
	$info = $object->_load_ldap_info();
	$dn = $object->_load_ldap_dn($info, 1);
	$search = "(".$object->_load_ldap_dn($info, 2).")";

	if (empty($dn)) {
		$langs->load("errors");
		echo '<tr class="oddeven"><td colspan="2"><span class="error">'.$langs->trans("ErrorModuleSetupNotComplete", $langs->transnoentitiesnoconv("Member")).'</span></td></tr>';
	} else {
		$records = $ldap->getAttribute($dn, $search);

		//print_r($records);

		// Show tree
		if (((!is_numeric($records)) || $records != 0) && (!isset($records['count']) || $records['count'] > 0)) {
			if (!is_array($records)) {
				echo '<tr class="oddeven"><td colspan="2"><span class="error">'.$langs->trans("ErrorFailedToReadLDAP").'</span></td></tr>';
			} else {
				$result = show_ldap_content($records, 0, $records['count'], true);
			}
		} else {
			echo '<tr class="oddeven"><td colspan="2">'.$langs->trans("LDAPRecordNotFound").' (dn='.dol_escape_htmltag($dn).' - search='.dol_escape_htmltag($search).')</td></tr>';
		}
	}

	$ldap->unbind();
} else {
	setEventMessages($ldap->error, $ldap->errors, 'errors');
}


echo '</table>';

// End of page
llxFooter();
$db->close();
