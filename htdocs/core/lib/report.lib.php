<?php
/* Copyright (C) 2008-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin		<regis.houssin@inodbox.com>
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
 * or see https://www.gnu.org/
 */

/**
 *  \file       	htdocs/core/lib/report.lib.php
 *  \brief      	Set of functions for reporting
 */


/**
 *	Show header of a report
 *
 *	@param	string				$reportname     Name of report
 *	@param 	string				$notused        Not used
 *	@param 	string				$period         Period of report
 *	@param 	string				$periodlink     Link to switch period
 *	@param 	string				$description    Description
 *	@param 	integer	            $builddate      Date generation
 *	@param 	string				$exportlink     Link for export or ''
 *	@param	array				$moreparam		Array with list of params to add into form
 *	@param	string				$calcmode		Calculation mode
 *  @param  string              $varlink        Add a variable into the address of the page
 *	@return	void
 */
function report_header($reportname, $notused, $period, $periodlink, $description, $builddate, $exportlink = '', $moreparam = array(), $calcmode = '', $varlink = '')
{
	global $langs;

	echo "\n\n<!-- start banner of report -->\n";

	if (!empty($varlink)) {
		$varlink = '?'.$varlink;
	}

	$title = $langs->trans("Report");

	print_barre_liste($title, 0, '', '', '', '', '', -1, '', 'generic', 0, '', '', -1, 1, 1);

	echo '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].$varlink.'">'."\n";
	echo '<input type="hidden" name="token" value="'.newToken().'">'."\n";

	echo dol_get_fiche_head();

	foreach ($moreparam as $key => $value) {
		echo '<input type="hidden" name="'.$key.'" value="'.$value.'">'."\n";
	}

	echo '<table class="border tableforfield centpercent">'."\n";

	$variant = ($periodlink || $exportlink);

	// Ligne de titre
	echo '<tr>';
	echo '<td width="150">'.$langs->trans("ReportName").'</td>';
	echo '<td>';
	echo $reportname;
	echo '</td>';
	if ($variant) {
		echo '<td></td>';
	}
	echo '</tr>'."\n";

	// Calculation mode
	if ($calcmode) {
		echo '<tr>';
		echo '<td width="150">'.$langs->trans("CalculationMode").'</td>';
		echo '<td>';
		echo $calcmode;
		if ($variant) {
			echo '<td></td>';
		}
		echo '</td>';
		echo '</tr>'."\n";
	}

	// Ligne de la periode d'analyse du rapport
	echo '<tr>';
	echo '<td>'.$langs->trans("ReportPeriod").'</td>';
	echo '<td>';
	if ($period) {
		echo $period;
	}
	if ($variant) {
		echo '<td class="nowraponall">'.$periodlink.'</td>';
	}
	echo '</td>';
	echo '</tr>'."\n";

	// Ligne de description
	echo '<tr>';
	echo '<td>'.$langs->trans("ReportDescription").'</td>';
	echo '<td>'.$description.'</td>';
	if ($variant) {
		echo '<td></td>';
	}
	echo '</tr>'."\n";

	// Ligne d'export
	echo '<tr>';
	echo '<td>'.$langs->trans("GeneratedOn").'</td>';
	echo '<td>';
	echo dol_print_date($builddate, 'dayhour');
	echo '</td>';
	if ($variant) {
		echo '<td>'.($exportlink ? $langs->trans("Export").': '.$exportlink : '').'</td>';
	}
	echo '</tr>'."\n";

	echo '</table>'."\n";

	echo dol_get_fiche_end();

	echo '<div class="center"><input type="submit" class="button" name="submit" value="'.$langs->trans("Refresh").'"></div>';

	echo '</form>';
	echo '<br>';

	echo "\n<!-- end banner of report -->\n\n";
}
