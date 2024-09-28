<?php
/* Copyright (C) 2013-2016  Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014-2015  Frederic France      <frederic.france@free.fr>
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
 *      \file       htdocs/printing/admin/printing.php
 *      \ingroup    printing
 *      \brief      Page to setup printing module
 */

// Load Gestimag environment
require '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/printing/modules_printing.php';
require_once DOL_DOCUMENT_ROOT.'/printing/lib/printing.lib.php';
use OAuth\Common\Storage\DoliStorage;

// Load translation files required by the page
$langs->loadLangs(array('admin', 'printing', 'oauth'));

$action = GETPOST('action', 'aZ09');
$mode = GETPOST('mode', 'alpha');
$value = GETPOST('value', 'alpha', 0, null, null, 1); // The value may be __google__docs so we force disable of replace
$varname = GETPOST('varname', 'alpha');
$driver = GETPOST('driver', 'alpha');

if (!empty($driver)) {
	$langs->load($driver);
}

if (!$mode) {
	$mode = 'config';
}

$OAUTH_SERVICENAME_GOOGLE = 'Google';

if (!$user->admin) {
	accessforbidden();
}


/*
 * Action
 */

if (($mode == 'test' || $mode == 'setup') && empty($driver)) {
	setEventMessages($langs->trans('PleaseSelectaDriverfromList'), null);
	header("Location: ".$_SERVER['PHP_SELF'].'?mode=config');
	exit;
}

if ($action == 'setconst' && $user->admin) {
	$error = 0;
	$db->begin();
	foreach ($_POST['setupdriver'] as $setupconst) {
		//echo '<pre>'.print_r($setupconst, true).'</pre>';
		$result = gestimag_set_const($db, $setupconst['varname'], $setupconst['value'], 'chaine', 0, '', $conf->entity);
		if (!($result > 0)) {
			$error++;
		}
	}

	if (!$error) {
		$db->commit();
		setEventMessages($langs->trans("SetupSaved"), null);
	} else {
		$db->rollback();
		dol_print_error($db);
	}
	$action = '';
}

if ($action == 'setvalue' && $user->admin) {
	$db->begin();

	$result = gestimag_set_const($db, $varname, $value, 'chaine', 0, '', $conf->entity);
	if (!($result > 0)) {
		$error++;
	}

	if (!$error) {
		$db->commit();
		setEventMessages($langs->trans("SetupSaved"), null);
	} else {
		$db->rollback();
		dol_print_error($db);
	}
	$action = '';
}


/*
 * View
 */

$form = new Form($db);

llxHeader('', $langs->trans("PrintingSetup"));

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
echo load_fiche_titre($langs->trans("PrintingSetup"), $linkback, 'title_setup');

$head = printingAdminPrepareHead($mode);

if ($mode == 'setup' && $user->admin) {
	echo '<form method="post" action="'.$_SERVER["PHP_SELF"].'?mode=setup&amp;driver='.$driver.'" autocomplete="off">';
	echo '<input type="hidden" name="token" value="'.newToken().'">';
	echo '<input type="hidden" name="action" value="setconst">';

	echo dol_get_fiche_head($head, $mode, $langs->trans("ModuleSetup"), -1, 'technic');

	echo $langs->trans("PrintingDriverDesc".$driver)."<br><br>\n";

	echo '<table class="noborder centpercent">'."\n";
	echo '<tr class="liste_titre">';
	echo '<th>'.$langs->trans("Parameters").'</th>';
	echo '<th>'.$langs->trans("Value").'</th>';
	echo '<th>&nbsp;</th>';
	echo "</tr>\n";
	$submit_enabled = 0;

	if (!empty($driver)) {
		if (!empty($conf->modules_parts['printing'])) {
			$dirmodels = array_merge(array('/core/modules/printing/'), (array) $conf->modules_parts['printing']);
		} else {
			$dirmodels = array('/core/modules/printing/');
		}

		foreach ($dirmodels as $dir) {
			if (file_exists(dol_buildpath($dir, 0).$driver.'.modules.php')) {
				$classfile = dol_buildpath($dir, 0).$driver.'.modules.php';
				break;
			}
		}
		require_once $classfile;
		$classname = 'printing_'.$driver;
		$printer = new $classname($db);
		$langs->load('printing');

		$i = 0;
		$submit_enabled = 0;
		foreach ($printer->conf as $key) {
			switch ($key['type']) {
				case "text":
				case "password":
					echo '<tr class="oddeven">';
					echo '<td'.($key['required'] ? ' class=required' : '').'>'.$langs->trans($key['varname']).'</td>';
					echo '<td><input class="width100" type="'.(empty($key['type']) ? 'text' : $key['type']).'" name="setupdriver['.$i.'][value]" value="'.getDolGlobalString($key['varname']).'"';
					echo isset($key['moreattributes']) ? ' '.$key['moreattributes'] : '';
					echo '><input type="hidden" name="setupdriver['.$i.'][varname]" value="'.$key['varname'].'"></td>';
					echo '<td>&nbsp;'.($key['example'] != '' ? $langs->trans("Example").' : '.$key['example'] : '').'</td>';
					echo '</tr>'."\n";
					break;
				case "checkbox":
					echo '<tr class="oddeven">';
					echo '<td'.($key['required'] ? ' class=required' : '').'>'.$langs->trans($key['varname']).'</td>';
					echo '<td><input class="width100" type="'.(empty($key['type']) ? 'text' : $key['type']).'" name="setupdriver['.$i.'][value]" value="1" '.((getDolGlobalInt($key['varname'])) ? 'checked' : '');
					echo isset($key['moreattributes']) ? ' '.$key['moreattributes'] : '';
					echo '><input type="hidden" name="setupdriver['.$i.'][varname]" value="'.$key['varname'].'"></td>';
					echo '<td>&nbsp;'.($key['example'] != '' ? $langs->trans("Example").' : '.$key['example'] : '').'</td>';
					echo '</tr>'."\n";
					break;
				case "info":    // Google Api setup or Google OAuth Token
					echo '<tr class="oddeven">';
					echo '<td'.($key['required'] ? ' class=required' : '').'>';
					if ($key['varname'] == 'PRINTGCP_TOKEN_ACCESS') {
						echo $langs->trans("IsTokenGenerated");
					} else {
						echo $langs->trans($key['varname']);
					}
					echo '</td>';
					echo '<td>'.$langs->trans($key['info']).'</td>';
					echo '<td>';
					//var_dump($key);
					if ($key['varname'] == 'PRINTGCP_TOKEN_ACCESS') {
						// Delete remote tokens
						if (!empty($key['delete'])) {
							echo '<a class="button" href="'.$key['delete'].'">'.$langs->trans('DeleteAccess').'</a><br><br>';
						}
						// Request remote token
						echo '<a class="button" href="'.$key['renew'].'">'.$langs->trans('RequestAccess').'</a><br><br>';
						// Check remote access
						echo $langs->trans("ToCheckDeleteTokenOnProvider", $OAUTH_SERVICENAME_GOOGLE).': <a href="https://security.google.com/settings/security/permissions" target="_google">https://security.google.com/settings/security/permissions</a>';
					}
					echo '</td>';
					echo '</tr>'."\n";
					break;
				case "submit":
					if ($key['enabled']) {
						$submit_enabled = 1;
					}
					break;
			}
			$i++;

			if ($key['varname'] == 'PRINTGCP_TOKEN_ACCESS') {
				$keyforprovider = '';	// @BUG This must be set

				// Token
				echo '<tr class="oddeven">';
				echo '<td>'.$langs->trans("Token").'</td>';
				echo '<td colspan="2">';
				$tokenobj = null;
				// Gestimag storage
				$storage = new DoliStorage($db, $conf, $keyforprovider);
				try {
					$tokenobj = $storage->retrieveAccessToken($OAUTH_SERVICENAME_GOOGLE);
				} catch (Exception $e) {
					// Return an error if token not found
				}
				if (is_object($tokenobj)) {
					//var_dump($tokenobj);
					echo $tokenobj->getAccessToken().'<br>';
					//echo 'Refresh: '.$tokenobj->getRefreshToken().'<br>';
					//echo 'EndOfLife: '.$tokenobj->getEndOfLife().'<br>';
					//var_dump($tokenobj->getExtraParams());
					/*echo '<br>Extra: <br><textarea class="quatrevingtpercent">';
					echo ''.join(',',$tokenobj->getExtraParams());
					echo '</textarea>';*/
				}
				echo '</td>';
				echo '</tr>'."\n";
			}
		}
	} else {
		echo $langs->trans('PleaseSelectaDriverfromList');
	}

	echo '</table>';

	echo dol_get_fiche_end();

	if (!empty($driver)) {
		if ($submit_enabled) {
			echo '<div class="center"><input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("Save")).'"></div>';
		}
	}

	echo '</form>';
}
if ($mode == 'config' && $user->admin) {
	echo dol_get_fiche_head($head, $mode, $langs->trans("ModuleSetup"), -1, 'technic');

	echo $langs->trans("PrintingDesc")."<br><br>\n";

	echo '<table class="noborder centpercent">'."\n";

	echo '<tr class="liste_titre">';
	echo '<th>'.$langs->trans("Description").'</th>';
	echo '<th class="center">'.$langs->trans("Active").'</th>';
	echo '<th class="center">'.$langs->trans("Setup").'</th>';
	echo '<th class="center">'.$langs->trans("TargetedPrinter").'</th>';
	echo "</tr>\n";

	$object = new PrintingDriver($db);
	$result = $object->listDrivers($db, 10);

	if (!empty($conf->modules_parts['printing'])) {
		$dirmodels = array_merge(array('/core/modules/printing/'), (array) $conf->modules_parts['printing']);
	} else {
		$dirmodels = array('/core/modules/printing/');
	}

	foreach ($result as $tmpdriver) {
		foreach ($dirmodels as $dir) {
			if (file_exists(dol_buildpath($dir, 0).$tmpdriver.'.modules.php')) {
				$classfile = dol_buildpath($dir, 0).$tmpdriver.'.modules.php';
				break;
			}
		}
		require_once $classfile;
		$classname = 'printing_'.$tmpdriver;
		$printer = new $classname($db);
		$langs->load('printing');
		//echo '<pre>'.print_r($printer, true).'</pre>';

		echo '<tr class="oddeven">';
		echo '<td>'.img_picto('', $printer->picto).' '.$langs->trans($printer->desc).'</td>';
		echo '<td class="center">';
		if (!empty($conf->use_javascript_ajax)) {
			echo ajax_constantonoff($printer->active);
		} else {
			if (!getDolGlobalString($printer->conf)) {
				echo '<a href="'.$_SERVER['PHP_SELF'].'?action=setvalue&token='.newToken().'&varname='.urlencode($printer->active).'&value=1">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
			} else {
				echo '<a href="'.$_SERVER['PHP_SELF'].'?action=setvalue&token='.newToken().'&varname='.urlencode($printer->active).'&value=0">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
			}
		}
		echo '<td class="center"><a href="'.$_SERVER['PHP_SELF'].'?mode=setup&token='.newToken().'&driver='.urlencode($printer->name).'">'.img_picto('', 'setup').'</a></td>';
		echo '<td class="center"><a href="'.$_SERVER['PHP_SELF'].'?mode=test&token='.newToken().'&driver='.urlencode($printer->name).'">'.img_picto('', 'setup').'</a></td>';
		echo '</tr>'."\n";
	}

	echo '</table>';

	echo dol_get_fiche_end();
}

if ($mode == 'test' && $user->admin) {
	echo dol_get_fiche_head($head, $mode, $langs->trans("ModuleSetup"), -1, 'technic');

	echo $langs->trans('PrintTestDesc'.$driver)."<br><br>\n";

	echo '<table class="noborder centpercent">';
	if (!empty($driver)) {
		if (!empty($conf->modules_parts['printing'])) {
			$dirmodels = array_merge(array('/core/modules/printing/'), (array) $conf->modules_parts['printing']);
		} else {
			$dirmodels = array('/core/modules/printing/');
		}

		foreach ($dirmodels as $dir) {
			if (file_exists(dol_buildpath($dir, 0).$driver.'.modules.php')) {
				$classfile = dol_buildpath($dir, 0).$driver.'.modules.php';
				break;
			}
		}
		require_once $classfile;
		$classname = 'printing_'.$driver;
		$printer = new $classname($db);
		$langs->load($driver);
		$langs->load('printing');

		//echo '<pre>'.print_r($printer, true).'</pre>';
		if (count($printer->getlistAvailablePrinters())) {
			if ($printer->listAvailablePrinters() == 0) {
				echo $printer->resprint;
			} else {
				setEventMessages($printer->error, $printer->errors, 'errors');
			}
		} else {
			echo $langs->trans('PleaseConfigureDriverfromList');
		}
	} else {
		echo $langs->trans('PleaseSelectaDriverfromList');
	}
	echo '</table>';

	echo dol_get_fiche_end();
}

if ($mode == 'userconf' && $user->admin) {
	echo dol_get_fiche_head($head, $mode, $langs->trans("ModuleSetup"), -1, 'technic');

	echo $langs->trans('PrintUserConfDesc'.$driver)."<br><br>\n";

	echo '<table class="noborder centpercent">';
	echo '<tr class="liste_titre">';
	echo '<th>'.$langs->trans("User").'</th>';
	echo '<th>'.$langs->trans("PrintModule").'</th>';
	echo '<th>'.$langs->trans("PrintDriver").'</th>';
	echo '<th>'.$langs->trans("Printer").'</th>';
	echo '<th>'.$langs->trans("PrinterLocation").'</th>';
	echo '<th>'.$langs->trans("PrinterId").'</th>';
	echo '<th>'.$langs->trans("NumberOfCopy").'</th>';
	echo '<th class="center">'.$langs->trans("Delete").'</th>';
	echo "</tr>\n";
	$sql = 'SELECT p.rowid, p.printer_name, p.printer_location, p.printer_id, p.copy, p.module, p.driver, p.userid, u.login FROM '.MAIN_DB_PREFIX.'printing as p, '.MAIN_DB_PREFIX.'user as u WHERE p.userid=u.rowid';
	$resql = $db->query($sql);
	while ($row = $db->fetch_array($resql)) {
		echo '<tr class="oddeven">';
		echo '<td>'.$row['login'].'</td>';
		echo '<td>'.$row['module'].'</td>';
		echo '<td>'.$row['driver'].'</td>';
		echo '<td>'.$row['printer_name'].'</td>';
		echo '<td>'.$row['printer_location'].'</td>';
		echo '<td>'.$row['printer_id'].'</td>';
		echo '<td>'.$row['copy'].'</td>';
		echo '<td class="center">'.img_picto($langs->trans("Delete"), 'delete').'</td>';
		echo "</tr>\n";
	}
	echo '</table>';

	echo dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();
