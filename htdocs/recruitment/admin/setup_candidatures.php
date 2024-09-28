<?php
/* Copyright (C) 2004-2020 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/recruitment/admin/setup_candidatures.php
 * \ingroup recruitment
 * \brief   Recruitment setup page for candidatures.
 */

// Load Gestimag environment
require '../../main.inc.php';

global $conf, $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT.'/recruitment/lib/recruitment.lib.php';
require_once DOL_DOCUMENT_ROOT."/recruitment/class/recruitmentjobposition.class.php";

// Translations
$langs->loadLangs(array("admin", "recruitment"));

// Access control
if (!$user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'recruitmentcandidature';

$arrayofparameters = array(
//	'RECRUITMENT_MYPARAM1'=>array('css'=>'minwidth200', 'enabled'=>1),
//	'RECRUITMENT_MYPARAM2'=>array('css'=>'minwidth500', 'enabled'=>1)
);

$error = 0;
$setupnotempty = 0;

$tmpobjectkey = GETPOST('object', 'aZ09');
$moduledir = 'recruitment';
$myTmpObjects = array();
$myTmpObjects['recruitmentcandidature'] = array('label' => 'RecruitmentCandidature', 'includerefgeneration' => 1, 'includedocgeneration' => 0, 'class' => 'RecruitmentCandidature');


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if ($action == 'updateMask') {
	$maskconst = GETPOST('maskconstcand', 'aZ09');
	$maskvalue = GETPOST('maskcand', 'alpha');

	if ($maskconst && preg_match('/_MASK$/', $maskconst)) {
		$res = gestimag_set_const($db, $maskconst, $maskvalue, 'chaine', 0, '', $conf->entity);
	}

	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} elseif ($action == 'specimen' && $tmpobjectkey) {
	$modele = GETPOST('module', 'alpha');

	$className = $myTmpObjects[$tmpobjectkey]['class'];
	$tmpobject = new $className($db);
	$tmpobject->initAsSpecimen();

	// Search template files
	$file = '';
	$classname = '';
	$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
	foreach ($dirmodels as $reldir) {
		$file = dol_buildpath($reldir."core/modules/mymodule/doc/pdf_".$modele."_".strtolower($tmpobjectkey).".modules.php", 0);
		if (file_exists($file)) {
			$classname = "pdf_".$modele;
			break;
		}
	}

	if ($classname !== '') {
		require_once $file;

		$module = new $classname($db);

		if ($module->write_file($tmpobject, $langs) > 0) {
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=".strtolower($tmpobjectkey)."&file=SPECIMEN.pdf");
			return;
		} else {
			setEventMessages($module->error, null, 'errors');
			dol_syslog($module->error, LOG_ERR);
		}
	} else {
		setEventMessages($langs->trans("ErrorModuleNotFound"), null, 'errors');
		dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
	}
} elseif ($action == 'set') {
	// Activate a model
	$ret = addDocumentModel($value, $type, $label, $scandir);
} elseif ($action == 'del') {
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		$constforval = 'RECRUITMENT_'.strtoupper($tmpobjectkey).'_ADDON_PDF';
		if (getDolGlobalString($constforval) == "$value") {
			gestimag_del_const($db, $constforval, $conf->entity);
		}
	}
} elseif ($action == 'setmod') {
	// TODO Check if numbering module chosen can be activated by calling method canBeActivated
	if (!empty($tmpobjectkey)) {
		$constforval = 'RECRUITMENT_'.strtoupper($tmpobjectkey)."_ADDON";

		gestimag_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity);
	}
} elseif ($action == 'setdoc') {
	// Set default model
	$constforval = 'RECRUITMENT_'.strtoupper($tmpobjectkey).'_ADDON_PDF';
	if (gestimag_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity)) {
		// The constant that was read before the new set
		// We therefore requires a variable to have a coherent view
		$conf->global->$constforval = $value;
	}

	// We disable/enable the document template (into llx_document_model table)
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
} elseif ($action == 'unsetdoc') {
	if (!empty($tmpobjectkey)) {
		$constforval = 'RECRUITMENT_'.strtoupper($tmpobjectkey).'_ADDON_PDF';
		gestimag_del_const($db, $constforval, $conf->entity);
	}
}



/*
 * View
 */

$form = new Form($db);

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

$page_name = "RecruitmentSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

echo load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = recruitmentAdminPrepareHead();
echo dol_get_fiche_head($head, 'settings_candidatures', '', -1, '');

// Setup page goes here
//echo  '<span class="opacitymedium">'.$langs->trans("RecruitmentSetupPage").'</span><br><br>';


if ($action == 'edit') {
	echo '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	echo '<input type="hidden" name="token" value="'.newToken().'">';
	echo '<input type="hidden" name="action" value="update">';

	echo '<table class="noborder centpercent">';
	echo '<tr class="liste_titre"><td class="titlefield">'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

	foreach ($arrayofparameters as $key => $val) {
		echo '<tr class="oddeven"><td>';
		$tooltiphelp = (($langs->trans($key.'Tooltip') != $key.'Tooltip') ? $langs->trans($key.'Tooltip') : '');
		echo $form->textwithpicto($langs->trans($key), $tooltiphelp);
		echo '</td><td><input name="'.$key.'"  class="flat '.(empty($val['css']) ? 'minwidth200' : $val['css']).'" value="'.getDolGlobalString($key).'"></td></tr>';
	}
	echo '</table>';

	echo '<br><div class="center">';
	echo '<input class="button button-save" type="submit" value="'.$langs->trans("Save").'">';
	echo '</div>';

	echo '</form>';
	echo '<br>';
} else {
	if (!empty($arrayofparameters)) {
		echo '<table class="noborder centpercent">';
		echo '<tr class="liste_titre"><td class="titlefield">'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

		foreach ($arrayofparameters as $key => $val) {
			$setupnotempty++;

			echo '<tr class="oddeven"><td>';
			$tooltiphelp = (($langs->trans($key.'Tooltip') != $key.'Tooltip') ? $langs->trans($key.'Tooltip') : '');
			echo $form->textwithpicto($langs->trans($key), $tooltiphelp);
			echo '</td><td>'.getDolGlobalString($key).'</td></tr>';
		}

		echo '</table>';

		echo '<div class="tabsAction">';
		echo '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&token='.newToken().'">'.$langs->trans("Modify").'</a>';
		echo '</div>';
	}
}


foreach ($myTmpObjects as $myTmpObjectKey => $myTmpObjectArray) {
	if ($myTmpObjectArray['includerefgeneration']) {
		/*
		 * Orders Numbering model
		 */
		$setupnotempty++;

		echo load_fiche_titre($langs->trans("NumberingModules", $myTmpObjectKey), '', '');

		echo '<table class="noborder centpercent">';
		echo '<tr class="liste_titre">';
		echo '<td>'.$langs->trans("Name").'</td>';
		echo '<td>'.$langs->trans("Description").'</td>';
		echo '<td class="nowrap">'.$langs->trans("Example").'</td>';
		echo '<td class="center" width="60">'.$langs->trans("Status").'</td>';
		echo '<td class="center" width="16">'.$langs->trans("ShortInfo").'</td>';
		echo '</tr>'."\n";

		clearstatcache();

		foreach ($dirmodels as $reldir) {
			$dir = dol_buildpath($reldir."core/modules/".$moduledir);

			if (is_dir($dir)) {
				$handle = opendir($dir);
				if (is_resource($handle)) {
					while (($file = readdir($handle)) !== false) {
						if (strpos($file, 'mod_'.strtolower($myTmpObjectKey).'_') === 0 && substr($file, dol_strlen($file) - 3, 3) == 'php') {
							$file = substr($file, 0, dol_strlen($file) - 4);

							require_once $dir.'/'.$file.'.php';

							$module = new $file($db);

							// Show modules according to features level
							if ($module->version == 'development' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) {
								continue;
							}
							if ($module->version == 'experimental' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1) {
								continue;
							}

							if ($module->isEnabled()) {
								dol_include_once('/'.$moduledir.'/class/'.strtolower($myTmpObjectKey).'.class.php');

								echo '<tr class="oddeven"><td>'.$module->name."</td><td>\n";
								echo $module->info($langs);
								echo '</td>';

								// Show example of numbering model
								echo '<td class="nowrap">';
								$tmp = $module->getExample();
								if (preg_match('/^Error/', $tmp)) {
									$langs->load("errors");
									echo '<div class="error">'.$langs->trans($tmp).'</div>';
								} elseif ($tmp == 'NotConfigured') {
									echo $langs->trans($tmp);
								} else {
									echo $tmp;
								}
								echo '</td>'."\n";

								echo '<td class="center">';
								$constforvar = 'RECRUITMENT_'.strtoupper($myTmpObjectKey).'_ADDON';
								if (getDolGlobalString($constforvar) == $file) {
									echo img_picto($langs->trans("Activated"), 'switch_on');
								} else {
									echo '<a href="'.$_SERVER["PHP_SELF"].'?action=setmod&token='.newToken().'&object='.strtolower($myTmpObjectKey).'&value='.urlencode($file).'">';
									echo img_picto($langs->trans("Disabled"), 'switch_off');
									echo '</a>';
								}
								echo '</td>';

								$className = $myTmpObjectArray['class'];
								$mytmpinstance = new $className($db);
								$mytmpinstance->initAsSpecimen();

								// Info
								$htmltooltip = '';
								$htmltooltip .= ''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';

								$nextval = $module->getNextValue($mytmpinstance);
								if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
									$htmltooltip .= ''.$langs->trans("NextValue").': ';
									if ($nextval) {
										if (preg_match('/^Error/', $nextval) || $nextval == 'NotConfigured') {
											$nextval = $langs->trans($nextval);
										}
										$htmltooltip .= $nextval.'<br>';
									} else {
										$htmltooltip .= $langs->trans($module->error).'<br>';
									}
								}

								echo '<td class="center">';
								echo $form->textwithpicto('', $htmltooltip, 1, 0);
								echo '</td>';

								echo "</tr>\n";
							}
						}
					}
					closedir($handle);
				}
			}
		}
		echo "</table><br>\n";
	}

	if ($myTmpObjectArray['includedocgeneration']) {
		/*
		 * Document templates generators
		 */
		$setupnotempty++;
		$type = strtolower($myTmpObjectKey);

		echo load_fiche_titre($langs->trans("DocumentModules", $myTmpObjectKey), '', '');

		// Load array def with activated templates
		$def = array();
		$sql = "SELECT nom";
		$sql .= " FROM ".MAIN_DB_PREFIX."document_model";
		$sql .= " WHERE type = '".$db->escape($type)."'";
		$sql .= " AND entity = ".$conf->entity;
		$resql = $db->query($sql);
		if ($resql) {
			$i = 0;
			$num_rows = $db->num_rows($resql);
			while ($i < $num_rows) {
				$array = $db->fetch_array($resql);
				if (is_array($array)) {
					array_push($def, $array[0]);
				}
				$i++;
			}
		} else {
			dol_print_error($db);
		}


		echo "<table class=\"noborder\" width=\"100%\">\n";
		echo "<tr class=\"liste_titre\">\n";
		echo '<td>'.$langs->trans("Name").'</td>';
		echo '<td>'.$langs->trans("Description").'</td>';
		echo '<td class="center" width="60">'.$langs->trans("Status")."</td>\n";
		echo '<td class="center" width="60">'.$langs->trans("Default")."</td>\n";
		echo '<td class="center" width="38">'.$langs->trans("ShortInfo").'</td>';
		echo '<td class="center" width="38">'.$langs->trans("Preview").'</td>';
		echo "</tr>\n";

		clearstatcache();
		$filelist = array();

		foreach ($dirmodels as $reldir) {
			foreach (array('', '/doc') as $valdir) {
				$realpath = $reldir."core/modules/".$moduledir.$valdir;
				$dir = dol_buildpath($realpath);

				if (is_dir($dir)) {
					$handle = opendir($dir);
					if (is_resource($handle)) {
						while (($file = readdir($handle)) !== false) {
							$filelist[] = $file;
						}
						closedir($handle);
						arsort($filelist);

						foreach ($filelist as $file) {
							if (preg_match('/\.modules\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file)) {
								if (file_exists($dir.'/'.$file)) {
									$name = substr($file, 4, dol_strlen($file) - 16);
									$classname = substr($file, 0, dol_strlen($file) - 12);

									require_once $dir.'/'.$file;
									$module = new $classname($db);

									$modulequalified = 1;
									if ($module->version == 'development' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) {
										$modulequalified = 0;
									}
									if ($module->version == 'experimental' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1) {
										$modulequalified = 0;
									}

									if ($modulequalified) {
										echo '<tr class="oddeven"><td width="100">';
										print(empty($module->name) ? $name : $module->name);
										echo "</td><td>\n";
										if (method_exists($module, 'info')) {
											echo $module->info($langs);
										} else {
											echo $module->description;
										}
										echo '</td>';

										// Active
										if (in_array($name, $def)) {
											echo '<td class="center">'."\n";
											echo '<a href="'.$_SERVER["PHP_SELF"].'?action=del&token='.newToken().'&value='.urlencode($name).'">';
											echo img_picto($langs->trans("Enabled"), 'switch_on');
											echo '</a>';
											echo '</td>';
										} else {
											echo '<td class="center">'."\n";
											echo '<a href="'.$_SERVER["PHP_SELF"].'?action=set&token='.newToken().'&value='.urlencode($name).'&scan_dir='.urlencode($module->scandir).'&label='.urlencode($module->name).'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
											echo "</td>";
										}

										// Default
										echo '<td class="center">';
										$constforvar = 'RECRUITMENT_'.strtoupper($myTmpObjectKey).'_ADDON';
										if (getDolGlobalString($constforvar) == $name) {
											//echo img_picto($langs->trans("Default"), 'on');
											// Even if choice is the default value, we allow to disable it. Replace this with previous line if you need to disable unset
											echo '<a href="'.$_SERVER["PHP_SELF"].'?action=unsetdoc&token='.newToken().'&object='.urlencode(strtolower($myTmpObjectKey)).'&value='.urlencode($name).'&scan_dir='.urlencode($module->scandir).'&label='.urlencode($module->name).'&type='.urlencode($type).'" alt="'.$langs->trans("Disable").'">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
										} else {
											echo '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;token='.newToken().'&object='.urlencode(strtolower($myTmpObjectKey)).'&value='.urlencode($name).'&scan_dir='.urlencode($module->scandir).'&label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
										}
										echo '</td>';

										// Info
										$htmltooltip = ''.$langs->trans("Name").': '.$module->name;
										$htmltooltip .= '<br>'.$langs->trans("Type").': '.($module->type ? $module->type : $langs->trans("Unknown"));
										if ($module->type == 'pdf') {
											$htmltooltip .= '<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
										}
										$htmltooltip .= '<br>'.$langs->trans("Path").': '.preg_replace('/^\//', '', $realpath).'/'.$file;

										$htmltooltip .= '<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
										$htmltooltip .= '<br>'.$langs->trans("Logo").': '.yn($module->option_logo, 1, 1);
										$htmltooltip .= '<br>'.$langs->trans("MultiLanguage").': '.yn($module->option_multilang, 1, 1);

										echo '<td class="center">';
										echo $form->textwithpicto('', $htmltooltip, 1, 0);
										echo '</td>';

										// Preview
										echo '<td class="center">';
										if ($module->type == 'pdf') {
											echo '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'&object='.$myTmpObjectKey.'">'.img_object($langs->trans("Preview"), 'generic').'</a>';
										} else {
											echo img_object($langs->trans("PreviewNotAvailable"), 'generic');
										}
										echo '</td>';

										echo "</tr>\n";
									}
								}
							}
						}
					}
				}
			}
		}

		echo '</table>';
	}
}

if (empty($setupnotempty)) {
	echo '<br>'.$langs->trans("NothingToSetup");
}

// Page end
echo dol_get_fiche_end();

llxFooter();
$db->close();
