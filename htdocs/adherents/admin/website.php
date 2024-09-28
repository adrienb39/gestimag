<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2006-2015	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2006-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2011		Juanjo Menent			<jmenent@2byte.es>
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
 *     	\file       htdocs/adherents/admin/website.php
 *		\ingroup    member
 *		\brief      File of main public page for member module
 */

// Load Gestimag environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "members"));

$action = GETPOST('action', 'aZ09');

if (!$user->admin) {
	accessforbidden();
}

$error = 0;


/*
 * Actions
 */

if ($action == 'setMEMBER_ENABLE_PUBLIC') {
	if (GETPOST('value')) {
		gestimag_set_const($db, 'MEMBER_ENABLE_PUBLIC', 1, 'chaine', 0, '', $conf->entity);
	} else {
		gestimag_set_const($db, 'MEMBER_ENABLE_PUBLIC', 0, 'chaine', 0, '', $conf->entity);
	}
}

if ($action == 'update') {
	$public = GETPOST('MEMBER_ENABLE_PUBLIC');
	if (GETPOST('MEMBER_NEWFORM_AMOUNT') !== '') {
		$amount = price2num(GETPOST('MEMBER_NEWFORM_AMOUNT'), 'MT', 2);
	} else {
		$amount = '';
	}
	$minamount = GETPOST('MEMBER_MIN_AMOUNT');
	$publiccounters = GETPOST('MEMBER_COUNTERS_ARE_PUBLIC');
	$showtable = GETPOST('MEMBER_SHOW_TABLE');
	$showvoteallowed = GETPOST('MEMBER_SHOW_VOTE_ALLOWED');
	$payonline = GETPOST('MEMBER_NEWFORM_PAYONLINE');
	$forcetype = GETPOSTINT('MEMBER_NEWFORM_FORCETYPE');
	$forcemorphy = GETPOST('MEMBER_NEWFORM_FORCEMORPHY', 'aZ09');

	$res = gestimag_set_const($db, "MEMBER_ENABLE_PUBLIC", $public, 'chaine', 0, '', $conf->entity);
	$res = gestimag_set_const($db, "MEMBER_NEWFORM_AMOUNT", $amount, 'chaine', 0, '', $conf->entity);
	$res = gestimag_set_const($db, "MEMBER_MIN_AMOUNT", $minamount, 'chaine', 0, '', $conf->entity);
	$res = gestimag_set_const($db, "MEMBER_COUNTERS_ARE_PUBLIC", $publiccounters, 'chaine', 0, '', $conf->entity);
	$res = gestimag_set_const($db, "MEMBER_SKIP_TABLE", !$showtable, 'chaine', 0, '', $conf->entity); // Logic is reversed for retrocompatibility: "skip -> show"
	$res = gestimag_set_const($db, "MEMBER_HIDE_VOTE_ALLOWED", !$showvoteallowed, 'chaine', 0, '', $conf->entity); // Logic is reversed for retrocompatibility: "hide -> show"
	$res = gestimag_set_const($db, "MEMBER_NEWFORM_PAYONLINE", $payonline, 'chaine', 0, '', $conf->entity);
	if ($forcetype < 0) {
		$res = gestimag_del_const($db, "MEMBER_NEWFORM_FORCETYPE", $conf->entity);
	} else {
		$res = gestimag_set_const($db, "MEMBER_NEWFORM_FORCETYPE", $forcetype, 'chaine', 0, '', $conf->entity);
	}
	if ($forcemorphy == '-1') {
		$res = gestimag_del_const($db, "MEMBER_NEWFORM_FORCEMORPHY", $conf->entity);
	} else {
		$res = gestimag_set_const($db, "MEMBER_NEWFORM_FORCEMORPHY", $forcemorphy, 'chaine', 0, '', $conf->entity);
	}

	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}


/*
 * View
 */

$form = new Form($db);

$title = $langs->trans("MembersSetup");
$help_url = 'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros|DE:Modul_Mitglieder';
llxHeader('', $title, $help_url);


$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
echo load_fiche_titre($title, $linkback, 'title_setup');

$head = member_admin_prepare_head();



echo '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
echo '<input type="hidden" name="action" value="update">';
echo '<input type="hidden" name="token" value="'.newToken().'">';

echo dol_get_fiche_head($head, 'website', $langs->trans("Members"), -1, 'user');

if ($conf->use_javascript_ajax) {
	echo "\n".'<script type="text/javascript">';
	echo 'jQuery(document).ready(function () {
                function initemail()
                {
                    if (jQuery("#MEMBER_NEWFORM_PAYONLINE").val()==\'-1\')
                    {
                        jQuery("#tremail").hide();
					}
					else
					{
                        jQuery("#tremail").show();
					}
				}
                function initfields()
                {
					if (jQuery("#MEMBER_ENABLE_PUBLIC").val()==\'0\')
                    {
                        jQuery("#trforcetype, #tramount, #tredit, #trpayment, #tremail").hide();
                    }
                    if (jQuery("#MEMBER_ENABLE_PUBLIC").val()==\'1\')
                    {
                        jQuery("#trforcetype, #tramount, #tredit, #trpayment").show();
                        if (jQuery("#MEMBER_NEWFORM_PAYONLINE").val()==\'-1\') jQuery("#tremail").hide();
                        else jQuery("#tremail").show();
					}
				}
				initfields();
                jQuery("#MEMBER_ENABLE_PUBLIC").change(function() { initfields(); });
                jQuery("#MEMBER_NEWFORM_PAYONLINE").change(function() { initemail(); });
			})';
	echo '</script>'."\n";
}


echo '<span class="opacitymedium">'.$langs->trans("BlankSubscriptionFormDesc").'</span><br><br>';

$param = '';

$enabledisablehtml = $langs->trans("EnablePublicSubscriptionForm").' ';
if (!getDolGlobalString('MEMBER_ENABLE_PUBLIC')) {
	// Button off, click to enable
	$enabledisablehtml .= '<a class="reposition valignmiddle" href="'.$_SERVER["PHP_SELF"].'?action=setMEMBER_ENABLE_PUBLIC&token='.newToken().'&value=1'.$param.'">';
	$enabledisablehtml .= img_picto($langs->trans("Disabled"), 'switch_off');
	$enabledisablehtml .= '</a>';
} else {
	// Button on, click to disable
	$enabledisablehtml .= '<a class="reposition valignmiddle" href="'.$_SERVER["PHP_SELF"].'?action=setMEMBER_ENABLE_PUBLIC&token='.newToken().'&value=0'.$param.'">';
	$enabledisablehtml .= img_picto($langs->trans("Activated"), 'switch_on');
	$enabledisablehtml .= '</a>';
}
echo $enabledisablehtml;
echo '<input type="hidden" id="MEMBER_ENABLE_PUBLIC" name="MEMBER_ENABLE_PUBLIC" value="'.(!getDolGlobalString('MEMBER_ENABLE_PUBLIC') ? 0 : 1).'">';

echo '<br><br>';


if (getDolGlobalString('MEMBER_ENABLE_PUBLIC')) {
	echo '<br>';
	//echo $langs->trans('FollowingLinksArePublic').'<br>';
	echo img_picto('', 'globe').' <span class="opacitymedium">'.$langs->trans('BlankSubscriptionForm').'</span><br>';
	if (isModEnabled('multicompany')) {
		$entity_qr = '?entity='.((int) $conf->entity);
	} else {
		$entity_qr = '';
	}

	// Define $urlwithroot
	$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($gestimag_main_url_root));
	$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
	//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

	echo '<div class="urllink">';
	echo '<input type="text" id="publicurlmember" class="quatrevingtpercentminusx" value="'.$urlwithroot.'/public/members/new.php'.$entity_qr.'">';
	echo '<a target="_blank" rel="noopener noreferrer" href="'.$urlwithroot.'/public/members/new.php'.$entity_qr.'">'.img_picto('', 'globe', 'class="paddingleft"').'</a>';
	echo '</div>';
	echo ajax_autoselect('publicurlmember');

	echo '<br><br>';

	echo '<div class="div-table-responsive-no-min">';
	echo '<table class="noborder centpercent">';

	echo '<tr class="liste_titre">';
	echo '<td>'.$langs->trans("Parameter").'</td>';
	echo '<td>'.$langs->trans("Value").'</td>';
	echo "</tr>\n";

	// Force Type
	$adht = new AdherentType($db);
	echo '<tr class="oddeven drag" id="trforcetype"><td>';
	echo $langs->trans("ForceMemberType");
	echo '</td><td>';
	$listofval = array();
	$listofval += $adht->liste_array(1);
	$forcetype = getDolGlobalInt('MEMBER_NEWFORM_FORCETYPE', -1);
	echo $form->selectarray("MEMBER_NEWFORM_FORCETYPE", $listofval, $forcetype, count($listofval) > 1 ? 1 : 0);
	echo "</td></tr>\n";

	// Force nature of member (mor/phy)
	$morphys = array();
	$morphys["phy"] = $langs->trans("Physical");
	$morphys["mor"] = $langs->trans("Moral");
	echo '<tr class="oddeven drag" id="trforcenature"><td>';
	echo $langs->trans("ForceMemberNature");
	echo '</td><td>';
	$forcenature = getDolGlobalInt('MEMBER_NEWFORM_FORCEMORPHY', 0);
	echo $form->selectarray("MEMBER_NEWFORM_FORCEMORPHY", $morphys, $forcenature, 1);
	echo "</td></tr>\n";

	// Amount
	echo '<tr class="oddeven" id="tramount"><td>';
	echo $langs->trans("DefaultAmount");
	echo '</td><td>';
	echo '<input type="text" class="right width50" id="MEMBER_NEWFORM_AMOUNT" name="MEMBER_NEWFORM_AMOUNT" value="'.getDolGlobalString('MEMBER_NEWFORM_AMOUNT').'">';
	echo "</td></tr>\n";

	// Min amount
	echo '<tr class="oddeven" id="tredit"><td>';
	echo $langs->trans("MinimumAmount");
	echo '</td><td>';
	echo '<input type="text" class="right width50" id="MEMBER_MIN_AMOUNT" name="MEMBER_MIN_AMOUNT" value="'.getDolGlobalString('MEMBER_MIN_AMOUNT').'">';
	echo "</td></tr>\n";

	// SHow counter of validated members publicly
	echo '<tr class="oddeven" id="tredit"><td>';
	echo $langs->trans("MemberCountersArePublic");
	echo '</td><td>';
	echo $form->selectyesno("MEMBER_COUNTERS_ARE_PUBLIC", getDolGlobalInt('MEMBER_COUNTERS_ARE_PUBLIC'), 1, false, 0, 1);
	echo "</td></tr>\n";

	// Show the table of all available membership types. If not, show a form (as the default was for Gestimag <=16.0)
	$skiptable = getDolGlobalInt('MEMBER_SKIP_TABLE');
	echo '<tr class="oddeven" id="tredit"><td>';
	echo $langs->trans("MembersShowMembershipTypesTable");
	echo '</td><td>';
	echo $form->selectyesno("MEMBER_SHOW_TABLE", !$skiptable, 1, false, 0, 1); // Reverse the logic "hide -> show" for retrocompatibility
	echo "</td></tr>\n";

	// Show "vote allowed" setting for membership types
	$hidevoteallowed = getDolGlobalInt('MEMBER_HIDE_VOTE_ALLOWED');
	echo '<tr class="oddeven" id="tredit"><td>';
	echo $langs->trans("MembersShowVotesAllowed");
	echo '</td><td>';
	echo $form->selectyesno("MEMBER_SHOW_VOTE_ALLOWED", !$hidevoteallowed, 1, false, 0, 1); // Reverse the logic "hide -> show" for retrocompatibility
	echo "</td></tr>\n";

	// Jump to an online payment page
	echo '<tr class="oddeven" id="trpayment"><td>';
	echo $langs->trans("MEMBER_NEWFORM_PAYONLINE");
	echo '</td><td>';
	$listofval = array();
	$listofval['-1'] = $langs->trans('No');
	$listofval['all'] = $langs->trans('Yes').' ('.$langs->trans("VisitorCanChooseItsPaymentMode").')';
	if (isModEnabled('paybox')) {
		$listofval['paybox'] = 'Paybox';
	}
	if (isModEnabled('paypal')) {
		$listofval['paypal'] = 'PayPal';
	}
	if (isModEnabled('stripe')) {
		$listofval['stripe'] = 'Stripe';
	}
	echo $form->selectarray("MEMBER_NEWFORM_PAYONLINE", $listofval, getDolGlobalString('MEMBER_NEWFORM_PAYONLINE'), 0);
	echo "</td></tr>\n";

	echo '</table>';
	echo '</div>';

	echo '<div class="center">';
	echo '<input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'">';
	echo '</div>';
}


echo dol_get_fiche_end();

echo '</form>';

// End of page
llxFooter();
$db->close();
