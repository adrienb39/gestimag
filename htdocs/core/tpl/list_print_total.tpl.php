<?php

'@phan-var-force array{nbfield:int,type?:array<int,string>,pos?:array<int,int>,val?:array<int,float>} $totalarray';

// Move fields of totalizable into the common array pos and val
if (!empty($totalarray['totalizable']) && is_array($totalarray['totalizable'])) {
	foreach ($totalarray['totalizable'] as $keytotalizable => $valtotalizable) {
		$totalarray['pos'][$valtotalizable['pos']] = $keytotalizable;
		$totalarray['val'][$keytotalizable] = isset($valtotalizable['total']) ? $valtotalizable['total'] : 0;
	}
}
// Show total line
if (isset($totalarray['pos'])) {
	echo '<tfoot>';
	echo '<tr class="liste_total">';
	$i = 0;
	while ($i < $totalarray['nbfield']) {
		$i++;
		if (!empty($totalarray['pos'][$i])) {
			printTotalValCell($totalarray['type'][$i] ?? '', empty($totalarray['val'][$totalarray['pos'][$i]]) ? 0 : $totalarray['val'][$totalarray['pos'][$i]]);
		} else {
			if ($i == 1) {
				if ((is_null($limit) || $num < $limit) && empty($offset)) {
					echo '<td>'.$langs->trans("Total").'</td>';
				} else {
					echo '<td>';
					if (is_object($form)) {
						echo $form->textwithpicto($langs->trans("Total"), $langs->transnoentitiesnoconv("Totalforthispage"));
					} else {
						echo $langs->trans("Totalforthispage");
					}
					echo '</td>';
				}
			} else {
				echo '<td></td>';
			}
		}
	}
	echo '</tr>';
	// Add grand total if necessary ie only if different of page total already printed above
	if (getDolGlobalString('MAIN_GRANDTOTAL_LIST_SHOW') && (!(is_null($limit) || $num < $limit))) {
		if (isset($totalarray['pos']) && is_array($totalarray['pos']) && count($totalarray['pos']) > 0) {
			$sumsarray = false;
			$tbsumfields = [];
			foreach ($totalarray['pos'] as $field) {
				$fieldforsum = preg_replace('/[^a-z0-9]/', '', $field);
				$tbsumfields[] = "sum($field) as $fieldforsum";
			}
			if (isset($sqlfields)) { // In project, commande list, this var is defined
				$sqlforgrandtotal = preg_replace('/^'.preg_quote($sqlfields, '/').'/', 'SELECT '. implode(",", $tbsumfields), $sql);
			} else {
				$sqlforgrandtotal = preg_replace('/^SELECT[a-zA-Z0-9\._\s\(\),=<>\:\-\']+\sFROM/', 'SELECT '. implode(",", $tbsumfields). ' FROM ', $sql);
			}
			$sqlforgrandtotal = preg_replace('/GROUP BY .*$/', '', $sqlforgrandtotal). '';
			$resql = $db->query($sqlforgrandtotal);
			if ($resql) {
				$sumsarray = $db->fetch_array($resql);
			} else {
				//dol_print_error($db); // as we're not sure it's ok for ALL lists, we don't echo sq errors, they'll be in logs
			}
			if (is_array($sumsarray) && count($sumsarray) > 0) {
				echo '<tr class="liste_grandtotal">';
				$i = 0;
				while ($i < $totalarray['nbfield']) {
					$i++;
					if (!empty($totalarray['pos'][$i])) {
						printTotalValCell($totalarray['type'][$i], $sumsarray[$totalarray['pos'][$i]]);
					} else {
						if ($i == 1) {
							echo '<td>';
							if (is_object($form)) {
								echo $form->textwithpicto($langs->trans("GrandTotal"), $langs->transnoentitiesnoconv("TotalforAllPages"));
							} else {
								echo $langs->trans("GrandTotal");
							}
							echo '</td>';
						} else {
							echo '<td></td>';
						}
					}
				}
				echo '</tr>';
			}
		}
	}
	echo '</tfoot>';
}

/** echo a total cell value according to its type
 *
 * @param string $type of field (duration, string..)
 * @param string $val the value to display
 *
 * @return void (direct print)
 */
function printTotalValCell($type, $val)
{
	// if $totalarray['type'] not present we consider it as number
	if (empty($type)) {
		$type = 'real';
	}
	switch ($type) {
		case 'duration':
			echo '<td class="right">';
			print(!empty($val) ? convertSecondToTime($val, 'allhourmin') : 0);
			echo '</td>';
			break;
		case 'string':	// This type is no more used. type is now varchar(x)
			echo '<td class="left">';
			print(!empty($val) ? $val : '');
			echo '</td>';
			break;
		case 'stock':
			echo '<td class="right">';
			echo price2num(!empty($val) ? $val : 0, 'MS');
			echo '</td>';
			break;
		default:
			echo '<td class="right">';
			echo price(!empty($val) ? $val : 0);
			echo '</td>';
			break;
	}
}
