<?php
/* Copyright (C) 2020       Laurent Destailleur     <eldy@users.sourceforge.net>
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
 *       \file       htdocs/public/recruitment/index.php
 *       \ingroup    recruitment
 *       \brief      Public file to show on job
 */

if (!defined('NOLOGIN')) {
	define("NOLOGIN", 1); // This means this output page does not require to be logged.
}
if (!defined('NOCSRFCHECK')) {
	define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
}
if (!defined('NOIPCHECK')) {
	define('NOIPCHECK', '1'); // Do not check IP defined into conf $gestimag_main_restrict_ip
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}

// Load Gestimag environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/recruitment/class/recruitmentjobposition.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("companies", "other", "recruitment"));

// Get parameters
$action   = GETPOST('action', 'aZ09');
$cancel   = GETPOST('cancel', 'alpha');
$SECUREKEY = GETPOST("securekey");
$entity = GETPOSTINT('entity') ? GETPOSTINT('entity') : $conf->entity;
$backtopage = '';
$suffix = "";

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	$page = 0;
}     // If $page is not defined, or '' or -1 or if we click on clear filters
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (GETPOST('btn_view')) {
	unset($_SESSION['email_customer']);
}
if (isset($_SESSION['email_customer'])) {
	$email = $_SESSION['email_customer'];
}

$object = new RecruitmentJobPosition($db);

// Define $urlwithroot
//$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($gestimag_main_url_root));
//$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
$urlwithroot = DOL_MAIN_URL_ROOT; // This is to use same domain name than current. For Paypal payment, we can use internal URL like localhost.

// Security check
if (empty($conf->recruitment->enabled)) {
	httponly_accessforbidden('Module Recruitment not enabled');
}


/*
 * Actions
 */

// None


/*
 * View
 */

$head = '';
if (getDolGlobalString('MAIN_RECRUITMENT_CSS_URL')) {
	$head = '<link rel="stylesheet" type="text/css" href="' . getDolGlobalString('MAIN_RECRUITMENT_CSS_URL').'?lang='.$langs->defaultlang.'">'."\n";
}

$conf->dol_hide_topmenu = 1;
$conf->dol_hide_leftmenu = 1;

if (!getDolGlobalString('RECRUITMENT_ENABLE_PUBLIC_INTERFACE')) {
	$langs->load("errors");
	echo '<div class="error">'.$langs->trans('ErrorPublicInterfaceNotEnabled').'</div>';
	$db->close();
	exit();
}

$arrayofjs = array();
$arrayofcss = array();

$replacemainarea = (empty($conf->dol_hide_leftmenu) ? '<div>' : '').'<div>';
llxHeader($head, $langs->trans("PositionToBeFilled"), '', '', 0, 0, '', '', '', 'onlinepaymentbody', $replacemainarea, 1, 1);


echo '<span id="dolpaymentspan"></span>'."\n";
echo '<div class="center">'."\n";
echo '<form id="dolpaymentform" class="center" name="paymentform" action="'.$_SERVER["PHP_SELF"].'" method="POST">'."\n";
echo '<input type="hidden" name="token" value="'.newToken().'">'."\n";
echo '<input type="hidden" name="action" value="dosign">'."\n";
echo '<input type="hidden" name="tag" value="'.GETPOST("tag", 'alpha').'">'."\n";
echo '<input type="hidden" name="suffix" value="'.GETPOST("suffix", 'alpha').'">'."\n";
echo '<input type="hidden" name="securekey" value="'.$SECUREKEY.'">'."\n";
echo '<input type="hidden" name="entity" value="'.$entity.'" />';
echo "\n";
echo '<!-- Form to view jobs -->'."\n";

// Show logo (search order: logo defined by ONLINE_SIGN_LOGO_suffix, then ONLINE_SIGN_LOGO_, then small company logo, large company logo, theme logo, common logo)
// Define logo and logosmall
$logosmall = $mysoc->logo_small;
$logo = $mysoc->logo;
$paramlogo = 'ONLINE_RECRUITMENT_LOGO_'.$suffix;
if (!empty($conf->global->$paramlogo)) {
	$logosmall = getDolGlobalString($paramlogo);
} elseif (getDolGlobalString('ONLINE_RECRUITMENT_LOGO')) {
	$logosmall = getDolGlobalString('ONLINE_RECRUITMENT_LOGO_');
}
//echo '<!-- Show logo (logosmall='.$logosmall.' logo='.$logo.') -->'."\n";
// Define urllogo
$urllogo = '';
$urllogofull = '';
if (!empty($logosmall) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$logosmall)) {
	$urllogo = DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;entity='.$conf->entity.'&amp;file='.urlencode('logos/thumbs/'.$logosmall);
	$urllogofull = $gestimag_main_url_root.'/viewimage.php?modulepart=mycompany&entity='.$conf->entity.'&file='.urlencode('logos/thumbs/'.$logosmall);
} elseif (!empty($logo) && is_readable($conf->mycompany->dir_output.'/logos/'.$logo)) {
	$urllogo = DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;entity='.$conf->entity.'&amp;file='.urlencode('logos/'.$logo);
	$urllogofull = $gestimag_main_url_root.'/viewimage.php?modulepart=mycompany&entity='.$conf->entity.'&file='.urlencode('logos/'.$logo);
}
// Output html code for logo
if ($urllogo) {
	echo '<div class="backgreypublicpayment">';
	echo '<div class="logopublicpayment">';
	echo '<img id="dolpaymentlogo" src="'.$urllogo.'">';
	echo '</div>';
	if (!getDolGlobalString('MAIN_HIDE_POWERED_BY')) {
		echo '<div class="poweredbypublicpayment opacitymedium right"><a class="poweredbyhref" href="https://www.gestimag.org?utm_medium=website&utm_source=poweredby" target="gestimag" rel="noopener">'.$langs->trans("PoweredBy").'<br><img class="poweredbyimg" src="'.DOL_URL_ROOT.'/theme/gestimag_logo.svg" width="80px"></a></div>';
	}
	echo '</div>';
}

if (getDolGlobalString('RECRUITMENT_IMAGE_PUBLIC_INTERFACE')) {
	echo '<div class="backimagepublicrecruitment">';
	echo '<img id="idPROJECT_IMAGE_PUBLIC_SUGGEST_BOOTH" src="' . getDolGlobalString('RECRUITMENT_IMAGE_PUBLIC_INTERFACE').'">';
	echo '</div>';
}


$results = $object->fetchAll($sortorder, $sortfield, 0, 0, '(status:=:1)');
$now = dol_now();

if (is_array($results)) {
	if (empty($results)) {
		echo '<br>';
		echo $langs->trans("NoPositionOpen");
	} else {
		echo '<br><br><br>';
		echo '<span class="opacitymedium">'.$langs->trans("WeAreRecruiting").'</span>';
		echo '<br><br><br>';
		echo '<br class="hideonsmartphone">';

		foreach ($results as $job) {
			$object = $job;

			echo '<table id="dolpaymenttable" summary="Job position offer" class="center">'."\n";

			// Output introduction text
			$text = '';
			if (getDolGlobalString('RECRUITMENT_NEWFORM_TEXT')) {
				$reg = array();
				if (preg_match('/^\((.*)\)$/', $conf->global->RECRUITMENT_NEWFORM_TEXT, $reg)) {
					$text .= $langs->trans($reg[1])."<br>\n";
				} else {
					$text .= getDolGlobalString('RECRUITMENT_NEWFORM_TEXT') . "<br>\n";
				}
				$text = '<tr><td align="center"><br>'.$text.'<br></td></tr>'."\n";
			}
			if (empty($text)) {
				$text .= '<tr><td class="textpublicpayment"><br>'.$langs->trans("JobOfferToBeFilled", $mysoc->name);
				$text .= ' &nbsp; - &nbsp; <strong>'.$mysoc->name.'</strong>';
				$text .= ' &nbsp; - &nbsp; <span class="nowraponall"><span class="fa fa-calendar secondary"></span> '.dol_print_date($object->date_creation).'</span>';
				$text .= '</td></tr>'."\n";
				$text .= '<tr><td class="textpublicpayment"><h1 class="paddingleft paddingright">'.$object->label.'</h1></td></tr>'."\n";
			}
			echo $text;

			// Output payment summary form
			echo '<tr><td class="left">';

			echo '<div class="centpercent" id="tablepublicpayment">';
			echo '<div class="opacitymedium">'.$langs->trans("ThisIsInformationOnJobPosition").' :</div>'."\n";

			$error = 0;
			$found = true;

			echo '<br>';

			// Label
			echo $langs->trans("Label").' : ';
			echo '<b>'.dol_escape_htmltag($object->label).'</b><br>';

			// Date
			echo  $langs->trans("DateExpected").' : ';
			echo '<b>';
			if ($object->date_planned > $now) {
				echo dol_print_date($object->date_planned, 'day');
			} else {
				echo $langs->trans("ASAP");
			}
			echo '</b><br>';

			// Remuneration
			echo  $langs->trans("Remuneration").' : ';
			echo '<b>';
			echo dol_escape_htmltag($object->remuneration_suggested);
			echo '</b><br>';

			// Contact
			$tmpuser = new User($db);
			$tmpuser->fetch($object->fk_user_recruiter);

			echo  $langs->trans("ContactForRecruitment").' : ';
			$emailforcontact = $object->email_recruiter;
			if (empty($emailforcontact)) {
				$emailforcontact = $tmpuser->email;
				if (empty($emailforcontact)) {
					$emailforcontact = $mysoc->email;
				}
			}
			echo '<b class="wordbreak">';
			echo $tmpuser->getFullName($langs);
			echo ' &nbsp; '.dol_print_email($emailforcontact, 0, 0, 1, 0, 0, 'envelope');
			echo '</b>';
			echo '</b><br>';

			if ($object->status == RecruitmentJobPosition::STATUS_RECRUITED) {
				echo info_admin($langs->trans("JobClosedTextCandidateFound"), 0, 0, 0, 'warning');
			}
			if ($object->status == RecruitmentJobPosition::STATUS_CANCELED) {
				echo info_admin($langs->trans("JobClosedTextCanceled"), 0, 0, 0, 'warning');
			}

			echo '<br>';

			// Description

			$text = $object->description;
			echo $text;
			echo '<input type="hidden" name="ref" value="'.$object->ref.'">';

			echo '</div>'."\n";
			echo "\n";


			if ($action != 'dosubmit') {
				if ($found && !$error) {
					// We are in a management option and no error
				} else {
					dol_print_error_email('ERRORSUBMITAPPLICATION');
				}
			} else {
				// Print
			}

			echo '</td></tr>'."\n";

			echo '</table>'."\n";

			echo '<br><br class="hideonsmartphone"><br class="hideonsmartphone"><br class="hideonsmartphone">'."\n";
		}
	}
} else {
	dol_print_error($db, $object->error, $object->errors);
}

echo '</form>'."\n";
echo '</div>'."\n";
echo '<br>';


htmlPrintOnlineFooter($mysoc, $langs);

llxFooter('', 'public');

$db->close();
