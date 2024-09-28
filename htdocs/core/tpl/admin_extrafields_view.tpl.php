<?php
/* Copyright (C) 2010-2018	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2021	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2018-2023  Frédéric France     <frederic.france@netlogic.fr>
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

/* To call this template, you must define
 * $textobject
 * $langs
 * $extrafield
 * $elementtype
 */

// Protection to avoid direct call of template
if (empty($langs) || !is_object($langs)) {
	echo "Error, template page can't be called as URL";
	exit(1);
}
global $action, $form, $langs;

$langs->load("modulebuilder");

if ($action == 'delete') {
	$attributekey = GETPOST('attrname', 'aZ09');
	echo $form->formconfirm($_SERVER['PHP_SELF']."?attrname=$attributekey", $langs->trans("DeleteExtrafield"), $langs->trans("ConfirmDeleteExtrafield", $attributekey), "confirm_delete", '', 0, 1);
}

?>

<!-- BEGIN PHP TEMPLATE admin_extrafields_view.tpl.php -->
<?php

$title = '<span class="opacitymedium">'.$langs->trans("DefineHereComplementaryAttributes", empty($textobject) ? '' : $textobject).'</span><br>'."\n";
//if ($action != 'create' && $action != 'edit') {
$newcardbutton = '';
$newcardbutton .= dolGetButtonTitle($langs->trans('NewAttribute'), '', 'fa fa-plus-circle', $_SERVER["PHP_SELF"].'?action=create', '', 1);
/*} else {
	$newcardbutton = '';
}*/

echo '<div class="centpercent tagtable marginbottomonly">';
echo '<div class="tagtr">';
echo '<div class="tagtd inline-block valignmiddle hideonsmartphoneimp">'.$title.'</div>';
echo '<div class="tagtd right inline-block valignmiddle"">'.$newcardbutton.'</div>';
echo '</div>';
echo '</div>';

// Load $extrafields->attributes
$extrafields->fetch_name_optionals_label($elementtype);

echo '<div class="div-table-responsive">';
echo '<table summary="listofattributes" class="noborder centpercent small">';

echo '<tr class="liste_titre">';
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	echo '<td width="80">&nbsp;</td>';
}
echo '<td class="left">'.$langs->trans("Position");
echo '<span class="nowrap">';
echo img_picto('A-Z', '1downarrow.png');
echo '</span>';
echo '</td>';
echo '<td>'.$langs->trans("LabelOrTranslationKey").'</td>';
echo '<td>'.$langs->trans("TranslationString").'</td>';
echo '<td>'.$langs->trans("AttributeCode").'</td>';
echo '<td>'.$langs->trans("Type").'</td>';
echo '<td class="right">'.$langs->trans("Size").'</td>';
echo '<td>'.$langs->trans("ComputedFormula").'</td>';
echo '<td class="center">'.$langs->trans("Unique").'</td>';
echo '<td class="center">'.$langs->trans("Mandatory").'</td>';
echo '<td class="center">'.$form->textwithpicto($langs->trans("AlwaysEditable"), $langs->trans("EditableWhenDraftOnly")).'</td>';
echo '<td class="center">'.$form->textwithpicto($langs->trans("Visibility"), $langs->trans("VisibleDesc").'<br><br>'.$langs->trans("ItCanBeAnExpression")).'</td>';
echo '<td class="center">'.$form->textwithpicto($langs->trans("DisplayOnPdf"), $langs->trans("DisplayOnPdfDesc")).'</td>';
echo '<td class="center">'.$form->textwithpicto($langs->trans("Totalizable"), $langs->trans("TotalizableDesc")).'</td>';
echo '<td class="center">'.$form->textwithpicto($langs->trans("CssOnEdit"), $langs->trans("HelpCssOnEditDesc")).'</td>';
echo '<td class="center">'.$form->textwithpicto($langs->trans("CssOnView"), $langs->trans("HelpCssOnViewDesc")).'</td>';
echo '<td class="center">'.$form->textwithpicto($langs->trans("CssOnList"), $langs->trans("HelpCssOnListDesc")).'</td>';
if (isModEnabled('multicompany')) {
	echo '<td class="center">'.$langs->trans("Entity").'</td>';
}
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	echo '<td width="80">&nbsp;</td>';
}
echo "</tr>\n";

if (isset($extrafields->attributes[$elementtype]['type']) && is_array($extrafields->attributes[$elementtype]['type']) && count($extrafields->attributes[$elementtype]['type'])) {
	foreach ($extrafields->attributes[$elementtype]['type'] as $key => $value) {
		/*if (! (int) dol_eval($extrafields->attributes[$elementtype]['enabled'][$key], 1, 1, '1')) {
			// TODO Uncomment this to exclude extrafields of modules not enabled. Add a link to "Show extrafields disabled"
			// continue;
		}*/

		// Load language if required
		if (!empty($extrafields->attributes[$elementtype]['langfile'][$key])) {
			$langs->load($extrafields->attributes[$elementtype]['langfile'][$key]);
		}

		echo '<tr class="oddeven">';
		// Actions
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			echo '<td class="center nowraponall">';
			echo '<a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=edit&token='.newToken().'&attrname='.urlencode($key).'#formeditextrafield">'.img_edit().'</a>';
			echo '&nbsp; <a class="paddingleft" href="'.$_SERVER["PHP_SELF"].'?action=delete&token='.newToken().'&attrname='.urlencode($key).'">'.img_delete().'</a>';
			if ($extrafields->attributes[$elementtype]['type'][$key] == 'password' && !empty($extrafields->attributes[$elementtype]['param'][$key]['options']) && array_key_exists('dolcrypt', $extrafields->attributes[$elementtype]['param'][$key]['options'])) {
				echo '&nbsp; <a class="aaa" href="'.$_SERVER["PHP_SELF"].'?action=encrypt&token='.newToken().'&attrname='.urlencode($key).'" title="'.dol_escape_htmltag($langs->trans("ReEncryptDesc")).'">'.img_picto('', 'refresh').'</a>';
			}
			echo '</td>'."\n";
		}
		// Position
		echo "<td>".dol_escape_htmltag($extrafields->attributes[$elementtype]['pos'][$key])."</td>\n";
		// Label
		echo '<td title="'.dol_escape_htmltag($extrafields->attributes[$elementtype]['label'][$key]).'" class="tdoverflowmax150">'.dol_escape_htmltag($extrafields->attributes[$elementtype]['label'][$key])."</td>\n"; // We don't translate here, we want admin to know what is the key not translated value
		// Label translated
		echo '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv($extrafields->attributes[$elementtype]['label'][$key])).'">'.dol_escape_htmltag($langs->transnoentitiesnoconv($extrafields->attributes[$elementtype]['label'][$key]))."</td>\n";
		// Key
		echo '<td title="'.dol_escape_htmltag($key).'" class="tdoverflowmax100">'.dol_escape_htmltag($key)."</td>\n";
		// Type
		$typetoshow = $type2label[$extrafields->attributes[$elementtype]['type'][$key]];
		echo '<td title="'.dol_escape_htmltag($typetoshow).'" class="tdoverflowmax100">';
		echo getPictoForType($extrafields->attributes[$elementtype]['type'][$key]);
		echo dol_escape_htmltag($typetoshow);
		echo "</td>\n";
		// Size
		echo '<td class="right">'.dol_escape_htmltag($extrafields->attributes[$elementtype]['size'][$key])."</td>\n";
		// Computed field
		echo '<td class="tdoverflowmax100" title="'.dol_escape_htmltag($extrafields->attributes[$elementtype]['computed'][$key]).'">'.dol_escape_htmltag($extrafields->attributes[$elementtype]['computed'][$key])."</td>\n";
		// Is unique ?
		echo '<td class="center">'.yn($extrafields->attributes[$elementtype]['unique'][$key])."</td>\n";
		// Is mandatory ?
		echo '<td class="center">'.yn($extrafields->attributes[$elementtype]['required'][$key])."</td>\n";
		// Can always be editable ?
		echo '<td class="center">'.yn($extrafields->attributes[$elementtype]['alwayseditable'][$key])."</td>\n";
		// Visible
		echo '<td class="center tdoverflowmax100" title="'.dol_escape_htmltag($extrafields->attributes[$elementtype]['list'][$key]).'">'.dol_escape_htmltag($extrafields->attributes[$elementtype]['list'][$key])."</td>\n";
		// Print on PDF
		echo '<td class="center tdoverflowmax100" title="'.dol_escape_htmltag($extrafields->attributes[$elementtype]['printable'][$key]).'">'.dol_escape_htmltag($extrafields->attributes[$elementtype]['printable'][$key])."</td>\n";
		// Summable
		echo '<td class="center">'.yn($extrafields->attributes[$elementtype]['totalizable'][$key])."</td>\n";
		// CSS
		echo '<td class="center tdoverflowmax100" title="'.dol_escape_htmltag($extrafields->attributes[$elementtype]['css'][$key]).'">'.dol_escape_htmltag($extrafields->attributes[$elementtype]['css'][$key])."</td>\n";
		// CSS view
		echo '<td class="center tdoverflowmax100" title="'.dol_escape_htmltag($extrafields->attributes[$elementtype]['cssview'][$key]).'">'.dol_escape_htmltag($extrafields->attributes[$elementtype]['cssview'][$key])."</td>\n";
		// CSS list
		echo '<td class="center tdoverflowmax100" title="'.dol_escape_htmltag($extrafields->attributes[$elementtype]['csslist'][$key]).'">'.dol_escape_htmltag($extrafields->attributes[$elementtype]['csslist'][$key])."</td>\n";
		// Multicompany
		if (isModEnabled('multicompany')) {
			echo '<td class="center">';
			if (empty($extrafields->attributes[$elementtype]['entityid'][$key])) {
				echo $langs->trans("All");
			} else {
				global $multicompanylabel_cache;
				if (!is_array($multicompanylabel_cache)) {
					$multicompanylabel_cache = array();
				}
				if (empty($multicompanylabel_cache[$extrafields->attributes[$elementtype]['entityid'][$key]])) {
					global $mc;
					if (is_object($mc) && method_exists($mc, 'getInfo')) {
						$mc->getInfo($extrafields->attributes[$elementtype]['entityid'][$key]);
						$multicompanylabel_cache[$extrafields->attributes[$elementtype]['entityid'][$key]] = $mc->label ? $mc->label : $extrafields->attributes[$elementtype]['entityid'][$key];
					}
				}
				echo $multicompanylabel_cache[$extrafields->attributes[$elementtype]['entityid'][$key]];
			}
			echo '</td>';
		}
		// Actions
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			echo '<td class="right nowraponall">';
			echo '<a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=edit&token='.newToken().'&attrname='.urlencode($key).'#formeditextrafield">'.img_edit().'</a>';
			echo '&nbsp; <a class="paddingleft" href="'.$_SERVER["PHP_SELF"].'?action=delete&token='.newToken().'&attrname='.urlencode($key).'">'.img_delete().'</a>';
			if ($extrafields->attributes[$elementtype]['type'][$key] == 'password' && !empty($extrafields->attributes[$elementtype]['param'][$key]['options']) && array_key_exists('dolcrypt', $extrafields->attributes[$elementtype]['param'][$key]['options'])) {
				echo '&nbsp; <a class="aaa" href="'.$_SERVER["PHP_SELF"].'?action=encrypt&token='.newToken().'&attrname='.urlencode($key).'" title="'.dol_escape_htmltag($langs->trans("ReEncryptDesc")).'">'.img_picto('', 'refresh').'</a>';
			}
			echo '</td>'."\n";
		}
		echo "</tr>";
	}
} else {
	$colspan = 17;
	if (isModEnabled('multicompany')) {
		$colspan++;
	}

	echo '<tr class="oddeven">';
	echo '<td colspan="'.$colspan.'"><span class="opacitymedium">';
	echo $langs->trans("None");
	echo '</span></td>';
	echo '</tr>';
}

echo "</table>";
echo '</div>';
?>
<!-- END PHP TEMPLATE admin_extrafields_view.tpl.php -->
