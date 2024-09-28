<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
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
 *	\file       recruitment/index.php
 *	\ingroup    recruitment
 *	\brief      Home page of recruitment top menu
 */

// Load Gestimag environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/recruitment/class/recruitmentjobposition.class.php';
require_once DOL_DOCUMENT_ROOT.'/recruitment/class/recruitmentcandidature.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Load translation files required by the page
$langs->loadLangs(array("recruitment", "boxes"));

$action = GETPOST('action', 'aZ09');

$NBMAX = getDolGlobalString('MAIN_SIZE_SHORTLIST_LIMIT', 5);
$max = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT', 5);
$now = dol_now();

$socid = GETPOSTINT('socid');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
// if (! $user->hasRight('mymodule', 'myobject', 'read')) {
// 	accessforbidden();
// }
restrictedArea($user, 'recruitment', 0, 'recruitment_recruitmentjobposition', 'recruitmentjobposition', '', 'rowid');


/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$staticrecruitmentjobposition = new RecruitmentJobPosition($db);
$staticrecruitmentcandidature = new RecruitmentCandidature($db);

llxHeader("", $langs->trans("RecruitmentArea"));

echo load_fiche_titre($langs->trans("RecruitmentArea"), '', 'object_recruitmentjobposition');

echo '<div class="fichecenter"><div class="fichethirdleft">';


/*
 * Statistics
 */

if ($conf->use_javascript_ajax) {
	$sql = "SELECT COUNT(t.rowid) as nb, status";
	$sql .= " FROM ".MAIN_DB_PREFIX."recruitment_recruitmentjobposition as t";
	$sql .= " GROUP BY t.status";
	$sql .= " ORDER BY t.status ASC";
	$resql = $db->query($sql);

	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;

		$totalnb = 0;
		$dataseries = array();
		$colorseries = array();
		$vals = array();

		include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';

		while ($i < $num) {
			$obj = $db->fetch_object($resql);
			if ($obj) {
				$vals[$obj->status] = $obj->nb;

				$totalnb += $obj->nb;
			}
			$i++;
		}
		$db->free($resql);

		echo '<div class="div-table-responsive-no-min">';
		echo '<table class="noborder nohover centpercent">';
		echo '<tr class="liste_titre"><th colspan="2">'.$langs->trans("Statistics").' - '.$langs->trans("JobPositions").'</th></tr>'."\n";
		$listofstatus = array(0, 1, 3, 9);
		foreach ($listofstatus as $status) {
			$dataseries[] = array(dol_html_entity_decode($staticrecruitmentjobposition->LibStatut($status, 1), ENT_QUOTES | ENT_HTML5), (isset($vals[$status]) ? (int) $vals[$status] : 0));
			if ($status == RecruitmentJobPosition::STATUS_DRAFT) {
				$colorseries[$status] = '-'.$badgeStatus0;
			}
			if ($status == RecruitmentJobPosition::STATUS_VALIDATED) {
				$colorseries[$status] = $badgeStatus4;
			}
			if ($status == RecruitmentJobPosition::STATUS_RECRUITED) {
				$colorseries[$status] = $badgeStatus6;
			}
			if ($status == RecruitmentJobPosition::STATUS_CANCELED) {
				$colorseries[$status] = $badgeStatus9;
			}

			if (empty($conf->use_javascript_ajax)) {
				echo '<tr class="oddeven">';
				echo '<td>'.$staticrecruitmentjobposition->LibStatut($status, 0).'</td>';
				echo '<td class="right"><a href="list.php?statut='.$status.'">'.(isset($vals[$status]) ? $vals[$status] : 0).'</a></td>';
				echo "</tr>\n";
			}
		}
		if ($conf->use_javascript_ajax) {
			echo '<tr><td class="center" colspan="2">';

			include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
			$dolgraph = new DolGraph();
			$dolgraph->SetData($dataseries);
			$dolgraph->SetDataColor(array_values($colorseries));
			$dolgraph->setShowLegend(2);
			$dolgraph->setShowPercent(1);
			$dolgraph->SetType(array('pie'));
			$dolgraph->SetHeight('200');
			$dolgraph->draw('idgraphstatus');
			echo $dolgraph->show($totalnb ? 0 : 1);

			echo '</td></tr>';
		}
		echo "</table>";
		echo "</div>";

		echo "<br>";
	} else {
		dol_print_error($db);
	}

	$sql = "SELECT COUNT(t.rowid) as nb, status";
	$sql .= " FROM ".MAIN_DB_PREFIX."recruitment_recruitmentcandidature as t";
	$sql .= " GROUP BY t.status";
	$sql .= " ORDER BY t.status ASC";
	$resql = $db->query($sql);

	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;

		$totalnb = 0;
		$dataseries = array();
		$colorseries = array();
		$vals = array();

		include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';

		while ($i < $num) {
			$obj = $db->fetch_object($resql);
			if ($obj) {
				$vals[$obj->status] = $obj->nb;

				$totalnb += $obj->nb;
			}
			$i++;
		}
		$db->free($resql);

		echo '<div class="div-table-responsive-no-min">';
		echo '<table class="noborder nohover centpercent">';
		echo '<tr class="liste_titre"><th colspan="2">'.$langs->trans("Statistics").' - '.$langs->trans("RecruitmentCandidatures").'</th></tr>'."\n";
		$listofstatus = array(0, 1, 3, 5, 8, 9);
		foreach ($listofstatus as $status) {
			$dataseries[] = array(dol_html_entity_decode($staticrecruitmentcandidature->LibStatut($status, 1), ENT_QUOTES | ENT_HTML5), (isset($vals[$status]) ? (int) $vals[$status] : 0));
			if ($status == RecruitmentCandidature::STATUS_DRAFT) {
				$colorseries[$status] = '-'.$badgeStatus0;
			}
			if ($status == RecruitmentCandidature::STATUS_VALIDATED) {
				$colorseries[$status] = $badgeStatus1;
			}
			if ($status == RecruitmentCandidature::STATUS_CONTRACT_PROPOSED) {
				$colorseries[$status] = $badgeStatus4;
			}
			if ($status == RecruitmentCandidature::STATUS_CONTRACT_SIGNED) {
				$colorseries[$status] = $badgeStatus5;
			}
			if ($status == RecruitmentCandidature::STATUS_REFUSED) {
				$colorseries[$status] = $badgeStatus9;
			}
			if ($status == RecruitmentCandidature::STATUS_CANCELED) {
				$colorseries[$status] = $badgeStatus9;
			}

			if (empty($conf->use_javascript_ajax)) {
				echo '<tr class="oddeven">';
				echo '<td>'.$staticrecruitmentcandidature->LibStatut($status, 0).'</td>';
				echo '<td class="right"><a href="list.php?statut='.$status.'">'.(isset($vals[$status]) ? $vals[$status] : 0).'</a></td>';
				echo "</tr>\n";
			}
		}
		if ($conf->use_javascript_ajax) {
			echo '<tr><td class="center" colspan="2">';

			include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
			$dolgraph = new DolGraph();
			$dolgraph->SetData($dataseries);
			$dolgraph->SetDataColor(array_values($colorseries));
			$dolgraph->setShowLegend(2);
			$dolgraph->setShowPercent(1);
			$dolgraph->SetType(array('pie'));
			$dolgraph->SetHeight('200');
			$dolgraph->draw('idgraphstatuscandidature');
			echo $dolgraph->show($totalnb ? 0 : 1);

			echo '</td></tr>';
		}
		echo "</table>";
		echo "</div>";

		echo "<br>";
	} else {
		dol_print_error($db);
	}
}

echo '<br>';

/* BEGIN MODULEBUILDER DRAFT MYOBJECT
// Draft MyObject
if (isModEnabled('recruitment') && $user->rights->recruitment->read)
{
	$langs->load("orders");

	$sql = "SELECT c.rowid, c.ref, c.ref_client, c.total_ht, c.total_tva, c.total_ttc, s.rowid as socid, s.nom as name, s.client, s.canvas";
	$sql.= ", s.code_client";
	$sql.= " FROM ".MAIN_DB_PREFIX."recruitment_recruitmentjobposition as c";
	$sql.= ", ".MAIN_DB_PREFIX."societe as s";
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE c.fk_soc = s.rowid";
	$sql.= " AND c.fk_statut = 0";
	$sql.= " AND c.entity IN (".getEntity('commande').")";
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	if ($socid)	$sql.= " AND c.fk_soc = ".((int) $socid);

	$resql = $db->query($sql);
	if ($resql)
	{
		$total = 0;
		$num = $db->num_rows($resql);

		echo '<table class="noborder centpercent">';
		echo '<tr class="liste_titre">';
		echo '<th colspan="3">'.$langs->trans("DraftOrders").($num?'<span class="badge marginleftonlyshort">'.$num.'</span>':'').'</th></tr>';

		$var = true;
		if ($num > 0)
		{
			$i = 0;
			while ($i < $num)
			{

				$obj = $db->fetch_object($resql);
				echo '<tr class="oddeven"><td class="nowrap">';
				$orderstatic->id=$obj->rowid;
				$orderstatic->ref=$obj->ref;
				$orderstatic->ref_client=$obj->ref_client;
				$orderstatic->total_ht = $obj->total_ht;
				$orderstatic->total_tva = $obj->total_tva;
				$orderstatic->total_ttc = $obj->total_ttc;
				echo $orderstatic->getNomUrl(1);
				echo '</td>';
				echo '<td class="nowrap">';
				$companystatic->id=$obj->socid;
				$companystatic->name=$obj->name;
				$companystatic->client=$obj->client;
				$companystatic->code_client = $obj->code_client;
				$companystatic->code_fournisseur = $obj->code_fournisseur;
				$companystatic->canvas=$obj->canvas;
				echo $companystatic->getNomUrl(1,'customer',16);
				echo '</td>';
				echo '<td class="right" class="nowrap">'.price($obj->total_ttc).'</td></tr>';
				$i++;
				$total += $obj->total_ttc;
			}
			if ($total>0)
			{

				echo '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td colspan="2" class="right">'.price($total)."</td></tr>";
			}
		}
		else
		{

			echo '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("NoOrder").'</td></tr>';
		}
		echo "</table><br>";

		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}
END MODULEBUILDER DRAFT MYOBJECT */


echo '</div><div class="fichetwothirdright">';


// Last modified job position
if (isModEnabled('recruitment') && $user->hasRight('recruitment', 'recruitmentjobposition', 'read')) {
	$sql = "SELECT s.rowid, s.ref, s.label, s.date_creation, s.tms, s.status, COUNT(rc.rowid) as nbapplications";
	$sql .= " FROM ".MAIN_DB_PREFIX."recruitment_recruitmentjobposition as s";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."recruitment_recruitmentcandidature as rc ON rc.fk_recruitmentjobposition = s.rowid";
	if (isModEnabled('societe') && !$user->hasRight('societe', 'client', 'voir') && !$socid) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= " WHERE s.entity IN (".getEntity($staticrecruitmentjobposition->element).")";
	if (isModEnabled('societe') && !$user->hasRight('societe', 'client', 'voir') && !$socid) {
		$sql .= " AND s.fk_soc = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	}
	if ($socid) {
		$sql .= " AND s.fk_soc = $socid";
	}
	$sql .= " GROUP BY s.rowid, s.ref, s.label, s.date_creation, s.tms, s.status";
	$sql .= $db->order('s.tms', 'DESC');
	$sql .= $db->plimit($max, 0);

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;

		echo '<div class="div-table-responsive-no-min">';
		echo '<table class="noborder centpercent">';
		echo '<tr class="liste_titre">';
		echo '<th colspan="2">';
		echo $langs->trans("BoxTitleLatestModifiedJobPositions", $max);
		echo '</th>';
		echo '<th class="right">';
		echo $langs->trans("Applications");
		echo '</th>';
		echo '<th class="right" colspan="2"><a href="'.DOL_URL_ROOT.'/recruitment/recruitmentjobposition_list.php?sortfield=t.tms&sortorder=DESC">'.$langs->trans("FullList").'</th>';
		echo '</tr>';
		if ($num) {
			while ($i < $num) {
				$objp = $db->fetch_object($resql);
				$staticrecruitmentjobposition->id = $objp->rowid;
				$staticrecruitmentjobposition->ref = $objp->ref;
				$staticrecruitmentjobposition->label = $objp->label;
				$staticrecruitmentjobposition->status = $objp->status;
				$staticrecruitmentjobposition->date_creation = $objp->date_creation;

				echo '<tr class="oddeven">';
				echo '<td class="nowrap">'.$staticrecruitmentjobposition->getNomUrl(1, '').'</td>';
				echo '<td class="right nowrap">';
				echo "</td>";
				echo '<td class="right">';
				echo $objp->nbapplications;
				echo '</td>';
				echo '<td class="right nowrap">'.dol_print_date($db->jdate($objp->tms), 'day')."</td>";
				echo '<td class="right nowrap" width="16">';
				echo $staticrecruitmentjobposition->getLibStatut(3);
				echo "</td>";
				echo '</tr>';
				$i++;
			}

			$db->free($resql);
		} else {
			echo '<tr class="oddeven"><td colspan="4"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
		}
		echo "</table>";
		echo "</div>";
		echo "<br>";
	} else {
		dol_print_error($db);
	}
}

// Last modified job position
if (isModEnabled('recruitment') && $user->hasRight('recruitment', 'recruitmentjobposition', 'read')) {
	$sql = "SELECT rc.rowid, rc.ref, rc.email, rc.lastname, rc.firstname, rc.date_creation, rc.tms, rc.status";
	$sql .= " FROM ".MAIN_DB_PREFIX."recruitment_recruitmentcandidature as rc";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."recruitment_recruitmentjobposition as s ON rc.fk_recruitmentjobposition = s.rowid";
	if (isModEnabled('societe') && !$user->hasRight('societe', 'client', 'voir') && !$socid) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= " WHERE rc.entity IN (".getEntity($staticrecruitmentjobposition->element).")";
	if (isModEnabled('societe') && !$user->hasRight('societe', 'client', 'voir') && !$socid) {
		$sql .= " AND s.fk_soc = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	}
	if ($socid) {
		$sql .= " AND s.fk_soc = $socid";
	}
	$sql .= $db->order('rc.tms', 'DESC');
	$sql .= $db->plimit($max, 0);

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;

		echo '<div class="div-table-responsive-no-min">';
		echo '<table class="noborder centpercent">';
		echo '<tr class="liste_titre">';
		echo '<th colspan="2">';
		echo $langs->trans("BoxTitleLatestModifiedCandidatures", $max);
		echo '</th>';
		echo '<th class="right" colspan="2"><a href="'.DOL_URL_ROOT.'/recruitment/recruitmentcandidature_list.php?sortfield=t.tms&sortorder=DESC">'.$langs->trans("FullList").'</th>';
		echo '</tr>';
		if ($num) {
			while ($i < $num) {
				$objp = $db->fetch_object($resql);
				$staticrecruitmentcandidature->id = $objp->rowid;
				$staticrecruitmentcandidature->ref = $objp->ref;
				$staticrecruitmentcandidature->email = $objp->email;
				$staticrecruitmentcandidature->status = $objp->status;
				$staticrecruitmentcandidature->date_creation = $objp->date_creation;
				$staticrecruitmentcandidature->firstname = $objp->firstname;
				$staticrecruitmentcandidature->lastname = $objp->lastname;

				echo '<tr class="oddeven">';
				echo '<td class="nowrap">'.$staticrecruitmentcandidature->getNomUrl(1, '').'</td>';
				echo '<td class="right nowrap">';
				echo "</td>";
				echo '<td class="right nowrap">'.dol_print_date($db->jdate($objp->tms), 'day')."</td>";
				echo '<td class="right nowrap" width="16">';
				echo $staticrecruitmentcandidature->getLibStatut(3);
				echo "</td>";
				echo '</tr>';
				$i++;
			}

			$db->free($resql);
		} else {
			echo '<tr class="oddeven"><td colspan="4"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
		}
		echo "</table>";
		echo "</div>";
		echo "<br>";
	} else {
		dol_print_error($db);
	}
}

echo '</div></div>';

// End of page
llxFooter();
$db->close();
