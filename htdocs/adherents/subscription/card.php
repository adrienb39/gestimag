<?php
/* Copyright (C) 2007-2019  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2020  Frédéric France         <frederic.france@netlogic.fr>
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
 *       \file       htdocs/adherents/subscription/card.php
 *       \ingroup    member
 *       \brief      Page to add/edit/remove a member subscription
 */

// Load Gestimag environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/subscription.class.php';
if (isModEnabled("bank")) {
	require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array("companies", "members", "bills", "users", "banks"));

$adh = new Adherent($db);
$adht = new AdherentType($db);
$object = new Subscription($db);
$errmsg = '';

$action = GETPOST("action", 'alpha');
$rowid = GETPOSTINT("rowid") ? GETPOSTINT("rowid") : GETPOSTINT("id");
$typeid = GETPOSTINT("typeid");
$cancel = GETPOST('cancel', 'alpha');
$confirm = GETPOST('confirm');
$note = GETPOST('note', 'alpha');
$typeid = GETPOSTINT('typeid');
$amount = (float) price2num(GETPOST('amount', 'alpha'), 'MT');

if (!$user->hasRight('adherent', 'cotisation', 'lire')) {
	accessforbidden();
}

$permissionnote = $user->hasRight('adherent', 'cotisation', 'creer'); // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->hasRight('adherent', 'cotisation', 'creer'); // Used by the include of actions_dellink.inc.php
$permissiontoedit = $user->hasRight('adherent', 'cotisation', 'creer'); // Used by the include of actions_lineupdonw.inc.php

$hookmanager->initHooks(array('subscriptioncard', 'globalcard'));

// Security check
$result = restrictedArea($user, 'subscription', 0); // TODO Check on object id


/*
 * 	Actions
 */

if ($cancel) {
	$action = '';
}

//include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be include, not include_once

include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php'; // Must be include, not include_once

//include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';	// Must be include, not include_once


if ($user->hasRight('adherent', 'cotisation', 'creer') && $action == 'update' && !$cancel) {
	// Load current object
	$result = $object->fetch($rowid);
	if ($result > 0) {
		$db->begin();

		$errmsg = '';

		$newdatestart = dol_mktime(GETPOSTINT('datesubhour'), GETPOSTINT('datesubmin'), 0, GETPOSTINT('datesubmonth'), GETPOSTINT('datesubday'), GETPOSTINT('datesubyear'));
		$newdateend = dol_mktime(GETPOSTINT('datesubendhour'), GETPOSTINT('datesubendmin'), 0, GETPOSTINT('datesubendmonth'), GETPOSTINT('datesubendday'), GETPOSTINT('datesubendyear'));

		if ($object->fk_bank > 0) {
			$accountline = new AccountLine($db);
			$result = $accountline->fetch($object->fk_bank);

			// If transaction consolidated
			if ($accountline->rappro) {
				$errmsg = $langs->trans("SubscriptionLinkedToConciliatedTransaction");
			} else {
				$accountline->datev = $newdatestart;
				$accountline->dateo = $newdatestart;
				$accountline->amount = $amount;

				$result = $accountline->update($user);
				if ($result < 0) {
					$errmsg = $accountline->error;
				}
			}
		}

		if (!$errmsg) {
			// Modify values
			$object->dateh = $newdatestart;
			$object->datef = $newdateend;
			$object->fk_type = $typeid;
			$object->note_public = $note;
			$object->note_private = $note;

			$object->amount = $amount;

			$result = $object->update($user);
			if ($result >= 0 && !count($object->errors)) {
				$db->commit();

				header("Location: card.php?rowid=".$object->id);
				exit;
			} else {
				$db->rollback();

				if ($object->error) {
					$errmsg = $object->error;
				} else {
					foreach ($object->errors as $error) {
						if ($errmsg) {
							$errmsg .= '<br>';
						}
						$errmsg .= $error;
					}
				}
				$action = '';
			}
		} else {
			$db->rollback();
		}
	}
}

if ($action == 'confirm_delete' && $confirm == 'yes' && $user->hasRight('adherent', 'cotisation', 'creer')) {
	$result = $object->fetch($rowid);
	$result = $object->delete($user);
	if ($result > 0) {
		header("Location: ".DOL_URL_ROOT."/adherents/card.php?rowid=".$object->fk_adherent);
		exit;
	} else {
		$errmesg = $adh->error;
	}
}



/*
 * View
 */

$form = new Form($db);

$help_url = 'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros|DE:Modul_Mitglieder';
llxHeader('', $langs->trans("SubscriptionCard"), $help_url);


dol_htmloutput_errors($errmsg);


if ($user->hasRight('adherent', 'cotisation', 'creer') && $action == 'edit') {
	/********************************************
	 *
	 * Subscription card in edit mode
	 *
	 ********************************************/

	$object->fetch($rowid);
	$result = $adh->fetch($object->fk_adherent);

	$head = subscription_prepare_head($object);

	echo '<form name="update" action="'.$_SERVER["PHP_SELF"].'" method="post">';
	echo '<input type="hidden" name="token" value="'.newToken().'">';
	echo "<input type=\"hidden\" name=\"action\" value=\"update\">";
	echo "<input type=\"hidden\" name=\"rowid\" value=\"$rowid\">";
	echo "<input type=\"hidden\" name=\"fk_bank\" value=\"".$object->fk_bank."\">";

	echo dol_get_fiche_head($head, 'general', $langs->trans("Subscription"), 0, 'payment');

	$linkback = '<a href="'.DOL_URL_ROOT.'/adherents/subscription/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	echo "\n";
	echo '<table class="border centpercent">';

	// Ref
	echo '<tr><td class="titlefieldcreate">'.$langs->trans("Ref").'</td>';
	echo '<td class="valeur">';
	echo $form->showrefnav($object, 'rowid', $linkback, 1);
	echo '</td></tr>';

	// Member
	$adh->ref = $adh->getFullName($langs);
	echo '<tr>';
	echo '<td>'.$langs->trans("Member").'</td>';
	echo '<td class="valeur">'.$adh->getNomUrl(-1, 0, 'subscription').'</td>';
	echo '</tr>';

	// Type
	echo '<tr>';
	echo '<td>'.$langs->trans("Type").'</td>';
	echo '<td class="valeur">';
	echo $form->selectarray("typeid", $adht->liste_array(), (GETPOSTISSET("typeid") ? GETPOST("typeid") : $object->fk_type));
	print'</td></tr>';

	// Date start subscription
	echo '<tr><td>'.$langs->trans("DateSubscription").'</td>';
	echo '<td class="valeur">';
	echo $form->selectDate($object->dateh, 'datesub', 1, 1, 0, 'update', 1);
	echo '</td>';
	echo '</tr>';

	// Date end subscription
	echo '<tr><td>'.$langs->trans("DateEndSubscription").'</td>';
	echo '<td class="valeur">';
	echo $form->selectDate($object->datef, 'datesubend', 0, 0, 0, 'update', 1);
	echo '</td>';
	echo '</tr>';

	// Amount
	echo '<tr><td>'.$langs->trans("Amount").'</td>';
	echo '<td class="valeur">';
	echo '<input type="text" class="flat width200" name="amount" value="'.price($object->amount).'"></td></tr>';

	// Label
	echo '<tr><td>'.$langs->trans("Label").'</td>';
	echo '<td class="valeur">';
	echo '<input type="text" class="flat" name="note" value="'.$object->note_public.'"></td></tr>';

	// Bank line
	if (isModEnabled("bank") && (getDolGlobalString('ADHERENT_BANK_USE') || $object->fk_bank)) {
		echo '<tr><td>'.$langs->trans("BankTransactionLine").'</td><td class="valeur">';
		if ($object->fk_bank) {
			$bankline = new AccountLine($db);
			$result = $bankline->fetch($object->fk_bank);
			echo $bankline->getNomUrl(1, 0, 'showall');
		} else {
			echo $langs->trans("NoneF");
		}
		echo '</td></tr>';
	}

	echo '</table>';

	echo dol_get_fiche_end();

	echo $form->buttonsSaveCancel();

	echo '</form>';
	echo "\n";
}

if ($rowid && $action != 'edit') {
	/********************************************
	 *
	 * Subscription card in view mode
	 *
	 ********************************************/

	$result = $object->fetch($rowid);
	$result = $adh->fetch($object->fk_adherent);

	$head = subscription_prepare_head($object);

	echo dol_get_fiche_head($head, 'general', $langs->trans("Subscription"), -1, 'payment');

	// Confirmation to delete subscription
	if ($action == 'delete') {
		$formquestion=array();
		//$formquestion['text']='<b>'.$langs->trans("ThisWillAlsoDeleteBankRecord").'</b>';
		$text = $langs->trans("ConfirmDeleteSubscription");
		if (isModEnabled("bank") && getDolGlobalString('ADHERENT_BANK_USE')) {
			$text .= '<br>'.img_warning().' '.$langs->trans("ThisWillAlsoDeleteBankRecord");
		}
		echo $form->formconfirm($_SERVER["PHP_SELF"]."?rowid=".$object->id, $langs->trans("DeleteSubscription"), $text, "confirm_delete", $formquestion, 0, 1);
	}

	echo '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
	echo '<input type="hidden" name="token" value="'.newToken().'">';

	$linkback = '<a href="'.DOL_URL_ROOT.'/adherents/subscription/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($object, 'rowid', $linkback, 1);

	echo '<div class="fichecenter">';

	echo '<div class="underbanner clearboth"></div>';

	echo '<table class="border centpercent tableforfield">';

	// Member
	$adh->ref = $adh->getFullName($langs);
	echo '<tr>';
	echo '<td class="titlefield">'.$langs->trans("Member").'</td><td class="valeur">'.$adh->getNomUrl(-1, 0, 'subscription').'</td>';
	echo '</tr>';

	// Type
	echo '<tr>';
	echo '<td class="titlefield">'.$langs->trans("Type").'</td>';
	echo '<td class="valeur">';
	if ($object->fk_type > 0 || $adh->typeid > 0) {
		$typeid = ($object->fk_type > 0 ? $object->fk_type : $adh->typeid);
		$adht->fetch($typeid);
		echo $adht->getNomUrl(1);
	} else {
		echo $langs->trans("NoType");
	}
	echo '</td></tr>';

	// Date subscription
	echo '<tr>';
	echo '<td>'.$langs->trans("DateSubscription").'</td><td class="valeur">'.dol_print_date($object->dateh, 'day').'</td>';
	echo '</tr>';

	// Date end subscription
	echo '<tr>';
	echo '<td>'.$langs->trans("DateEndSubscription").'</td><td class="valeur">'.dol_print_date($object->datef, 'day').'</td>';
	echo '</tr>';

	// Amount
	echo '<tr><td>'.$langs->trans("Amount").'</td><td class="valeur"><span class="amount">'.price($object->amount).'</span></td></tr>';

	// Label
	echo '<tr><td>'.$langs->trans("Label").'</td><td class="valeur sensiblehtmlcontent">'.dol_string_onlythesehtmltags(dol_htmlentitiesbr($object->note_public)).'</td></tr>';

	// Bank line
	if (isModEnabled("bank") && (getDolGlobalString('ADHERENT_BANK_USE') || $object->fk_bank)) {
		echo '<tr><td>'.$langs->trans("BankTransactionLine").'</td><td class="valeur">';
		if ($object->fk_bank > 0) {
			$bankline = new AccountLine($db);
			$result = $bankline->fetch($object->fk_bank);
			echo $bankline->getNomUrl(1, 0, 'showall');
		} else {
			echo $langs->trans("NoneF");
		}
		echo '</td></tr>';
	}

	echo "</table>\n";
	echo '</div>';

	echo '</form>';

	echo dol_get_fiche_end();

	/*
	 * Action bar
	 */
	echo '<div class="tabsAction">';

	if ($user->hasRight('adherent', 'cotisation', 'creer')) {
		if (empty($bankline->rappro) || empty($bankline)) {
			echo '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"]."?rowid=".((int) $object->id).'&action=edit&token='.newToken().'">'.$langs->trans("Modify")."</a></div>";
		} else {
			echo '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" title="'.$langs->trans("BankLineConciliated").'" href="#">'.$langs->trans("Modify")."</a></div>";
		}
	}

	// Delete
	if ($user->hasRight('adherent', 'cotisation', 'creer')) {
		echo '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER["PHP_SELF"]."?rowid=".((int) $object->id).'&action=delete&token='.newToken().'">'.$langs->trans("Delete")."</a></div>\n";
	}

	echo '</div>';


	echo '<div class="fichecenter"><div class="fichehalfleft">';
	echo '<a name="builddoc"></a>'; // ancre

	// Generated documents
	/*
	$filename = dol_sanitizeFileName($object->ref);
	$filedir = $conf->facture->dir_output . '/' . dol_sanitizeFileName($object->ref);
	$urlsource = $_SERVER['PHP_SELF'] . '?facid=' . $object->id;
	$genallowed = $user->hasRight('facture', 'lire');
	$delallowed = $user->hasRight('facture', 'creer');

	echo $formfile->showdocuments('facture', $filename, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $soc->default_lang);
	$somethingshown = $formfile->numoffiles;
	*/
	// Show links to link elements
	//$linktoelem = $form->showLinkToObjectBlock($object, null, array('subscription'));
	$somethingshown = $form->showLinkedObjectBlock($object, '');

	// Show links to link elements
	/*$linktoelem = $form->showLinkToObjectBlock($object,array('order'));
	if ($linktoelem) echo ($somethingshown?'':'<br>').$linktoelem;
	*/

	echo '</div><div class="fichehalfright">';

	// List of actions on element
	/*
	include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
	$formactions = new FormActions($db);
	$somethingshown = $formactions->showactions($object, $object->element, $socid, 1, '', $MAXEVENT);
	*/

	echo '</div></div>';
}

// End of page
llxFooter();
$db->close();
