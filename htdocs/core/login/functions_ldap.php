<?php
/* Copyright (C) 2007-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2008-2021 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2024      MDW                  <mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024      William Mead         <william.mead@manchenumerique.fr>
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

/**
 *       \file       htdocs/core/login/functions_ldap.php
 *       \ingroup    core
 *       \brief      Authentication functions for LDAP
 */


/**
 * Check validity of user/password/entity
 * If test is ko, reason must be filled into $_SESSION["dol_loginmesg"]
 *
 * @param	string	$usertotest		Login
 * @param	string	$passwordtotest	Password
 * @param   int		$entitytotest   Numero of instance (always 1 if module multicompany not enabled)
 * @return	string					Login if OK, '' if KO
 */
function check_user_password_ldap($usertotest, $passwordtotest, $entitytotest)
{
	global $db, $conf, $langs;
	global $gestimag_main_auth_ldap_host, $gestimag_main_auth_ldap_port;
	global $gestimag_main_auth_ldap_version, $gestimag_main_auth_ldap_servertype;
	global $gestimag_main_auth_ldap_login_attribute, $gestimag_main_auth_ldap_dn;
	global $gestimag_main_auth_ldap_admin_login, $gestimag_main_auth_ldap_admin_pass;
	global $gestimag_main_auth_ldap_filter;
	global $gestimag_main_auth_ldap_debug;

	// Force master entity in transversal mode
	$entity = $entitytotest;
	if (isModEnabled('multicompany') && getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE')) {
		$entity = 1;
	}

	$login = '';
	$resultFetchUser = '';

	if (!function_exists("ldap_connect")) {
		dol_syslog("functions_ldap::check_user_password_ldap Authentication KO failed to connect to LDAP. LDAP functions are disabled on this PHP", LOG_ERR);
		sleep(1);

		// Load translation files required by the page
		$langs->loadLangs(array('main', 'other'));

		$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("ErrorLDAPFunctionsAreDisabledOnThisPHP").' '.$langs->transnoentitiesnoconv("TryAnotherConnectionMode");
		return '';
	}

	if ($usertotest) {
		dol_syslog("functions_ldap::check_user_password_ldap usertotest=".$usertotest." passwordtotest=".preg_replace('/./', '*', $passwordtotest)." entitytotest=".$entitytotest);

		// If test username/password asked, we define $test=false and $login var if ok, set $_SESSION["dol_loginmesg"] if ko
		$ldaphost = $gestimag_main_auth_ldap_host;
		$ldapport = $gestimag_main_auth_ldap_port;
		$ldapversion = $gestimag_main_auth_ldap_version;
		$ldapservertype = (empty($gestimag_main_auth_ldap_servertype) ? 'openldap' : $gestimag_main_auth_ldap_servertype);

		$ldapuserattr = $gestimag_main_auth_ldap_login_attribute;
		$ldapdn = $gestimag_main_auth_ldap_dn;
		$ldapadminlogin = $gestimag_main_auth_ldap_admin_login;
		$ldapadminpass = $gestimag_main_auth_ldap_admin_pass;
		$ldapdebug = ((empty($gestimag_main_auth_ldap_debug) || $gestimag_main_auth_ldap_debug == "false") ? false : true);

		if ($ldapdebug) {
			echo"DEBUG: Logging LDAP steps<br>\n";
		}

		require_once DOL_DOCUMENT_ROOT.'/core/class/ldap.class.php';
		$ldap = new Ldap();
		$ldap->server = explode(',', $ldaphost);
		$ldap->serverPort = $ldapport;
		$ldap->ldapProtocolVersion = $ldapversion;
		$ldap->serverType = $ldapservertype;
		$ldap->searchUser = $ldapadminlogin;
		$ldap->searchPassword = $ldapadminpass;

		if ($ldapdebug) {
			dol_syslog("functions_ldap::check_user_password_ldap Server:".implode(',', $ldap->server).", Port:".$ldap->serverPort.", Protocol:".$ldap->ldapProtocolVersion.", Type:".$ldap->serverType);
			dol_syslog("functions_ldap::check_user_password_ldap uid/samaccountname=".$ldapuserattr.", dn=".$ldapdn.", Admin:".$ldap->searchUser.", Pass:".dol_trunc($ldap->searchPassword, 3));
			echo"DEBUG: Server:".implode(',', $ldap->server).", Port:".$ldap->serverPort.", Protocol:".$ldap->ldapProtocolVersion.", Type:".$ldap->serverType."<br>\n";
			echo"DEBUG: uid/samaccountname=".$ldapuserattr.", dn=".$ldapdn.", Admin:".$ldap->searchUser.", Pass:".dol_trunc($ldap->searchPassword, 3)."<br>\n";
		}

		$resultFetchLdapUser = 0;

		// Define $userSearchFilter
		$userSearchFilter = "";
		if (empty($gestimag_main_auth_ldap_filter)) {
			$userSearchFilter = "(".$ldapuserattr."=".$usertotest.")";
		} else {
			// @phan-suppress-next-line PhanPluginSuspiciousParamOrderInternal
			$userSearchFilter = str_replace('%1%', $usertotest, $gestimag_main_auth_ldap_filter);
		}

		// If admin login or ldap auth filter provided
		// Code to get user in LDAP from an admin connection (may differ from user connection, done later)
		if ($ldapadminlogin || $gestimag_main_auth_ldap_filter) {
			$result = $ldap->connectBind();
			if ($result > 0) {
				$resultFetchLdapUser = $ldap->fetch($usertotest, $userSearchFilter);
				//dol_syslog('functions_ldap::check_user_password_ldap resultFetchLdapUser='.$resultFetchLdapUser);
				if ($resultFetchLdapUser > 0 && $ldap->pwdlastset == 0) { // If ok but password need to be reset
					dol_syslog('functions_ldap::check_user_password_ldap '.$usertotest.' must change password next logon');
					if ($ldapdebug) {
						echo"DEBUG: User ".$usertotest." must change password<br>\n";
					}
					$ldap->unbind();
					sleep(1); // Anti brut force protection. Must be same delay when user and password are not valid.
					$langs->load('ldap');
					$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("YouMustChangePassNextLogon", $usertotest, $ldap->domainFQDN);
					return '';
				}
			} else {
				if ($ldapdebug) {
					echo"DEBUG: ".$ldap->error."<br>\n";
				}
			}
			$ldap->unbind();
		}

		// Forge LDAP user and password to test with them
		// If LDAP need a dn with login like "uid=jbloggs,ou=People,dc=foo,dc=com", default dn may work even if previous code with
		// admin login no executed.
		$ldap->searchUser = $ldapuserattr."=".$usertotest.",".$ldapdn; // Default dn (will work if LDAP accept a dn with login value inside)
		// But if LDAP need a dn with name like "cn=Jhon Bloggs,ou=People,dc=foo,dc=com", previous part must have been executed to have
		// dn detected into ldapUserDN.
		if ($resultFetchLdapUser && !empty($ldap->ldapUserDN)) {
			$ldap->searchUser = $ldap->ldapUserDN;
		}
		$ldap->searchPassword = $passwordtotest;

		// Test with this->seachUser and this->searchPassword
		//echo$resultFetchLdapUser."-".$ldap->ldapUserDN."-".$ldap->searchUser.'-'.$ldap->searchPassword;exit;
		$result = $ldap->connectBind();
		if ($result > 0) {
			if ($result == 2) {	// Connection is ok for user/pass into LDAP
				$login = $usertotest;
				dol_syslog("functions_ldap::check_user_password_ldap $login authentication ok");
				// For the case, we search the user id using a search key without the login (but using other fields like id),
				// we need to get the real login to use in the ldap answer.
				if (getDolGlobalString('LDAP_FIELD_LOGIN') && !empty($ldap->login)) {
					$login = $ldap->login;
					dol_syslog("functions_ldap::check_user_password_ldap login is now $login (LDAP_FIELD_LOGIN=".getDolGlobalString('LDAP_FIELD_LOGIN').")");
				}

				require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

				// Note: Test on date validity is done later natively with isNotIntoValidityDateRange() by core after calling checkLoginPassEntity() that call this method

				// ldap2gestimag synchronisation
				if ($login && !empty($conf->ldap->enabled) && getDolGlobalInt('LDAP_SYNCHRO_ACTIVE') == Ldap::SYNCHRO_LDAP_TO_DOLIBARR) {	// ldap2gestimag synchronization
					dol_syslog("functions_ldap::check_user_password_ldap Sync ldap2gestimag");

					// On charge les attributes du user ldap
					if ($ldapdebug) {
						echo"DEBUG: login ldap = ".$login."<br>\n";
					}
					$resultFetchLdapUser = $ldap->fetch($login, $userSearchFilter);

					if ($ldapdebug) {
						echo"DEBUG: UACF = ".implode(',', $ldap->uacf)."<br>\n";
					}
					if ($ldapdebug) {
						echo"DEBUG: pwdLastSet = ".dol_print_date($ldap->pwdlastset, 'day')."<br>\n";
					}
					if ($ldapdebug) {
						echo"DEBUG: badPasswordTime = ".dol_print_date($ldap->badpwdtime, 'day')."<br>\n";
					}

					// On recherche le user gestimag en fonction de son SID ldap (only for Active Directory)
					$sid = null;
					if (getDolGlobalString('LDAP_SERVER_TYPE') == "activedirectory") {
						$sid = $ldap->getObjectSid($login);
						if ($ldapdebug) {
							echo"DEBUG: sid = ".$sid."<br>\n";
						}
					}

					$usertmp = new User($db);
					$resultFetchUser = $usertmp->fetch('', $login, $sid, 1, ($entitytotest > 0 ? $entitytotest : -1));
					if ($resultFetchUser > 0) {
						dol_syslog("functions_ldap::check_user_password_ldap Sync user found user id=".$usertmp->id);
						// Verify if the login changed and update the Gestimag attributes

						if ($usertmp->login != $ldap->login && $ldap->login) {
							$usertmp->login = $ldap->login;
							$usertmp->update($usertmp);
							// TODO What to do if the update fails because the login already exists for another account.
						}

						//$resultUpdate = $usertmp->update_ldap2gestimag($ldap);
					}

					unset($usertmp);
				}

				if (isModEnabled('multicompany')) {	// We must check entity (even if sync is not active)
					global $mc;

					$usertmp = new User($db);
					$usertmp->fetch('', $login);
					if (is_object($mc)) {
						$ret = $mc->checkRight($usertmp->id, $entitytotest);
						if ($ret < 0) {
							dol_syslog("functions_ldap::check_user_password_ldap Authentication KO entity '".$entitytotest."' not allowed for user id '".$usertmp->id."'", LOG_NOTICE);
							$login = ''; // force authentication failure
						}
						unset($usertmp);
					}
				}
			}
			if ($result == 1) {
				dol_syslog("functions_ldap::check_user_password_ldap Authentication KO bad user/password for '".$usertotest."'", LOG_NOTICE);
				sleep(1); // Anti brut force protection. Must be same delay when user and password are not valid.

				// Load translation files required by the page
				$langs->loadLangs(array('main', 'other'));

				$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("ErrorBadLoginPassword");
			}
		} else {
			/* Login failed. Return false, together with the error code and text from
			 ** the LDAP server. The common error codes and reasons are listed below :
			 ** (for iPlanet, other servers may differ)
			 ** 19 - Account locked out (too many invalid login attempts)
			 ** 32 - User does not exist
			 ** 49 - Wrong password
			 ** 53 - Account inactive (manually locked out by administrator)
			 */
			dol_syslog("functions_ldap::check_user_password_ldap Authentication KO failed to connect to LDAP for '".$usertotest."'", LOG_NOTICE);
			if (is_resource($ldap->connection) || is_object($ldap->connection)) {    // If connection ok but bind ko
				try {
					// @phan-suppress-next-line PhanTypeMismatchArgumentInternal  Expects LDAP\Connection, not 'resource'
					$ldap->ldapErrorCode = ldap_errno($ldap->connection);
					// @phan-suppress-next-line PhanTypeMismatchArgumentInternal  Expects LDAP\Connection, not 'resource'
					$ldap->ldapErrorText = ldap_error($ldap->connection);
					dol_syslog("functions_ldap::check_user_password_ldap ".$ldap->ldapErrorCode." ".$ldap->ldapErrorText);
				} catch (Throwable $exception) {
					$ldap->ldapErrorCode = 0;
					$ldap->ldapErrorText = '';
					dol_syslog('functions_ldap::check_user_password_ldap '.$exception, LOG_WARNING);
				}
			}
			sleep(1); // Anti brut force protection. Must be same delay when user and password are not valid.
			// Load translation files required by the page
			$langs->loadLangs(array('main', 'other', 'errors'));
			$_SESSION["dol_loginmesg"] = ($ldap->error ? $ldap->error : $langs->transnoentitiesnoconv("ErrorBadLoginPassword"));
		}
		$ldap->unbind();
	}

	return $login;
}
