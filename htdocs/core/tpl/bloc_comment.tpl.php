<?php

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	echo "Error, template page can't be called as URL";
	exit(1);
}

// Require
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';


// Vars
$userstatic = new User($db);
$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;


// Add comment
echo '<br>';
echo '<div id="comment">';
echo '<form method="POST" action="'.$varpage.'?id='.$object->id.'">';
echo '<input type="hidden" name="token" value="'.newToken().'">';
echo '<input type="hidden" name="action" value="addcomment">';
echo '<input type="hidden" name="id" value="'.$object->id.'">';
echo '<input type="hidden" name="comment_element_type" value="'.$object->element.'">';
echo '<input type="hidden" name="withproject" value="'.$withproject.'">';

echo '<table class="noborder nohover centpercent">';

echo '<tr class="liste_titre">';
echo '<td width="25%">'.$langs->trans("Comments").'</td>';
echo '<td width="25%"></td>';
echo '<td width="25%"></td>';
echo '<td width="25%"></td>';
echo "</tr>\n";

if ($action !== 'editcomment') {
	echo '<tr class="oddeven">';

	// Description
	echo '<td colspan="3">';

	$desc = GETPOST('comment_description');

	$doleditor = new DolEditor('comment_description', $desc, '', 80, 'gestimag_notes', 'In', 0, true, true, ROWS_3, '100%');
	echo $doleditor->Create(1);

	echo '</td>';

	echo '<td class="center">';
	echo '<input type="submit" class="button button-add" value="'.$langs->trans("Add").'">';
	echo '</td></tr>';
}

echo '</table></form>';

// List of comments
if (!empty($object->comments)) {
	// Default color for current user
	$TColors = array($user->id => array('bgcolor'=>'efefef', 'color'=>'555'));
	$first = true;
	foreach ($object->comments as $comment) {
		$fk_user = $comment->fk_user_author;
		$userstatic->fetch($fk_user);

		if (empty($TColors[$fk_user])) {
			$bgcolor = randomColor(180, 240);
			if (!empty($userstatic->color)) {
				$bgcolor = $userstatic->color;
			}
			$color = (colorIsLight($bgcolor)) ? '555' : 'fff';
			$TColors[$fk_user] = array('bgcolor'=>$bgcolor, 'color'=>$color);
		}
		echo '<div class="width100p" style="color:#'.$TColors[$fk_user]['color'].'">';
		if ($fk_user != $user->id) {
			echo '<div class="width25p float">&nbsp;</div>';
		}

		echo '<div class="width75p float comment comment-table" style="background-color:#'.$TColors[$fk_user]['bgcolor'].'">';
		echo '<div class="comment-info comment-cell">';
		if (!empty($user->photo)) {
			echo Form::showphoto('userphoto', $userstatic, 80, 0, 0, '', 'small', 0, 1).'<br>';
		}
		echo $langs->trans('User').' : '.$userstatic->getNomUrl().'<br>';
		echo $langs->trans('Date').' : '.dol_print_date($comment->datec, 'dayhoursec');
		echo '</div>'; // End comment-info

		echo '<div class="comment-cell comment-right">';
		echo '<div class="comment-table width100p">';

		if ($action === 'editcomment' && $comment->id == $idcomment) {
			echo '<form method="POST" action="'.$varpage.'?id='.$object->id.'">';
			echo '<input type="hidden" name="token" value="'.newToken().'">';
			echo '<input type="hidden" name="action" value="updatecomment">';
			echo '<input type="hidden" name="id" value="'.$object->id.'">';
			echo '<input type="hidden" name="idcomment" value="'.$idcomment.'">';
			echo '<input type="hidden" name="withproject" value="'.$withproject.'">';
		}

		echo '<div class="comment-description comment-cell">';
		if ($action === 'editcomment' && $comment->id == $idcomment) {
			$doleditor = new DolEditor('comment_description', $comment->description, '', 80, 'gestimag_notes', 'In', 0, true, true, ROWS_3, '100%');
			echo $doleditor->Create(1);
		} else {
			echo $comment->description;
		}
		echo '</div>'; // End comment-description

		if ($action === 'editcomment' && $comment->id == $idcomment) {
			echo '<input name="update" type="submit" class="button" value="'.$langs->trans("Update").'">';
			echo '<input name="cancel" type="submit" class="button button-cancel" value="'.$langs->trans("Cancel").'">';

			echo '</form>';
		} else {
			if ($fk_user == $user->id || $user->admin == 1) {
				echo '<a class="comment-edit comment-cell" href="'.$varpage.'?action=editcomment&token='.newToken().'&id='.$id.'&withproject=1&idcomment='.$comment->id.'#comment" title="'.$langs->trans('Edit').'">';
				echo img_picto('', 'edit.png');
				echo '</a>';
			}
			if (($first && $fk_user == $user->id) || $user->admin == 1) {
				echo '<a class="comment-delete comment-cell" href="'.$varpage.'?action=deletecomment&token='.newToken().'&id='.$id.'&withproject=1&idcomment='.$comment->id.'" title="'.$langs->trans('Delete').'">';
				echo img_picto('', 'delete.png');
				echo '</a>';
			}
		}

		echo '</div>'; // End comment-table
		echo '</div>'; // End comment-right
		echo '</div>'; // End comment

		if ($fk_user == $user->id) {
			echo '<div class="width25p float">&nbsp;</div>';
		}
		echo '<div class="clearboth"></div>';
		echo '</div>'; // end 100p

		$first = false;
	}
}

echo '<br>';
echo '</div>';
