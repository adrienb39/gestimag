<!-- BEGIN TEMPLATE resource_view.tpl.php -->
<?php
// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	echo "Error, template page can't be called as URL";
	exit(1);
}


$form = new Form($db);


echo '<div class="tagtable centpercent noborder allwidth">';

echo '<form method="POST" class="tagtable centpercent noborder borderbottom allwidth">';

echo '<div class="tagtr liste_titre">';
echo '<div class="tagtd liste_titre">'.$langs->trans('Resource').'</div>';
echo '<div class="tagtd liste_titre">'.$langs->trans('Type').'</div>';
echo '<div class="tagtd liste_titre center">'.$langs->trans('Busy').'</div>';
echo '<div class="tagtd liste_titre center">'.$langs->trans('Mandatory').'</div>';
echo '<div class="tagtd liste_titre"></div>';
echo '</div>';

echo '<input type="hidden" name="token" value="'.newToken().'" />';
echo '<input type="hidden" name="id" value="'.$element_id.'" />';
echo '<input type="hidden" name="action" value="update_linked_resource" />';
echo '<input type="hidden" name="resource_type" value="'.$resource_type.'" />';

if ((array) $linked_resources && count($linked_resources) > 0) {
	foreach ($linked_resources as $linked_resource) {
		$object_resource = fetchObjectByElement($linked_resource['resource_id'], $linked_resource['resource_type']);

		//$element_id = $linked_resource['rowid'];

		if ($mode == 'edit' && $linked_resource['rowid'] == GETPOSTINT('lineid')) {
			echo '<div class="tagtr oddeven">';
			echo '<input type="hidden" name="lineid" value="'.$linked_resource['rowid'].'" />';
			echo '<input type="hidden" name="element" value="'.$element.'" />';
			echo '<input type="hidden" name="element_id" value="'.$element_id.'" />';

			echo '<div class="tagtd">'.$object_resource->getNomUrl(1).'</div>';
			echo '<div class="tagtd">'.$object_resource->type_label.'</div>';
			echo '<div class="tagtd center">'.$form->selectyesno('busy', $linked_resource['busy'] ? 1 : 0, 1).'</div>';
			echo '<div class="tagtd center">'.$form->selectyesno('mandatory', $linked_resource['mandatory'] ? 1 : 0, 1).'</div>';
			echo '<div class="tagtd right"><input type="submit" class="button" value="'.$langs->trans("Update").'"></div>';
			echo '</div>';
		} else {
			$class = '';
			if ($linked_resource['rowid'] == GETPOSTINT('lineid')) {
				$class = 'highlight';
			}

			echo '<div class="tagtr oddeven'.($class ? ' '.$class : '').'">';

			echo '<div class="tagtd">';
			echo $object_resource->getNomUrl(1);
			echo '</div>';

			echo '<div class="tagtd">';
			echo $object_resource->type_label;
			echo '</div>';

			echo '<div class="tagtd center">';
			echo yn($linked_resource['busy']);
			echo '</div>';

			echo '<div class="tagtd center">';
			echo yn($linked_resource['mandatory']);
			echo '</div>';

			echo '<div class="tagtd right">';
			echo '<a class="editfielda marginleftonly marginrightonly" href="'.$_SERVER['PHP_SELF'].'?mode=edit&token='.newToken().'&resource_type='.$linked_resource['resource_type'].'&element='.$element.'&element_id='.$element_id.'&lineid='.$linked_resource['rowid'].'">';
			echo img_edit();
			echo '</a>';
			echo '&nbsp;';
			echo '<a class="marginleftonly marginrightonly" href="'.$_SERVER['PHP_SELF'].'?action=delete_resource&token='.newToken().'&id='.$linked_resource['resource_id'].'&element='.$element.'&element_id='.$element_id.'&lineid='.$linked_resource['rowid'].'">';
			echo img_picto($langs->trans("Unlink"), 'unlink');
			echo '</a>';
			echo '</div>';

			echo '</div>';
		}
	}
} else {
	echo '<div class="tagtr oddeven">';
	echo '<div class="tagtd opacitymedium">'.$langs->trans('NoResourceLinked').'</div>';
	echo '<div class="tagtd opacitymedium"></div>';
	echo '<div class="tagtd opacitymedium"></div>';
	echo '<div class="tagtd opacitymedium"></div>';
	echo '<div class="tagtd opacitymedium"></div>';
	echo '</div>';
}

echo '</form>';

echo '</div>';

?>
<!-- END TEMPLATE resource_view.tpl.php -->
