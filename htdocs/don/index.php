<?php
/* Copyright (C) 2001-2002  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2019       Nicolas ZABOURI         <info@inovea-conseil.com>
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
 *  \file       htdocs/don/index.php
 *  \ingroup    donations
 *  \brief      Home page of donation module
 */

// Load Gestimag environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';

$hookmanager = new HookManager($db);

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('donationindex'));

$langs->load("donations");

$donation_static = new Don($db);

// Security check
$result = restrictedArea($user, 'don');


/*
 * Actions
 */

// None


/*
 * View
 */

$donstatic = new Don($db);

$help_url = 'EN:Module_Donations|FR:Module_Dons|ES:M&oacute;dulo_Donaciones|DE:Modul_Spenden';

llxHeader('', $langs->trans("Donations"), $help_url, '', 0, 0, '', '', '', 'mod-donation page-index');

$nb = array();
$somme = array();
$total = 0;

$sql = "SELECT count(d.rowid) as nb, sum(d.amount) as somme , d.fk_statut";
$sql .= " FROM ".MAIN_DB_PREFIX."don as d WHERE d.entity IN (".getEntity('donation').")";
$sql .= " GROUP BY d.fk_statut";
$sql .= " ORDER BY d.fk_statut";

$result = $db->query($sql);
if ($result) {
	$i = 0;
	$num = $db->num_rows($result);
	while ($i < $num) {
		$objp = $db->fetch_object($result);

		$somme[$objp->fk_statut] = $objp->somme;
		$nb[$objp->fk_statut] = $objp->nb;
		$total += $objp->somme;

		$i++;
	}
	$db->free($result);
} else {
	dol_print_error($db);
}

echo load_fiche_titre($langs->trans("DonationsArea"), '', 'object_donation');


echo '<div class="fichecenter"><div class="fichethirdleft">';

if (!isset($listofsearchfields) || !is_array($listofsearchfields)) {
	// Ensure $listofsearchfields is an array
	$listofsearchfields = array();
}
if (getDolGlobalString('MAIN_SEARCH_FORM_ON_HOME_AREAS')) {     // TODO Add a search into global search combo so we can remove this
	if (isModEnabled('don') && $user->hasRight('don', 'lire')) {
		$listofsearchfields['search_donation'] = array('text' => 'Donation');
	}

	if (count($listofsearchfields)) {
		echo '<form method="post" action="'.DOL_URL_ROOT.'/core/search.php">';
		echo '<input type="hidden" name="token" value="'.newToken().'">';
		echo '<table class="noborder nohover centpercent">';
		$i = 0;
		foreach ($listofsearchfields as $key => $value) {
			if ($i == 0) {
				echo '<tr class="liste_titre"><td colspan="3">'.$langs->trans("Search").'</td></tr>';
			}
			echo '<tr>';
			echo '<td class="nowrap"><label for="'.$key.'">'.$langs->trans($value["text"]).'</label></td><td><input type="text" class="flat inputsearch" name="'.$key.'" id="'.$key.'"></td>';
			if ($i == 0) {
				echo '<td rowspan="'.count($listofsearchfields).'"><input type="submit" class="button" value="'.$langs->trans("Search").'"></td>';
			}
			echo '</tr>';
			$i++;
		}
		echo '</table>';
		echo '</form>';
		echo '<br>';
	}
}

$dataseries = array();
$colorseries = array();

include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';

echo '<table class="noborder nohover centpercent">';
echo '<tr class="liste_titre">';
echo '<th colspan="4">'.$langs->trans("Statistics").'</th>';
echo "</tr>\n";

$listofstatus = array(0, 1, -1, 2);
foreach ($listofstatus as $status) {
	$dataseries[] = array($donstatic->LibStatut($status, 1), (isset($nb[$status]) ? (int) $nb[$status] : 0));
	if ($status == Don::STATUS_DRAFT) {
		$colorseries[$status] = '-'.$badgeStatus0;
	}
	if ($status == Don::STATUS_VALIDATED) {
		$colorseries[$status] = $badgeStatus1;
	}
	if ($status == Don::STATUS_CANCELED) {
		$colorseries[$status] = $badgeStatus9;
	}
	if ($status == Don::STATUS_PAID) {
		$colorseries[$status] = $badgeStatus6;
	}
}

if ($conf->use_javascript_ajax) {
	echo '<tr><td class="center" colspan="4">';

	include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
	$dolgraph = new DolGraph();
	$dolgraph->SetData($dataseries);
	$dolgraph->SetDataColor(array_values($colorseries));
	$dolgraph->setShowLegend(2);
	$dolgraph->setShowPercent(1);
	$dolgraph->SetType(array('pie'));
	$dolgraph->setHeight('200');
	$dolgraph->draw('idgraphstatus');
	echo $dolgraph->show($total ? 0 : 1);

	echo '</td></tr>';
}

echo '<tr class="liste_titre">';
echo '<td>'.$langs->trans("Status").'</td>';
echo '<td class="right">'.$langs->trans("Number").'</td>';
echo '<td class="right">'.$langs->trans("Total").'</td>';
echo '<td class="right">'.$langs->trans("Average").'</td>';
echo '</tr>';

$total = 0;
$totalnb = 0;
foreach ($listofstatus as $status) {
	echo '<tr class="oddeven">';
	echo '<td><a href="list.php?search_status='.$status.'">'.$donstatic->LibStatut($status, 4).'</a></td>';
	echo '<td class="right">'.(!empty($nb[$status]) ? $nb[$status] : '&nbsp;').'</td>';
	echo '<td class="right nowraponall amount">'.(!empty($nb[$status]) ? price($somme[$status], 1, '', 1, -1, 'MT') : '&nbsp;').'</td>';
	echo '<td class="right nowraponall">'.(!empty($nb[$status]) ? price(price2num($somme[$status] / $nb[$status], 'MT')) : '&nbsp;').'</td>';
	$totalnb += (!empty($nb[$status]) ? $nb[$status] : 0);
	$total += (!empty($somme[$status]) ? $somme[$status] : 0);
	echo "</tr>";
}

echo '<tr class="liste_total">';
echo '<td>'.$langs->trans("Total").'</td>';
echo '<td class="right nowraponall">'.$totalnb.'</td>';
echo '<td class="right nowraponall">'.price($total, 1, "", 1, -1, 'MT').'</td>';
echo '<td class="right nowraponall">'.($totalnb ? price(price2num($total / $totalnb, 'MT')) : '&nbsp;').'</td>';
echo '</tr>';
echo "</table>";


echo '</div><div class="fichetwothirdright">';


$max = 10;

/*
 * Last modified donations
 */

$sql = "SELECT c.rowid, c.ref, c.fk_statut, c.societe, c.lastname, c.firstname, c.tms as datem, c.amount";
$sql .= " FROM ".MAIN_DB_PREFIX."don as c";
$sql .= " WHERE c.entity = ".$conf->entity;
//$sql.= " AND c.fk_statut > 2";
$sql .= " ORDER BY c.tms DESC";
$sql .= $db->plimit($max, 0);

$resql = $db->query($sql);
if ($resql) {
	echo '<table class="noborder centpercent">';
	echo '<tr class="liste_titre">';
	echo '<th colspan="5">'.$langs->trans("LastModifiedDonations", $max).'</th></tr>';

	$num = $db->num_rows($resql);
	if ($num) {
		$i = 0;
		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			echo '<tr class="oddeven">';

			$donation_static->id = $obj->rowid;
			$donation_static->ref = $obj->ref ? $obj->ref : $obj->rowid;

			echo '<td width="96" class="nobordernopadding nowrap">';
			echo $donation_static->getNomUrl(1);
			echo '</td>';

			echo '<td class="nobordernopadding">';
			echo $obj->societe;
			print($obj->societe && ($obj->lastname || $obj->firstname) ? ' / ' : '');
			echo dolGetFirstLastname($obj->firstname, $obj->lastname);
			echo '</td>';

			echo '<td class="right nobordernopadding nowraponall amount">';
			echo price($obj->amount, 1);
			echo '</td>';

			// Date
			echo '<td class="center">'.dol_print_date($db->jdate($obj->datem), 'day').'</td>';

			echo '<td class="right">'.$donation_static->LibStatut($obj->fk_statut, 5).'</td>';

			echo '</tr>';
			$i++;
		}
	}
	echo "</table><br>";
} else {
	dol_print_error($db);
}


echo '</div></div>';

$parameters = array('user' => $user);
$reshook = $hookmanager->executeHooks('dashboardDonation', $parameters, $object); // Note that $action and $object may have been modified by hook

llxFooter();

$db->close();
