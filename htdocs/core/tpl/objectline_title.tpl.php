<?php
/* Copyright (C) 2010-2013	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2011	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2013	Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2017		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2022		OpenDSI				<support@open-dsi.fr>
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
 *
 * Need to have following variables defined:
 * $object (invoice, order, ...)
 * $conf
 * $langs
 * $element     (used to test $user->rights->$element->creer)
 * $permtoedit  (used to replace test $user->rights->$element->creer)
 * $inputalsopricewithtax (0 by default, 1 to also show column with unit price including tax)
 * $outputalsopricetotalwithtax
 * $usemargins (0 to disable all margins columns, 1 to show according to margin setup)
 *
 * $type, $text, $description, $line
 */

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	echo "Error, template page can't be called as URL";
	exit(1);
}

'@phan-var-force CommonObject $this
 @phan-var-force CommonObject $object';

echo "<!-- BEGIN PHP TEMPLATE objectline_title.tpl.php -->\n";

// Title line
echo "<thead>\n";

echo '<tr class="liste_titre nodrag nodrop">';

// Adds a line numbering column
if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER')) {
	echo '<th class="linecolnum center">&nbsp;</th>';
}

// Description
echo '<th class="linecoldescription">'.$langs->trans('Description');
$constant = get_class($object)."::STATUS_DRAFT";
if (in_array($object->element, array('propal', 'commande', 'facture', 'order_supplier', 'invoice_supplier')) && defined($constant) && $object->status == constant($constant)) {
	if (empty($disableedit) && GETPOST('mode', 'aZ09') != 'servicedateforalllines') {
		echo '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?mode=servicedateforalllines&id='.$object->id.'">'.img_edit($langs->trans("UpdateForAllLines"), 0, 'class="clickvatforalllines opacitymedium paddingleft cursorpointer"').'</a>';
	}
	if (GETPOST('mode', 'aZ09') == 'servicedateforalllines') {
		echo '&nbsp;&nbsp;<div class="classvatforalllines inline-block nowraponall">';
		$hourmin = (isset($conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE) ? $conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE : '');
		echo $langs->trans('ServiceLimitedDuration').' '.$langs->trans('From').' ';
		echo $form->selectDate('', 'alldate_start', $hourmin, $hourmin, 1, "updatealllines", 1, 0);
		echo ' '.$langs->trans('to').' ';
		echo $form->selectDate('', 'alldate_end', $hourmin, $hourmin, 1, "updatealllines", 1, 0);
		echo '<input class="inline-block button smallpaddingimp" type="submit" name="submitforalllines" value="'.$langs->trans("Update").'">';
		echo '</div>';
	}
}
echo '</th>';

// Supplier ref
if ($this->element == 'supplier_proposal' || $this->element == 'order_supplier' || $this->element == 'invoice_supplier' || $this->element == 'invoice_supplier_rec') {
	echo '<th class="linerefsupplier maxwidth125"><span id="title_fourn_ref">'.$langs->trans("SupplierRef").'</span></th>';
}

// VAT
echo '<th class="linecolvat right nowraponall">';
if (getDolGlobalString('FACTURE_LOCAL_TAX1_OPTION') || getDolGlobalString('FACTURE_LOCAL_TAX2_OPTION')) {
	echo $langs->trans('Taxes');
} else {
	echo $langs->trans('VAT');
}

// @phan-suppress-next-line PhanUndeclaredConstantOfClass
if (in_array($object->element, array('propal', 'commande', 'facture', 'supplier_proposal', 'order_supplier', 'invoice_supplier')) && $object->status == $object::STATUS_DRAFT) {
	global $mysoc;

	if (empty($disableedit) && GETPOST('mode', 'aZ09') != 'vatforalllines') {
		echo '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?mode=vatforalllines&id='.$object->id.'">'.img_edit($langs->trans("UpdateForAllLines"), 0, 'class="clickvatforalllines opacitymedium paddingleft cursorpointer"').'</a>';
	}
	//echo '<script>$(document).ready(function() { $(".clickvatforalllines").click(function() { jQuery(".classvatforalllines").toggle(); }); });</script>';
	if (GETPOST('mode', 'aZ09') == 'vatforalllines') {
		echo '<div class="classvatforalllines inline-block nowraponall">';
		echo $form->load_tva('vatforalllines', '', $mysoc, $object->thirdparty, 0, 0, '', false, 1);
		echo '<input class="inline-block button smallpaddingimp" type="submit" name="submitforalllines" value="'.$langs->trans("Update").'">';
		echo '</div>';
	}
}
echo '</th>';

// Price HT
echo '<th class="linecoluht right nowraponall">'.$langs->trans('PriceUHT').'</th>';

// Multicurrency
if (isModEnabled("multicurrency") && $this->multicurrency_code != $conf->currency) {
	echo '<th class="linecoluht_currency right" style="width: 80px">'.$langs->trans('PriceUHTCurrency', $this->multicurrency_code).'</th>';
}

if (!empty($inputalsopricewithtax) && !getDolGlobalInt('MAIN_NO_INPUT_PRICE_WITH_TAX')) {
	echo '<th class="right nowraponall">'.$langs->trans('PriceUTTC').'</th>';
}

// Qty
echo '<th class="linecolqty right">'.$langs->trans('Qty').'</th>';

// Unit
if (getDolGlobalString('PRODUCT_USE_UNITS')) {
	echo '<th class="linecoluseunit left">'.$langs->trans('Unit').'</th>';
}

// Reduction short
echo '<th class="linecoldiscount right nowraponall">';
echo $langs->trans('ReductionShort');

// @phan-suppress-next-line PhanUndeclaredConstantOfClass
if (in_array($object->element, array('propal', 'commande', 'facture')) && $object->status == $object::STATUS_DRAFT) {
	global $mysoc;

	if (empty($disableedit) && GETPOST('mode', 'aZ09') != 'remiseforalllines') {
		echo '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?mode=remiseforalllines&id='.$object->id.'">'.img_edit($langs->trans("UpdateForAllLines"), 0, 'class="clickvatforalllines opacitymedium paddingleft cursorpointer"').'</a>';
	}
	//echo '<script>$(document).ready(function() { $(".clickremiseforalllines").click(function() { jQuery(".classremiseforalllines").toggle(); }); });</script>';
	if (GETPOST('mode', 'aZ09') == 'remiseforalllines') {
		echo '<div class="remiseforalllines inline-block nowraponall">';
		echo '<input class="inline-block smallpaddingimp width50 right" name="remiseforalllines" value="" placeholder="%">';
		echo '<input class="inline-block button smallpaddingimp" type="submit" name="submitforalllines" value="'.$langs->trans("Update").'">';
		echo '</div>';
	}
}
echo '</th>';

// Fields for situation invoice
if (isset($this->situation_cycle_ref) && $this->situation_cycle_ref) {
	echo '<th class="linecolcycleref right">'.$langs->trans('Progress').'</th>';
	if (getDolGlobalInt('INVOICE_USE_SITUATION') == 2) {
		echo '<th class="linecolcycleref2 right">' . $langs->trans('SituationInvoiceProgressCurrent') . '</th>';
	}
	echo '<th class="linecolcycleref2 right">'.$form->textwithpicto($langs->trans('TotalHT100Short'), $langs->trans('UnitPriceXQtyLessDiscount')).'</th>';
}

// Purchase price
if ($usemargins && isModEnabled('margin') && empty($user->socid)) {
	if ($user->hasRight('margins', 'creer')) {
		if (getDolGlobalString('MARGIN_TYPE') == "1") {
			echo '<th class="linecolmargin1 margininfos right width75">'.$langs->trans('BuyingPrice').'</th>';
		} else {
			echo '<th class="linecolmargin1 margininfos right width75">'.$langs->trans('CostPrice').'</th>';
		}
	}

	if (getDolGlobalString('DISPLAY_MARGIN_RATES') && $user->hasRight('margins', 'liretous')) {
		echo '<th class="linecolmargin2 margininfos right width75">'.$langs->trans('MarginRate');
		// @phan-suppress-next-line PhanUndeclaredConstantOfClass
		if (in_array($object->element, array('propal', 'commande', 'facture', 'supplier_proposal', 'order_supplier', 'invoice_supplier')) && $object->status == $object::STATUS_DRAFT) {
			echo '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?mode=marginforalllines&id='.$object->id.'">'.img_edit($langs->trans("UpdateForAllLines"), 0, 'class="clickmarginforalllines opacitymedium paddingleft cursorpointer"').'</a>';
			if (GETPOST('mode', 'aZ09') == 'marginforalllines') {
				echo '<div class="classmarginforalllines inline-block nowraponall">';
				echo '<input type="number" name="marginforalllines" min="0" max="999.9" value="20.0" step="0.1" class="width50"><label>%</label>';
				echo '<input class="inline-block button smallpaddingimp" type="submit" name="submitforallmargins" value="'.$langs->trans("Update").'">';
				echo '</div>';
			}
		}
		echo '</th>';
	}
	if (getDolGlobalString('DISPLAY_MARK_RATES') && $user->hasRight('margins', 'liretous')) {
		echo '<th class="linecolmargin2 margininfos right width75">'.$langs->trans('MarkRate').'</th>';
	}
}

// Total HT
echo '<th class="linecolht right">'.$langs->trans('TotalHTShort').'</th>';

// Multicurrency
if (isModEnabled("multicurrency") && $this->multicurrency_code != $conf->currency) {
	echo '<th class="linecoltotalht_currency right">'.$langs->trans('TotalHTShortCurrency', $this->multicurrency_code).'</th>';
}

if ($outputalsopricetotalwithtax) {
	echo '<th class="right" style="width: 80px">'.$langs->trans('TotalTTCShort').'</th>';
}

if (isModEnabled('asset') && $object->element == 'invoice_supplier') {
	echo '<th class="linecolasset"></th>';
}

echo '<th class="linecoledit"></th>'; // No width to allow autodim

echo '<th class="linecoldelete" style="width: 10px"></th>';

echo '<th class="linecolmove" style="width: 10px"></th>';

if ($action == 'selectlines') {
	echo '<th class="linecolcheckall center">';
	echo '<input type="checkbox" class="linecheckboxtoggle" />';
	echo '<script>$(document).ready(function() {$(".linecheckboxtoggle").click(function() {var checkBoxes = $(".linecheckbox");checkBoxes.prop("checked", this.checked);})});</script>';
	echo '</th>';
}

echo "</tr>\n";
echo "</thead>\n";

echo "<!-- END PHP TEMPLATE objectline_title.tpl.php -->\n";
