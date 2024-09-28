<?php
/* Copyright (C) 2006-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2010-2017	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2015	    Alexandre Spangaro	<aspangaro@open-dsi.fr>
 * Copyright (C) 2018       Ferran Marcet       <fmarcet@2byte.es>
 * Copyright (C) 2021-2023  Anthony Berton      <anthony.berton@bb2a.fr>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 * or see https://www.gnu.org/
 */


/**
 *	    \file       htdocs/core/lib/usergroups.lib.php
 *		\brief      Set of function to manage users, groups and permissions
 */

/**
 * Prepare array with list of tabs
 *
 * @param   User	$object		Object related to tabs
 * @return  array				Array of tabs to show
 */
function user_prepare_head(User $object)
{
	global $langs, $conf, $user, $db;

	$langs->load("users");

	$canreadperms = true;
	if (getDolGlobalString('MAIN_USE_ADVANCED_PERMS')) {
		$canreadperms = ($user->admin || ($user->id != $object->id && $user->hasRight('user', 'user_advance', 'readperms')) || ($user->id == $object->id && $user->hasRight('user', 'self_advance', 'readperms')));
	}

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/user/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans("User");
	$head[$h][2] = 'user';
	$h++;

	if ((!empty($conf->ldap->enabled) && getDolGlobalString('LDAP_SYNCHRO_ACTIVE'))
		&& (!getDolGlobalString('MAIN_DISABLE_LDAP_TAB') || !empty($user->admin))) {
		$langs->load("ldap");
		$head[$h][0] = DOL_URL_ROOT.'/user/ldap.php?id='.$object->id;
		$head[$h][1] = $langs->trans("LDAPCard");
		$head[$h][2] = 'ldap';
		$h++;
	}

	if ($canreadperms) {
		$head[$h][0] = DOL_URL_ROOT.'/user/perms.php?id='.$object->id;
		$head[$h][1] = $langs->trans("Rights").(!getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER') ? '<span class="badge marginleftonlyshort">'.($object->nb_rights).'</span>' : '');
		$head[$h][2] = 'rights';
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT.'/user/param_ihm.php?id='.$object->id;
	$head[$h][1] = $langs->trans("UserGUISetup");
	$head[$h][2] = 'guisetup';
	$h++;

	if (isModEnabled('agenda')) {
		if (!getDolGlobalString('AGENDA_EXT_NB')) {
			$conf->global->AGENDA_EXT_NB = 5;
		}
		$MAXAGENDA = getDolGlobalString('AGENDA_EXT_NB');

		$i = 1;
		$nbagenda = 0;
		while ($i <= $MAXAGENDA) {
			$key = $i;
			$name = 'AGENDA_EXT_NAME_'.$object->id.'_'.$key;
			$src = 'AGENDA_EXT_SRC_'.$object->id.'_'.$key;
			$offsettz = 'AGENDA_EXT_OFFSETTZ_'.$object->id.'_'.$key;
			$color = 'AGENDA_EXT_COLOR_'.$object->id.'_'.$key;
			$i++;

			if (!empty($object->conf->$name)) {
				$nbagenda++;
			}
		}

		$head[$h][0] = DOL_URL_ROOT.'/user/agenda_extsites.php?id='.$object->id;
		$head[$h][1] = $langs->trans("ExtSites").($nbagenda ? '<span class="badge marginleftonlyshort">'.$nbagenda.'</span>' : '');
		$head[$h][2] = 'extsites';
		$h++;
	}

	if (isModEnabled('clicktodial')) {
		$head[$h][0] = DOL_URL_ROOT.'/user/clicktodial.php?id='.$object->id;
		$head[$h][1] = $langs->trans("ClickToDial");
		$head[$h][2] = 'clicktodial';
		$h++;
	}

	// Notifications
	if ($user->socid == 0 && isModEnabled('notification')) {
		$nbNote = 0;
		$sql = "SELECT COUNT(n.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."notify_def as n";
		$sql .= " WHERE fk_user = ".((int) $object->id);
		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				$nbNote = $obj->nb;
				$i++;
			}
		} else {
			dol_print_error($db);
		}

		$langs->load("mails");
		$head[$h][0] = DOL_URL_ROOT.'/user/notify/card.php?id='.$object->id;
		$head[$h][1] = $langs->trans("NotificationsAuto");
		if ($nbNote > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
		}
		$head[$h][2] = 'notify';
		$h++;
	}

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'user');

	if ((isModEnabled('salaries') && $user->hasRight('salaries', 'read'))
		|| (isModEnabled('hrm') && $user->hasRight('hrm', 'employee', 'read'))
		|| (isModEnabled('expensereport') && $user->hasRight('expensereport', 'lire') && ($user->id == $object->id || $user->hasRight('expensereport', 'readall')))
		|| (isModEnabled('holiday') && $user->hasRight('holiday', 'read') && ($user->id == $object->id || $user->hasRight('holiday', 'readall')))
	) {
		// Bank
		$head[$h][0] = DOL_URL_ROOT.'/user/bank.php?id='.$object->id;
		$head[$h][1] = $langs->trans("HRAndBank");
		$head[$h][2] = 'bank';
		$h++;
	}

	// Such info on users is visible only by internal user
	if (empty($user->socid)) {
		// Notes
		$nbNote = 0;
		if (!empty($object->note_public)) {
			$nbNote++;
		}
		if (!empty($object->note_private)) {
			$nbNote++;
		}
		$head[$h][0] = DOL_URL_ROOT.'/user/note.php?id='.$object->id;
		$head[$h][1] = $langs->trans("Note");
		if ($nbNote > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
		}
		$head[$h][2] = 'note';
		$h++;

		// Attached files
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
		$upload_dir = $conf->user->dir_output."/".$object->id;
		$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
		$nbLinks = Link::count($db, $object->element, $object->id);
		$head[$h][0] = DOL_URL_ROOT.'/user/document.php?userid='.$object->id;
		$head[$h][1] = $langs->trans("Documents");
		if (($nbFiles + $nbLinks) > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
		}
		$head[$h][2] = 'document';
		$h++;

		$head[$h][0] = DOL_URL_ROOT.'/user/agenda.php?id='.$object->id;
		$head[$h][1] = $langs->trans("Events");
		if (isModEnabled('agenda') && ($user->hasRight('agenda', 'myactions', 'read') || $user->hasRight('agenda', 'allactions', 'read'))) {
			$nbEvent = 0;
			// Enable caching of thirdparty count actioncomm
			require_once DOL_DOCUMENT_ROOT.'/core/lib/memory.lib.php';
			$cachekey = 'count_events_user_'.$object->id;
			$dataretrieved = dol_getcache($cachekey);
			if (!is_null($dataretrieved)) {
				$nbEvent = $dataretrieved;
			} else {
				$sql = "SELECT COUNT(ac.id) as nb";
				$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as ac";
				$sql .= " WHERE ac.fk_user_action = ".((int) $object->id);
				$sql .= " AND ac.entity IN (".getEntity('agenda').")";
				$resql = $db->query($sql);
				if ($resql) {
					$obj = $db->fetch_object($resql);
					$nbEvent = $obj->nb;
				} else {
					dol_syslog('Failed to count actioncomm '.$db->lasterror(), LOG_ERR);
				}
				dol_setcache($cachekey, $nbEvent, 120);		// If setting cache fails, this is not a problem, so we do not test result.
			}

			$head[$h][1] .= '/';
			$head[$h][1] .= $langs->trans("Agenda");
			if ($nbEvent > 0) {
				$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbEvent.'</span>';
			}
		}
		$head[$h][2] = 'info';
		$h++;
	}

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'user', 'remove');

	return $head;
}

/**
 * Prepare array with list of tabs
 *
 * @param 	UserGroup $object		Object group
 * @return	array				    Array of tabs
 */
function group_prepare_head($object)
{
	global $langs, $conf, $user;

	$canreadperms = true;
	if (getDolGlobalString('MAIN_USE_ADVANCED_PERMS')) {
		$canreadperms = ($user->admin || $user->hasRight('user', 'group_advance', 'readperms'));
	}

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/user/group/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'group';
	$h++;

	if ((!empty($conf->ldap->enabled) && getDolGlobalString('LDAP_SYNCHRO_ACTIVE'))
		&& (!getDolGlobalString('MAIN_DISABLE_LDAP_TAB') || !empty($user->admin))) {
		$langs->load("ldap");
		$head[$h][0] = DOL_URL_ROOT.'/user/group/ldap.php?id='.$object->id;
		$head[$h][1] = $langs->trans("LDAPCard");
		$head[$h][2] = 'ldap';
		$h++;
	}

	if ($canreadperms) {
		$head[$h][0] = DOL_URL_ROOT.'/user/group/perms.php?id='.$object->id;
		$head[$h][1] = $langs->trans("GroupRights").'<span class="badge marginleftonlyshort">'.($object->nb_rights).'</span>';
		$head[$h][2] = 'rights';
		$h++;
	}

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'group');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'group', 'remove');

	return $head;
}

/**
 * Prepare array with list of tabs
 *
 * @return  array				Array of tabs to show
 */
function user_admin_prepare_head()
{
	global $langs, $conf, $user, $db;

	$extrafields = new ExtraFields($db);
	$extrafields->fetch_name_optionals_label('user');
	$extrafields->fetch_name_optionals_label('usergroup');

	$langs->load("users");
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/admin/user.php';
	$head[$h][1] = $langs->trans("Parameters");
	$head[$h][2] = 'card';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/admin/usergroup.php';
	$head[$h][1] = $langs->trans("Group");
	$head[$h][2] = 'usergroupcard';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/user/admin/user_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFields")." (".$langs->trans("Users").")";
	$nbExtrafields = $extrafields->attributes['user']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'attributes';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/user/admin/group_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFields")." (".$langs->trans("Groups").")";
	$nbExtrafields = $extrafields->attributes['usergroup']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'attributes_group';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'useradmin');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'useradmin', 'remove');

	return $head;
}

/**
 * 	Show list of themes. Show all thumbs of themes
 *
 * 	@param	User|null	$fuser				User concerned or null for global theme
 * 	@param	int			$edit				1 to add edit form
 * 	@param	boolean		$foruserprofile		Show for user profile view
 * 	@return	void
 */
function showSkins($fuser, $edit = 0, $foruserprofile = false)
{
	global $conf, $langs, $db, $form;

	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

	$formother = new FormOther($db);

	$dirthemes = array('/theme');
	if (!empty($conf->modules_parts['theme'])) {		// Using this feature slow down application
		foreach ($conf->modules_parts['theme'] as $reldir) {
			$dirthemes = array_merge($dirthemes, (array) ($reldir.'theme'));
		}
	}
	$dirthemes = array_unique($dirthemes);
	// Now dir_themes=array('/themes') or dir_themes=array('/theme','/mymodule/theme')

	$selected_theme = '';
	if (empty($foruserprofile)) {
		$selected_theme = getDolGlobalString('MAIN_THEME');
	} else {
		$selected_theme = ((is_object($fuser) && !empty($fuser->conf->MAIN_THEME)) ? $fuser->conf->MAIN_THEME : '');
	}

	$hoverdisabled = '';
	if (empty($foruserprofile)) {
		$hoverdisabled = (getDolGlobalString('THEME_ELDY_USE_HOVER') == '0');
	} else {
		$hoverdisabled = (is_object($fuser) ? (empty($fuser->conf->THEME_ELDY_USE_HOVER) || $fuser->conf->THEME_ELDY_USE_HOVER == '0') : '');
	}

	$checkeddisabled = '';
	if (empty($foruserprofile)) {
		$checkeddisabled = (getDolGlobalString('THEME_ELDY_USE_CHECKED') == '0');
	} else {
		$checkeddisabled = (is_object($fuser) ? (empty($fuser->conf->THEME_ELDY_USE_CHECKED) || $fuser->conf->THEME_ELDY_USE_CHECKED == '0') : '');
	}

	$colspan = 2;
	if ($foruserprofile) {
		$colspan = 4;
	}

	$thumbsbyrow = 6;
	echo'<div class="div-table-responsive-no-min">';
	echo'<table class="noborder centpercent'.($edit ? ' editmodeforshowskin' : '').'">';

	// Title
	if ($foruserprofile) {
		echo'<tr class="liste_titre"><th class="titlefieldmiddle">'.$langs->trans("Parameter").'</th><th>'.$langs->trans("DefaultValue").'</th>';
		echo'<th colspan="2">&nbsp;</th>';
		echo'</tr>';

		echo'<tr>';
		echo'<td>'.$langs->trans("DefaultSkin").'</td>';
		echo'<td>' . getDolGlobalString('MAIN_THEME').'</td>';
		echo'<td class="nowrap left"><input id="check_MAIN_THEME" name="check_MAIN_THEME"'.($edit ? '' : ' disabled').' type="checkbox" '.($selected_theme ? " checked" : "").'> <label for="check_MAIN_THEME">'.$langs->trans("UsePersonalValue").'</label></td>';
		echo'<td>&nbsp;</td>';
		echo'</tr>';
	} else {
		$dirthemestring = '';
		foreach ($dirthemes as $dirtheme) {
			$dirthemestring .= '"'.$dirtheme.'" ';
		}

		echo'<tr class="liste_titre"><th class="titlefieldmiddle">';
		echo$form->textwithpicto($langs->trans("DefaultSkin"), $langs->trans("ThemeDir").' : '.$dirthemestring);
		echo'</th>';
		echo'<th class="right">';
		$url = 'https://www.dolistore.com/9-skins';
		echo'<a href="'.$url.'" target="_blank" rel="noopener noreferrer external">';
		echo$langs->trans('DownloadMoreSkins');
		echoimg_picto('', 'globe', 'class="paddingleft"');
		echo'</a>';
		echo'</th></tr>';
	}

	echo'<tr><td colspan="'.$colspan.'" class="center">';

	if (getDolGlobalString('MAIN_FORCETHEME')) {
		$langs->load("errors");
		echo$langs->trans("WarningThemeForcedTo", getDolGlobalString('MAIN_FORCETHEME'));
	}

	echo'<table class="nobordernopadding centpercent"><tr><td><div class="center">';

	$i = 0;
	foreach ($dirthemes as $dir) {
		//echo$dirroot.$dir;exit;
		$dirtheme = dol_buildpath($dir, 0); // This include loop on $conf->file->dol_document_root
		$urltheme = dol_buildpath($dir, 1);

		if (is_dir($dirtheme)) {
			$handle = opendir($dirtheme);
			if (is_resource($handle)) {
				while (($subdir = readdir($handle)) !== false) {
					if (is_dir($dirtheme."/".$subdir) && substr($subdir, 0, 1) != '.'
							&& substr($subdir, 0, 3) != 'CVS' && !preg_match('/common|phones/i', $subdir)) {
						// Disable not stable themes (dir ends with _exp or _dev)
						if (getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2 && preg_match('/_dev$/i', $subdir)) {
							continue;
						}
						if (getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1 && preg_match('/_exp$/i', $subdir)) {
							continue;
						}

						echo'<div class="inline-block" style="margin-top: 10px; margin-bottom: 10px; margin-right: 20px; margin-left: 20px;">';
						$file = $dirtheme."/".$subdir."/thumb.png";
						$url = $urltheme."/".$subdir."/thumb.png";
						if (!file_exists($file)) {
							$url = DOL_URL_ROOT.'/public/theme/common/nophoto.png';
						}
						echo'<a href="'.$_SERVER["PHP_SELF"].($edit ? '?action=edit&token='.newToken().'&mode=template&theme=' : '?theme=').$subdir.(GETPOST('optioncss', 'alpha', 1) ? '&optioncss='.GETPOST('optioncss', 'alpha', 1) : '').($fuser ? '&id='.$fuser->id : '').'" style="font-weight: normal;" alt="'.$langs->trans("Preview").'">';
						if ($subdir == $conf->global->MAIN_THEME) {
							$title = $langs->trans("ThemeCurrentlyActive");
						} else {
							$title = $langs->trans("ShowPreview");
						}
						echo'<img class="img-skinthumb shadow" src="'.$url.'" alt="'.dol_escape_htmltag($title).'" title="'.dol_escape_htmltag($title).'" style="border: none; margin-bottom: 5px;">';
						echo'</a><br>';
						if ($subdir == $selected_theme) {
							echo'<input '.($edit ? '' : 'disabled').' type="radio" class="themethumbs" style="border: 0px;" id="main_theme'.$subdir.'" checked name="main_theme" value="'.$subdir.'"><label for="main_theme'.$subdir.'"> <b>'.$subdir.'</b></label>';
						} else {
							echo'<input '.($edit ? '' : 'disabled').' type="radio" class="themethumbs" style="border: 0px;" id="main_theme'.$subdir.'" name="main_theme" value="'.$subdir.'"><label for="main_theme'.$subdir.'"> '.$subdir.'</label>';
						}
						echo'</div>';

						$i++;
					}
				}
			}
		}
	}

	echo'</div></td></tr></table>';

	echo'</td></tr>';

	// Set variables of theme
	$colorbackhmenu1 = '';
	$colorbackvmenu1 = '';
	$colortexttitlenotab = '';
	$colortexttitlelink = '';
	$colorbacktitle1 = '';
	$colortexttitle = '';
	$colorbacklineimpair1 = '';
	$colorbacklineimpair2 = '';
	$colorbacklinepair1 = '';
	$colorbacklinepair2 = '';
	$colortextlink = '';
	$colorbacklinepairhover = '';
	$colorbacklinepairchecked = '';
	$butactionbg = '';
	$textbutaction = '';
	// Set the variables with the default value
	if (file_exists(DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php')) {
		include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';
	}

	// Dark mode
	if ($foruserprofile) {
		//Nothing
	} else {
		$listofdarkmodes = array(
			$langs->trans("AlwaysDisabled"),
			$langs->trans("AccordingToBrowser"),
			$langs->trans("AlwaysEnabled")
		);
		echo'<tr class="oddeven">';
		echo'<td>'.$langs->trans("DarkThemeMode").'</td>';
		echo'<td colspan="'.($colspan - 1).'">';
		if ($edit) {
			echo$form->selectarray('THEME_DARKMODEENABLED', $listofdarkmodes, getDolGlobalInt('THEME_DARKMODEENABLED'));
		} else {
			echo$listofdarkmodes[getDolGlobalInt('THEME_DARKMODEENABLED')];
		}
		echo$form->textwithpicto('', $langs->trans("DoesNotWorkWithAllThemes"));
		echo'</tr>';
	}


	// TopMenuDisableImages
	if ($foruserprofile) {
		/*
		 echo'<tr class="oddeven">';
		 echo'<td>'.$langs->trans("TopMenuDisableImages").'</td>';
		 echo'<td>'.(getDolGlobalString('THEME_TOPMENU_DISABLE_IMAGE',$langs->trans("Default")).'</td>';
		 echo'<td class="left" class="nowrap" width="20%"><input name="check_THEME_TOPMENU_DISABLE_IMAGE" id="check_THEME_TOPMENU_DISABLE_IMAGE" type="checkbox" '.(!empty($object->conf->THEME_ELDY_TEXTLINK)?" checked":"");
		 echo(empty($gestimag_main_demo) && $edit)?'':' disabled="disabled"';	// Disabled for demo
		 echo'> '.$langs->trans("UsePersonalValue").'</td>';
		 echo'<td>';
		 if ($edit)
		 {
		 echo$formother->selectColor(colorArrayToHex(colorStringToArray(getDolGlobalString('THEME_TOPMENU_DISABLE_IMAGE'),array()),''),'THEME_TOPMENU_DISABLE_IMAGE','',1).' ';
		 }
		 else
		 {
		 $color = colorArrayToHex(colorStringToArray($conf->global->THEME_TOPMENU_DISABLE_IMAGE,array()),'');
		 if ($color) echo'<input type="text" class="colorthumb" disabled style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
		 else echo'';
		 }
		 if ($edit) echo'<br>('.$langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis").')';
		 echo'</td>';*/
	} else {
		$listoftopmenumodes = array(
			$langs->transnoentitiesnoconv("IconAndText"),
			$langs->transnoentitiesnoconv("TextOnly"),
			$langs->transnoentitiesnoconv("IconOnlyAllTextsOnHover"),
			$langs->transnoentitiesnoconv("IconOnlyTextOnHover"),
			$langs->transnoentitiesnoconv("IconOnly"),
		);
		echo'<tr class="oddeven">';
		echo'<td>'.$langs->trans("TopMenuDisableImages").'</td>';
		echo'<td colspan="'.($colspan - 1).'">';
		if ($edit) {
			//echoajax_constantonoff('THEME_TOPMENU_DISABLE_IMAGE', array(), null, 0, 0, 1);
			echo$form->selectarray('THEME_TOPMENU_DISABLE_IMAGE', $listoftopmenumodes, isset($conf->global->THEME_TOPMENU_DISABLE_IMAGE) ? $conf->global->THEME_TOPMENU_DISABLE_IMAGE : 0, 0, 0, 0, '', 0, 0, 0, '', 'widthcentpercentminusx maxwidth500');
		} else {
			echo$listoftopmenumodes[getDolGlobalInt('THEME_TOPMENU_DISABLE_IMAGE')];
			//echoyn($conf->global->THEME_TOPMENU_DISABLE_IMAGE);
		}
		echo$form->textwithpicto('', $langs->trans("NotSupportedByAllThemes"));
		echo'</td>';
		echo'</tr>';
	}

	// Show logo
	if ($foruserprofile) {
		// Nothing
	} else {
		// Show logo
		echo'<tr class="oddeven"><td class="titlefieldmiddle">'.$langs->trans("EnableShowLogo").'</td>';
		echo'<td colspan="'.($colspan - 1).'" class="valignmiddle">';
		if ($edit) {
			echoajax_constantonoff('MAIN_SHOW_LOGO', array(), null, 0, 0, 1);
			//echo$form->selectyesno('MAIN_SHOW_LOGO', $conf->global->MAIN_SHOW_LOGO, 1);
		} else {
			echoyn(getDolGlobalString('MAIN_SHOW_LOGO'));
		}
		echo$form->textwithpicto('', $langs->trans("NotSupportedByAllThemes"), 1, 'help', 'inline-block');
		echo'</td>';
		echo'</tr>';
	}

	// Main menu color on pictos
	if ($foruserprofile) {
		// Nothing
	} else {
		// Show logo
		echo'<tr class="oddeven"><td class="titlefieldmiddle">'.$langs->trans("THEME_MENU_COLORLOGO").'</td>';
		echo'<td colspan="'.($colspan - 1).'" class="valignmiddle">';
		if ($edit) {
			echoajax_constantonoff('THEME_MENU_COLORLOGO', array(), null, 0, 0, 1);
		} else {
			echoyn(getDolGlobalString('THEME_MENU_COLORLOGO'));
		}
		echo$form->textwithpicto('', $langs->trans("NotSupportedByAllThemes"), 1, 'help', 'inline-block');
		echo'</td>';
		echo'</tr>';
	}

	// Use border on tables
	if ($foruserprofile) {
	} else {
		echo'<tr class="oddeven">';
		echo'<td>'.$langs->trans("UseBorderOnTable").'</td>';
		echo'<td colspan="'.($colspan - 1).'" class="valignmiddle">';
		if ($edit) {
			echoajax_constantonoff('THEME_ELDY_USEBORDERONTABLE', array(), null, 0, 0, 1);
			//echo$form->selectyesno('THEME_ELDY_USEBORDERONTABLE', $conf->global->THEME_ELDY_USEBORDERONTABLE, 1);
		} else {
			echoyn(getDolGlobalString('THEME_ELDY_USEBORDERONTABLE'));
		}
		echo$form->textwithpicto('', $langs->trans("NotSupportedByAllThemes"), 1, 'help', 'inline-block');
		echo'</td>';
		echo'</tr>';
	}

	// Table line height
	/* removed. height of column must use padding of td and not lineheight that has bad side effect
	if ($foruserprofile) {
	} else {
		$listoftopmenumodes = array(
			'0' => $langs->transnoentitiesnoconv("Normal"),
			'1' => $langs->transnoentitiesnoconv("LargeModern"),
		);
		echo'<tr class="oddeven">';
		echo'<td>'.$langs->trans("TableLineHeight").'</td>';
		echo'<td colspan="'.($colspan - 1).'" class="valignmiddle">';
		if ($edit) {
			//echoajax_constantonoff('THEME_ELDY_USECOMOACTROW', array(), null, 0, 0, 1);
			echo$form->selectarray('THEME_ELDY_USECOMOACTROW', $listoftopmenumodes, getDolGlobalString('THEME_ELDY_USECOMOACTROW'), 0, 0, 0, '', 0, 0, 0, '', 'widthcentpercentminusx maxwidth300');
		} else {
			echo$listoftopmenumodes[getDolGlobalString('THEME_ELDY_USECOMOACTROW')];
		}
		echo$form->textwithpicto('', $langs->trans("NotSupportedByAllThemes"), 1, 'help', 'inline-block');
		echo'</td>';
		echo'</tr>';
	}
	*/

	// Background color for top menu - TopMenuBackgroundColor
	if ($foruserprofile) {
		/*
		echo'<tr class="oddeven">';
		echo'<td>'.$langs->trans("TopMenuBackgroundColor").'</td>';
		echo'<td>'.($conf->global->THEME_ELDY_TOPMENU_BACK1?$conf->global->THEME_ELDY_TOPMENU_BACK1:$langs->trans("Default")).'</td>';
		echo'<td class="nowrap left" width="20%"><input name="check_THEME_ELDY_TOPMENU_BACK1" id="check_THEME_ELDY_TOPMENU_BACK1" type="checkbox" '.(!empty($object->conf->THEME_ELDY_TOPMENU_BACK1)?" checked":"");
		echo(empty($gestimag_main_demo) && $edit)?'':' disabled="disabled"';	// Disabled for demo
		echo'> '.$langs->trans("UsePersonalValue").'</td>';
		echo'<td>';
		if ($edit)
		{
			echo$formother->selectColor(colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_TOPMENU_BACK1,array()),''),'THEME_ELDY_TOPMENU_BACK1','',1).' ';
		}
		   else
		   {
			   $color = colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_TOPMENU_BACK1,array()),'');
			if ($color) echo'<input type="text" class="colorthumb" disabled style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
			else echo'';
		   }
		if ($edit) echo'<br>('.$langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis").')';
		echo'</td>';*/
	} else {
		$default = (empty($colorbackhmenu1) ? $langs->trans("Unknown") : colorArrayToHex(colorStringToArray($colorbackhmenu1)));

		echo'<tr class="oddeven">';
		echo'<td>'.$langs->trans("TopMenuBackgroundColor").'</td>';
		echo'<td colspan="'.($colspan - 1).'">';
		if ($edit) {
			echo$formother->selectColor(colorArrayToHex(colorStringToArray((getDolGlobalString('THEME_ELDY_TOPMENU_BACK1') ? $conf->global->THEME_ELDY_TOPMENU_BACK1 : ''), array()), ''), 'THEME_ELDY_TOPMENU_BACK1', '', 1, '', '', 'colorbackhmenu1', $default).' ';
		} else {
			$color = colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_TOPMENU_BACK1, array()), '');
			if ($color) {
				echo'<input type="text" class="colorthumb" disabled="disabled" style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
			} else {
				echo$langs->trans("Default");
			}
		}
		echo' &nbsp; <span class="nowraponall opacitymedium">'.$langs->trans("Default").'</span>: <strong>'.$default.'</strong> ';
		echo$form->textwithpicto('', $langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis"));
		echo'</td>';
		echo'</tr>';
	}

	// Background color for left menu - LeftMenuBackgroundColor
	if ($foruserprofile) {
		/*
		 echo'<tr class="oddeven">';
		 echo'<td>'.$langs->trans("TopMenuBackgroundColor").'</td>';
		 echo'<td>'.($conf->global->THEME_ELDY_TOPMENU_BACK1?$conf->global->THEME_ELDY_VERMENU_BACK1:$langs->trans("Default")).'</td>';
		 echo'<td class="nowrap left" width="20%"><input name="check_THEME_ELDY_VERMENU_BACK1" id="check_THEME_ELDY_VERMENU_BACK1" type="checkbox" '.(!empty($object->conf->THEME_ELDY_TOPMENU_BACK1)?" checked":"");
		 echo(empty($gestimag_main_demo) && $edit)?'':' disabled="disabled"';	// Disabled for demo
		 echo'> '.$langs->trans("UsePersonalValue").'</td>';
		 echo'<td>';
		 if ($edit)
		 {
		 echo$formother->selectColor(colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_VERMENU_BACK1,array()),''),'THEME_ELDY_VERMENU_BACK1','',1).' ';
		 }
		 else
		 {
		 $color = colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_VERMENU_BACK1,array()),'');
		 if ($color) echo'<input type="text" class="colorthumb" disabled style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
		 else echo'';
		 }
		 if ($edit) echo'<br>('.$langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis").')';
		 echo'</td>';*/
	} else {
		$default = (empty($colorbackvmenu1) ? $langs->trans("Unknown") : colorArrayToHex(colorStringToArray($colorbackvmenu1)));

		echo'<tr class="oddeven">';
		echo'<td>'.$langs->trans("LeftMenuBackgroundColor").'</td>';
		echo'<td colspan="'.($colspan - 1).'">';
		if ($edit) {
			echo$formother->selectColor(colorArrayToHex(colorStringToArray((getDolGlobalString('THEME_ELDY_VERMENU_BACK1') ? $conf->global->THEME_ELDY_VERMENU_BACK1 : ''), array()), ''), 'THEME_ELDY_VERMENU_BACK1', '', 1, '', '', 'colorbackvmenu1', $default).' ';
		} else {
			$color = colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_VERMENU_BACK1, array()), '');
			if ($color) {
				echo'<input type="text" class="colorthumb" disabled="disabled" style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
			} else {
				echo$langs->trans("Default");
			}
		}
		echo' &nbsp; <span class="nowraponall opacitymedium">'.$langs->trans("Default").'</span>: <strong>'.$default.'</strong> ';
		echo$form->textwithpicto('', $langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis"));
		echo'</td>';
		echo'</tr>';
	}

	// Background color for main area THEME_ELDY_BACKBODY
	if ($foruserprofile) {
		/*
		 echo'<tr class="oddeven">';
		 echo'<td>'.$langs->trans("TopMenuBackgroundColor").'</td>';
		 echo'<td>'.($conf->global->THEME_ELDY_TOPMENU_BACK1?$conf->global->THEME_ELDY_TOPMENU_BACK1:$langs->trans("Default")).'</td>';
		 echo'<td class="nowrap left" width="20%"><input name="check_THEME_ELDY_TOPMENU_BACK1" id="check_THEME_ELDY_TOPMENU_BACK1" type="checkbox" '.(!empty($object->conf->THEME_ELDY_TOPMENU_BACK1)?" checked":"");
		 echo(empty($gestimag_main_demo) && $edit)?'':' disabled="disabled"';	// Disabled for demo
		 echo'> '.$langs->trans("UsePersonalValue").'</td>';
		 echo'<td>';
		 if ($edit) {
		 echo$formother->selectColor(colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_TOPMENU_BACK1,array()),''),'THEME_ELDY_TOPMENU_BACK1','',1).' ';
		 } else {
		 $color = colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_TOPMENU_BACK1,array()),'');
		 if ($color) echo'<input type="text" class="colorthumb" disabled style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
		 else echo'';
		 }
		 if ($edit) echo'<br>('.$langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis").')';
		 echo'</td>';*/
	} else {
		$default = 'ffffff';
		echo'<tr class="oddeven">';
		echo'<td>'.$langs->trans("BackgroundColor").'</td>';
		echo'<td colspan="'.($colspan - 1).'">';
		//var_dump($conf->global->THEME_ELDY_BACKBODY);
		if ($edit) {
			echo$formother->selectColor(colorArrayToHex(colorStringToArray((getDolGlobalString('THEME_ELDY_BACKBODY') ? $conf->global->THEME_ELDY_BACKBODY : ''), array()), ''), 'THEME_ELDY_BACKBODY', '', 1, '', '', 'colorbackbody', $default).' ';
		} else {
			$color = colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_BACKBODY, array()), '');
			if ($color) {
				echo'<input type="text" class="colorthumb" disabled="disabled" style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
			} else {
				echo$langs->trans("Default");
			}
		}
		echo' &nbsp; <span class="nowraponall opacitymedium">'.$langs->trans("Default").'</span>: <strong>'.$default.'</strong> ';
		echo$form->textwithpicto('', $langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis"));
		echo'</td>';
		echo'</tr>';
	}

	// TextTitleColor for title of Pages
	if ($foruserprofile) {
	} else {
		$default = (empty($colortexttitlenotab) ? $langs->trans("Unknown") : colorArrayToHex(colorStringToArray($colortexttitlenotab)));

		echo'<tr class="oddeven">';
		echo'<td>'.$langs->trans("TextTitleColor").'</td>';
		echo'<td colspan="'.($colspan - 1).'">';
		if ($edit) {
			echo$formother->selectColor(colorArrayToHex(colorStringToArray((getDolGlobalString('THEME_ELDY_TEXTTITLENOTAB') ? $conf->global->THEME_ELDY_TEXTTITLENOTAB : ''), array()), ''), 'THEME_ELDY_TEXTTITLENOTAB', '', 1, '', '', 'colortexttitlenotab', $default).' ';
		} else {
			echo$formother->showColor($conf->global->THEME_ELDY_TEXTTITLENOTAB, $langs->trans("Default"));
		}
		echo' &nbsp; <span class="nowraponall opacitymedium">'.$langs->trans("Default").'</span>: <strong><span style="color: #'.$default.'">'.$default.'</span></strong> ';
		echo$form->textwithpicto('', $langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis"));
		echo'</td>';

		echo'</tr>';
	}

	// BackgroundTableTitleColor
	if ($foruserprofile) {
	} else {
		$default = (empty($colorbacktitle1) ? $langs->trans("Unknown") : colorArrayToHex(colorStringToArray($colorbacktitle1)));

		echo'<tr class="oddeven">';
		echo'<td>'.$langs->trans("BackgroundTableTitleColor").'</td>';
		echo'<td colspan="'.($colspan - 1).'">';
		if ($edit) {
			echo$formother->selectColor(colorArrayToHex(colorStringToArray((getDolGlobalString('THEME_ELDY_BACKTITLE1') ? $conf->global->THEME_ELDY_BACKTITLE1 : ''), array()), ''), 'THEME_ELDY_BACKTITLE1', '', 1, '', '', 'colorbacktitle1', $default).' ';
		} else {
			echo$formother->showColor($conf->global->THEME_ELDY_BACKTITLE1, $langs->trans("Default"));
		}
		echo' &nbsp; <span class="nowraponall opacitymedium">'.$langs->trans("Default").'</span>: <strong>'.$default.'</strong> '; // $colorbacktitle1 in CSS
		echo$form->textwithpicto('', $langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis"));
		echo'</td>';

		echo'</tr>';
	}

	// TextTitleColor
	if ($foruserprofile) {
	} else {
		$default = (empty($colortexttitle) ? $langs->trans("Unknown") : colorArrayToHex(colorStringToArray($colortexttitle)));

		echo'<tr class="oddeven">';
		echo'<td>'.$langs->trans("BackgroundTableTitleTextColor").'</td>';
		echo'<td colspan="'.($colspan - 1).'">';
		if ($edit) {
			echo$formother->selectColor(colorArrayToHex(colorStringToArray((getDolGlobalString('THEME_ELDY_TEXTTITLE') ? $conf->global->THEME_ELDY_TEXTTITLE : ''), array()), ''), 'THEME_ELDY_TEXTTITLE', '', 1, '', '', 'colortexttitle', $default).' ';
		} else {
			echo$formother->showColor($conf->global->THEME_ELDY_TEXTTITLE, $langs->trans("Default"));
		}
		echo' &nbsp; <span class="nowraponall opacitymedium">'.$langs->trans("Default").'</span>: <strong><span style="color: #'.$default.'">'.$default.'</span></strong> ';
		echo$form->textwithpicto('', $langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis"));
		echo'</td>';

		echo'</tr>';
	}

	// TextTitleLinkColor
	if ($foruserprofile) {
	} else {
		$default = (empty($colortexttitlelink) ? $langs->trans("Unknown") : colorArrayToHex(colorStringToArray($colortexttitlelink)));

		echo'<tr class="oddeven">';
		echo'<td>'.$langs->trans("BackgroundTableTitleTextlinkColor").'</td>';
		echo'<td colspan="'.($colspan - 1).'">';
		if ($edit) {
			echo$formother->selectColor(colorArrayToHex(colorStringToArray((getDolGlobalString('THEME_ELDY_TEXTTITLELINK') ? $conf->global->THEME_ELDY_TEXTTITLELINK : ''), array()), ''), 'THEME_ELDY_TEXTTITLELINK', '', 1, '', '', 'colortexttitlelink', $default).' ';
		} else {
			echo$formother->showColor($conf->global->THEME_ELDY_TEXTTITLELINK, $langs->trans("Default"));
		}
		echo' &nbsp; <span class="nowraponall opacitymedium">'.$langs->trans("Default").'</span>: <strong><span style="color: #'.$default.'">'.$default.'</span></strong> ';
		echo$form->textwithpicto('', $langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis"));
		echo'</span>';
		echo'</td>';

		echo'</tr>';
	}

	// BackgroundTableLineOddColor
	if ($foruserprofile) {
	} else {
		$default = (empty($colorbacklineimpair1) ? $langs->trans("Unknown") : colorArrayToHex(colorStringToArray($colorbacklineimpair1)));

		echo'<tr class="oddeven">';
		echo'<td>'.$langs->trans("BackgroundTableLineOddColor").'</td>';
		echo'<td colspan="'.($colspan - 1).'">';
		if ($edit) {
			echo$formother->selectColor(colorArrayToHex(colorStringToArray((getDolGlobalString('THEME_ELDY_LINEIMPAIR1') ? $conf->global->THEME_ELDY_LINEIMPAIR1 : ''), array()), ''), 'THEME_ELDY_LINEIMPAIR1', '', 1, '', '', 'colorbacklineimpair2', $default).' ';
		} else {
			$color = colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_LINEIMPAIR1, array()), '');
			if ($color) {
				echo'<input type="text" class="colorthumb" disabled="disabled" style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
			} else {
				echo$langs->trans("Default");
			}
		}
		echo' &nbsp; <span class="nowraponall opacitymedium">'.$langs->trans("Default").'</span>: <strong>'.$default.'</strong> ';
		echo$form->textwithpicto('', $langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis"));
		echo'</td>';
		echo'</tr>';
	}

	// BackgroundTableLineEvenColor
	if ($foruserprofile) {
	} else {
		$default = (empty($colorbacklinepair1) ? $langs->trans("Unknown") : colorArrayToHex(colorStringToArray($colorbacklinepair1)));

		echo'<tr class="oddeven">';
		echo'<td>'.$langs->trans("BackgroundTableLineEvenColor").'</td>';
		echo'<td colspan="'.($colspan - 1).'">';
		if ($edit) {
			echo$formother->selectColor(colorArrayToHex(colorStringToArray((getDolGlobalString('THEME_ELDY_LINEPAIR1') ? $conf->global->THEME_ELDY_LINEPAIR1 : ''), array()), ''), 'THEME_ELDY_LINEPAIR1', '', 1, '', '', 'colorbacklinepair2', $default).' ';
		} else {
			$color = colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_LINEPAIR1, array()), '');
			if ($color) {
				echo'<input type="text" class="colorthumb" disabled="disabled" style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
			} else {
				echo$langs->trans("Default");
			}
		}
		echo' &nbsp; <span class="nowraponall opacitymedium">'.$langs->trans("Default").'</span>: <strong>'.$default.'</strong> ';
		echo$form->textwithpicto('', $langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis"));
		echo'</td>';
		echo'</tr>';
	}

	// Text LinkColor
	if ($foruserprofile) {
		/*
		 echo'<tr class="oddeven">';
		 echo'<td>'.$langs->trans("TopMenuBackgroundColor").'</td>';
		 echo'<td>'.($conf->global->THEME_ELDY_TOPMENU_BACK1?$conf->global->THEME_ELDY_TEXTLINK:$langs->trans("Default")).'</td>';
		 echo'<td class="nowrap left" width="20%"><input name="check_THEME_ELDY_TEXTLINK" id="check_THEME_ELDY_TEXTLINK" type="checkbox" '.(!empty($object->conf->THEME_ELDY_TEXTLINK)?" checked":"");
		 echo(empty($gestimag_main_demo) && $edit)?'':' disabled="disabled"';	// Disabled for demo
		 echo'> '.$langs->trans("UsePersonalValue").'</td>';
		 echo'<td>';
		 if ($edit)
		 {
		 echo$formother->selectColor(colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_TEXTLINK,array()),''),'THEME_ELDY_TEXTLINK','',1).' ';
		 }
		 else
		 {
		 $color = colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_TEXTLINK,array()),'');
		 if ($color) echo'<input type="text" class="colorthumb" disabled style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
		 else echo'';
		 }
			if ($edit) echo'<br>('.$langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis").')';
			echo'</td>';*/
	} else {
		$default = (empty($colortextlink) ? $langs->trans("Unknown") : colorArrayToHex(colorStringToArray($colortextlink)));

		echo'<tr class="oddeven">';
		echo'<td>'.$langs->trans("LinkColor").'</td>';
		echo'<td colspan="'.($colspan - 1).'">';
		if ($edit) {
			echo$formother->selectColor(colorArrayToHex(colorStringToArray((getDolGlobalString('THEME_ELDY_TEXTLINK') ? $conf->global->THEME_ELDY_TEXTLINK : ''), array()), ''), 'THEME_ELDY_TEXTLINK', '', 1, '', '', 'colortextlink', $default).' ';
		} else {
			$color = colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_TEXTLINK, array()), '');
			if ($color) {
				echo'<input type="text" class="colorthumb" disabled="disabled" style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
			} else {
				//echo'<input type="text" class="colorthumb" disabled="disabled" style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$defaultcolor.'" value="'.$langs->trans("Default").'">';
				//echo'<span style="color: #000078">'.$langs->trans("Default").'</span>';
				echo$langs->trans("Default");
			}
		}
		echo' &nbsp; <span class="nowraponall opacitymedium">'.$langs->trans("Default").'</span>: <strong><span style="color: #'.$default.'">'.$default.'</span></strong> ';
		echo$form->textwithpicto('', $langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis"));
		echo'</td>';
		echo'</tr>';
	}

	// Use Hover
	if ($foruserprofile) {
		/* Must first change option to choose color of highlight instead of yes or no.
		 echo'<tr class="oddeven">';
		 echo'<td>'.$langs->trans("HighlightLinesOnMouseHover").'</td>';
		 echo'<td><input name="check_THEME_ELDY_USE_HOVER" disabled="disabled" type="checkbox" '.($conf->global->THEME_ELDY_USE_HOVER?" checked":"").'></td>';
		 echo'<td class="nowrap left" width="20%"><input name="check_MAIN_THEME"'.($edit?'':' disabled').' type="checkbox" '.($selected_theme?" checked":"").'> '.$langs->trans("UsePersonalValue").'</td>';
		 echo'<td><input name="check_THEME_ELDY_USE_HOVER"'.($edit?'':' disabled="disabled"').' type="checkbox" '.($hoverdisabled?"":" checked").'>';
		 echo' &nbsp; ('.$langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis").')';
		 echo'</td>';
		 echo'</tr>';
		 */
	} else {
		$default = (empty($colorbacklinepairhover) ? $langs->trans("Unknown") : colorArrayToHex(colorStringToArray($colorbacklinepairhover)));

		echo'<tr class="oddeven">';
		echo'<td>'.$langs->trans("HighlightLinesColor").'</td>';
		echo'<td colspan="'.($colspan - 1).'">';
		//echo'<input name="check_THEME_ELDY_USE_HOVER"'.($edit?'':' disabled').' type="checkbox" '.($hoverdisabled?"":" checked").'>';
		//echo' &nbsp; ('.$langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis").')';
		if ($edit) {
			if (getDolGlobalString('THEME_ELDY_USE_HOVER') == '1') {
				$color = colorArrayToHex(colorStringToArray($colorbacklinepairhover));
			} else {
				$color = colorArrayToHex(colorStringToArray((getDolGlobalString('THEME_ELDY_USE_HOVER') ? $conf->global->THEME_ELDY_USE_HOVER : ''), array()), '');
			}
			echo$formother->selectColor($color, 'THEME_ELDY_USE_HOVER', '', 1, '', '', 'colorbacklinepairhover', $default).' ';
		} else {
			if (getDolGlobalString('THEME_ELDY_USE_HOVER') == '1') {
				$color = colorArrayToHex(colorStringToArray($colorbacklinepairhover));
			} else {
				$color = colorArrayToHex(colorStringToArray((getDolGlobalString('THEME_ELDY_USE_HOVER') ? $conf->global->THEME_ELDY_USE_HOVER : ''), array()), '');
			}
			if ($color) {
				if ($color != colorArrayToHex(colorStringToArray($colorbacklinepairhover))) {
					echo'<input type="text" class="colorthumb" disabled="disabled" style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
				} else {
					echo$langs->trans("Default");
				}
			} else {
				echo$langs->trans("Default");
			}
		}
		echo' &nbsp; <span class="nowraponall opacitymedium">'.$langs->trans("Default").'</span>: <strong>'.$default.'</strong> ';
		echo$form->textwithpicto('', $langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis"));
		echo'</td>';
	}

	// Use Checked
	if ($foruserprofile) {
		/* Must first change option to choose color of highlight instead of yes or no.
		echo'<tr class="oddeven">';
		echo'<td>'.$langs->trans("HighlightLinesOnMouseHover").'</td>';
		echo'<td><input name="check_THEME_ELDY_USE_HOVER" disabled="disabled" type="checkbox" '.($conf->global->THEME_ELDY_USE_HOVER?" checked":"").'></td>';
		echo'<td class="nowrap left" width="20%"><input name="check_MAIN_THEME"'.($edit?'':' disabled').' type="checkbox" '.($selected_theme?" checked":"").'> '.$langs->trans("UsePersonalValue").'</td>';
		echo'<td><input name="check_THEME_ELDY_USE_HOVER"'.($edit?'':' disabled="disabled"').' type="checkbox" '.($hoverdisabled?"":" checked").'>';
		echo' &nbsp; ('.$langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis").')';
		echo'</td>';
		echo'</tr>';
		*/
	} else {
		$default = (empty($colorbacklinepairchecked) ? $langs->trans("Unknown") : colorArrayToHex(colorStringToArray($colorbacklinepairchecked)));

		echo'<tr class="oddeven">';
		echo'<td>'.$langs->trans("HighlightLinesChecked").'</td>';
		echo'<td colspan="'.($colspan - 1).'">';
		//echo'<input name="check_THEME_ELDY_USE_HOVER"'.($edit?'':' disabled').' type="checkbox" '.($hoverdisabled?"":" checked").'>';
		//echo' &nbsp; ('.$langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis").')';
		if ($edit) {
			if (getDolGlobalString('THEME_ELDY_USE_CHECKED') == '1') {
				$color = 'e6edf0';
			} else {
				$color = colorArrayToHex(colorStringToArray((getDolGlobalString('THEME_ELDY_USE_CHECKED') ? $conf->global->THEME_ELDY_USE_CHECKED : ''), array()), '');
			}
			echo$formother->selectColor($color, 'THEME_ELDY_USE_CHECKED', '', 1, '', '', 'colorbacklinepairchecked', $default).' ';
		} else {
			if (getDolGlobalString('THEME_ELDY_USE_CHECKED') == '1') {
				$color = 'e6edf0';
			} else {
				$color = colorArrayToHex(colorStringToArray((getDolGlobalString('THEME_ELDY_USE_CHECKED') ? $conf->global->THEME_ELDY_USE_CHECKED : ''), array()), '');
			}
			if ($color) {
				if ($color != 'e6edf0') {
					echo'<input type="text" class="colorthumb" disabled="disabled" style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
				} else {
					echo$langs->trans("Default");
				}
			} else {
				echo$langs->trans("Default");
			}
		}
		echo' &nbsp; <span class="nowraponall opacitymedium">'.$langs->trans("Default").'</span>: <strong>'.$default.'</strong> ';
		echo$form->textwithpicto('', $langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis"));
		echo'</td>';
		echo'</tr>';
	}

	// Btn action
	if ($foruserprofile) {
		/*
		 echo'<tr class="oddeven">';
		 echo'<td>'.$langs->trans("TopMenuBackgroundColor").'</td>';
		 echo'<td>'.($conf->global->THEME_ELDY_TOPMENU_BACK1?$conf->global->THEME_ELDY_BTNACTION:$langs->trans("Default")).'</td>';
		 echo'<td class="nowrap left" width="20%"><input name="check_THEME_ELDY_BTNACTION" id="check_THEME_ELDY_BTNACTION" type="checkbox" '.(!empty($object->conf->THEME_ELDY_BTNACTION)?" checked":"");
		 echo(empty($gestimag_main_demo) && $edit)?'':' disabled="disabled"';	// Disabled for demo
		 echo'> '.$langs->trans("UsePersonalValue").'</td>';
		 echo'<td>';
		 if ($edit)
		 {
		 echo$formother->selectColor(colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_BTNACTION,array()),''),'THEME_ELDY_BTNACTION','',1).' ';
		 }
		 else
		 {
		 $color = colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_BTNACTION,array()),'');
		 if ($color) echo'<input type="text" class="colorthumb" disabled style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
		 else echo'';
		 }
			if ($edit) echo'<br>('.$langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis").')';
			echo'</td>';*/
	} else {
		$default = (empty($butactionbg) ? $langs->trans("Unknown") : colorArrayToHex(colorStringToArray($butactionbg)));

		echo'<tr class="oddeven">';
		echo'<td>'.$langs->trans("BtnActionColor").'</td>';
		echo'<td colspan="'.($colspan - 1).'">';
		if ($edit) {
			echo$formother->selectColor(colorArrayToHex(colorStringToArray((getDolGlobalString('THEME_ELDY_BTNACTION') ? $conf->global->THEME_ELDY_BTNACTION : ''), array()), ''), 'THEME_ELDY_BTNACTION', '', 1, '', '', 'butactionbg', $default).' ';
		} else {
			$color = colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_BTNACTION, array()), '');
			if ($color) {
				echo'<input type="text" class="colorthumb" disabled="disabled" style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
			} else {
				//echo'<input type="text" class="colorthumb" disabled="disabled" style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$defaultcolor.'" value="'.$langs->trans("Default").'">';
				//echo'<span style="color: #000078">'.$langs->trans("Default").'</span>';
				echo$langs->trans("Default");
			}
		}
		echo' &nbsp; <span class="nowraponall opacitymedium">'.$langs->trans("Default").'</span>: <strong><span style="color: #'.$default.'">'.$default.'</span></strong> ';
		echo$form->textwithpicto('', $langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis"));
		echo'</td>';
		echo'</tr>';
	}

	// Text btn action
	if ($foruserprofile) {
		/*
		 echo'<tr class="oddeven">';
		 echo'<td>'.$langs->trans("TopMenuBackgroundColor").'</td>';
		 echo'<td>'.($conf->global->THEME_ELDY_TOPMENU_BACK1?$conf->global->THEME_ELDY_TEXTBTNACTION:$langs->trans("Default")).'</td>';
		 echo'<td class="nowrap left" width="20%"><input name="check_THEME_ELDY_TEXTBTNACTION" id="check_THEME_ELDY_TEXTBTNACTION" type="checkbox" '.(!empty($object->conf->THEME_ELDY_TEXTBTNACTION)?" checked":"");
		 echo(empty($gestimag_main_demo) && $edit)?'':' disabled="disabled"'; // Disabled for demo
		 echo'> '.$langs->trans("UsePersonalValue").'</td>';
		 echo'<td>';
		 if ($edit)
		 {
		 echo$formother->selectColor(colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_TEXTBTNACTION,array()),''),'THEME_ELDY_TEXTBTNACTION','',1).' ';
		 }
		 else
		 {
		 $color = colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_BTNACTION,array()),'');
		 if ($color) echo'<input type="text" class="colorthumb" disabled style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
		 else echo'';
		 }
			if ($edit) echo'<br>('.$langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis").')';
			echo'</td>';*/
	} else {
		$default = (empty($textbutaction) ? $langs->trans("Unknown") : colorArrayToHex(colorStringToArray($textbutaction)));

		echo'<tr class="oddeven">';
		echo'<td>'.$langs->trans("TextBtnActionColor").'</td>';
		echo'<td colspan="'.($colspan - 1).'">';
		if ($edit) {
			echo$formother->selectColor(colorArrayToHex(colorStringToArray((getDolGlobalString('THEME_ELDY_TEXTBTNACTION') ? $conf->global->THEME_ELDY_TEXTBTNACTION : ''), array()), ''), 'THEME_ELDY_TEXTBTNACTION', '', 1, '', '', 'textbutaction', $default).' ';
		} else {
			$color = colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_TEXTBTNACTION, array()), '');
			if ($color) {
				echo'<input type="text" class="colorthumb" disabled="disabled" style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
			} else {
				//echo'<input type="text" class="colorthumb" disabled="disabled" style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$defaultcolor.'" value="'.$langs->trans("Default").'">';
				//echo'<span style="color: #000078">'.$langs->trans("Default").'</span>';
				echo$langs->trans("Default");
			}
		}
		echo' &nbsp; <span class="nowraponall opacitymedium">'.$langs->trans("Default").'</span>: <strong><span style="color: #000">'.$default.'</span></strong> ';
		echo$form->textwithpicto('', $langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis"));
		echo'</td>';
		echo'</tr>';
	}

	// Use MAIN_OPTIMIZEFORTEXTBROWSER
	if ($foruserprofile) {
		//$default=yn($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER);
		$default = $langs->trans('No');
		echo'<tr class="oddeven">';
		echo'<td>'.$langs->trans("MAIN_OPTIMIZEFORTEXTBROWSER").'</td>';
		echo'<td colspan="'.($colspan - 1).'">';
		//echoajax_constantonoff("MAIN_OPTIMIZEFORTEXTBROWSER", array(), null, 0, 0, 1, 0);
		if ($edit) {
			echo$form->selectyesno('MAIN_OPTIMIZEFORTEXTBROWSER', (isset($fuser->conf->MAIN_OPTIMIZEFORTEXTBROWSER) ? $fuser->conf->MAIN_OPTIMIZEFORTEXTBROWSER : 0), 1);
		} else {
			if (!getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				echoyn(isset($fuser->conf->MAIN_OPTIMIZEFORTEXTBROWSER) ? $fuser->conf->MAIN_OPTIMIZEFORTEXTBROWSER : 0);
			} else {
				echoyn(1);
				if (empty($fuser->conf->MAIN_OPTIMIZEFORTEXTBROWSER)) {
					echo' ('.$langs->trans("ForcedByGlobalSetup").')';
				}
			}
		}
		echo' &nbsp; <span class="opacitymedium">'.$langs->trans("Default").'</span>: <strong>'.$default.'</strong> ';
		echo$form->textwithpicto('', $langs->trans("MAIN_OPTIMIZEFORTEXTBROWSERDesc"));
		echo'</td>';
		echo'</tr>';
	} else {
		//var_dump($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER);
		/*
		$default=$langs->trans('No');
		echo'<tr class="oddeven">';
		echo'<td>'.$langs->trans("MAIN_OPTIMIZEFORTEXTBROWSER").'</td>';
		echo'<td colspan="'.($colspan-1).'">';
		if ($edit) {
			echo$form->selectyesno('MAIN_OPTIMIZEFORTEXTBROWSER', $conf->global->MAIN_OPTIMIZEFORTEXTBROWSER, 1);
		} else {
			echoyn($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER);
		}
		echo' &nbsp; wspan class="opacitymedium">'.$langs->trans("Default").'</span>: <strong>'.$default.'</strong> ';
		echo$form->textwithpicto('', $langs->trans("MAIN_OPTIMIZEFORTEXTBROWSERDesc"));
		echo'</span>';
		echo'</td>';
		echo'</tr>';
		*/
	}


	// Use MAIN_OPTIMIZEFORCOLORBLIND
	if ($foruserprofile) {
		//$default=yn($conf->global->MAIN_OPTIMIZEFORCOLORBLIND);
		$default = $langs->trans('No');
		echo'<tr class="oddeven">';
		echo'<td>'.$langs->trans("MAIN_OPTIMIZEFORCOLORBLIND").'</td>';
		echo'<td colspan="'.($colspan - 1).'">';

		$colorBlindOptions = array(
			0 => $langs->trans('No'),
			'flashy' => $langs->trans('Flashy'),
			'protanopia' => $langs->trans('Protanopia'),
			'deuteranopes' => $langs->trans('Deuteranopes'),
			'tritanopes' => $langs->trans('Tritanopes'),
		);

		if ($edit) {
			echo$form->selectArray('MAIN_OPTIMIZEFORCOLORBLIND', $colorBlindOptions, (isset($fuser->conf->MAIN_OPTIMIZEFORCOLORBLIND) ? $fuser->conf->MAIN_OPTIMIZEFORCOLORBLIND : 0), 0);
		} else {
			if (!empty($fuser->conf->MAIN_OPTIMIZEFORCOLORBLIND) && isset($colorBlindOptions[$fuser->conf->MAIN_OPTIMIZEFORCOLORBLIND])) {
				echo$colorBlindOptions[$fuser->conf->MAIN_OPTIMIZEFORCOLORBLIND];
			} else {
				echoyn(0);
			}
		}
		echo' &nbsp; <span class="opacitymedium">'.$langs->trans("Default").'</span>: <strong>'.$default.'</strong> ';
		echo$form->textwithpicto('', $langs->trans("MAIN_OPTIMIZEFORCOLORBLINDDesc"));
		echo'</td>';
		echo'</tr>';
	} else {
	}
	echo'</table>';
	echo'</div>';
}
