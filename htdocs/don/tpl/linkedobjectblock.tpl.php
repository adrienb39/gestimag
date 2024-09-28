<?php
/* Copyright (C) 2010-2011	Regis Houssin <regis.houssin@inodbox.com>
 * Copyright (C) 2013		Juanjo Menent <jmenent@2byte.es>
 * Copyright (C) 2014       Marcos García <marcosgdf@gmail.com>
 * Copyright (C) 2017       Charlene Benke <cf.benke@patas-monkey.com>
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
 */

echo "<!-- BEGIN PHP TEMPLATE don/tpl/linkedobjectblock.tpl.php -->\n";

global $user;
global $noMoreLinkedObjectBlockAfter;

$langs = $GLOBALS['langs'];
$linkedObjectBlock = $GLOBALS['linkedObjectBlock'];

$langs->load("donations");

$total = 0;
$ilink = 0;
foreach ($linkedObjectBlock as $key => $objectlink) {
	$ilink++;

	$trclass = 'oddeven';
	if ($ilink == count($linkedObjectBlock) && empty($noMoreLinkedObjectBlockAfter) && count($linkedObjectBlock) <= 1) {
		$trclass .= ' liste_sub_total';
	}
	echo '<tr class="'.$trclass.'">';
	echo '<td>'.$langs->trans("Donation").'</td>';
	echo '<td>'.$objectlink->getNomUrl(1).'</td>';
	echo '<td class="center">'.$objectlink->ref_client.'</td>';
	echo '<td class="center">'.dol_print_date($objectlink->date, 'day').'</td>';
	echo '<td class="right">';
	$total = $total + $objectlink->total_ht;
	echo  price($objectlink->total_ht);
}
echo '</td>';
echo '<td class="right">'.$objectlink->getLibStatut(3).'</td>';
echo '</tr>';

if (count($linkedObjectBlock) > 1) {
	?>
	<tr class="liste_total <?php echo (empty($noMoreLinkedObjectBlockAfter) ? 'liste_sub_total' : ''); ?>">
		<td><?php echo  $langs->trans("Total"); ?></td>
		<td></td>
		<td class="center"></td>
		<td class="center"></td>
		<td class="right"><?php echo  price($total); ?></td>
		<td class="right"></td>
		<td class="right"></td>
	</tr>
	<?php
}

echo "<!-- END PHP TEMPLATE -->\n";
