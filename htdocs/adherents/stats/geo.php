<?php
/* Copyright (c) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	    \file       htdocs/adherents/stats/geo.php
 *      \ingroup    member
 *		\brief      Page with geographical statistics on members
 */

// Load Gestimag environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';

$graphwidth = DolGraph::getDefaultGraphSizeForStats('width', 700);
$mapratio = 0.5;
$graphheight = round($graphwidth * $mapratio);

$mode = GETPOST('mode') ? GETPOST('mode') : '';


// Security check
if ($user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}
$result = restrictedArea($user, 'adherent', '', '', 'cotisation');

$year = (int) dol_print_date(dol_now('gmt'), "%Y", 'gmt');
$startyear = $year - (!getDolGlobalString('MAIN_STATS_GRAPHS_SHOW_N_YEARS') ? 2 : max(1, min(10, getDolGlobalString('MAIN_STATS_GRAPHS_SHOW_N_YEARS'))));
$endyear = $year;

// Load translation files required by the page
$langs->loadLangs(array("companies", "members", "banks"));


/*
 * View
 */

$memberstatic = new Adherent($db);

$arrayjs = array('https://www.google.com/jsapi');
if (!empty($conf->dol_use_jmobile)) {
	$arrayjs = array();
}

$title = $langs->trans("Statistics");
if ($mode == 'memberbycountry') {
	$title = $langs->trans("MembersStatisticsByCountries");
}
if ($mode == 'memberbystate') {
	$title = $langs->trans("MembersStatisticsByState");
}
if ($mode == 'memberbytown') {
	$title = $langs->trans("MembersStatisticsByTown");
}
if ($mode == 'memberbyregion') {
	$title = $langs->trans("MembersStatisticsByRegion");
}

llxHeader('', $title, '', '', 0, 0, $arrayjs);

echo load_fiche_titre($title, '', $memberstatic->picto);

//dol_mkdir($dir);

if ($mode) {
	// Define sql
	if ($mode == 'memberbycountry') {
		$label = $langs->trans("Country");
		$tab = 'statscountry';

		$data = array();
		$sql = "SELECT COUNT(DISTINCT d.rowid) as nb, COUNT(s.rowid) as nbsubscriptions, MAX(d.datevalid) as lastdate, MAX(s.dateadh) as lastsubscriptiondate, c.code, c.label";
		$sql .= " FROM ".MAIN_DB_PREFIX."adherent as d";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as c on d.country = c.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."subscription as s ON s.fk_adherent = d.rowid";
		$sql .= " WHERE d.entity IN (".getEntity('adherent').")";
		$sql .= " AND d.statut <> ".Adherent::STATUS_DRAFT;
		$sql .= " GROUP BY c.label, c.code";
		//echo $sql;
	}

	if ($mode == 'memberbystate') {
		$label = $langs->trans("Country");
		$label2 = $langs->trans("State");
		$tab = 'statsstate';

		$data = array();
		$sql = "SELECT COUNT(DISTINCT d.rowid) as nb, COUNT(s.rowid) as nbsubscriptions, MAX(d.datevalid) as lastdate, MAX(s.dateadh) as lastsubscriptiondate, co.code, co.label, c.nom as label2"; //
		$sql .= " FROM ".MAIN_DB_PREFIX."adherent as d";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as c on d.state_id = c.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_regions as r on c.fk_region = r.code_region";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as co on d.country = co.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."subscription as s ON s.fk_adherent = d.rowid";
		$sql .= " WHERE d.entity IN (".getEntity('adherent').")";
		$sql .= " AND d.statut <> ".Adherent::STATUS_DRAFT;
		$sql .= " GROUP BY co.label, co.code, c.nom";
		//echo $sql;
	}
	if ($mode == 'memberbyregion') { //
		$label = $langs->trans("Country");
		$label2 = $langs->trans("Region"); //département
		$tab = 'statsregion'; //onglet

		$data = array(); //tableau de donnée
		$sql = "SELECT COUNT(DISTINCT d.rowid) as nb, COUNT(s.rowid) as nbsubscriptions, MAX(d.datevalid) as lastdate, MAX(s.dateadh) as lastsubscriptiondate, co.code, co.label, r.nom as label2";
		$sql .= " FROM ".MAIN_DB_PREFIX."adherent as d";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as c on d.state_id = c.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_regions as r on c.fk_region = r.code_region";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as co on d.country = co.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."subscription as s ON s.fk_adherent = d.rowid";
		$sql .= " WHERE d.entity IN (".getEntity('adherent').")";
		$sql .= " AND d.statut <> ".Adherent::STATUS_DRAFT;
		$sql .= " GROUP BY co.label, co.code, r.nom"; //+
		//echo $sql;
	}
	if ($mode == 'memberbytown') {
		$label = $langs->trans("Country");
		$label2 = $langs->trans("Town");
		$tab = 'statstown';

		$data = array();
		$sql = "SELECT COUNT(DISTINCT d.rowid) as nb, COUNT(s.rowid) as nbsubscriptions, MAX(d.datevalid) as lastdate, MAX(s.dateadh) as lastsubscriptiondate, c.code, c.label, d.town as label2";
		$sql .= " FROM ".MAIN_DB_PREFIX."adherent as d";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as c on d.country = c.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."subscription as s ON s.fk_adherent = d.rowid";
		$sql .= " WHERE d.entity IN (".getEntity('adherent').")";
		$sql .= " AND d.statut <> ".Adherent::STATUS_DRAFT;
		$sql .= " GROUP BY c.label, c.code, d.town";
		//echo $sql;
	}

	$langsen = new Translate('', $conf);
	$langsen->setDefaultLang('en_US');
	$langsen->load("dict");
	//echo $langsen->trans("Country"."FI");exit;

	// Define $data array
	dol_syslog("Count member", LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		while ($i < $num) {
			$obj = $db->fetch_object($resql);
			if ($mode == 'memberbycountry') {
				$data[] = array('label' => (($obj->code && $langs->trans("Country".$obj->code) != "Country".$obj->code) ? img_picto('', DOL_URL_ROOT.'/theme/common/flags/'.strtolower($obj->code).'.png', '', 1).' '.$langs->trans("Country".$obj->code) : ($obj->label ? $obj->label : '<span class="opacitymedium">'.$langs->trans("Unknown").'</span>')),
					'label_en' => (($obj->code && $langsen->transnoentitiesnoconv("Country".$obj->code) != "Country".$obj->code) ? $langsen->transnoentitiesnoconv("Country".$obj->code) : ($obj->label ? $obj->label : '<span class="opacitymedium">'.$langs->trans("Unknown").'</span>')),
					'code' => $obj->code,
					'nb' => $obj->nb,
					'lastdate' => $db->jdate($obj->lastdate),
					'lastsubscriptiondate' => $db->jdate($obj->lastsubscriptiondate)
				);
			}
			if ($mode == 'memberbyregion') { //+
				$data[] = array(
					'label' => (($obj->code && $langs->trans("Country".$obj->code) != "Country".$obj->code) ? img_picto('', DOL_URL_ROOT.'/theme/common/flags/'.strtolower($obj->code).'.png', '', 1).' '.$langs->trans("Country".$obj->code) : ($obj->label ? $obj->label : '<span class="opacitymedium">'.$langs->trans("Unknown").'</span>')),
					'label_en' => (($obj->code && $langsen->transnoentitiesnoconv("Country".$obj->code) != "Country".$obj->code) ? $langsen->transnoentitiesnoconv("Country".$obj->code) : ($obj->label ? $obj->label : '<span class="opacitymedium">'.$langs->trans("Unknown").'</span>')),
					'label2' => ($obj->label2 ? $obj->label2 : '<span class="opacitymedium">'.$langs->trans("Unknown").'</span>'),
					'nb' => $obj->nb,
					'lastdate' => $db->jdate($obj->lastdate),
					'lastsubscriptiondate' => $db->jdate($obj->lastsubscriptiondate)
				);
			}
			if ($mode == 'memberbystate') {
				$data[] = array('label' => (($obj->code && $langs->trans("Country".$obj->code) != "Country".$obj->code) ? img_picto('', DOL_URL_ROOT.'/theme/common/flags/'.strtolower($obj->code).'.png', '', 1).' '.$langs->trans("Country".$obj->code) : ($obj->label ? $obj->label : '<span class="opacitymedium">'.$langs->trans("Unknown").'</span>')),
					'label_en' => (($obj->code && $langsen->transnoentitiesnoconv("Country".$obj->code) != "Country".$obj->code) ? $langsen->transnoentitiesnoconv("Country".$obj->code) : ($obj->label ? $obj->label : '<span class="opacitymedium">'.$langs->trans("Unknown").'</span>')),
					'label2' => ($obj->label2 ? $obj->label2 : '<span class="opacitymedium">'.$langs->trans("Unknown").'</span>'),
					'nb' => $obj->nb,
					'lastdate' => $db->jdate($obj->lastdate),
					'lastsubscriptiondate' => $db->jdate($obj->lastsubscriptiondate)
				);
			}
			if ($mode == 'memberbytown') {
				$data[] = array('label' => (($obj->code && $langs->trans("Country".$obj->code) != "Country".$obj->code) ? img_picto('', DOL_URL_ROOT.'/theme/common/flags/'.strtolower($obj->code).'.png', '', 1).' '.$langs->trans("Country".$obj->code) : ($obj->label ? $obj->label : '<span class="opacitymedium">'.$langs->trans("Unknown").'</span>')),
					'label_en' => (($obj->code && $langsen->transnoentitiesnoconv("Country".$obj->code) != "Country".$obj->code) ? $langsen->transnoentitiesnoconv("Country".$obj->code) : ($obj->label ? $obj->label : '<span class="opacitymedium">'.$langs->trans("Unknown").'</span>')),
					'label2' => ($obj->label2 ? $obj->label2 : '<span class="opacitymedium">'.$langs->trans("Unknown").'</span>'),
					'nb' => $obj->nb,
					'lastdate' => $db->jdate($obj->lastdate),
					'lastsubscriptiondate' => $db->jdate($obj->lastsubscriptiondate)
				);
			}

			$i++;
		}
		$db->free($resql);
	} else {
		dol_print_error($db);
	}
}


$head = member_stats_prepare_head($memberstatic);

echo dol_get_fiche_head($head, $tab, '', -1, '');


// Print title
if ($mode && !count($data)) {
	echo $langs->trans("NoValidatedMemberYet").'<br>';
	echo '<br>';
} else {
	if ($mode == 'memberbycountry') {
		echo '<span class="opacitymedium">'.$langs->trans("MembersByCountryDesc").'</span><br>';
	} elseif ($mode == 'memberbystate') {
		echo '<span class="opacitymedium">'.$langs->trans("MembersByStateDesc").'</span><br>';
	} elseif ($mode == 'memberbytown') {
		echo '<span class="opacitymedium">'.$langs->trans("MembersByTownDesc").'</span><br>';
	} elseif ($mode == 'memberbyregion') {
		echo '<span class="opacitymedium">'.$langs->trans("MembersByRegion").'</span><br>'; //+
	} else {
		echo '<span class="opacitymedium">'.$langs->trans("MembersStatisticsDesc").'</span><br>';
		echo '<br>';
		echo '<a href="'.$_SERVER["PHP_SELF"].'?mode=memberbycountry">'.$langs->trans("MembersStatisticsByCountries").'</a><br>';
		echo '<br>';
		echo '<a href="'.$_SERVER["PHP_SELF"].'?mode=memberbystate">'.$langs->trans("MembersStatisticsByState").'</a><br>';
		echo '<br>';
		echo '<a href="'.$_SERVER["PHP_SELF"].'?mode=memberbytown">'.$langs->trans("MembersStatisticsByTown").'</a><br>';
		echo '<br>'; //+
		echo '<a href="'.$_SERVER["PHP_SELF"].'?mode=memberbyregion">'.$langs->trans("MembersStatisticsByRegion").'</a><br>'; //+
	}
	echo '<br>';
}


// Show graphics
if (count($arrayjs) && $mode == 'memberbycountry') {
	$color_file = DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';
	if (is_readable($color_file)) {
		include $color_file;
	}

	// Assume we've already included the proper headers so just call our script inline
	// More doc: https://developers.google.com/chart/interactive/docs/gallery/geomap?hl=fr-FR
	echo "\n<script type='text/javascript'>\n";
	echo "google.load('visualization', '1', {'packages': ['geomap']});\n";
	echo "google.setOnLoadCallback(drawMap);\n";
	echo "function drawMap() {\n\tvar data = new google.visualization.DataTable();\n";

	// Get the total number of rows
	echo "\tdata.addRows(".count($data).");\n";
	echo "\tdata.addColumn('string', 'Country');\n";
	echo "\tdata.addColumn('number', 'Number');\n";

	// loop and dump
	$i = 0;
	foreach ($data as $val) {
		$valcountry = strtoupper($val['code']); // Should be ISO-3166 code (faster)
		//$valcountry=ucfirst($val['label_en']);
		if ($valcountry == 'Great Britain') {
			$valcountry = 'United Kingdom';
		}    // fix case of uk (when we use labels)
		echo "\tdata.setValue(".$i.", 0, \"".$valcountry."\");\n";
		echo "\tdata.setValue(".$i.", 1, ".$val['nb'].");\n";
		// Google's Geomap only supports up to 400 entries
		if ($i >= 400) {
			break;
		}
		$i++;
	}

	echo "\tvar options = {};\n";
	echo "\toptions['dataMode'] = 'regions';\n";
	echo "\toptions['showZoomOut'] = false;\n";
	//echo "\toptions['zoomOutLabel'] = '".dol_escape_js($langs->transnoentitiesnoconv("Numbers"))."';\n";
	echo "\toptions['width'] = ".$graphwidth.";\n";
	echo "\toptions['height'] = ".$graphheight.";\n";
	echo "\toptions['colors'] = [0x".colorArrayToHex($theme_datacolor[1], 'BBBBBB').", 0x".colorArrayToHex($theme_datacolor[0], '444444')."];\n";
	echo "\tvar container = document.getElementById('".$mode."');\n";
	echo "\tvar geomap = new google.visualization.GeoMap(container);\n";
	echo "\tgeomap.draw(data, options);\n";
	echo "}\n";
	echo "</script>\n";

	// echo the div tag that will contain the map
	echo '<div class="center" id="'.$mode.'"></div>'."\n";
}

if ($mode) {
	// Print array
	echo '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
	echo '<table class="liste centpercent">';
	echo '<tr class="liste_titre">';
	echo '<td>'.$label.'</td>';
	if (isset($label2)) {
		echo '<td class="center">'.$label2.'</td>';
	}
	echo '<td class="right">'.$langs->trans("NbOfMembers").' <span class="opacitymedium">('.$langs->trans("AllTime").')</span></td>';
	echo '<td class="center">'.$langs->trans("LastMemberDate").'</td>';
	echo '<td class="center">'.$langs->trans("LatestSubscriptionDate").'</td>';
	echo '</tr>';

	foreach ($data as $val) {
		$year = isset($val['year']) ? $val['year'] : '';
		echo '<tr class="oddeven">';
		echo '<td>'.$val['label'].'</td>';
		if (isset($label2)) {
			echo '<td class="center">'.$val['label2'].'</td>';
		}
		echo '<td class="right">'.$val['nb'].'</td>';
		echo '<td class="center">'.dol_print_date($val['lastdate'], 'dayhour').'</td>';
		echo '<td class="center">'.dol_print_date($val['lastsubscriptiondate'], 'dayhour').'</td>';
		echo '</tr>';
	}

	echo '</table>';
	echo '</div>';
}


echo dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
