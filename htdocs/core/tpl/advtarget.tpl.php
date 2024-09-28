<?php
/* Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 */
/*
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

if (isModEnabled('category') && $user->hasRight('categorie', 'lire')) {
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
}

echo '<script>
	$(document).ready(function() {

		// Click Function
		$(":button[name=addcontact]").click(function() {
				$(":hidden[name=action]").val("add");
				$("#find_customer").submit();
		});

		$(":button[name=loadfilter]").click(function() {
				$(":hidden[name=action]").val("loadfilter");
				$("#find_customer").submit();
		});

		$(":button[name=deletefilter]").click(function() {
				$(":hidden[name=action]").val("deletefilter");
				$("#find_customer").submit();
		});

		$(":button[name=savefilter]").click(function() {
				$(":hidden[name=action]").val("savefilter");
				$("#find_customer").submit();
		});

		$(":button[name=createfilter]").click(function() {
				$(":hidden[name=action]").val("createfilter");
				$("#find_customer").submit();
		});
	});
</script>';


echo load_fiche_titre($langs->trans("AdvTgtTitle"));

echo '<div class="tabBar">'."\n";
echo '<form name="find_customer" id="find_customer" action="'.$_SERVER['PHP_SELF'].'?id='.$id.'"  method="POST">'."\n";
echo '<input type="hidden" name="token" value="'.newToken().'">'."\n";
echo '<input type="hidden" name="action" value="">'."\n";
echo '<table class="border centpercent">'."\n";

echo '<tr>'."\n";
echo '<td colspan="3" class="right">'."\n";

echo '<input type="button" name="addcontact" id="addcontact" value="'.$langs->trans('AdvTgtAddContact').'" class="button"/>'."\n";

echo '</td>'."\n";
echo '</tr>'."\n";

echo '<tr><td>'.$langs->trans('AdvTgtNameTemplate').'</td><td class="valignmiddle">';
if (!empty($template_id)) {
	$default_template = $template_id;
} else {
	$default_template = $advTarget->id;
}
echo $formadvtargetemaling->selectAdvtargetemailingTemplate('template_id', $default_template, 0, $advTarget->type_element, 'valignmiddle');
echo '<input type="button" name="loadfilter" id="loadfilter" value="'.$langs->trans('AdvTgtLoadFilter').'" class="button"/>';
echo '<input type="button" name="deletefilter" id="deletefilter" value="'.$langs->trans('AdvTgtDeleteFilter').'" class="button"/>';
echo '<input type="button" name="savefilter" id="savefilter" value="'.$langs->trans('AdvTgtSaveFilter').'" class="button"/>';
echo '</td><td>'."\n";
echo '</td></tr>'."\n";

echo '<tr><td>'.$langs->trans('AdvTgtOrCreateNewFilter').'</td><td>';
echo '<input type="text" name="template_name" id="template_name" value=""/>';
echo '<input type="button" name="createfilter" id="createfilter" value="'.$langs->trans('AdvTgtCreateFilter').'" class="button"/>';
echo '</td><td>'."\n";
echo '</td></tr>'."\n";

echo '<tr><td>'.$langs->trans('AdvTgtTypeOfIncude').'</td><td>';
echo $form->selectarray('type_of_target', $advTarget->select_target_type, $array_query['type_of_target']);
echo '</td><td>'."\n";
echo $form->textwithpicto('', $langs->trans("AdvTgtTypeOfIncudeHelp"), 1, 'help');
echo '</td></tr>'."\n";

// Customer name
echo '<tr><td>'.$langs->trans('ThirdPartyName');
if (!empty($array_query['cust_name'])) {
	echo img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
echo '</td><td><input type="text" name="cust_name" value="'.$array_query['cust_name'].'"/></td><td>'."\n";
echo $form->textwithpicto('', $langs->trans("AdvTgtSearchTextHelp"), 1, 'help');
echo '</td></tr>'."\n";

// Code Client
echo '<tr><td>'.$langs->trans('CustomerCode');
if (!empty($array_query['cust_code'])) {
	echo img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
	$cust_code_str = (string) $array_query['cust_code'];
} else {
	$cust_code_str = null;
}
echo '</td><td><input type="text" name="cust_code"'.($cust_code_str!=null?' value="'.$cust_code_str:'').'"/></td><td>'."\n";
echo $form->textwithpicto('', $langs->trans("AdvTgtSearchTextHelp"), 1, 'help');
echo '</td></tr>'."\n";

// Address Client
echo '<tr><td>'.$langs->trans('Address');
if (!empty($array_query['cust_adress'])) {
	echo img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
echo '</td><td><input type="text" name="cust_adress" value="'.$array_query['cust_adress'].'"/></td><td>'."\n";
echo $form->textwithpicto('', $langs->trans("AdvTgtSearchTextHelp"), 1, 'help');
echo '</td></tr>'."\n";

// Zip Client
echo '<tr><td>'.$langs->trans('Zip');
if (!empty($array_query['cust_zip'])) {
	echo img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
echo '</td><td><input type="text" name="cust_zip" value="'.$array_query['cust_zip'].'"/></td><td>'."\n";
echo $form->textwithpicto('', $langs->trans("AdvTgtSearchTextHelp"), 1, 'help');
echo '</td></tr>'."\n";

// City Client
echo '<tr><td>'.$langs->trans('Town');
if (!empty($array_query['cust_city'])) {
	echo img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
echo '</td><td><input type="text" name="cust_city" value="'.$array_query['cust_city'].'"/></td><td>'."\n";
echo $form->textwithpicto('', $langs->trans("AdvTgtSearchTextHelp"), 1, 'help');
echo '</td></tr>'."\n";

// State Client
echo '<tr><td>'.$langs->trans('State');
if (!empty($array_query['cust_state'])) {
	echo img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
echo '</td><td>'."\n";
echo $formadvtargetemaling->multiselectState('cust_state', $array_query['cust_state']);
echo '</td><td>'."\n";
echo '</td></tr>'."\n";

// Customer Country
echo '<tr><td>'.$langs->trans("Country");
if (!empty($array_query['cust_country'])) {
	echo img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
echo '</td><td>'."\n";
echo $formadvtargetemaling->multiselectCountry('cust_country', $array_query['cust_country']);
echo '</td><td>'."\n";
echo '</td></tr>'."\n";

// State Customer
echo '<tr><td>'.$langs->trans('Status').' '.$langs->trans('ThirdParty');
if (!empty($array_query['cust_status'])) {
	echo img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
echo '</td><td>';
echo $formadvtargetemaling->advMultiselectarray(
	'cust_status',
	array(
		'0' => $langs->trans('ActivityCeased'),
		'1' => $langs->trans('InActivity')
	),
	$array_query['cust_status']
);
echo '</td><td>'."\n";
echo '</td></tr>'."\n";

// Mother Company
echo '<tr><td>'.$langs->trans("ParentCompany");
if (!empty($array_query['cust_mothercompany'])) {
	echo img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
echo '</td><td>'."\n";
echo '<input type="text" name="cust_mothercompany" value="'.$array_query['cust_mothercompany'].'"/>';
echo '</td><td>'."\n";
echo $form->textwithpicto('', $langs->trans("AdvTgtSearchTextHelp"), 1, 'help');
echo '</td></tr>'."\n";

// Prospect/Customer
$selected = $array_query['cust_typecust'];
echo '<tr><td>'.$langs->trans('ProspectCustomer').' '.$langs->trans('ThirdParty');
if (!empty($array_query['cust_typecust'])) {
	echo img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
echo '</td><td>';
$options_array = array(
	2 => $langs->trans('Prospect'),
	3 => $langs->trans('ProspectCustomer'),
	1 => $langs->trans('Customer'),
	0 => $langs->trans('NorProspectNorCustomer')
);
echo $formadvtargetemaling->advMultiselectarray('cust_typecust', $options_array, $array_query['cust_typecust']);
echo '</td><td>'."\n";
echo '</td></tr>'."\n";

// Prospection status
echo '<tr><td>'.$langs->trans('ProspectLevel');
if (!empty($array_query['cust_prospect_status'])) {
	echo img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
echo '</td><td>';
echo $formadvtargetemaling->multiselectProspectionStatus($array_query['cust_prospect_status'], 'cust_prospect_status');
echo '</td><td>'."\n";
echo '</td></tr>'."\n";

// Prospection comm status
echo '<tr><td>'.$langs->trans('StatusProsp');
if (!empty($array_query['cust_comm_status'])) {
	echo img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
echo '</td><td>';
echo $formadvtargetemaling->advMultiselectarray('cust_comm_status', $advTarget->type_statuscommprospect, $array_query['cust_comm_status']);
echo '</td><td>'."\n";
echo '</td></tr>'."\n";

// Customer Type
echo '<tr><td>'.$langs->trans("ThirdPartyType");
if (!empty($array_query['cust_typeent'])) {
	echo img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
echo '</td><td>'."\n";
echo $formadvtargetemaling->advMultiselectarray('cust_typeent', $formcompany->typent_array(0, " AND id <> 0"), $array_query['cust_typeent']);
echo '</td><td>'."\n";
echo '</td></tr>'."\n";

// Staff number
echo '<td>'.$langs->trans("Staff");
if (!empty($array_query['cust_effectif_id'])) {
	echo img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
echo '</td><td>';
echo $formadvtargetemaling->advMultiselectarray("cust_effectif_id", $formcompany->effectif_array(0, " AND id <> 0"), $array_query['cust_effectif_id']);
echo '</td><td>'."\n";
echo '</td></tr>'."\n";

// Sales manager
echo '<tr><td>'.$langs->trans("SalesRepresentatives");
if (!empty($array_query['cust_saleman'])) {
	echo img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
echo '</td><td>'."\n";
echo $formadvtargetemaling->multiselectselectSalesRepresentatives('cust_saleman', $array_query['cust_saleman'], $user);
echo '</td><td>'."\n";
echo '</td></tr>'."\n";

// Customer Default Language
if (getDolGlobalInt('MAIN_MULTILANGS')) {
	echo '<tr><td>'.$langs->trans("DefaultLang");
	if (!empty($array_query['cust_language'])) {
		echo img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
	}
	echo '</td><td>'."\n";
	echo $formadvtargetemaling->multiselectselectLanguage('cust_language', $array_query['cust_language']);
	echo '</td><td>'."\n";
	echo '</td></tr>'."\n";
}

if (isModEnabled('category') && $user->hasRight('categorie', 'lire')) {
	// Customer Categories
	echo '<tr><td>'.$langs->trans("CustomersCategoryShort");
	if (!empty($array_query['cust_categ'])) {
		echo img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
	}
	echo '</td><td>'."\n";
	$cate_arbo = $form->select_all_categories(Categorie::TYPE_CUSTOMER, null, 'parent', null, null, 1);
	echo $form->multiselectarray('cust_categ', $cate_arbo, GETPOST('cust_categ', 'array'), null, null, null, null, "90%");
	echo '</td><td>'."\n";
	echo '</td></tr>'."\n";
}

// Standard Extrafield feature
if (!getDolGlobalString('MAIN_EXTRAFIELDS_DISABLED')) {
	$socstatic = new Societe($db);
	$elementtype = $socstatic->table_element;
	// fetch optionals attributes and labels
	require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
	$extrafields = new ExtraFields($db);
	$extrafields->fetch_name_optionals_label($elementtype);
	foreach ($extrafields->attributes[$elementtype]['label'] as $key => $val) {
		if ($key != 'ts_nameextra' && $key != 'ts_payeur') {
			echo '<tr><td>'.$extrafields->attributes[$elementtype]['label'][$key];
			if (!empty($array_query['options_'.$key]) || (is_array($array_query['options_'.$key]) && count($array_query['options_'.$key]) > 0)) {
				echo img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
			}
			echo '</td><td>';
			if (($extrafields->attributes[$elementtype]['type'][$key] == 'varchar') || ($extrafields->attributes[$elementtype]['type'][$key] == 'text')) {
				echo '<input type="text" name="options_'.$key.'"/></td><td>'."\n";
				echo $form->textwithpicto('', $langs->trans("AdvTgtSearchTextHelp"), 1, 'help');
			} elseif (($extrafields->attributes[$elementtype]['type'][$key] == 'int') || ($extrafields->attributes[$elementtype]['type'][$key] == 'double')) {
				echo $langs->trans("AdvTgtMinVal").'<input type="text" name="options'.$key.'_min"/>';
				echo $langs->trans("AdvTgtMaxVal").'<input type="text" name="options'.$key.'_max"/>';
				echo '</td><td>'."\n";
				echo $form->textwithpicto('', $langs->trans("AdvTgtSearchIntHelp"), 1, 'help');
			} elseif (($extrafields->attributes[$elementtype]['type'][$key] == 'date') || ($extrafields->attributes[$elementtype]['type'][$key] == 'datetime')) {
				echo '<table class="nobordernopadding"><tr>';
				echo '<td>'.$langs->trans("AdvTgtStartDt").'</td><td>';
				echo $form->selectDate('', 'options_'.$key.'_st_dt');
				echo '</td><td>'.$langs->trans("AdvTgtEndDt").'</td><td>';
				echo $form->selectDate('', 'options_'.$key.'_end_dt');
				echo '</td></tr></table>';

				echo '</td><td>'."\n";
				echo $form->textwithpicto('', $langs->trans("AdvTgtSearchDtHelp"), 1, 'help');
			} elseif (($extrafields->attributes[$elementtype]['type'][$key] == 'boolean')) {
				echo $form->selectarray(
					'options_'.$key,
					array(
						'' => '',
						'1' => $langs->trans('Yes'),
						'0' => $langs->trans('No')
					),
					$array_query['options_'.$key]
				);
				echo '</td><td>'."\n";
			} elseif (($extrafields->attributes[$elementtype]['type'][$key] == 'select')) {
				echo $formadvtargetemaling->advMultiselectarray('options_'.$key, $extrafields->attributes[$key]['param']['options'], $array_query['options_'.$key]);
				echo '</td><td>'."\n";
			} elseif (($extrafields->attributes[$elementtype]['type'][$key] == 'sellist')) {
				echo $formadvtargetemaling->advMultiselectarraySelllist('options_'.$key, $extrafields->attributes[$key]['param']['options'], $array_query['options_'.$key]);
				echo '</td><td>'."\n";
			} else {
				echo '<table class="nobordernopadding"><tr>';
				echo '<td></td><td>';
				if (is_array($array_query['options_'.$key])) {
					echo $extrafields->showInputField($key, implode(',', $array_query['options_'.$key]));
				} else {
					echo $extrafields->showInputField($key, $array_query['options_'.$key]);
				}
				echo '</td></tr></table>';

				echo '</td><td>'."\n";
			}
			echo '</td></tr>'."\n";
		}
	}
} else {
	$std_soc = new Societe($db);
	$action_search = 'query';

	$parameters = array('advtarget' => 1);
	if (!empty($advTarget->id)) {
		$parameters = array('array_query' => $advTarget->filtervalue);
	}
	// Other attributes
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $std_soc, $action_search);
	echo $hookmanager->resPrint;
}

// State Contact
echo '<tr><td>'.$langs->trans('Status').' '.$langs->trans('Contact');
if (!empty($array_query['contact_status'])) {
	echo img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
echo '</td><td>';
echo $formadvtargetemaling->advMultiselectarray(
	'contact_status',
	array(
		'0' => $langs->trans('ActivityCeased'),
		'1' => $langs->trans('InActivity')
	),
	$array_query['contact_status']
);
echo '</td><td>'."\n";
echo $form->textwithpicto('', $langs->trans("AdvTgtContactHelp"), 1, 'help');
echo '</td></tr>'."\n";

// Civility
echo '<tr><td width="15%">'.$langs->trans("UserTitle");
if (!empty($array_query['contact_civility'])) {
	echo img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
echo '</td><td>';
echo $formadvtargetemaling->multiselectCivility('contact_civility', $array_query['contact_civility']);
echo '</td></tr>';

// contact name
echo '<tr><td>'.$langs->trans('Contact').' '.$langs->trans('Lastname');
if (!empty($array_query['contact_lastname'])) {
	echo img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
echo '</td><td><input type="text" name="contact_lastname" value="'.$array_query['contact_lastname'].'"/></td><td>'."\n";
echo $form->textwithpicto('', $langs->trans("AdvTgtSearchTextHelp"), 1, 'help');
echo '</td></tr>'."\n";
echo '<tr><td>'.$langs->trans('Contact').' '.$langs->trans('Firstname');
if (!empty($array_query['contact_firstname'])) {
	echo img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
echo '</td><td><input type="text" name="contact_firstname" value="'.$array_query['contact_firstname'].'"/></td><td>'."\n";
echo $form->textwithpicto('', $langs->trans("AdvTgtSearchTextHelp"), 1, 'help');
echo '</td></tr>'."\n";

// Contact Country
echo '<tr><td>'.$langs->trans('Contact').' '.$langs->trans("Country");
if (!empty($array_query['contact_country'])) {
	echo img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
echo '</td><td>'."\n";
echo $formadvtargetemaling->multiselectCountry('contact_country', $array_query['contact_country']);
echo '</td><td>'."\n";
echo '</td></tr>'."\n";

// Never send mass mailing
echo '<tr><td>'.$langs->trans('Contact').' '.$langs->trans("No_Email");
if (!empty($array_query['contact_no_email'])) {
	echo img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
echo '</td><td>'."\n";
echo $form->selectarray(
	'contact_no_email',
	array(
		'' => '',
		'1' => $langs->trans('Yes'),
		'0' => $langs->trans('No')
	),
	$array_query['contact_no_email']
);
echo '</td><td>'."\n";
echo '</td></tr>'."\n";

// Contact Date Create
echo '<tr><td>'.$langs->trans('Contact').' '.$langs->trans("DateCreation");
if (!empty($array_query['contact_create_st_dt'])) {
	echo img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
echo '</td><td>'."\n";
echo '<table class="nobordernopadding"><tr>';
echo '<td>'.$langs->trans("AdvTgtStartDt").'</td><td>';
echo $form->selectDate($array_query['contact_create_st_dt'], 'contact_create_st_dt', 0, 0, 1, 'find_customer', 1, 1);
echo '</td><td>'.$langs->trans("AdvTgtEndDt").'</td><td>';
echo $form->selectDate($array_query['contact_create_end_dt'], 'contact_create_end_dt', 0, 0, 1, 'find_customer', 1, 1);
echo '</td></tr></table>';
echo '</td><td>'."\n";
echo '</td></tr>'."\n";

// Contact update Create
echo '<tr><td>'.$langs->trans('Contact').' '.$langs->trans("DateLastModification");
if (!empty($array_query['contact_update_st_dt'])) {
	echo img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
echo '</td><td>'."\n";
echo '<table class="nobordernopadding"><tr>';
echo '<td>'.$langs->trans("AdvTgtStartDt").'</td><td>';
echo $form->selectDate($array_query['contact_update_st_dt'], 'contact_update_st_dt', 0, 0, 1, 'find_customer', 1, 1);
echo '</td><td>'.$langs->trans("AdvTgtEndDt").'</td><td>';
echo $form->selectDate($array_query['contact_update_end_dt'], 'contact_update_end_dt', 0, 0, 1, 'find_customer', 1, 1);
echo '</td></tr></table>';
echo '</td><td>'."\n";
echo '</td></tr>'."\n";

if (isModEnabled('category') && $user->hasRight('categorie', 'lire')) {
	// Customer Categories
	echo '<tr><td>'.$langs->trans("ContactCategoriesShort");
	if (!empty($array_query['contact_categ'])) {
		echo img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
	}
	echo '</td><td>'."\n";
	$cate_arbo = $form->select_all_categories(Categorie::TYPE_CONTACT, null, 'parent', null, null, 1);
	echo $form->multiselectarray('contact_categ', $cate_arbo, GETPOST('contact_categ', 'array'), null, null, null, null, "90%");
	echo '</td><td>'."\n";
	echo '</td></tr>'."\n";
}

// Standard Extrafield feature
if (!getDolGlobalString('MAIN_EXTRAFIELDS_DISABLED')) {
	$contactstatic = new Contact($db);
	$elementype = $contactstatic->table_element;
	// fetch optionals attributes and labels
	dol_include_once('/core/class/extrafields.class.php');
	$extrafields = new ExtraFields($db);
	$extrafields->fetch_name_optionals_label($elementype);
	if (!empty($extrafields->attributes[$elementtype]['type'])) {
		foreach ($extrafields->attributes[$elementtype]['type'] as $key => &$value) {
			if ($value == 'radio') {
				$value = 'select';
			}
		}
	}
	if (!empty($extrafields->attributes[$elementtype]['label'])) {
		foreach ($extrafields->attributes[$elementtype]['label'] as $key => $val) {
			echo '<tr><td>'.$extrafields->attributes[$elementtype]['label'][$key];
			if ($array_query['options_'.$key.'_cnct'] != '' || (is_array($array_query['options_'.$key.'_cnct']) && count($array_query['options_'.$key.'_cnct']) > 0)) {
				echo img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
			}
			echo '</td><td>';
			if (($extrafields->attributes[$elementtype]['type'][$key] == 'varchar') || ($extrafields->attributes[$elementtype]['type'][$key] == 'text')) {
				echo '<input type="text" name="options_'.$key.'_cnct"/></td><td>'."\n";
				echo $form->textwithpicto('', $langs->trans("AdvTgtSearchTextHelp"), 1, 'help');
			} elseif (($extrafields->attributes[$elementtype]['type'][$key] == 'int') || ($extrafields->attributes[$elementtype]['type'][$key] == 'double')) {
				echo $langs->trans("AdvTgtMinVal").'<input type="text" name="options_'.$key.'_min_cnct"/>';
				echo $langs->trans("AdvTgtMaxVal").'<input type="text" name="options_'.$key.'_max_cnct"/>';
				echo '</td><td>'."\n";
				echo $form->textwithpicto('', $langs->trans("AdvTgtSearchIntHelp"), 1, 'help');
			} elseif (($extrafields->attributes[$elementtype]['type'][$key] == 'date') || ($extrafields->attributes[$elementtype]['type'][$key] == 'datetime')) {
				echo '<table class="nobordernopadding"><tr>';
				echo '<td>'.$langs->trans("AdvTgtStartDt").'</td><td>';
				echo $form->selectDate('', 'options_'.$key.'_st_dt_cnct');
				echo '</td><td>'.$langs->trans("AdvTgtEndDt").'</td><td>';
				echo $form->selectDate('', 'options_'.$key.'_end_dt_cnct');
				echo '</td></tr></table>';
				echo '</td><td>'."\n";
				echo $form->textwithpicto('', $langs->trans("AdvTgtSearchDtHelp"), 1, 'help');
			} elseif (($extrafields->attributes[$elementtype]['type'][$key] == 'boolean')) {
				echo $form->selectarray(
					'options_'.$key.'_cnct',
					array(
						''  => '',
						'1' => $langs->trans('Yes'),
						'0' => $langs->trans('No')
					),
					$array_query['options_'.$key.'_cnct']
				);
				echo '</td><td>'."\n";
			} elseif (($extrafields->attributes[$elementtype]['type'][$key] == 'select')) {
				echo $formadvtargetemaling->advMultiselectarray('options_'.$key.'_cnct', $extrafields->attributes[$key]['param']['options'], $array_query['options_'.$key.'_cnct']);
				echo '</td><td>'."\n";
			} elseif (($extrafields->attributes[$elementtype]['type'][$key] == 'sellist')) {
				echo $formadvtargetemaling->advMultiselectarraySelllist('options_'.$key.'_cnct', $extrafields->attributes[$key]['param']['options'], $array_query['options_'.$key.'_cnct']);
				echo '</td><td>'."\n";
			} else {
				if (is_array($array_query['options_'.$key.'_cnct'])) {
					echo $extrafields->showInputField($key, implode(',', $array_query['options_'.$key.'_cnct']), '', '_cnct');
				} else {
					echo $extrafields->showInputField($key, $array_query['options_'.$key.'_cnct'], '', '_cnct');
				}
				echo '</td><td>'."\n";
			}
			echo '</td></tr>'."\n";
		}
	}
}
echo '<tr>'."\n";
echo '<td colspan="3" class="right">'."\n";
echo '<input type="button" name="addcontact" id="addcontact" value="'.$langs->trans('AdvTgtAddContact').'" class="butAction"/>'."\n";
echo '</td>'."\n";
echo '</tr>'."\n";
echo '</table>'."\n";
echo '</form>'."\n";
echo '</div>'."\n";
echo '<form action="'.$_SERVER['PHP_SELF'].'?action=clear&id='.$object->id.'" method="POST">';
echo '<input type="hidden" name="token" value="'.newToken().'">';
echo load_fiche_titre($langs->trans("ToClearAllRecipientsClickHere"));
echo '<table class="noborder centpercent">';
echo '<tr class="liste_titre">';
echo '<td class="liste_titre right"><input type="submit" class="button" value="'.$langs->trans("TargetsReset").'"></td>';
echo '</tr>';
echo '</table>';
echo '</form>';
echo '<br>';
