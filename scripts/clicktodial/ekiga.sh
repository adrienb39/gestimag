#!/bin/bash
#---------------------------------------------------------
# Script to launch Ekiga softphone.
# This script can be used to setup a ClickToDial system
# when using Ekiga soft phone with Gestimag.
# More information on https://wiki.gestimag.org/index.php/Module_ClickToDial_En
#---------------------------------------------------------

ekiga -c "$1" &

