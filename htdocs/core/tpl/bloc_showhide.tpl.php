<?php
/* Copyright (C) 2012       Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2013       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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

// Protection to avoid direct call of template
if (empty($blocname)) {
	echo "Error, template page can't be called as URL";
	exit(1);
}

$hide = true; // Hide by default
if (isset($parameters['showblocbydefault'])) {
	$hide = (empty($parameters['showblocbydefault']) ? true : false);
}
if (isset($object->extraparams[$blocname]['showhide'])) {
	$hide = (empty($object->extraparams[$blocname]['showhide']) ? true : false);
}

?>
<!-- BEGIN PHP TEMPLATE bloc_showhide.tpl.php -->

<?php
echo '<script>'."\n";
echo '$(document).ready(function() {'."\n";
echo '$("#hide-'.$blocname.'").click(function(){'."\n";
echo '		setShowHide(0);'."\n";
echo '		$("#'.$blocname.'_bloc").hide("blind", {direction: "vertical"}, 300).removeClass("nohideobject");'."\n";
echo '		$(this).hide();'."\n";
echo '		$("#show-'.$blocname.'").show();'."\n";
echo '});'."\n";

echo '$("#show-'.$blocname.'").click(function(){'."\n";
echo '		setShowHide(1);'."\n";
echo '		$("#'.$blocname.'_bloc").show("blind", {direction: "vertical"}, 300).addClass("nohideobject");'."\n";
echo '		$(this).hide();'."\n";
echo '		$("#hide-'.$blocname.'").show();'."\n";
echo '});'."\n";

echo 'function setShowHide(status) {'."\n";
echo '		var id			= '.((int) $object->id).";\n";
echo "		var element		= '".dol_escape_js($object->element)."';\n";
echo "		var htmlelement	= '".dol_escape_js($blocname)."';\n";
echo '		var type		= "showhide";'."\n";
echo '		$.get("'.dol_buildpath('/core/ajax/extraparams.php', 1);
echo '?id="+id+"&element="+element+"&htmlelement="+htmlelement+"&type="+type+"&value="+status);'."\n";
echo '}'."\n";

echo '});'."\n";
echo '</script>'."\n";

echo '<div style="float:right; position: relative; top: 3px; right:5px;" id="hide-'.$blocname.'"';
echo ' class="linkobject'.($hide ? ' hideobject' : '').'">'.img_picto('', '1uparrow.png').'</div>'."\n";
echo '<div style="float:right; position: relative; top: 3px; right:5px;" id="show-'.$blocname.'"';
echo ' class="linkobject'.($hide ? '' : ' hideobject').'">'.img_picto('', '1downarrow.png').'</div>'."\n";
echo '<div id="'.$blocname.'_title" class="liste_titre">'.$title.'</div>'."\n";
echo '<div id="'.$blocname.'_bloc" class="'.($hide ? 'hideobject' : 'nohideobject').'">'."\n";

include DOL_DOCUMENT_ROOT.'/core/tpl/'.$blocname.'.tpl.php';
echo '</div><br>';
?>
<!-- END PHP TEMPLATE BLOCK SHOW/HIDE -->
