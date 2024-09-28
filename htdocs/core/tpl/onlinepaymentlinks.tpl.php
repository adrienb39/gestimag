<?php
/* Copyright (C) 2017 Laurent Destailleur <eldy@destailleur.fr>
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

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	echo "Error, template page can't be called as URL";
	exit(1);
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';

echo '<!-- BEGIN PHP TEMPLATE ONLINEPAYMENTLINKS -->';

// Url list
echo '<u>'.$langs->trans("FollowingUrlAreAvailableToMakePayments").':</u><br><br>';
echo img_picto('', 'globe').' <span class="opacitymedium">'.$langs->trans("ToOfferALinkForOnlinePaymentOnFreeAmount", $servicename).':</span><br>';
echo '<strong class="wordbreak">'.getOnlinePaymentUrl(1, 'free')."</strong><br><br>\n";

if (isModEnabled('order')) {
	echo '<div id="order"></div>';
	echo img_picto('', 'globe').' <span class="opacitymedium">'.$langs->trans("ToOfferALinkForOnlinePaymentOnOrder", $servicename).':</span><br>';
	echo '<strong class="wordbreak">'.getOnlinePaymentUrl(1, 'order')."</strong><br>\n";
	if (getDolGlobalString('PAYMENT_SECURITY_TOKEN') && getDolGlobalString('PAYMENT_SECURITY_TOKEN_UNIQUE')) {
		$langs->load("orders");
		echo '<form action="'.$_SERVER["PHP_SELF"].'#order" method="POST">';
		echo '<input type="hidden" name="token" value="'.newToken().'">';

		echo $langs->trans("EnterRefToBuildUrl", $langs->transnoentitiesnoconv("Order")).': ';
		echo '<input type="text class="flat" id="generate_order_ref" name="generate_order_ref" value="'.GETPOST('generate_order_ref', 'alpha').'" size="10">';
		echo '<input type="submit" class="none reposition button smallpaddingimp" value="'.$langs->trans("GetSecuredUrl").'">';
		if (GETPOST('generate_order_ref', 'alpha')) {
			$url = getOnlinePaymentUrl(0, 'order', GETPOST('generate_order_ref', 'alpha'));
			echo '<div class="urllink"><input type="text" class="wordbreak quatrevingtpercent" value="';
			echo $url;
			echo '"></div>'."\n";
		}
		echo '</form>';
	}
	echo '<br>';
}
if (isModEnabled('invoice')) {
	echo '<div id="invoice"></div>';
	echo img_picto('', 'globe').' <span class="opacitymedium">'.$langs->trans("ToOfferALinkForOnlinePaymentOnInvoice", $servicename).':</span><br>';
	echo '<strong class="wordbreak">'.getOnlinePaymentUrl(1, 'invoice')."</strong><br>\n";
	if (getDolGlobalString('PAYMENT_SECURITY_TOKEN') && getDolGlobalString('PAYMENT_SECURITY_TOKEN_UNIQUE')) {
		$langs->load("bills");
		echo '<form action="'.$_SERVER["PHP_SELF"].'#invoice" method="POST">';
		echo '<input type="hidden" name="token" value="'.newToken().'">';

		echo $langs->trans("EnterRefToBuildUrl", $langs->transnoentitiesnoconv("Invoice")).': ';
		echo '<input type="text class="flat" id="generate_invoice_ref" name="generate_invoice_ref" value="'.GETPOST('generate_invoice_ref', 'alpha').'" size="10">';
		echo '<input type="submit" class="none reposition button smallpaddingimp" value="'.$langs->trans("GetSecuredUrl").'">';
		if (GETPOST('generate_invoice_ref', 'alpha')) {
			$url = getOnlinePaymentUrl(0, 'invoice', GETPOST('generate_invoice_ref', 'alpha'));
			echo '<div class="urllink"><input type="text" class="wordbreak quatrevingtpercent" value="';
			echo $url;
			echo '"></div>'."\n";
		}
		echo '</form>';
	}
	echo '<br>';
}
if (isModEnabled('contract')) {
	echo '<div id="contractline"></div>';
	echo img_picto('', 'globe').' <span class="opacitymedium">'.$langs->trans("ToOfferALinkForOnlinePaymentOnContractLine", $servicename).':</span><br>';
	echo '<strong class="wordbreak">'.getOnlinePaymentUrl(1, 'contractline')."</strong><br>\n";
	if (getDolGlobalString('PAYMENT_SECURITY_TOKEN') && getDolGlobalString('PAYMENT_SECURITY_TOKEN_UNIQUE')) {
		$langs->load("contracts");
		echo '<form action="'.$_SERVER["PHP_SELF"].'#contractline" method="POST">';
		echo '<input type="hidden" name="token" value="'.newToken().'">';

		echo $langs->trans("EnterRefToBuildUrl", $langs->transnoentitiesnoconv("ContractLine")).': ';
		echo '<input type="text class="flat" id="generate_contract_ref" name="generate_contract_ref" value="'.GETPOST('generate_contract_ref', 'alpha').'" size="10">';
		echo '<input type="submit" class="none reposition button smallpaddingimp" value="'.$langs->trans("GetSecuredUrl").'">';
		if (GETPOST('generate_contract_ref')) {
			$url = getOnlinePaymentUrl(0, 'contractline', GETPOST('generate_contract_ref', 'alpha'));
			echo '<div class="urllink"><input type="text" class="wordbreak quatrevingtpercent" value="';
			echo $url;
			echo '"></div>'."\n";
		}
		echo '</form>';
	}
	echo '<br>';
}
if (isModEnabled('member')) {
	echo '<div id="membersubscription"></div>';
	echo img_picto('', 'globe').' <span class="opacitymedium">'.$langs->trans("ToOfferALinkForOnlinePaymentOnMemberSubscription", $servicename).':</span><br>';
	echo '<strong class="wordbreak">'.getOnlinePaymentUrl(1, 'membersubscription')."</strong><br>\n";
	if (getDolGlobalString('PAYMENT_SECURITY_TOKEN') && getDolGlobalString('PAYMENT_SECURITY_TOKEN_UNIQUE')) {
		$langs->load("members");
		echo '<form action="'.$_SERVER["PHP_SELF"].'#membersubscription" method="POST">';
		echo '<input type="hidden" name="token" value="'.newToken().'">';

		echo $langs->trans("EnterRefToBuildUrl", $langs->transnoentitiesnoconv("Member")).': ';
		echo '<input type="text class="flat" id="generate_member_ref" name="generate_member_ref" value="'.GETPOST('generate_member_ref', 'alpha').'" size="10">';
		echo '<input type="submit" class="none reposition button smallpaddingimp" value="'.$langs->trans("GetSecuredUrl").'">';
		if (GETPOST('generate_member_ref')) {
			$url = getOnlinePaymentUrl(0, 'membersubscription', GETPOST('generate_member_ref', 'alpha'));
			echo '<div class="urllink"><input type="text" class="wordbreak quatrevingtpercent" value="';
			echo $url;
			echo '"></div>'."\n";
		}
		echo '</form>';
	}
	echo '<br>';
}
if (isModEnabled('don')) {
	echo '<div id="donation"></div>';
	echo img_picto('', 'globe').' <span class="opacitymedium">'.$langs->trans("ToOfferALinkForOnlinePaymentOnDonation", $servicename).':</span><br>';
	echo '<strong class="wordbreak">'.getOnlinePaymentUrl(1, 'donation')."</strong><br>\n";
	if (getDolGlobalString('PAYMENT_SECURITY_TOKEN') && getDolGlobalString('PAYMENT_SECURITY_TOKEN_UNIQUE')) {
		$langs->load("members");
		echo '<form action="'.$_SERVER["PHP_SELF"].'#donation" method="POST">';
		echo '<input type="hidden" name="token" value="'.newToken().'">';

		echo $langs->trans("EnterRefToBuildUrl", $langs->transnoentitiesnoconv("Don")).': ';
		echo '<input type="text class="flat" id="generate_donation_ref" name="generate_donation_ref" value="'.GETPOST('generate_donation_ref', 'alpha').'" size="10">';
		echo '<input type="submit" class="none reposition button smallpaddingimp" value="'.$langs->trans("GetSecuredUrl").'">';
		if (GETPOST('generate_donation_ref')) {
			echo '<div class="urllink"><input type="text" class="wordbreak quatrevingtpercent" value="';
			$url = getOnlinePaymentUrl(0, 'donation', GETPOST('generate_donation_ref', 'alpha'));
			echo $url;
			echo '"></div>'."\n";
		}
		echo '</form>';
	}
	echo '<br>';
}

$constname = 'PAYMENT_SECURITY_TOKEN';

// Add button to autosuggest a key
include_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
echo dolJSToSetRandomPassword($constname);

echo info_admin($langs->trans("YouCanAddTagOnUrl"));

if (isModEnabled('website')) {
	echo info_admin($langs->trans("YouCanEmbedOnWebsite"));
}

echo '<!-- END PHP TEMPLATE ONLINEPAYMENTLINKS -->';
