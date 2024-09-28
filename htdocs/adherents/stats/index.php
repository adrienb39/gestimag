<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
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
 *	    \file       htdocs/adherents/stats/index.php
 *      \ingroup    member
 *		\brief      Page of subscription members statistics
 */

// Load Gestimag environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherentstats.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';

$WIDTH = DolGraph::getDefaultGraphSizeForStats('width');
$HEIGHT = DolGraph::getDefaultGraphSizeForStats('height');

$userid = GETPOSTINT('userid');
if ($userid < 0) {
	$userid = 0;
}
$socid = GETPOSTINT('socid');
if ($socid < 0) {
	$socid = 0;
}

// Security check
if ($user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}
$result = restrictedArea($user, 'adherent', '', '', 'cotisation');

$year = (int) dol_print_date(dol_now('gmt'), "%Y", 'gmt');
$startyear = $year - (!getDolGlobalInt('MAIN_STATS_GRAPHS_SHOW_N_YEARS') ? 2 : max(1, min(10, getDolGlobalInt('MAIN_STATS_GRAPHS_SHOW_N_YEARS'))));
$endyear = $year;
if (getDolGlobalString('MEMBER_SUBSCRIPTION_START_AFTER')) {
	$endyear = dol_print_date(dol_time_plus_duree(dol_now('gmt'), (int) substr(getDolGlobalString('MEMBER_SUBSCRIPTION_START_AFTER'), 0, -1), substr(getDolGlobalString('MEMBER_SUBSCRIPTION_START_AFTER'), -1)), "%Y", 'gmt');
}

// Load translation files required by the page
$langs->loadLangs(array("companies", "members"));


/*
 * View
 */

$memberstatic = new Adherent($db);
$form = new Form($db);

$title = $langs->trans("SubscriptionsStatistics");
llxHeader('', $title);

echo load_fiche_titre($title, '', $memberstatic->picto);

$dir = $conf->adherent->dir_temp;

dol_mkdir($dir);

$stats = new AdherentStats($db, $socid, $userid);

// Build graphic number of object
$data = $stats->getNbByMonthWithPrevYear($endyear, $startyear);
//var_dump($data);
// $data = array(array('Lib',val1,val2,val3),...)


$filenamenb = $dir.'/subscriptionsnbinyear-'.$year.'.png';
$fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=memberstats&file=subscriptionsnbinyear-'.$year.'.png';


$px1 = new DolGraph();
$mesg = $px1->isGraphKo();
if (!$mesg) {
	$px1->SetData($data);
	$i = $startyear;
	$legend = array();
	while ($i <= $endyear) {
		$legend[] = $i;
		$i++;
	}
	$px1->SetLegend($legend);
	$px1->SetMaxValue($px1->GetCeilMaxValue());
	$px1->SetMinValue(min(0, $px1->GetFloorMinValue()));
	$px1->SetWidth($WIDTH);
	$px1->SetHeight($HEIGHT);
	$px1->SetYLabel($langs->trans("NbOfSubscriptions"));
	$px1->SetShading(3);
	$px1->SetHorizTickIncrement(1);
	$px1->mode = 'depth';
	$px1->SetTitle($langs->trans("NbOfSubscriptions"));

	$px1->draw($filenamenb, $fileurlnb);
}

// Build graphic amount of object
$data = $stats->getAmountByMonthWithPrevYear($endyear, $startyear);
//var_dump($data);
// $data = array(array('Lib',val1,val2,val3),...)

$filenameamount = $dir.'/subscriptionsamountinyear-'.$year.'.png';
$fileurlamount = DOL_URL_ROOT.'/viewimage.php?modulepart=memberstats&file=subscriptionsamountinyear-'.$year.'.png';

$px2 = new DolGraph();
$mesg = $px2->isGraphKo();
if (!$mesg) {
	$px2->SetData($data);
	$i = $startyear;
	while ($i <= $endyear) {
		$legend[] = $i;
		$i++;
	}
	$px2->SetLegend($legend);
	$px2->SetMaxValue($px2->GetCeilMaxValue());
	$px2->SetMinValue(min(0, $px2->GetFloorMinValue()));
	$px2->SetWidth($WIDTH);
	$px2->SetHeight($HEIGHT);
	$px2->SetYLabel($langs->trans("AmountOfSubscriptions"));
	$px2->SetShading(3);
	$px2->SetHorizTickIncrement(1);
	$px2->mode = 'depth';
	$px2->SetTitle($langs->trans("AmountOfSubscriptions"));

	$px2->draw($filenameamount, $fileurlamount);
}


$head = member_stats_prepare_head($memberstatic);

echo dol_get_fiche_head($head, 'statssubscription', '', -1, '');


echo '<div class="fichecenter"><div class="fichethirdleft">';

// Show filter box
/*echo '<form name="stats" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
echo '<input type="hidden" name="token" value="'.newToken().'">';

echo '<table class="border centpercent">';
echo '<tr class="liste_titre"><td class="liste_titre" colspan="2">'.$langs->trans("Filter").'</td></tr>';
echo '<tr><td>'.$langs->trans("Member").'</td><td>';
echo img_picto('', 'company', 'class="pictofixedwidth"');
echo $form->select_company($id,'memberid','',1);
echo '</td></tr>';
echo '<tr><td>'.$langs->trans("User").'</td><td>';
echo img_picto('', 'user', 'class="pictofixedwidth"');
echo $form->select_dolusers($userid, 'userid', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'widthcentpercentminusx maxwidth300');
echo '</td></tr>';
echo '<tr><td class="center" colspan="2"><input type="submit" name="submit" class="button small" value="'.$langs->trans("Refresh").'"></td></tr>';
echo '</table>';
echo '</form>';
echo '<br><br>';
*/

// Show array
$data = $stats->getAllByYear();


echo '<div class="div-table-responsive-no-min">';
echo '<table class="noborder">';
echo '<tr class="liste_titre" height="24">';
echo '<td class="center">'.$langs->trans("Year").'</td>';
echo '<td class="right">'.$langs->trans("NbOfSubscriptions").'</td>';
echo '<td class="right">'.$langs->trans("AmountTotal").'</td>';
echo '<td class="right">'.$langs->trans("AmountAverage").'</td>';
echo '</tr>';

$oldyear = 0;
foreach ($data as $val) {
	$year = $val['year'];
	while ($oldyear > $year + 1) {	// If we have empty year
		$oldyear--;
		echo '<tr class="oddeven" height="24">';
		echo '<td class="center">';
		//echo '<a href="month.php?year='.$oldyear.'&mode='.$mode.'">';
		echo $oldyear;
		//echo '</a>';
		echo '</td>';
		echo '<td class="right">0</td>';
		echo '<td class="right amount nowraponall">0</td>';
		echo '<td class="right amount nowraponall">0</td>';
		echo '</tr>';
	}
	echo '<tr class="oddeven" height="24">';
	echo '<td class="center">';
	echo '<a href="'.DOL_URL_ROOT.'/adherents/subscription/list.php?date_select='.((int) $year).'">'.$year.'</a>';
	echo '</td>';
	echo '<td class="right">'.$val['nb'].'</td>';
	echo '<td class="right amount nowraponall"><span class="amount">'.price(price2num($val['total'], 'MT'), 1).'</span></td>';
	echo '<td class="right amount nowraponall"><span class="amount">'.price(price2num($val['avg'], 'MT'), 1).'</span></td>';
	echo '</tr>';
	$oldyear = $year;
}

echo '</table>';
echo '</div>';


echo '</div><div class="fichetwothirdright">';


// Show graphs
echo '<table class="border centpercent"><tr class="pair nohover"><td class="center">';
if ($mesg) {
	echo $mesg;
} else {
	echo $px1->show();
	echo "<br>\n";
	echo $px2->show();
}
echo '</td></tr></table>';


echo '</div></div>';
echo '<div class="clearboth"></div>';


echo dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
