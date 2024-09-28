<?php

'@phan-var-force array{nbfield:int,pos?:array<int,int>,val?:array<int,float>} $totalarray';

// Move fields of totalizable into the common array pos and val
if (!empty($subtotalarray['totalizable']) && is_array($subtotalarray['totalizable'])) {
	foreach ($subtotalarray['totalizable'] as $keytotalizable => $valtotalizable) {
		$totalarray['pos'][$valtotalizable['pos']] = $keytotalizable;
		$subtotalarray['val'][$keytotalizable] = isset($valtotalizable['total']) ? $valtotalizable['total'] : 0;
	}
}
// Show total line
if (isset($totalarray['pos'])) {
	echo '<tr class="liste_total">';
	$j = 0;
	while ($j < $totalarray['nbfield']) {
		$j++;
		if (!empty($totalarray['pos'][$j])) {
			switch ($totalarray['pos'][$j]) {
				case 'duration':
					echo '<td class="right">';
					print(!empty($subtotalarray['val'][$totalarray['pos'][$j]]) ? convertSecondToTime($subtotalarray['val'][$totalarray['pos'][$j]], 'allhourmin') : 0);
					echo '</td>';
					break;
				case 'string':
					echo '<td class="left">';
					print(!empty($subtotalarray['val'][$totalarray['pos'][$j]]) ? $subtotalarray['val'][$totalarray['pos'][$j]] : '');
					echo '</td>';
					break;
				case 'stock':
					echo '<td class="right">';
					echo price2num(!empty($subtotalarray['val'][$totalarray['pos'][$j]]) ? $subtotalarray['val'][$totalarray['pos'][$j]] : 0, 'MS');
					echo '</td>';
					break;
				default:
					echo '<td class="right">';
					echo price(!empty($subtotalarray['val'][$totalarray['pos'][$j]]) ? $subtotalarray['val'][$totalarray['pos'][$j]] : 0);
					echo '</td>';
					break;
			}
			$subtotalarray['val'][$totalarray['pos'][$j]] = 0;
		} else {
			if ($j == 1) {
				echo '<td>'.$langs->trans("SubTotal").'</td>';
			} else {
				echo '<td></td>';
			}
		}
	}
	echo '</tr>';
}
