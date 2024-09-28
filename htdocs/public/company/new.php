<?php
/* Copyright (C) 2001-2002  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2001-2002  Jean-Louis Bergamo      <jlb@j1b.org>
 * Copyright (C) 2006-2013  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2012       Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2012       J. Fernando Lagrange    <fernando@demo-tic.org>
 * Copyright (C) 2018-2019  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2018       Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2021       Waël Almoman            <info@almoman.com>
 * Copyright (C) 2022       Udo Tamm                <dev@dolibit.de>
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

/**
 *	\file       htdocs/public/company/new.php
 *	\ingroup    prospect
 *	\brief      Example of form to add a new prospect
 *
 */

if (!defined('NOLOGIN')) {
	define("NOLOGIN", 1); // This means this output page does not require to be logged.
}
if (!defined('NOCSRFCHECK')) {
	define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}


// For MultiCompany module.
// Do not use GETPOST here, function is not defined and define must be done before including main.inc.php
// Because 2 entities can have the same ref
$entity = (!empty($_GET['entity']) ? (int) $_GET['entity'] : (!empty($_POST['entity']) ? (int) $_POST['entity'] : 1));
if (is_numeric($entity)) {
	define("DOLENTITY", $entity);
}


// Load Gestimag environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/payments.lib.php';
require_once DOL_DOCUMENT_ROOT . '/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT . '/adherents/class/adherent_type.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/cunits.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formadmin.class.php';
// Init vars
$backtopage = GETPOST('backtopage', 'alpha');
$action = GETPOST('action', 'aZ09');

$errmsg = '';
$num = 0;
$error = 0;

// Load translation files
$langs->loadLangs(array("main", "members", "companies", "install", "other", "errors"));

// Security check
if (!isModEnabled('societe')) {
	httponly_accessforbidden('Module Thirdparty not enabled');
}

if (!getDolGlobalString('SOCIETE_ENABLE_PUBLIC')) {
	httponly_accessforbidden("Online form for contact for public visitors has not been enabled");
}


//permissions

$permissiontoadd 	= $user->hasRight('societe', 'creer');

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('publicnewmembercard', 'globalcard'));

$extrafields = new ExtraFields($db);

$objectsoc = new Societe($db);
$user->loadDefaultValues();

$extrafields->fetch_name_optionals_label($objectsoc->table_element); // fetch optionals attributes and labels


/**
 * Show header for new prospect
 *
 * @param 	string		$title				Title
 * @param 	string		$head				Head array
 * @param 	int    		$disablejs			More content into html header
 * @param 	int    		$disablehead		More content into html header
 * @param 	array  		$arrayofjs			Array of complementary js files
 * @param 	array  		$arrayofcss			Array of complementary css files
 * @return	void
 */
function llxHeaderVierge($title, $head = "", $disablejs = 0, $disablehead = 0, $arrayofjs = [], $arrayofcss = [])
{
	global $conf, $langs, $mysoc;

	top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss); // Show html headers

	echo '<body id="mainbody" class="publicnewmemberform">';

	// Define urllogo
	$urllogo = DOL_URL_ROOT . '/theme/common/login_logo.png';

	if (!empty($mysoc->logo_small) && is_readable($conf->mycompany->dir_output . '/logos/thumbs/' . $mysoc->logo_small)) {
		$urllogo = DOL_URL_ROOT . '/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file=' . urlencode('logos/thumbs/' . $mysoc->logo_small);
	} elseif (!empty($mysoc->logo) && is_readable($conf->mycompany->dir_output . '/logos/' . $mysoc->logo)) {
		$urllogo = DOL_URL_ROOT . '/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file=' . urlencode('logos/' . $mysoc->logo);
	} elseif (is_readable(DOL_DOCUMENT_ROOT . '/theme/gestimag_logo.svg')) {
		$urllogo = DOL_URL_ROOT . '/theme/gestimag_logo.svg';
	}

	echo '<header class="center">';

	// Output html code for logo
	if ($urllogo) {
		echo '<div class="backgreypublicpayment">';
		echo '<div class="logopublicpayment">';
		echo '<img id="dolpaymentlogo" src="' . $urllogo . '">';
		echo '</div>';
		if (!getDolGlobalString('MAIN_HIDE_POWERED_BY')) {
			echo '<div class="poweredbypublicpayment opacitymedium right"><a class="poweredbyhref" href="https://www.gestimag.org?utm_medium=website&utm_source=poweredby" target="gestimag" rel="noopener">' . $langs->trans("PoweredBy") . '<br><img class="poweredbyimg" src="' . DOL_URL_ROOT . '/theme/gestimag_logo.svg" width="80px"></a></div>';
		}
		echo '</div>';
	}

	if (getDolGlobalString('MEMBER_IMAGE_PUBLIC_REGISTRATION')) {
		echo '<div class="backimagepublicregistration">';
		echo '<img id="idEVENTORGANIZATION_IMAGE_PUBLIC_INTERFACE" src="' . getDolGlobalString('MEMBER_IMAGE_PUBLIC_REGISTRATION') . '">';
		echo '</div>';
	}

	echo '</header>';

	echo '<div class="divmainbodylarge">';
}

/**
 * Show footer for new societe
 *
 * @return	void
 */
function llxFooterVierge()
{
	global $conf, $langs;

	$ext = '';

	echo '</div>';

	printCommonFooter('public');

	if (!empty($conf->use_javascript_ajax)) {
		echo "\n" . '<!-- Includes JS Footer of Gestimag -->' . "\n";
		echo '<script src="' . DOL_URL_ROOT . '/core/js/lib_foot.js.php?lang=' . $langs->defaultlang . (!empty($ext) ? '&' . $ext : '') . '"></script>' . "\n";
	}

	echo "</body>\n";
	echo "</html>\n";
}



/*
 * Actions
 */

$parameters = array();
// Note that $action and $object may have been modified by some hooks
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

// Action called when page is submitted
if (empty($reshook) && $action == 'add') {
	$error = 0;
	$urlback = '';

	$db->begin();

	if (!GETPOST('name')) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Company")), null, 'errors');
		$error++;
	}

	// Check Captcha code if is enabled
	if (getDolGlobalString('MAIN_SECURITY_ENABLECAPTCHA')) {
		$sessionkey = 'dol_antispam_value';
		$ok = (array_key_exists($sessionkey, $_SESSION) === true && (strtolower($_SESSION[$sessionkey]) == strtolower(GETPOST('code'))));
		if (!$ok) {
			$error++;
			$errmsg .= $langs->trans("ErrorBadValueForCode") . "<br>\n";
			$action = '';
		}
	}

	if (!$error) {
		$societe = new Societe($db);

		// TODO Support MAIN_SECURITY_MAX_POST_ON_PUBLIC_PAGES_BY_IP_ADDRESS


		$societe->name = GETPOST('name', 'alphanohtml');
		$societe->client = GETPOSTINT('client') ? GETPOSTINT('client') : $societe->client;
		$societe->address	= GETPOST('address', 'alphanohtml');
		$societe->country_id				= GETPOSTINT('country_id');
		$societe->phone					= GETPOST('phone', 'alpha');
		$societe->fax				= GETPOST('fax', 'alpha');
		$societe->email					= trim(GETPOST('email', 'custom', 0, FILTER_SANITIZE_EMAIL));
		$societe->client = 2 ; // our client is a prospect
		$societe->code_client		= '-1';
		$societe->name_alias = GETPOST('name_alias', 'alphanohtml');
		$societe->note_private = GETPOST('note_private', 'alphanohtml');

		// Fill array 'array_options' with data from add form
		/*
		$extrafields->fetch_name_optionals_label($societe->table_element);
		$ret = $extrafields->setOptionalsFromPost(null, $societe);
		if ($ret < 0) {
			$error++;
			$errmsg .= $societe->error;
		}
		*/

		if (!$error) {
			$result = $societe->create($user);
			if ($result > 0) {
				require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
				$objectsoc = $societe;

				if (!empty($backtopage)) {
					$urlback = $backtopage;
				} elseif (getDolGlobalString('MEMBER_URL_REDIRECT_SUBSCRIPTION')) {
					$urlback = getDolGlobalString('MEMBER_URL_REDIRECT_SUBSCRIPTION');
					// TODO Make replacement of __AMOUNT__, etc...
				} else {
					$urlback = $_SERVER["PHP_SELF"] . "?action=added&token=" . newToken();
				}
			} else {
				$error++;
				$errmsg .= implode('<br>', $societe->errors);
			}
		}
	}

	if (!$error) {
		$db->commit();

		header("Location: " . $urlback);
		exit;
	} else {
		$db->rollback();
		$action = "create";
	}
}

// Action called after a submitted was send and prospect created successfully
// If MEMBER_URL_REDIRECT_SUBSCRIPTION is set to an url, we never go here because a redirect was done to this url. Same if we ask to redirect to the payment page.
// backtopage parameter with an url was set on prospect submit page, we never go here because a redirect was done to this url.

if (empty($reshook) && $action == 'added') {
	llxHeaderVierge("newSocieteAdded");

	// If we have not been redirected
	echo '<br><br>';
	echo '<div class="center">';
	echo $langs->trans("newSocieteAdded");
	echo '</div>';

	llxFooterVierge();
	exit;
}



/*
 * View
 */

$form = new Form($db);
$formcompany = new FormCompany($db);
$adht = new AdherentType($db);
$formadmin = new FormAdmin($db);


llxHeaderVierge($langs->trans("ContactUs"));

echo '<br>';
echo load_fiche_titre(img_picto('', 'member_nocolor', 'class="pictofixedwidth"') . ' &nbsp; ' . $langs->trans("ContactUs"), '', '', 0, 0, 'center');


echo '<div align="center">';
echo '<div id="divsubscribe">';

echo '<div class="center subscriptionformhelptext opacitymedium justify">';
if (getDolGlobalString('COMPANY_NEWFORM_TEXT')) {
	echo $langs->trans(getDolGlobalString('COMPANY_NEWFORM_TEXT')) . "<br>\n";
} else {
	echo $langs->trans("ContactUsDesc", getDolGlobalString("MAIN_INFO_SOCIETE_MAIL")) . "<br>\n";
}
echo '</div>';

dol_htmloutput_errors($errmsg);
dol_htmloutput_events();

// Print form
echo '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST" name="newprospect">' . "\n";
echo '<input type="hidden" name="token" value="' . newToken() . '" / >';
echo '<input type="hidden" name="entity" value="' . $entity . '" />';
echo '<input type="hidden" name="action" value="add" />';
echo '<br>';

$messagemandatory = '<span class="">' . $langs->trans("FieldsWithAreMandatory", '*') . '</span>';
//echo '<br><span class="opacitymedium">'.$langs->trans("FieldsWithAreMandatory", '*').'</span><br>';
//echo $langs->trans("FieldsWithIsForPublic",'**').'<br>';

echo dol_get_fiche_head();

echo '<script type="text/javascript">
jQuery(document).ready(function () {
	jQuery(document).ready(function () {
		function initmorphy()
		{
			console.log("Call initmorphy");
			if (jQuery("#morphy").val() == \'phy\') {
				jQuery("#trcompany").hide();
			}
			if (jQuery("#morphy").val() == \'mor\') {
				jQuery("#trcompany").show();
			}
		}
		initmorphy();
		jQuery("#morphy").change(function() {
			initmorphy();
		});
		jQuery("#selectcountry_id").change(function() {
		document.newprospect.action.value="create";
		document.newprospect.submit();
		});
		jQuery("#typeid").change(function() {
		document.newprospect.action.value="create";
		document.newprospect.submit();
		});
	});
});
</script>';


echo '<table class="border" summary="form to subscribe" id="tablesubscribe">' . "\n";
//Third party name
/*
if ($objectsoc->particulier || $private) {
	echo '<span id="TypeName" class="fieldrequired">'.$langs->trans('ThirdPartyName').' / '.$langs->trans('LastName', 'name').'</span>';
} else {
	echo '<span id="TypeName" class="fieldrequired">'.$form->editfieldkey('ThirdPartyName', 'name', '', $objectsoc, 0).'</span>';
}
*/
echo '<tr class="tr-field-thirdparty-name"><td class="titlefieldcreate">'; // text appreas left
echo '<input type="hidden" name="ThirdPartyName" value="' . $langs->trans('ThirdPartyName') . '">';
echo '<span id="TypeName" class="fieldrequired"  title="' .dol_escape_htmltag($langs->trans("FieldsWithAreMandatory", '*')) . '" >' . $form->editfieldkey('Company', 'name', '', $objectsoc, 0) . '<span class="star"> *</span></span>';
echo '</td><td>'; // inline input
echo '<input type="text" class="minwidth300" maxlength="128" name="name" id="name" value="' . dol_escape_htmltag($objectsoc->name) . '" autofocus="autofocus">';
//

// Name and lastname
echo '<tr><td class="classfortooltip" title="' . dol_escape_htmltag($messagemandatory) . '">' . $langs->trans("Firstname") . ' <span class="star">*</span></td><td><input type="text" name="firstname" class="minwidth150" value="' . dol_escape_htmltag(GETPOST('firstname')) . '"></td></tr>' . "\n";

echo '<tr><td class="classfortooltip" title="' . dol_escape_htmltag($messagemandatory) . '">' . $langs->trans("Lastname") . ' <span class="star">*</span></td><td><input type="text" name="lastname" class="minwidth150" value="' . dol_escape_htmltag(GETPOST('lastname')) . '"></td></tr>' . "\n";

// Address
echo '<tr><td class="tdtop">';
echo $form->editfieldkey('Address', 'address', '', $objectsoc, 0);
echo '</td>';
echo '<td>';
echo '<textarea name="address" id="address" class="quatrevingtpercent" rows="' . ROWS_2 . '" wrap="soft">';
echo dol_escape_htmltag($objectsoc->address, 0, 1);
echo '</textarea>';
echo $form->widgetForTranslation("address", $objectsoc, $permissiontoadd, 'textarea', 'alphanohtml', 'quatrevingtpercent');
echo '</td></tr>';

// Country
echo '<tr><td>' . $form->editfieldkey('Country', 'selectcountry_id', '', $objectsoc, 0) . '</td><td class="maxwidthonsmartphone">';
echo img_picto('', 'country', 'class="pictofixedwidth"');
echo $form->select_country((GETPOSTISSET('country_id') ? GETPOST('country_id') : $objectsoc->country_id), 'country_id', '', 0, 'minwidth300 maxwidth500 widthcentpercentminusx');
if ($user->admin) {
	echo info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
}
echo '</td></tr>';

// Phone / Fax
echo '<tr><td>' . $form->editfieldkey('Phone', 'phone', '', $objectsoc, 0) . '</td>';
echo '<td>' . img_picto('', 'object_phoning', 'class="pictofixedwidth"') . ' <input type="text" name="phone" id="phone" class="maxwidth200 widthcentpercentminusx" value="' . (GETPOSTISSET('phone') ? GETPOST('phone', 'alpha') : $objectsoc->phone) . '"></td>';
echo '</tr>';

echo '<tr>';
echo '<td>' . $form->editfieldkey('Fax', 'fax', '', $objectsoc, 0) . '</td>';
echo '<td>' . img_picto('', 'object_phoning_fax', 'class="pictofixedwidth"') . ' <input type="text" name="fax" id="fax" class="maxwidth200 widthcentpercentminusx" value="' . (GETPOSTISSET('fax') ? GETPOST('fax', 'alpha') : $objectsoc->fax) . '"></td>';
echo '</tr>';

// Email / Web
echo '<tr><td>' . $form->editfieldkey('EMail', 'email', '', $objectsoc, 0, 'string', '', !getDolGlobalString('SOCIETE_EMAIL_MANDATORY') ? '' : $conf->global->SOCIETE_EMAIL_MANDATORY) . '</td>';
echo '<td>' . img_picto('', 'object_email', 'class="pictofixedwidth"') . ' <input type="text" class="maxwidth200 widthcentpercentminusx" name="email" id="email" value="' . $objectsoc->email . '"></td>';
if (isModEnabled('mailing') && getDolGlobalString('THIRDPARTY_SUGGEST_ALSO_ADDRESS_CREATION')) {
	if ($conf->browser->layout == 'phone') {
		echo '</tr><tr>';
	}
	echo '<td class="individualline noemail">' . $form->editfieldkey($langs->trans('No_Email') . ' (' . $langs->trans('Contact') . ')', 'contact_no_email', '', $objectsoc, 0) . '</td>';
	echo '<td class="individualline" ' . (($conf->browser->layout == 'phone') || !isModEnabled('mailing') ? ' colspan="3"' : '') . '>' . $form->selectyesno('contact_no_email', (GETPOSTISSET("contact_no_email") ? GETPOST("contact_no_email", 'alpha') : (empty($objectsoc->no_email) ? 0 : 1)), 1, false, 1) . '</td>';
}
echo '</tr>';

echo '<tr><td>' . $form->editfieldkey('Web', 'url', '', $objectsoc, 0) . '</td>';
echo '<td>' . img_picto('', 'globe', 'class="pictofixedwidth"') . ' <input type="text" class="maxwidth500 widthcentpercentminusx" name="url" id="url" value="' . $objectsoc->url . '"></td></tr>';


// Comments
echo '<tr>';
echo '<td class="tdtop">' . $langs->trans("Comments") . '</td>';
echo '<td class="tdtop"><textarea name="note_private" id="note_private" wrap="soft" class="quatrevingtpercent" rows="' . ROWS_3 . '">' . dol_escape_htmltag(GETPOST('note_private', 'restricthtml'), 0, 1) . '</textarea></td>';
echo '</tr>' . "\n";


// Other attributes
$parameters['tpl_context'] = 'public';	// define template context to public
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';


// TODO Move this into generic feature.

// Display Captcha code if is enabled
if (getDolGlobalString('MAIN_SECURITY_ENABLECAPTCHA')) {
	require_once DOL_DOCUMENT_ROOT . '/core/lib/security2.lib.php';
	echo '<tr><td class="titlefield"><label for="email"><span class="fieldrequired">' . $langs->trans("SecurityCode") . '</span></label></td><td>';
	echo '<span class="span-icon-security inline-block">';
	echo '<input id="securitycode" placeholder="' . $langs->trans("SecurityCode") . '" class="flat input-icon-security width150" type="text" maxlength="5" name="code" tabindex="3" />';
	echo '</span>';
	echo '<span class="nowrap inline-block">';
	echo '<img class="inline-block valignmiddle" src="' . DOL_URL_ROOT . '/core/antispamimage.php" border="0" width="80" height="32" id="img_securitycode" />';
	echo '<a class="inline-block valignmiddle" href="' . $php_self . '" tabindex="4" data-role="button">' . img_picto($langs->trans("Refresh"), 'refresh', 'id="captcha_refresh_img"') . '</a>';
	echo '</span>';
	echo '</td></tr>';
}

echo "</table>\n";

echo dol_get_fiche_end();

// Save / Submit
echo '<div class="center">';
echo '<input type="submit" value="' . $langs->trans("Send") . '" id="submitsave" class="button">';
if (!empty($backtopage)) {
	echo ' &nbsp; &nbsp; <input type="submit" value="' . $langs->trans("Cancel") . '" id="submitcancel" class="button button-cancel">';
}
echo '</div>';


echo "</form>\n";
echo "<br>";
echo '</div></div>';



llxFooterVierge();

$db->close();
