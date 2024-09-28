<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2003		Jean-Louis Bergamo		<jlb@j1b.org>
 * Copyright (C) 2004-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2017	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2013		Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2015		Alexandre Spangaro		<aspangaro@open-dsi.fr>
 * Copyright (C) 2019-2022	Thibault Foucart		<support@ptibogxiv.net>
 * Copyright (C) 2020		Josep Lluís Amador		<joseplluis@lliuretic.cat>
 * Copyright (C) 2021		Waël Almoman			<info@almoman.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *      \file       htdocs/adherents/type.php
 *      \ingroup    member
 *      \brief      Member's type setup
 */

// Load Gestimag environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';

// Load translation files required by the page
$langs->load("members");

$rowid  = GETPOSTINT('rowid');
$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$toselect 	= GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : str_replace('_', '', basename(dirname(__FILE__)).basename(__FILE__, '.php')); // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$mode = GETPOST('mode', 'alpha');

$sall = GETPOST("sall", "alpha");
$filter = GETPOST("filter", 'alpha');
$search_ref = GETPOST('search_ref', 'alpha');
$search_lastname = GETPOST('search_lastname', 'alpha');
$search_login = GETPOST('search_login', 'alpha');
$search_email = GETPOST('search_email', 'alpha');
$type = GETPOST('type', 'intcomma');
$status = GETPOST('status', 'alpha');
$optioncss = GETPOST('optioncss', 'alpha');

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) {
	$sortorder = "DESC";
}
if (!$sortfield) {
	$sortfield = "d.lastname";
}

$label = GETPOST("label", "alpha");
$morphy = GETPOST("morphy", "alpha");
$status = GETPOST("status", "intcomma");
$subscription = GETPOSTINT("subscription");
$amount = GETPOST('amount', 'alpha');
$duration_value = GETPOSTINT('duration_value');
$duration_unit = GETPOST('duration_unit', 'alpha');
$vote = GETPOSTINT("vote");
$comment = GETPOST("comment", 'restricthtml');
$mail_valid = GETPOST("mail_valid", 'restricthtml');
$caneditamount = GETPOSTINT("caneditamount");

// Initialize technical objects
$object = new AdherentType($db);
$extrafields = new ExtraFields($db);
$hookmanager->initHooks(array('membertypecard', 'globalcard'));

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Security check
$result = restrictedArea($user, 'adherent', $rowid, 'adherent_type');


/*
 *	Actions
 */

if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
	$search_ref = "";
	$search_lastname = "";
	$search_login = "";
	$search_email = "";
	$type = "";
	$sall = "";
}

if (GETPOST('cancel', 'alpha')) {
	$action = 'list';
	$massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
	$massaction = '';
}

if ($cancel) {
	$action = '';

	if (!empty($backtopage)) {
		header("Location: ".$backtopage);
		exit;
	}
}

if ($action == 'add' && $user->hasRight('adherent', 'configurer')) {
	$object->label = trim($label);
	$object->morphy = trim($morphy);
	$object->status = (int) $status;
	$object->subscription = (int) $subscription;
	$object->amount = ($amount == '' ? '' : price2num($amount, 'MT'));
	$object->caneditamount = $caneditamount;
	$object->duration_value = $duration_value;
	$object->duration_unit = $duration_unit;
	$object->note_public = trim($comment);
	$object->note_private = '';
	$object->mail_valid = trim($mail_valid);
	$object->vote = $vote;  // $vote is already int

	// Fill array 'array_options' with data from add form
	$ret = $extrafields->setOptionalsFromPost(null, $object);
	if ($ret < 0) {
		$error++;
	}

	if (empty($object->label)) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Label")), null, 'errors');
	} else {
		$sql = "SELECT libelle FROM ".MAIN_DB_PREFIX."adherent_type WHERE libelle = '".$db->escape($object->label)."'";
		$sql .= " WHERE entity IN (".getEntity('member_type').")";
		$result = $db->query($sql);
		$num = null;
		if ($result) {
			$num = $db->num_rows($result);
		}
		if ($num) {
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorLabelAlreadyExists", $login), null, 'errors');
		}
	}

	if (!$error) {
		$id = $object->create($user);
		if ($id > 0) {
			$backurlforlist = $_SERVER["PHP_SELF"];

			$urltogo = $backtopage ? str_replace('__ID__', (string) $id, $backtopage) : $backurlforlist;
			$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', (string) $object->id, $urltogo); // New method to autoselect field created after a New on another form object creation

			header("Location: " . $urltogo);
			exit;
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = 'create';
		}
	} else {
		$action = 'create';
	}
}

if ($action == 'update' && $user->hasRight('adherent', 'configurer')) {
	$object->fetch($rowid);

	$object->oldcopy = dol_clone($object, 2);

	$object->label = trim($label);
	$object->morphy	= trim($morphy);
	$object->status	= (int) $status;
	$object->subscription = (int) $subscription;
	$object->amount = ($amount == '' ? '' : price2num($amount, 'MT'));
	$object->caneditamount = $caneditamount;
	$object->duration_value = $duration_value;
	$object->duration_unit = $duration_unit;
	$object->note_public = trim($comment);
	$object->note_private = '';
	$object->mail_valid = trim($mail_valid);
	$object->vote = $vote;  // $vote is already int.

	// Fill array 'array_options' with data from add form
	$ret = $extrafields->setOptionalsFromPost(null, $object, '@GETPOSTISSET');
	if ($ret < 0) {
		$error++;
	}

	$ret = $object->update($user);

	if ($ret >= 0 && !count($object->errors)) {
		setEventMessages($langs->trans("MemberTypeModified"), null, 'mesgs');
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
	}

	header("Location: ".$_SERVER["PHP_SELF"]."?rowid=".$object->id);
	exit;
}

if ($action == 'confirm_delete' && $user->hasRight('adherent', 'configurer')) {
	$object->fetch($rowid);
	$res = $object->delete($user);

	if ($res > 0) {
		setEventMessages($langs->trans("MemberTypeDeleted"), null, 'mesgs');
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	} else {
		setEventMessages($langs->trans("MemberTypeCanNotBeDeleted"), null, 'errors');
		$action = '';
	}
}


/*
 * View
 */

$form = new Form($db);
$formproduct = new FormProduct($db);

$help_url = 'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros|DE:Modul_Mitglieder';

llxHeader('', $langs->trans("MembersTypeSetup"), $help_url);

$arrayofselected = is_array($toselect) ? $toselect : array();

// List of members type
if (!$rowid && $action != 'create' && $action != 'edit') {
	//echo dol_get_fiche_head('');

	$sql = "SELECT d.rowid, d.libelle as label, d.subscription, d.amount, d.caneditamount, d.vote, d.statut as status, d.morphy, d.duration";
	$sql .= " FROM ".MAIN_DB_PREFIX."adherent_type as d";
	$sql .= " WHERE d.entity IN (".getEntity('member_type').")";

	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$nbtotalofrecords = $num;

		$i = 0;

		$param = '';
		if (!empty($mode)) {
			$param .= '&mode='.urlencode($mode);
		}
		if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
			$param .= '&contextpage='.$contextpage;
		}
		if ($limit > 0 && $limit != $conf->liste_limit) {
			$param .= '&limit='.$limit;
		}

		$newcardbutton = '';

		$newcardbutton .= dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', $_SERVER["PHP_SELF"].'?mode=common'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ((empty($mode) || $mode == 'common') ? 2 : 1), array('morecss' => 'reposition'));
		$newcardbutton .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fa fa-th-list imgforviewmode', $_SERVER["PHP_SELF"].'?mode=kanban'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ($mode == 'kanban' ? 2 : 1), array('morecss' => 'reposition'));

		if ($user->hasRight('adherent', 'configurer')) {
			$newcardbutton .= dolGetButtonTitleSeparator();
			$newcardbutton .= dolGetButtonTitle($langs->trans('NewMemberType'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/adherents/type.php?action=create');
		}

		echo '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
		if ($optioncss != '') {
			echo '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
		}
		echo '<input type="hidden" name="token" value="'.newToken().'">';
		echo '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
		echo '<input type="hidden" name="action" value="list">';
		echo '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
		echo '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
		echo '<input type="hidden" name="mode" value="'.$mode.'">';


		print_barre_liste($langs->trans("MembersTypes"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, $newcardbutton, '', $limit, 0, 0, 1);

		$moreforfilter = '';

		echo '<div class="div-table-responsive">';
		echo '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

		echo '<tr class="liste_titre">';
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			echo '<th>&nbsp;</th>';
		}
		echo '<th>'.$langs->trans("Ref").'</th>';
		echo '<th>'.$langs->trans("Label").'</th>';
		echo '<th class="center">'.$langs->trans("MembersNature").'</th>';
		echo '<th class="center">'.$langs->trans("MembershipDuration").'</th>';
		echo '<th class="center">'.$langs->trans("SubscriptionRequired").'</th>';
		echo '<th class="center">'.$langs->trans("Amount").'</th>';
		echo '<th class="center">'.$langs->trans("CanEditAmountShort").'</th>';
		echo '<th class="center">'.$langs->trans("VoteAllowed").'</th>';
		echo '<th class="center">'.$langs->trans("Status").'</th>';
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			echo '<th>&nbsp;</th>';
		}
		echo "</tr>\n";

		$membertype = new AdherentType($db);

		$i = 0;
		$savnbfield = 10;
		/*$savnbfield = $totalarray['nbfield'];
		$totalarray = array();
		$totalarray['nbfield'] = 0;*/

		$imaxinloop = ($limit ? min($num, $limit) : $num);
		while ($i < $imaxinloop) {
			$objp = $db->fetch_object($result);

			$membertype->id = $objp->rowid;
			$membertype->ref = $objp->rowid;
			$membertype->label = $objp->rowid;
			$membertype->status = $objp->status;
			$membertype->subscription = $objp->subscription;
			$membertype->amount = $objp->amount;
			$membertype->caneditamount = $objp->caneditamount;

			if ($mode == 'kanban') {
				if ($i == 0) {
					echo '<tr class="trkanban"><td colspan="'.$savnbfield.'">';
					echo '<div class="box-flex-container kanban">';
				}
				//output kanban
				$membertype->label = $objp->label;
				echo $membertype->getKanbanView('', array('selected' => in_array($object->id, $arrayofselected)));
				if ($i == ($imaxinloop - 1)) {
					echo '</div>';
					echo '</td></tr>';
				}
			} else {
				echo '<tr class="oddeven">';

				if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					if ($user->hasRight('adherent', 'configurer')) {
						echo '<td class="center"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=edit&rowid='.$objp->rowid.'">'.img_edit().'</a></td>';
					}
				}

				echo '<td class="nowraponall">';
				echo $membertype->getNomUrl(1);
				//<a href="'.$_SERVER["PHP_SELF"].'?rowid='.$objp->rowid.'">'.img_object($langs->trans("ShowType"),'group').' '.$objp->rowid.'</a>
				echo '</td>';

				echo '<td>'.dol_escape_htmltag($objp->label).'</td>';

				echo '<td class="center">';
				if ($objp->morphy == 'phy') {
					echo $langs->trans("Physical");
				} elseif ($objp->morphy == 'mor') {
					echo $langs->trans("Moral");
				} else {
					echo $langs->trans("MorAndPhy");
				}
				echo '</td>';

				echo '<td class="center nowrap">';
				if ($objp->duration) {
					$duration_value = intval($objp->duration);
					if ($duration_value > 1) {
						$dur = array("i" => $langs->trans("Minutes"), "h" => $langs->trans("Hours"), "d" => $langs->trans("Days"), "w" => $langs->trans("Weeks"), "m" => $langs->trans("Months"), "y" => $langs->trans("Years"));
					} else {
						$dur = array("i" => $langs->trans("Minute"), "h" => $langs->trans("Hour"), "d" => $langs->trans("Day"), "w" => $langs->trans("Week"), "m" => $langs->trans("Month"), "y" => $langs->trans("Year"));
					}
					$unit = preg_replace("/[^a-zA-Z]+/", "", $objp->duration);
					echo max(1, $duration_value).' '.$dur[$unit];
				}
				echo '</td>';

				echo '<td class="center">'.yn($objp->subscription).'</td>';

				echo '<td class="center"><span class="amount">'.(is_null($objp->amount) || $objp->amount === '' ? '' : price($objp->amount)).'</span></td>';

				echo '<td class="center">'.yn($objp->caneditamount).'</td>';

				echo '<td class="center">'.yn($objp->vote).'</td>';

				echo '<td class="center">'.$membertype->getLibStatut(5).'</td>';

				if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					if ($user->hasRight('adherent', 'configurer')) {
						echo '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=edit&rowid='.$objp->rowid.'">'.img_edit().'</a></td>';
					}
				}
				echo "</tr>";
			}
			$i++;
		}

		// If no record found
		if ($num == 0) {
			/*$colspan = 1;
			foreach ($arrayfields as $key => $val) {
				if (!empty($val['checked'])) {
					$colspan++;
				}
			}*/
			$colspan = 9;
			echo '<tr><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
		}

		echo "</table>";
		echo '</div>';

		echo '</form>';
	} else {
		dol_print_error($db);
	}
}

// Creation
if ($action == 'create') {
	$object = new AdherentType($db);

	echo load_fiche_titre($langs->trans("NewMemberType"), '', 'members');

	echo '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
	echo '<input type="hidden" name="token" value="'.newToken().'">';
	echo '<input type="hidden" name="action" value="add">';
	echo '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

	echo dol_get_fiche_head('');

	echo '<table class="border centpercent">';
	echo '<tbody>';

	echo '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("Label").'</td><td><input type="text" class="minwidth200" name="label" autofocus="autofocus"></td></tr>';

	echo '<tr><td>'.$langs->trans("Status").'</td><td>';
	echo $form->selectarray('status', array('0' => $langs->trans('ActivityCeased'), '1' => $langs->trans('InActivity')), 1, 0, 0, 0, '', 0, 0, 0, '', 'minwidth100');
	echo '</td></tr>';

	// Morphy
	$morphys = array();
	$morphys[""] = $langs->trans("MorAndPhy");
	$morphys["phy"] = $langs->trans("Physical");
	$morphys["mor"] = $langs->trans("Moral");
	echo '<tr><td><span>'.$langs->trans("MembersNature").'</span></td><td>';
	echo $form->selectarray("morphy", $morphys, GETPOSTISSET("morphy") ? GETPOST("morphy", 'aZ09') : 'morphy');
	echo "</td></tr>";

	echo '<tr><td>'.$form->textwithpicto($langs->trans("SubscriptionRequired"), $langs->trans("SubscriptionRequiredDesc")).'</td><td>';
	echo $form->selectyesno("subscription", 1, 1);
	echo '</td></tr>';

	echo '<tr><td>'.$langs->trans("Amount").'</td><td>';
	echo '<input name="amount" size="5" value="'.(GETPOSTISSET('amount') ? GETPOST('amount') : price($amount)).'">';
	echo '</td></tr>';

	echo '<tr><td>'.$form->textwithpicto($langs->trans("CanEditAmountShort"), $langs->transnoentities("CanEditAmount")).'</td><td>';
	echo $form->selectyesno("caneditamount", GETPOSTISSET('caneditamount') ? GETPOST('caneditamount') : 0, 1);
	echo '</td></tr>';

	echo '<tr><td>'.$langs->trans("VoteAllowed").'</td><td>';
	echo $form->selectyesno("vote", GETPOSTISSET("vote") ? GETPOST('vote', 'aZ09') : 1, 1);
	echo '</td></tr>';

	echo '<tr><td>'.$langs->trans("Duration").'</td><td colspan="3">';
	echo '<input name="duration_value" size="5" value="'.GETPOST('duraction_unit', 'aZ09').'"> ';
	echo $formproduct->selectMeasuringUnits("duration_unit", "time", GETPOSTISSET("duration_unit") ? GETPOST('duration_unit', 'aZ09') : 'y', 0, 1);
	echo '</td></tr>';

	echo '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td>';
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor = new DolEditor('comment', (GETPOSTISSET('comment') ? GETPOST('comment', 'restricthtml') : $object->note_public), '', 200, 'gestimag_notes', '', false, true, isModEnabled('fckeditor'), 15, '90%');
	$doleditor->Create();

	echo '<tr><td class="tdtop">'.$langs->trans("WelcomeEMail").'</td><td>';
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor = new DolEditor('mail_valid', GETPOSTISSET('mail_valid') ? GETPOST('mail_valid') : $object->mail_valid, '', 250, 'gestimag_notes', '', false, true, isModEnabled('fckeditor'), 15, '90%');
	$doleditor->Create();
	echo '</td></tr>';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	echo '<tbody>';
	echo "</table>\n";

	echo dol_get_fiche_end();

	echo $form->buttonsSaveCancel();

	echo "</form>\n";
}

// View
if ($rowid > 0) {
	if ($action != 'edit') {
		$object = new AdherentType($db);
		$object->fetch($rowid);
		$object->fetch_optionals();

		/*
		 * Confirmation deletion
		 */
		if ($action == 'delete') {
			echo $form->formconfirm($_SERVER['PHP_SELF']."?rowid=".$object->id, $langs->trans("DeleteAMemberType"), $langs->trans("ConfirmDeleteMemberType", $object->label), "confirm_delete", '', 0, 1);
		}

		$head = member_type_prepare_head($object);

		echo dol_get_fiche_head($head, 'card', $langs->trans("MemberType"), -1, 'group');

		$linkback = '<a href="'.DOL_URL_ROOT.'/adherents/type.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

		dol_banner_tab($object, 'rowid', $linkback);

		echo '<div class="fichecenter">';
		echo '<div class="underbanner clearboth"></div>';

		echo '<table class="tableforfield border centpercent">';

		// Morphy
		echo '<tr><td>'.$langs->trans("MembersNature").'</td><td class="valeur" >'.$object->getmorphylib($object->morphy).'</td>';
		echo '</tr>';

		echo '<tr><td>'.$form->textwithpicto($langs->trans("SubscriptionRequired"), $langs->trans("SubscriptionRequiredDesc")).'</td><td>';
		echo yn($object->subscription);
		echo '</tr>';

		// Amount
		echo '<tr><td class="titlefield">'.$langs->trans("Amount").'</td><td>';
		print((is_null($object->amount) || $object->amount === '') ? '' : '<span class="amount">'.price($object->amount).'</span>');
		echo '</tr>';

		echo '<tr><td>'.$form->textwithpicto($langs->trans("CanEditAmountShort"), $langs->transnoentities("CanEditAmount")).'</td><td>';
		echo yn($object->caneditamount);
		echo '</td></tr>';

		echo '<tr><td>'.$langs->trans("VoteAllowed").'</td><td>';
		echo yn($object->vote);
		echo '</tr>';

		// Duration
		echo '<tr><td class="titlefield">'.$langs->trans("Duration").'</td><td colspan="2">'.$object->duration_value.'&nbsp;';
		if ($object->duration_value > 1) {
			$dur = array("i" => $langs->trans("Minutes"), "h" => $langs->trans("Hours"), "d" => $langs->trans("Days"), "w" => $langs->trans("Weeks"), "m" => $langs->trans("Months"), "y" => $langs->trans("Years"));
		} elseif ($object->duration_value > 0) {
			$dur = array("i" => $langs->trans("Minute"), "h" => $langs->trans("Hour"), "d" => $langs->trans("Day"), "w" => $langs->trans("Week"), "m" => $langs->trans("Month"), "y" => $langs->trans("Year"));
		}
		print(!empty($object->duration_unit) && isset($dur[$object->duration_unit]) ? $langs->trans($dur[$object->duration_unit]) : '')."&nbsp;";
		echo '</td></tr>';

		// Description
		echo '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td><div class="longmessagecut">';
		echo dol_string_onlythesehtmltags(dol_htmlentitiesbr($object->note_public));
		echo "</div></td></tr>";

		// Welcome email content
		echo '<tr><td class="tdtop">'.$langs->trans("WelcomeEMail").'</td><td><div class="longmessagecut">';
		echo dol_string_onlythesehtmltags(dol_htmlentitiesbr($object->mail_valid));
		echo "</div></td></tr>";

		// Other attributes
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

		echo '</table>';
		echo '</div>';

		echo dol_get_fiche_end();


		/*
		 * Buttons
		 */

		echo '<div class="tabsAction">';

		// Edit
		if ($user->hasRight('adherent', 'configurer')) {
			echo '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=edit&token='.newToken().'&rowid='.$object->id.'">'.$langs->trans("Modify").'</a></div>';
		}

		// Add
		if ($object->morphy == 'phy') {
			$morphy = 'phy';
		} elseif ($object->morphy == 'mor') {
			$morphy = 'mor';
		} else {
			$morphy = '';
		}

		if ($user->hasRight('adherent', 'configurer') && !empty($object->status)) {
			echo '<div class="inline-block divButAction"><a class="butAction" href="card.php?action=create&token='.newToken().'&typeid='.$object->id.($morphy ? '&morphy='.urlencode($morphy) : '').'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?rowid='.$object->id).'">'.$langs->trans("AddMember").'</a></div>';
		} else {
			echo '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NoAddMember")).'">'.$langs->trans("AddMember").'</a></div>';
		}

		// Delete
		if ($user->hasRight('adherent', 'configurer')) {
			echo '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=delete&token='.newToken().'&rowid='.$object->id.'">'.$langs->trans("DeleteType").'</a></div>';
		}

		echo "</div>";


		// Show list of members (nearly same code than in page list.php)

		$membertypestatic = new AdherentType($db);

		$now = dol_now();

		$sql = "SELECT d.rowid, d.ref, d.entity, d.login, d.firstname, d.lastname, d.societe as company, d.fk_soc,";
		$sql .= " d.datefin,";
		$sql .= " d.email, d.photo, d.fk_adherent_type as type_id, d.morphy, d.statut as status,";
		$sql .= " t.libelle as type, t.subscription, t.amount";

		$sqlfields = $sql; // $sql fields to remove for count total

		$sql .= " FROM ".MAIN_DB_PREFIX."adherent as d, ".MAIN_DB_PREFIX."adherent_type as t";
		$sql .= " WHERE d.fk_adherent_type = t.rowid ";
		$sql .= " AND d.entity IN (".getEntity('adherent').")";
		$sql .= " AND t.rowid = ".((int) $object->id);
		if ($sall) {
			$sql .= natural_search(array("d.firstname", "d.lastname", "d.societe", "d.email", "d.login", "d.address", "d.town", "d.note_public", "d.note_private"), $sall);
		}
		if ($status != '') {
			$sql .= natural_search('d.statut', $status, 2);
		}
		if ($action == 'search') {
			if (GETPOST('search', 'alpha')) {
				$sql .= natural_search(array("d.firstname", "d.lastname"), GETPOST('search', 'alpha'));
			}
		}
		if (!empty($search_ref)) {
			$sql .= natural_search("d.ref", $search_ref);
		}
		if (!empty($search_lastname)) {
			$sql .= natural_search(array("d.firstname", "d.lastname"), $search_lastname);
		}
		if (!empty($search_login)) {
			$sql .= natural_search("d.login", $search_login);
		}
		if (!empty($search_email)) {
			$sql .= natural_search("d.email", $search_email);
		}
		if ($filter == 'uptodate') {
			$sql .= " AND (datefin >= '".$db->idate($now)."') OR t.subscription = 0)";
		}
		if ($filter == 'outofdate') {
			$sql .= " AND (datefin < '".$db->idate($now)."' AND t.subscription = 1)";
		}

		// Count total nb of records
		$nbtotalofrecords = '';
		if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
			/* The fast and low memory method to get and count full list converts the sql into a sql count */
			$sqlforcount = preg_replace('/^'.preg_quote($sqlfields, '/').'/', 'SELECT COUNT(*) as nbtotalofrecords', $sql);
			$sqlforcount = preg_replace('/GROUP BY .*$/', '', $sqlforcount);
			$resql = $db->query($sqlforcount);
			if ($resql) {
				$objforcount = $db->fetch_object($resql);
				$nbtotalofrecords = $objforcount->nbtotalofrecords;
			} else {
				dol_print_error($db);
			}

			if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller than the paging size (filtering), goto and load page 0
				$page = 0;
				$offset = 0;
			}
			$db->free($resql);
		}

		// Complete request and execute it with limit
		$sql .= $db->order($sortfield, $sortorder);
		if ($limit) {
			$sql .= $db->plimit($limit + 1, $offset);
		}

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;

			$titre = $langs->trans("MembersList");
			if ($status != '') {
				if ($status == '-1,1') {
					$titre = $langs->trans("MembersListQualified");
				} elseif ($status == '-1') {
					$titre = $langs->trans("MembersListToValid");
				} elseif ($status == '1' && !$filter) {
					$titre = $langs->trans("MembersListValid");
				} elseif ($status == '1' && $filter == 'uptodate') {
					$titre = $langs->trans("MembersListUpToDate");
				} elseif ($status == '1' && $filter == 'outofdate') {
					$titre = $langs->trans("MembersListNotUpToDate");
				} elseif ($status == '0') {
					$titre = $langs->trans("MembersListResiliated");
				} elseif ($status == '-2') {
					$titre = $langs->trans("MembersListExcluded");
				}
			} elseif ($action == 'search') {
				$titre = $langs->trans("MembersListQualified");
			}

			if ($type > 0) {
				$membertype = new AdherentType($db);
				$result = $membertype->fetch($type);
				$titre .= " (".$membertype->label.")";
			}

			$param = "&rowid=".urlencode((string) ($object->id));
			if (!empty($mode)) {
				$param .= '&mode='.urlencode($mode);
			}
			if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
				$param .= '&contextpage='.urlencode($contextpage);
			}
			if ($limit > 0 && $limit != $conf->liste_limit) {
				$param .= '&limit='.((int) $limit);
			}
			if (!empty($status)) {
				$param .= "&status=".urlencode($status);
			}
			if (!empty($search_ref)) {
				$param .= "&search_ref=".urlencode($search_ref);
			}
			if (!empty($search_lastname)) {
				$param .= "&search_lastname=".urlencode($search_lastname);
			}
			if (!empty($search_login)) {
				$param .= "&search_login=".urlencode($search_login);
			}
			if (!empty($search_email)) {
				$param .= "&search_email=".urlencode($search_email);
			}
			if (!empty($filter)) {
				$param .= "&filter=".urlencode($filter);
			}

			if ($sall) {
				echo $langs->trans("Filter")." (".$langs->trans("Lastname").", ".$langs->trans("Firstname").", ".$langs->trans("EMail").", ".$langs->trans("Address")." ".$langs->trans("or")." ".$langs->trans("Town")."): ".$sall;
			}

			echo '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'" name="formfilter" autocomplete="off">';
			echo '<input type="hidden" name="token" value="'.newToken().'">';
			echo '<input class="flat" type="hidden" name="rowid" value="'.$object->id.'"></td>';
			echo '<input class="flat" type="hidden" name="page_y" value=""></td>';

			print_barre_liste('', $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'generic', 0, '', '', $limit);

			$moreforfilter = '';

			echo '<div class="div-table-responsive">';
			echo '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

			// Fields title search
			echo '<tr class="liste_titre_filter">';

			if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				echo '<td class="liste_titre center maxwidthsearch">';
				$searchpicto = $form->showFilterButtons('left');
				echo $searchpicto;
				echo '</td>';
			}

			echo '<td class="liste_titre left">';
			echo '<input class="flat maxwidth100" type="text" name="search_ref" value="'.dol_escape_htmltag($search_ref).'"></td>';

			echo '<td class="liste_titre left">';
			echo '<input class="flat maxwidth100" type="text" name="search_lastname" value="'.dol_escape_htmltag($search_lastname).'"></td>';

			echo '<td class="liste_titre left">';
			echo '<input class="flat maxwidth100" type="text" name="search_login" value="'.dol_escape_htmltag($search_login).'"></td>';

			echo '<td class="liste_titre">&nbsp;</td>';

			echo '<td class="liste_titre left">';
			echo '<input class="flat maxwidth100" type="text" name="search_email" value="'.dol_escape_htmltag($search_email).'"></td>';

			echo '<td class="liste_titre">&nbsp;</td>';

			echo '<td class="liste_titre">&nbsp;</td>';

			if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				echo '<td class="liste_titre center nowraponall">';
				echo '<input type="image" class="liste_titre" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" name="button_search" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
				echo '&nbsp; ';
				echo '<input type="image" class="liste_titre" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png" name="button_removefilter" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
				echo '</td>';
			}

			echo "</tr>\n";

			echo '<tr class="liste_titre">';
			if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print_liste_field_titre("Action", $_SERVER["PHP_SELF"], "", $param, "", 'width="60" align="center"', $sortfield, $sortorder);
			}
			print_liste_field_titre("Ref", $_SERVER["PHP_SELF"], "d.ref", $param, "", "", $sortfield, $sortorder);
			print_liste_field_titre("NameSlashCompany", $_SERVER["PHP_SELF"], "d.lastname", $param, "", "", $sortfield, $sortorder);
			print_liste_field_titre("Login", $_SERVER["PHP_SELF"], "d.login", $param, "", "", $sortfield, $sortorder);
			print_liste_field_titre("MemberNature", $_SERVER["PHP_SELF"], "d.morphy", $param, "", "", $sortfield, $sortorder);
			print_liste_field_titre("EMail", $_SERVER["PHP_SELF"], "d.email", $param, "", "", $sortfield, $sortorder);
			print_liste_field_titre("Status", $_SERVER["PHP_SELF"], "d.statut,d.datefin", $param, "", "", $sortfield, $sortorder);
			print_liste_field_titre("EndSubscription", $_SERVER["PHP_SELF"], "d.datefin", $param, "", 'align="center"', $sortfield, $sortorder);
			if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print_liste_field_titre("Action", $_SERVER["PHP_SELF"], "", $param, "", 'width="60" align="center"', $sortfield, $sortorder);
			}
			echo "</tr>\n";

			$adh = new Adherent($db);

			$imaxinloop = ($limit ? min($num, $limit) : $num);
			while ($i < $imaxinloop) {
				$objp = $db->fetch_object($resql);

				$datefin = $db->jdate($objp->datefin);

				$adh->id = $objp->rowid;
				$adh->ref = $objp->ref;
				$adh->login = $objp->login;
				$adh->lastname = $objp->lastname;
				$adh->firstname = $objp->firstname;
				$adh->datefin = $datefin;
				$adh->need_subscription = $objp->subscription;
				$adh->statut = $objp->status;
				$adh->status = $objp->status;
				$adh->email = $objp->email;
				$adh->photo = $objp->photo;

				echo '<tr class="oddeven">';

				// Actions
				if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					echo '<td class="center">';
					if ($user->hasRight('adherent', 'creer')) {
						echo '<a class="editfielda marginleftonly" href="card.php?rowid='.$objp->rowid.'&action=edit&token='.newToken().'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?rowid='.$object->id).'">'.img_edit().'</a>';
					}
					if ($user->hasRight('adherent', 'supprimer')) {
						echo '<a class="marginleftonly" href="card.php?rowid='.$objp->rowid.'&action=resiliate&token='.newToken().'">'.img_picto($langs->trans("Resiliate"), 'disable.png').'</a>';
					}
					echo "</td>";
				}

				// Ref
				echo "<td>";
				echo $adh->getNomUrl(-1, 0, 'card', 'ref', '', -1, 0, 1);
				echo "</td>\n";

				// Lastname
				if ($objp->company != '') {
					echo '<td><a href="card.php?rowid='.$objp->rowid.'">'.img_object($langs->trans("ShowMember"), "user", 'class="paddingright"').$adh->getFullName($langs, 0, -1, 20).' / '.dol_trunc($objp->company, 12).'</a></td>'."\n";
				} else {
					echo '<td><a href="card.php?rowid='.$objp->rowid.'">'.img_object($langs->trans("ShowMember"), "user", 'class="paddingright"').$adh->getFullName($langs, 0, -1, 32).'</a></td>'."\n";
				}

				// Login
				echo "<td>".dol_escape_htmltag($objp->login)."</td>\n";

				// Type
				/*echo '<td class="nowrap">';
				$membertypestatic->id=$objp->type_id;
				$membertypestatic->label=$objp->type;
				echo $membertypestatic->getNomUrl(1,12);
				echo '</td>';
				*/

				// Moral/Physique
				echo "<td>".$adh->getmorphylib($objp->morphy, 1)."</td>\n";

				// EMail
				echo "<td>".dol_print_email($objp->email, 0, 0, 1)."</td>\n";

				// Status
				echo '<td class="nowrap">';
				echo $adh->getLibStatut(2);
				echo "</td>";

				// Date end subscription
				if ($datefin) {
					echo '<td class="nowrap center">';
					if ($datefin < dol_now() && $objp->status > 0) {
						echo dol_print_date($datefin, 'day')." ".img_warning($langs->trans("SubscriptionLate"));
					} else {
						echo dol_print_date($datefin, 'day');
					}
					echo '</td>';
				} else {
					echo '<td class="nowrap center">';
					if (!empty($objp->subscription)) {
						echo '<span class="opacitymedium">'.$langs->trans("SubscriptionNotReceived").'</span>';
						if ($objp->status > 0) {
							echo " ".img_warning();
						}
					} else {
						echo '&nbsp;';
					}
					echo '</td>';
				}

				// Actions
				if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					echo '<td class="center">';
					if ($user->hasRight('adherent', 'creer')) {
						echo '<a class="editfielda marginleftonly" href="card.php?rowid='.$objp->rowid.'&action=edit&token='.newToken().'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?rowid='.$object->id).'">'.img_edit().'</a>';
					}
					if ($user->hasRight('adherent', 'supprimer')) {
						echo '<a class="marginleftonly" href="card.php?rowid='.$objp->rowid.'&action=resiliate&token='.newToken().'">'.img_picto($langs->trans("Resiliate"), 'disable.png').'</a>';
					}
					echo "</td>";
				}
				echo "</tr>\n";
				$i++;
			}

			if ($i == 0) {
				echo '<tr><td colspan="9"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
			}

			echo "</table>\n";
			echo '</div>';
			echo '</form>';
		} else {
			dol_print_error($db);
		}
	}

	/* ************************************************************************** */
	/*                                                                            */
	/* Edition mode                                                               */
	/*                                                                            */
	/* ************************************************************************** */

	if ($action == 'edit') {
		$object = new AdherentType($db);
		$object->fetch($rowid);
		$object->fetch_optionals();

		$head = member_type_prepare_head($object);

		echo '<form method="post" action="'.$_SERVER["PHP_SELF"].'?rowid='.$object->id.'">';
		echo '<input type="hidden" name="token" value="'.newToken().'">';
		echo '<input type="hidden" name="rowid" value="'.$object->id.'">';
		echo '<input type="hidden" name="action" value="update">';

		echo dol_get_fiche_head($head, 'card', $langs->trans("MemberType"), 0, 'group');

		echo '<table class="border centpercent">';

		echo '<tr><td class="titlefield">'.$langs->trans("Ref").'</td><td>'.$object->id.'</td></tr>';

		echo '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td><input type="text" class="minwidth300" name="label" value="'.dol_escape_htmltag($object->label).'"></td></tr>';

		echo '<tr><td>'.$langs->trans("Status").'</td><td>';
		echo $form->selectarray('status', array('0' => $langs->trans('ActivityCeased'), '1' => $langs->trans('InActivity')), $object->status, 0, 0, 0, '', 0, 0, 0, '', 'minwidth100');
		echo '</td></tr>';

		// Morphy
		$morphys[""] = $langs->trans("MorAndPhy");
		$morphys["phy"] = $langs->trans("Physical");
		$morphys["mor"] = $langs->trans("Moral");
		echo '<tr><td><span>'.$langs->trans("MembersNature").'</span></td><td>';
		echo $form->selectarray("morphy", $morphys, GETPOSTISSET("morphy") ? GETPOST("morphy", 'aZ09') : $object->morphy);
		echo "</td></tr>";

		echo '<tr><td>'.$langs->trans("SubscriptionRequired").'</td><td>';
		echo $form->selectyesno("subscription", $object->subscription, 1);
		echo '</td></tr>';

		echo '<tr><td>'.$langs->trans("Amount").'</td><td>';
		echo '<input name="amount" size="5" value="';
		print((is_null($object->amount) || $object->amount === '') ? '' : price($object->amount));
		echo '">';
		echo '</td></tr>';

		echo '<tr><td>'.$form->textwithpicto($langs->trans("CanEditAmountShort"), $langs->transnoentities("CanEditAmountDetail")).'</td><td>';
		echo $form->selectyesno("caneditamount", $object->caneditamount, 1);
		echo '</td></tr>';

		echo '<tr><td>'.$langs->trans("VoteAllowed").'</td><td>';
		echo $form->selectyesno("vote", $object->vote, 1);
		echo '</td></tr>';

		echo '<tr><td>'.$langs->trans("Duration").'</td><td colspan="3">';
		echo '<input name="duration_value" size="5" value="'.$object->duration_value.'"> ';
		echo $formproduct->selectMeasuringUnits("duration_unit", "time", ($object->duration_unit === '' ? 'y' : $object->duration_unit), 0, 1);
		echo '</td></tr>';

		echo '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td>';
		require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$doleditor = new DolEditor('comment', $object->note_public, '', 220, 'gestimag_notes', '', false, true, isModEnabled('fckeditor'), 15, '90%');
		$doleditor->Create();
		echo "</td></tr>";

		echo '<tr><td class="tdtop">'.$langs->trans("WelcomeEMail").'</td><td>';
		$doleditor = new DolEditor('mail_valid', $object->mail_valid, '', 280, 'gestimag_notes', '', false, true, isModEnabled('fckeditor'), 15, '90%');
		$doleditor->Create();
		echo "</td></tr>";

		// Other attributes
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

		echo '</table>';

		echo dol_get_fiche_end();

		echo $form->buttonsSaveCancel();

		echo "</form>";
	}
}

// End of page
llxFooter();
$db->close();
