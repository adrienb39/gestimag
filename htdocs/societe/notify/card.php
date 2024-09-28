<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010-2014 Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
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
 *	    \file       htdocs/societe/notify/card.php
 *      \ingroup    societe notification
 *		\brief      Tab for notifications of third party
 */

// Load Gestimag environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/notify.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/triggers/interface_50_modNotification_Notification.class.php';

$langs->loadLangs(array("companies", "mails", "admin", "other", "errors"));

$socid     = GETPOSTINT("socid");
$action    = GETPOST('action', 'aZ09');
$contactid = GETPOST('contactid', 'alpha'); // May be an int or 'thirdparty'
$actionid  = GETPOSTINT('actionid');
$optioncss = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'societe', '', '');

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (!$sortorder) {
	$sortorder = "DESC";
}
if (!$sortfield) {
	$sortfield = "n.daten";
}
if (empty($page) || $page == -1) {
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$now = dol_now();

$object = new Societe($db);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('thirdpartynotification', 'globalcard'));



/*
 * Actions
 */

$parameters = array('id' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$error = 0;

	if (GETPOST('cancel', 'alpha')) {
		$action = '';
	}

	// Add a notification
	if ($action == 'add') {
		if (empty($contactid)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Contact")), null, 'errors');
			$error++;
		}
		if ($actionid <= 0) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Action")), null, 'errors');
			$error++;
		}

		if (!$error) {
			$db->begin();

			$sql = "DELETE FROM ".MAIN_DB_PREFIX."notify_def";
			$sql .= " WHERE fk_soc=".((int) $socid)." AND fk_contact=".((int) $contactid)." AND fk_action=".((int) $actionid);
			if ($db->query($sql)) {
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."notify_def (datec,fk_soc, fk_contact, fk_action)";
				$sql .= " VALUES ('".$db->idate($now)."',".((int) $socid).",".((int) $contactid).",".((int) $actionid).")";

				if (!$db->query($sql)) {
					$error++;
					dol_print_error($db);
				}
			} else {
				dol_print_error($db);
			}

			if (!$error) {
				$db->commit();
			} else {
				$db->rollback();
			}
		}
	}

	// Remove a notification
	if ($action == 'delete') {
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."notify_def where rowid=".GETPOSTINT('actid');
		$db->query($sql);
	}
}



/*
 *	View
 */

$form = new Form($db);

$object = new Societe($db);
$result = $object->fetch($socid);

$title = $langs->trans("ThirdParty").' - '.$langs->trans("Notification");
if (getDolGlobalString('MAIN_HTML_TITLE') && preg_match('/thirdpartynameonly/', getDolGlobalString('MAIN_HTML_TITLE')) && $object->name) {
	$title = $object->name.' - '.$langs->trans("Notification");
}
$help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';

llxHeader('', $title, $help_url);


if ($result > 0) {
	$langs->load("other");

	$head = societe_prepare_head($object);

	echo dol_get_fiche_head($head, 'notify', $langs->trans("ThirdParty"), -1, 'company');

	$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($object, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom');

	echo '<div class="fichecenter">';

	echo '<div class="underbanner clearboth"></div>';
	echo '<table class="border centpercent tableforfield">';

	// Type Prospect/Customer/Supplier
	echo '<tr><td class="titlefield">'.$langs->trans('NatureOfThirdParty').'</td><td>';
	echo $object->getTypeUrl(1);
	echo '</td></tr>';

	// Prefix
	if (getDolGlobalString('SOCIETE_USEPREFIX')) {  // Old not used prefix field
		echo '<tr><td class="titlefield">'.$langs->trans('Prefix').'</td><td colspan="3">'.$object->prefix_comm.'</td></tr>';
	}

	if ($object->client) {
		echo '<tr><td class="titlefield">';
		echo $langs->trans('CustomerCode').'</td><td colspan="3">';
		echo showValueWithClipboardCPButton(dol_escape_htmltag($object->code_client));
		$tmpcheck = $object->check_codeclient();
		if ($tmpcheck != 0 && $tmpcheck != -5) {
			echo ' <span class="error">('.$langs->trans("WrongCustomerCode").')</span>';
		}
		echo '</td></tr>';
	}

	if ((isModEnabled("supplier_order") || isModEnabled("supplier_invoice")) && $object->fournisseur && $user->hasRight('fournisseur', 'lire')) {
		echo '<tr><td class="titlefield">';
		echo $langs->trans('SupplierCode').'</td><td colspan="3">';
		echo showValueWithClipboardCPButton(dol_escape_htmltag($object->code_fournisseur));
		$tmpcheck = $object->check_codefournisseur();
		if ($tmpcheck != 0 && $tmpcheck != -5) {
			echo ' <span class="error">('.$langs->trans("WrongSupplierCode").')</span>';
		}
		echo '</td></tr>';
	}

	/*echo '<tr><td class="titlefield">'.$langs->trans("NbOfActiveNotifications").'</td>';   // Notification for this thirdparty
	echo '<td colspan="3">';
	$nbofrecipientemails=0;
	$notify=new Notify($db);
	$tmparray = $notify->getNotificationsArray('', $object->id, null, 0, array('thirdparty'));
	foreach($tmparray as $tmpkey => $tmpval)
	{
		if (!empty($tmpkey)) $nbofrecipientemails++;
	}
	echo $nbofrecipientemails;
	echo '</td></tr>';*/

	echo '</table>';

	echo '</div>';

	echo dol_get_fiche_end();

	echo "\n";

	// Help
	echo '<div class="opacitymedium hideonsmartphone">';
	echo $langs->trans("NotificationsDesc");
	echo '<br>'.$langs->trans("NotificationsDescUser");
	echo '<br>'.$langs->trans("NotificationsDescContact").' - '.$langs->trans("YouAreHere");
	echo '<br>'.$langs->trans("NotificationsDescGlobal");
	echo '<br>';
	echo '</div>';

	echo '<br><br>'."\n";

	$nbtotalofrecords = '';

	// List of notifications enabled for contacts of the thirdparty
	$sql = "SELECT n.rowid, n.type,";
	$sql .= " a.code, a.label,";
	$sql .= " c.rowid as contactid, c.lastname, c.firstname, c.email";
	$sql .= " FROM ".MAIN_DB_PREFIX."c_action_trigger as a,";
	$sql .= " ".MAIN_DB_PREFIX."notify_def as n,";
	$sql .= " ".MAIN_DB_PREFIX."socpeople as c";
	$sql .= " WHERE a.rowid = n.fk_action";
	$sql .= " AND c.rowid = n.fk_contact";
	$sql .= " AND c.fk_soc = ".((int) $object->id);

	$resql = $db->query($sql);
	if ($resql) {
		$nbtotalofrecords = $db->num_rows($resql);
	} else {
		dol_print_error($db);
	}

	$param = '';
	$newcardbutton = '';
	$newcardbutton .= dolGetButtonTitle($langs->trans('New'), '', 'fa fa-plus-circle', $_SERVER["PHP_SELF"].'?socid='.$object->id.'&action=create&backtopage='.urlencode($_SERVER['PHP_SELF']), '', $user->hasRight("societe", "creer"));

	$titlelist = $langs->trans("ListOfActiveNotifications");

	// Add notification form
	//echo load_fiche_titre($titlelist.' <span class="opacitymedium colorblack paddingleft">('.$num.')</span>', '', '');
	$num = $nbtotalofrecords;
	print_barre_liste($titlelist, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, (empty($nbtotalofrecords) ? -1 : $nbtotalofrecords), 'email', 0, $newcardbutton, '', $limit, 0, 0, 1);

	echo '<form action="'.$_SERVER["PHP_SELF"].'?socid='.$socid.'" method="post">';
	echo '<input type="hidden" name="token" value="'.newToken().'">';
	echo '<input type="hidden" name="action" value="add">';

	$param = "&socid=".$socid;

	// Line with titles
	echo '<div class="div-table-responsive-no-min">';
	echo '<table class="centpercent noborder">';
	echo '<tr class="liste_titre">';
	print_liste_field_titre("Target", $_SERVER["PHP_SELF"], "c.lastname,c.firstname", '', $param, 'width="45%"', $sortfield, $sortorder);
	print_liste_field_titre("Action", $_SERVER["PHP_SELF"], "", '', $param, 'width="35%"', $sortfield, $sortorder);
	print_liste_field_titre("Type", $_SERVER["PHP_SELF"], "n.type", '', $param, 'width="10%"', $sortfield, $sortorder);
	print_liste_field_titre('');
	echo "</tr>\n";

	// Line to add a new subscription
	if ($action == 'create') {
		$listofemails = $object->thirdparty_and_contact_email_array();
		if (count($listofemails) > 0) {
			$actions = array();

			// Load array of available notifications
			$notificationtrigger = new InterfaceNotification($db);
			$listofmanagedeventfornotification = $notificationtrigger->getListOfManagedEvents();

			foreach ($listofmanagedeventfornotification as $managedeventfornotification) {
				$label = ($langs->trans("Notify_".$managedeventfornotification['code']) != "Notify_".$managedeventfornotification['code'] ? $langs->trans("Notify_".$managedeventfornotification['code']) : $managedeventfornotification['label']);
				$actions[$managedeventfornotification['rowid']] = $label;
			}

			$newlistofemails = array();
			foreach ($listofemails as $tmpkey => $tmpval) {
				$labelhtml = str_replace(array('<', '>'), array(' - <span class="opacitymedium">', '</span>'), $tmpval);
				$newlistofemails[$tmpkey] = array('label' => dol_string_nohtmltag($tmpval), 'id' => $tmpkey, 'data-html' => $labelhtml);
			}

			echo '<tr class="oddeven nohover">';
			echo '<td class="nowraponall">';
			echo img_picto('', 'contact', '', false, 0, 0, '', 'paddingright');
			echo $form->selectarray("contactid", $newlistofemails, '', 1, 0, 0, '', 0, 0, 0, '', 'minwidth100imp maxwidthonsmartphone');
			echo '</td>';
			echo '<td class="nowraponall">';
			echo img_picto('', 'object_action', '', false, 0, 0, '', 'paddingright');
			echo $form->selectarray("actionid", $actions, '', 1, 0, 0, '', 0, 0, 0, '', 'minwidth100imp maxwidthonsmartphone');
			echo '</td>';
			echo '<td>';
			$type = array('email' => $langs->trans("EMail"));
			echo $form->selectarray("typeid", $type, '', 0, 0, 0, '', 0, 0, 0, '', 'minwidth75imp');
			echo '</td>';
			echo '<td class="right nowraponall">';
			echo '<input type="submit" class="button button-add small" value="'.$langs->trans("Add").'">';
			echo '<input type="submit" class="button button-cancel small" name="cancel" value="'.$langs->trans("Cancel").'">';
			echo '</td>';
			echo '</tr>';
		} else {
			echo '<tr class="oddeven"><td colspan="4" class="opacitymedium">';
			echo $langs->trans("YouMustCreateContactFirst");
			echo '</td></tr>';
		}
	} else {
		if ($num) {
			$i = 0;

			$contactstatic = new Contact($db);

			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				$contactstatic->id = $obj->contactid;
				$contactstatic->lastname = $obj->lastname;
				$contactstatic->firstname = $obj->firstname;

				echo '<tr class="oddeven">';
				echo '<td>'.$contactstatic->getNomUrl(1);
				if ($obj->type == 'email') {
					if (isValidEmail($obj->email)) {
						echo ' &lt;'.$obj->email.'&gt;';
					} else {
						$langs->load("errors");
						echo ' '.img_warning().' <span class="warning">'.$langs->trans("ErrorBadEMail", $obj->email).'</span>';
					}
				}
				echo '</td>';

				$label = ($langs->trans("Notify_".$obj->code) != "Notify_".$obj->code ? $langs->trans("Notify_".$obj->code) : $obj->label);
				echo '<td class="tdoverflowmax200" title="'.dol_escape_htmltag($label).'">';
				echo img_picto('', 'object_action', '', false, 0, 0, '', 'paddingright').$label;
				echo '</td>';
				echo '<td>';
				if ($obj->type == 'email') {
					echo $langs->trans("Email");
				}
				if ($obj->type == 'sms') {
					echo $langs->trans("SMS");
				}
				echo '</td>';
				echo '<td class="right"><a href="card.php?socid='.$socid.'&action=delete&token='.newToken().'&actid='.$obj->rowid.'">'.img_delete().'</a></td>';
				echo '</tr>';
				$i++;
			}
			$db->free($resql);
		} else {
			echo '<tr><td colspan="4"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
		}
	}



	echo '</table>';
	echo '</div>';
	echo '</form>';

	echo '<br><br>'."\n";


	// List
	$sql = "SELECT n.rowid, n.daten, n.email, n.objet_type as object_type, n.objet_id as object_id, n.type,";
	$sql .= " c.rowid as id, c.lastname, c.firstname, c.email as contactemail,";
	$sql .= " a.code, a.label";
	$sql .= " FROM ".MAIN_DB_PREFIX."c_action_trigger as a,";
	$sql .= " ".MAIN_DB_PREFIX."notify as n ";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as c ON n.fk_contact = c.rowid";
	$sql .= " WHERE a.rowid = n.fk_action";
	$sql .= " AND n.fk_soc = ".((int) $object->id);
	$sql .= $db->order($sortfield, $sortorder);

	// Count total nb of records
	$nbtotalofrecords = '';
	if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
		$result = $db->query($sql);
		$nbtotalofrecords = $db->num_rows($result);
		if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller then paging size (filtering), goto and load page 0
			$page = 0;
			$offset = 0;
		}
	}

	$sql .= $db->plimit($limit + 1, $offset);

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
	} else {
		dol_print_error($db);
	}

	$param = '&socid='.$object->id;
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
		$param .= '&contextpage='.$contextpage;
	}
	if ($limit > 0 && $limit != $conf->liste_limit) {
		$param .= '&limit='.$limit;
	}

	echo '<form method="post" action="'.$_SERVER["PHP_SELF"].'" name="formfilter">';
	if ($optioncss != '') {
		echo '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	}
	echo '<input type="hidden" name="token" value="'.newToken().'">';
	echo '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	echo '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	echo '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	echo '<input type="hidden" name="page" value="'.$page.'">';
	echo '<input type="hidden" name="socid" value="'.$object->id.'">';

	// List of active notifications  @phan-suppress-next-line PhanPluginSuspiciousParamOrder
	print_barre_liste($langs->trans("ListOfNotificationsDone"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, empty($nbtotalofrecords) ? -1 : $nbtotalofrecords, 'email', 0, '', '', $limit);

	// Line with titles
	echo '<div class="div-table-responsive-no-min">';
	echo '<table class="centpercent noborder">';
	echo '<tr class="liste_titre">';
	print_liste_field_titre("Target", $_SERVER["PHP_SELF"], "c.lastname,c.firstname", '', $param, '', $sortfield, $sortorder);
	print_liste_field_titre("Action", $_SERVER["PHP_SELF"], "", '', $param, '', $sortfield, $sortorder);
	print_liste_field_titre("Type", $_SERVER["PHP_SELF"], "n.type", '', $param, '', $sortfield, $sortorder);
	//print_liste_field_titre("Object",$_SERVER["PHP_SELF"],"",'',$param,'"',$sortfield,$sortorder);
	print_liste_field_titre("Date", $_SERVER["PHP_SELF"], "n.daten", '', $param, '', $sortfield, $sortorder, 'right ');
	echo '</tr>';

	if ($num > 0) {
		$i = 0;

		$contactstatic = new Contact($db);

		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			echo '<tr class="oddeven"><td>';
			if ($obj->id > 0) {
				$contactstatic->id = $obj->id;
				$contactstatic->lastname = $obj->lastname;
				$contactstatic->firstname = $obj->firstname;
				echo $contactstatic->getNomUrl(1);
				echo $obj->email ? ' &lt;'.$obj->email.'&gt;' : $langs->trans("NoMail");
			} else {
				echo $obj->email;
			}
			echo '</td>';
			echo '<td>';
			$label = ($langs->trans("Notify_".$obj->code) != "Notify_".$obj->code ? $langs->trans("Notify_".$obj->code) : $obj->label);
			echo $label;
			echo '</td>';
			echo '<td>';
			if ($obj->type == 'email') {
				echo $langs->trans("Email");
			}
			if ($obj->type == 'sms') {
				echo $langs->trans("Sms");
			}
			echo '</td>';
			// TODO Add link to object here for other types
			/*echo '<td>';
			if ($obj->object_type == 'order')
			{
				$orderstatic->id=$obj->object_id;
				$orderstatic->ref=...
				echo $orderstatic->getNomUrl(1);
			}
			   echo '</td>';*/
			// print
			print'<td class="right">'.dol_print_date($db->jdate($obj->daten), 'dayhour').'</td>';
			echo '</tr>';
			$i++;
		}
		$db->free($resql);
	} else {
		echo '<tr><td colspan="4"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
	}

	echo '</table>';
	echo '</div>';

	echo '</form>';
} else {
	dol_print_error(null, 'RecordNotFound');
}

// End of page
llxFooter();
$db->close();
