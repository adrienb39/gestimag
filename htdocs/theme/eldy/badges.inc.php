<?php
if (!defined('ISLOADEDBYSTEELSHEET')) {
	die('Must be call by steelsheet');
}
?>
/* Badge style is based on bootstrap framework */

.badge {
	display: inline-block;
	padding: .1em .35em;
	font-size: 80%;
	font-weight: 700 !important;
	line-height: 1;
	text-align: center;
	white-space: nowrap;
	vertical-align: baseline;
	border-radius: .25rem;
	transition: color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out;
	border-width: 2px;
	border-style: solid;
	border-color: rgba(255,255,255,0);
	box-sizing: border-box;
}

.badge-status {
	font-size: 0.95em;
	padding: .19em .35em;			/* more than 0.19 generate a change into height of lines */
}
.tabBar .arearef .statusref .badge-status, .tabBar .arearefnobottom .statusref .badge-status {
	font-size: 1.1em;
	padding: .4em .4em;
}
/* Force values for small screen 767 */
@media only screen and (max-width: 767px)
{
	.tabBar .arearef .statusref .badge-status, .tabBar .arearefnobottom .statusref .badge-status {
		font-size: 0.95em;
		padding: .3em .2em;
	}
}

.badge-pill, .tabs .badge {
	padding-right: .5em;
	padding-left: .5em;
	border-radius: 0.25rem;
}

.badge-dot {
	padding: 0;
	border-radius: 50%;
	padding: 0.45em;
	vertical-align: text-top;
}

a.badge:focus, a.badge:hover {
	text-decoration: none;
}

.liste_titre .badge:not(.nochangebackground) {
	background-color: <?php echo $badgeSecondary; ?>;
	color: #fff;
}

span.badgeneutral {
	padding: 2px 7px 2px 7px;
	background-color: #e4e4e4;
	color: #666;
	border-radius: 10px;
	white-space: nowrap;
}


/* PRIMARY */
.badge-primary{
	color: #fff !important;
	background-color: <?php echo $badgePrimary; ?>;
}
a.badge-primary.focus, a.badge-primary:focus {
	outline: 0;
	box-shadow: 0 0 0 0.2rem <?php echo colorHexToRgb($badgePrimary, 0.5); ?>;
}
a.badge-primary:focus, a.badge-primary:hover {
	color: #fff !important;
	background-color: <?php echo colorDarker($badgePrimary, 10); ?>;
}

/* SECONDARY */
.badge-secondary, .tabs .badge {
	color: #fff !important;
	background-color: <?php echo $badgeSecondary; ?>;
}
a.badge-secondary.focus, a.badge-secondary:focus {
	outline: 0;
	box-shadow: 0 0 0 0.2rem <?php echo colorHexToRgb($badgeSecondary, 0.5); ?>;
}
a.badge-secondary:focus, a.badge-secondary:hover {
	color: #fff !important;
	background-color: <?php echo colorDarker($badgeSecondary, 10); ?>;
}

/* SUCCESS */
.badge-success {
	color: #fff !important;
	background-color: <?php echo $badgeSuccess; ?>;
}
a.badge-success.focus, a.badge-success:focus {
	outline: 0;
	box-shadow: 0 0 0 0.2rem <?php echo colorHexToRgb($badgeSuccess, 0.5); ?>;
}
a.badge-success:focus, a.badge-success:hover {
	color: #fff !important;
	background-color: <?php echo colorDarker($badgeSuccess, 10); ?>;
}

/* DANGER */
.badge-danger {
	color: #fff !important;
	background-color: <?php echo $badgeDanger; ?>;
}
a.badge-danger.focus, a.badge-danger:focus {
	outline: 0;
	box-shadow: 0 0 0 0.2rem <?php echo colorHexToRgb($badgeDanger, 0.5); ?>;
}
a.badge-danger:focus, a.badge-danger:hover {
	color: #fff !important;
	background-color: <?php echo colorDarker($badgeDanger, 10); ?>;
}

/* WARNING */
.badge-warning {
	color: #fff !important;
	background-color: <?php echo $badgeWarning; ?>;
}
a.badge-warning.focus, a.badge-warning:focus {
	outline: 0;
	box-shadow: 0 0 0 0.2rem <?php echo colorHexToRgb($badgeWarning, 0.5); ?>;
}
a.badge-warning:focus, a.badge-warning:hover {
	color: #212529 !important;
	background-color: <?php echo colorDarker($badgeWarning, 10); ?>;
}

/* WARNING colorblind */
body[class*="colorblind-"] .badge-warning {
	  background-color: <?php echo $colorblind_deuteranopes_badgeWarning; ?>;
  }
body[class*="colorblind-"] a.badge-warning.focus,body[class^="colorblind-"] a.badge-warning:focus {
	box-shadow: 0 0 0 0.2rem <?php echo colorHexToRgb($colorblind_deuteranopes_badgeWarning, 0.5); ?>;
}
body[class*="colorblind-"] a.badge-warning:focus, a.badge-warning:hover {
	background-color: <?php echo colorDarker($colorblind_deuteranopes_badgeWarning, 10); ?>;
}

/* INFO */
.badge-info {
	color: #fff !important;
	background-color: <?php echo $badgeInfo; ?>;
}
a.badge-info.focus, a.badge-info:focus {
	outline: 0;
	box-shadow: 0 0 0 0.2rem <?php echo colorHexToRgb($badgeInfo, 0.5); ?>;
}
a.badge-info:focus, a.badge-info:hover {
	color: #fff !important;
	background-color: <?php echo colorDarker($badgeInfo, 10); ?>;
}

/* LIGHT */
.badge-light {
	color: #212529 !important;
	background-color: <?php echo $badgeLight; ?>;
}
a.badge-light.focus, a.badge-light:focus {
	outline: 0;
	box-shadow: 0 0 0 0.2rem <?php echo colorHexToRgb($badgeLight, 0.5); ?>;
}
a.badge-light:focus, a.badge-light:hover {
	color: #212529 !important;
	background-color: <?php echo colorDarker($badgeLight, 10); ?>;
}

/* DARK */
.badge-dark {
	color: #fff !important;
	background-color: <?php echo $badgeDark; ?>;
}
a.badge-dark.focus, a.badge-dark:focus {
	outline: 0;
	box-shadow: 0 0 0 0.2rem <?php echo colorHexToRgb($badgeDark, 0.5); ?>;
}
a.badge-dark:focus, a.badge-dark:hover {
	color: #fff !important;
	background-color: <?php echo colorDarker($badgeDark, 10); ?>;
}


@media only screen and (max-width: 570px)
{
	span.badge.badge-status {
		overflow: hidden;
		max-width: 130px;
		text-overflow: ellipsis;
	}
}


/* STATUS BADGES */
<?php
for ($i = 0; $i <= 10; $i++) {
	/* Default Status */
	_createStatusBadgeCss($i, '', "STATUS".$i);

	// create status for accessibility
	_createStatusBadgeCss($i, 'colorblind_deuteranopes_', "COLORBLIND STATUS".$i, 'body[class*="colorblind-"] ');
}

_createStatusBadgeCss('1b', '', "STATUS1b");
_createStatusBadgeCss('4b', '', "STATUS4b");


/**
 * Create status badge
 *
 * @param string $statusName 			name of status
 * @param string $statusVarNamePrefix 	a prefix for var ${$statusVarNamePrefix.'badgeStatus'.$statusName}
 * @param string $commentLabel 			a comment label
 * @param string $cssPrefix 			a css prefix
 * @return void
 */
function _createStatusBadgeCss($statusName, $statusVarNamePrefix = '', $commentLabel = '', $cssPrefix = '')
{
	global ${$statusVarNamePrefix.'badgeStatus'.$statusName}, ${$statusVarNamePrefix.'badgeStatus_textColor'.$statusName};

	if (!empty(${$statusVarNamePrefix.'badgeStatus'.$statusName})) {
		echo "\n/* ".strtoupper($commentLabel)." */\n";

		$thisBadgeBackgroundColor = $thisBadgeBorderColor = ${$statusVarNamePrefix.'badgeStatus'.$statusName};

		$TBadgeBorderOnly = array('0', '1b', '3', '4b', '5', '7', '10');
		$thisBadgeTextColor = colorIsLight(${$statusVarNamePrefix.'badgeStatus'.$statusName}) ? '#212529' : '#ffffff';

		if (!empty(${$statusVarNamePrefix.'badgeStatus_textColor'.$statusName})) {
			$thisBadgeTextColor = ${$statusVarNamePrefix.'badgeStatus_textColor'.$statusName};
		}

		if (in_array((string) $statusName, $TBadgeBorderOnly)) {
			$thisBadgeTextColor = '#212529';
			$thisBadgeBackgroundColor = "";
		}

		if (in_array((string) $statusName, array('0', '5', '9'))) {
			$thisBadgeTextColor = '#999999';
		}
		if (in_array((string) $statusName, array('6'))) {
			$thisBadgeTextColor = '#777777';
		}

		// badge-statusX
		echo $cssPrefix.".badge-status".$statusName." {\n";
		echo "        color: ".$thisBadgeTextColor." !important;\n";
		if (in_array((string) $statusName, $TBadgeBorderOnly)) {
			echo "        border-color: ".$thisBadgeBorderColor." !important;\n";
		}
		if ($thisBadgeBackgroundColor != '') {
			echo "        background-color: ".$thisBadgeBackgroundColor." !important;\n";
		}
		echo "}\n";

		echo $cssPrefix.".font-status".$statusName." {\n";
		if ($thisBadgeBackgroundColor != '') {
			echo "        color: ".$thisBadgeBackgroundColor." !important;\n";
		}
		echo "}\n";

		echo $cssPrefix.".badge-status".$statusName.".focus, ".$cssPrefix.".badge-status".$statusName.":focus {\n";
		echo "    outline: 0;\n";
		echo "    box-shadow: 0 0 0 0.2rem ".colorHexToRgb($thisBadgeBackgroundColor, 0.5)." !important;\n";
		echo "}\n";

		// badge-statusX:focus
		echo $cssPrefix.".badge-status".$statusName.":focus, ".$cssPrefix.".badge-status".$statusName.":hover {\n";
		echo "    color: ".$thisBadgeTextColor." !important;\n";
		//echo "    background-color: " . colorDarker($thisBadgeBackgroundColor, 10) . ";\n";
		if (in_array((string) $statusName, $TBadgeBorderOnly)) {
			echo "        border-color: ".colorDarker($thisBadgeBorderColor, 10)." !important;\n";
		}
		echo "}\n";
	}
}
