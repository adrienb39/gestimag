<?php
/* Copyright (C) 2005-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/imports/index.php
 *       \ingroup    import
 *       \brief      Home page of import wizard
 */

require_once '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/imports/class/import.class.php';

// Load translation files required by the page
$langs->load("exports");

if (!$user->socid == 0) {
	accessforbidden();
}

$import = new Import($db);
$import->load_arrays($user);


/*
 * View
 */

$form = new Form($db);

llxHeader('', $langs->trans("ImportArea"), 'EN:Module_Imports_En|FR:Module_Imports|ES:M&oacute;dulo_Importaciones');

echo load_fiche_titre($langs->trans("ImportArea"));

echo $langs->trans("FormatedImportDesc1").'<br>';
echo '<br>';


echo '<div class="center">';
if (count($import->array_import_code)) {
	echo dolGetButtonTitle($langs->trans('NewImport'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/imports/import.php?leftmenu=import');
}
echo '</div>';
echo '<br>';


// List of available import format
echo '<div class="div-table-responsive-no-min">';
echo '<table class="noborder centpercent">';
echo '<tr class="liste_titre">';
echo '<td colspan="2">'.$langs->trans("AvailableFormats").'</td>';
echo '<td>'.$langs->trans("LibraryShort").'</td>';
echo '<td class="right">'.$langs->trans("LibraryVersion").'</td>';
echo '</tr>';

include_once DOL_DOCUMENT_ROOT.'/core/modules/import/modules_import.php';
$model = new ModeleImports();
$list = $model->listOfAvailableImportFormat($db);

foreach ($list as $key) {
	echo '<tr class="oddeven">';
	echo '<td width="16">'.img_picto_common($model->getDriverLabelForKey($key), $model->getPictoForKey($key)).'</td>';
	$text = $model->getDriverDescForKey($key);
	// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
	echo '<td>'.$form->textwithpicto($model->getDriverLabelForKey($key), $text).'</td>';
	echo '<td>'.$model->getLibLabelForKey($key).'</td>';
	echo '<td class="nowrap right">'.$model->getLibVersionForKey($key).'</td>';
	echo '</tr>';
}

echo '</table>';
echo '</div>';


// End of page
llxFooter();
$db->close();
