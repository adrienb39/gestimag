<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2022 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014      Marcos García        <marcosgdf@gmail.com>
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
 *    \file       htdocs/bookmarks/card.php
 *    \ingroup    bookmark
 *    \brief      Page display/creation of bookmarks
 */


// Load Gestimag environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/bookmarks/class/bookmark.class.php';


// Load translation files required by the page
$langs->loadLangs(array('bookmarks', 'other'));


// Get Parameters
$id = GETPOSTINT("id");
$action = GETPOST("action", "alpha");
$title = (string) GETPOST("title", "alpha");
$url = (string) GETPOST("url", "alpha");
$urlsource = GETPOST("urlsource", "alpha");
$target = GETPOSTINT("target");
$userid = GETPOSTINT("userid");
$position = GETPOSTINT("position");
$backtopage = GETPOST('backtopage', 'alpha');


// Initialize Objects
$object = new Bookmark($db);
if ($id > 0) {
	$object->fetch($id);
}

// Security check
restrictedArea($user, 'bookmark', $object);

$permissiontoread = $user->hasRight('bookmark', 'lire');
$permissiontoadd = $user->hasRight('bookmark', 'creer');
$permissiontodelete = ($user->hasRight('bookmark', 'supprimer') || ($permissiontoadd && $object->fk_user == $user->id));	// Can always delete its own bookmark



/*
 * Actions
 */

if (($action == 'add' || $action == 'addproduct' || $action == 'update') && $permissiontoadd) {
	if ($action == 'update') {	// Test on permission already done
		$invertedaction = 'edit';
	} else {
		$invertedaction = 'create';
	}

	$error = 0;

	if (GETPOST('cancel', 'alpha')) {
		if (empty($backtopage)) {
			$backtopage = ($urlsource ? $urlsource : ((!empty($url) && !preg_match('/^http/i', $url)) ? $url : DOL_URL_ROOT.'/bookmarks/list.php'));
		}
		header("Location: ".$backtopage);
		exit;
	}

	if ($action == 'update') {	// Test on permission already done
		$object->fetch(GETPOSTINT("id"));
	}
	// Check if null because user not admin can't set an user and send empty value here.
	if (!empty($userid)) {
		$object->fk_user = $userid;
	}
	$object->title = $title;
	$object->url = $url;
	$object->target = $target;
	$object->position = $position;

	if (!$title) {
		$error++;
		setEventMessages($langs->transnoentities("ErrorFieldRequired", $langs->trans("BookmarkTitle")), null, 'errors');
	}

	if (!$url) {
		$error++;
		setEventMessages($langs->transnoentities("ErrorFieldRequired", $langs->trans("UrlOrLink")), null, 'errors');
	}

	if (!$error) {
		$object->favicon = 'none';

		if ($action == 'update') {	// Test on permission already done
			$res = $object->update();
		} else {
			$res = $object->create();
		}

		if ($res > 0) {
			if (empty($backtopage)) {
				$backtopage = ($urlsource ? $urlsource : ((!empty($url) && !preg_match('/^http/i', $url)) ? $url : DOL_URL_ROOT.'/bookmarks/list.php'));
			}
			header("Location: ".$backtopage);
			exit;
		} else {
			if ($object->errno == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
				$langs->load("errors");
				setEventMessages($langs->transnoentities("WarningBookmarkAlreadyExists"), null, 'warnings');
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
			}
			$action = $invertedaction;
		}
	} else {
		$action = $invertedaction;
	}
}



/*
 * View
 */

llxHeader('', '', '', '', 0, 0, '', '', '', 'mod-bookmarks page-card');

$form = new Form($db);


$head = array();
$h = 1;

$head[$h][0] = $_SERVER["PHP_SELF"].($object->id ? '?id='.$object->id : '');
$head[$h][1] = $langs->trans("Bookmark");
$head[$h][2] = 'card';
$h++;

$hselected = 'card';


if ($action == 'create') {
	/*
	 * Fact bookmark creation mode
	 */

	echo '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" enctype="multipart/form-data">'."\n";
	echo '<input type="hidden" name="token" value="'.newToken().'">';
	echo '<input type="hidden" name="action" value="add">';
	echo '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

	echo load_fiche_titre($langs->trans("NewBookmark"), '', 'bookmark');

	echo dol_get_fiche_head(null, 'bookmark', '', 0, '');

	echo '<table class="border centpercent tableforfieldcreate">';

	echo '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("BookmarkTitle").'</td><td><input id="titlebookmark" class="flat minwidth250" name="title" value="'.dol_escape_htmltag($title).'"></td><td class="hideonsmartphone"><span class="opacitymedium">'.$langs->trans("SetHereATitleForLink").'</span></td></tr>';
	dol_set_focus('#titlebookmark');

	// URL
	echo '<tr><td class="fieldrequired">'.$langs->trans("UrlOrLink").'</td><td><input class="flat quatrevingtpercent minwidth500" name="url" value="'.dol_escape_htmltag($url).'"></td><td class="hideonsmartphone"><span class="opacitymedium">'.$langs->trans("UseAnExternalHttpLinkOrRelativeGestimagLink").'</span></td></tr>';

	// Target
	echo '<tr><td>'.$langs->trans("BehaviourOnClick").'</td><td>';
	$liste = array(0=>$langs->trans("ReplaceWindow"), 1=>$langs->trans("OpenANewWindow"));
	$defaulttarget = 1;
	if ($url && !preg_match('/^http/i', $url)) {
		$defaulttarget = 0;
	}
	echo $form->selectarray('target', $liste, GETPOSTISSET('target') ? GETPOSTINT('target') : $defaulttarget, 0, 0, 0, '', 0, 0, 0, '', 'maxwidth300');
	echo '</td><td class="hideonsmartphone"><span class="opacitymedium">'.$langs->trans("ChooseIfANewWindowMustBeOpenedOnClickOnBookmark").'</span></td></tr>';

	// Visibility / Owner
	echo '<tr><td>'.$langs->trans("Visibility").'</td><td>';
	echo img_picto('', 'user', 'class="pictofixedwidth"');
	echo $form->select_dolusers(GETPOSTISSET('userid') ? GETPOSTINT('userid') : $user->id, 'userid', 0, '', 0, ($user->admin ? '' : array($user->id)), '', 0, 0, 0, '', ($user->admin) ? 1 : 0, '', 'maxwidth300 widthcentpercentminusx');
	echo '</td><td class="hideonsmartphone"></td></tr>';

	// Position
	echo '<tr><td>'.$langs->trans("Position").'</td><td>';
	echo '<input class="flat width50" name="position" value="'.(GETPOSTISSET("position") ? GETPOSTINT("position") : $object->position).'">';
	echo '</td><td class="hideonsmartphone"></td></tr>';

	echo '</table>';

	echo dol_get_fiche_end();

	echo $form->buttonsSaveCancel("CreateBookmark");

	echo '</form>';
}


if ($id > 0 && !preg_match('/^add/i', $action)) {
	if ($action == 'edit') {
		echo '<form name="edit" method="POST" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data">';
		echo '<input type="hidden" name="token" value="'.newToken().'">';
		echo '<input type="hidden" name="action" value="update">';
		echo '<input type="hidden" name="id" value="'.$object->id.'">';
		echo '<input type="hidden" name="urlsource" value="'.DOL_URL_ROOT.'/bookmarks/card.php?id='.$object->id.'">';
		echo '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}

	echo dol_get_fiche_head($head, $hselected, $langs->trans("Bookmark"), -1, 'bookmark');

	$linkback = '<a href="'.DOL_URL_ROOT.'/bookmarks/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', '', '', 0, '', '', 0);

	echo '<div class="fichecenter">';

	echo '<div class="underbanner clearboth"></div>';
	echo '<table class="border centpercent tableforfield">';

	// Title
	echo '<tr><td class="titlefield">';
	if ($action == 'edit') {
		echo '<span class="fieldrequired">';
	}

	echo $langs->trans("BookmarkTitle");

	if ($action == 'edit') {
		echo '</span>';
	}

	echo '</td><td>';
	if ($action == 'edit') {
		echo '<input class="flat minwidth250" name="title" value="'.(GETPOSTISSET("title") ? GETPOST("title", '', 2) : $object->title).'">';
	} else {
		echo dol_escape_htmltag($object->title);
	}
	echo '</td></tr>';

	// URL
	echo '<tr><td>';
	if ($action == 'edit') {
		echo '<span class="fieldrequired">';
	}
	echo $langs->trans("UrlOrLink");
	if ($action == 'edit') {
		echo '</span>';
	}
	echo '</td><td class="wordbreak">';
	if ($action == 'edit') {
		echo '<input class="flat minwidth500 quatrevingtpercent" name="url" value="'.(GETPOSTISSET("url") ? GETPOST("url") : $object->url).'">';
	} else {
		echo '<a href="'.(preg_match('/^http/i', $object->url) ? $object->url : DOL_URL_ROOT.$object->url).'"'.($object->target ? ' target="_blank" rel="noopener noreferrer"' : '').'>';
		echo img_picto('', 'globe', 'class="paddingright"');
		echo $object->url;
		echo '</a>';
	}
	echo '</td></tr>';

	echo '<tr><td>'.$langs->trans("BehaviourOnClick").'</td><td>';
	if ($action == 'edit') {
		$liste = array(1=>$langs->trans("OpenANewWindow"), 0=>$langs->trans("ReplaceWindow"));
		echo $form->selectarray('target', $liste, GETPOSTISSET("target") ? GETPOST("target") : $object->target);
	} else {
		if ($object->target == 0) {
			echo $langs->trans("ReplaceWindow");
		}
		if ($object->target == 1) {
			echo $langs->trans("OpenANewWindow");
		}
	}
	echo '</td></tr>';

	// Visibility / owner
	echo '<tr><td>'.$langs->trans("Visibility").'</td><td>';
	if ($action == 'edit' && $user->admin) {
		echo img_picto('', 'user', 'class="pictofixedwidth"');
		echo $form->select_dolusers(GETPOSTISSET('userid') ? GETPOSTINT('userid') : ($object->fk_user ? $object->fk_user : ''), 'userid', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth300 widthcentpercentminusx');
	} else {
		if ($object->fk_user > 0) {
			$fuser = new User($db);
			$fuser->fetch($object->fk_user);
			echo $fuser->getNomUrl(-1);
		} else {
			echo '<span class="opacitymedium">'.$langs->trans("Everybody").'</span>';
		}
	}
	echo '</td></tr>';

	// Position
	echo '<tr><td>'.$langs->trans("Position").'</td><td>';
	if ($action == 'edit') {
		echo '<input class="flat" name="position" size="5" value="'.(GETPOSTISSET("position") ? GETPOSTINT("position") : $object->position).'">';
	} else {
		echo $object->position;
	}
	echo '</td></tr>';

	// Date creation
	echo '<tr><td>'.$langs->trans("DateCreation").'</td><td>'.dol_print_date($object->datec, 'dayhour').'</td></tr>';

	echo '</table>';

	echo '</div>';

	echo dol_get_fiche_end();

	if ($action == 'edit') {
		echo $form->buttonsSaveCancel();

		echo '</form>';
	}


	// Buttons

	echo '<div class="tabsAction">'."\n";

	// Edit
	if ($permissiontoadd && $action != 'edit') {
		echo '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken().'">'.$langs->trans("Edit").'</a>'."\n";
	}

	// Remove
	if ($permissiontodelete && $action != 'edit') {
		echo '<a class="butActionDelete" href="list.php?id='.$object->id.'&action=delete&token='.newToken().'">'.$langs->trans("Delete").'</a>'."\n";
	}

	echo '</div>';
}

// End of page
llxFooter();
$db->close();
