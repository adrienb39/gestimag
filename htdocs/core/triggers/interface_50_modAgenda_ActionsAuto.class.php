<?php
/* Copyright (C) 2005-2017	Laurent Destailleur 	<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2017	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2011-2014	Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2013		Cedric GROSS			<c.gross@kreiz-it.fr>
 * Copyright (C) 2014		Marcos García		<marcosgdf@gmail.com>
 * Copyright (C) 2015		Bahfir Abbes			<bafbes@gmail.com>
 * Copyright (C) 2022		Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2023		William Mead			<william.mead@manchenumerique.fr>
 * Copyright (C) 2023       Christian Foellmann     <christian@foellmann.de>
 * Copyright (C) 2024		William Mead			<william.mead@manchenumerique.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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

/**
 *	\file       htdocs/core/triggers/interface_50_modAgenda_ActionsAuto.class.php
 *  \ingroup    agenda
 *  \brief      Trigger file for agenda module
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/gestimagtriggers.class.php';


/**
 *  Class of triggered functions for agenda module
 */
class InterfaceActionsAuto extends GestimagTriggers
{
	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "agenda";
		$this->description = "Triggers of this module add actions in agenda according to setup made in agenda setup.";
		$this->version = self::VERSIONS['prod'];
		$this->picto = 'action';
	}

	/**
	 * Function called when a Gestimag business event is done.
	 * All functions "runTrigger" are triggered if file is inside directory htdocs/core/triggers or htdocs/module/code/triggers (and declared)
	 *
	 * Following properties may be set before calling trigger. The may be completed by this trigger to be used for writing the event into database:
	 *      $object->actiontypecode (translation action code: AC_OTH, ...)
	 *      $object->actionmsg (note, long text)
	 *      $object->actionmsg2 (label, short text)
	 *      $object->sendtoid (id of contact or array of ids of contacts)
	 *      $object->socid (id of thirdparty)
	 *      $object->fk_project
	 *      $object->fk_element	(ID of object to link action event to)
	 *      $object->elementtype (->element of object to link action to)
	 *      $object->module (if defined, elementtype in llx_actioncomm will be elementtype@module)
	 *
	 * @param string		$action		Event action code ('CONTRACT_MODIFY', 'RECRUITMENTCANDIDATURE_MODIFIY', or example by external module: 'SENTBYSMS'...)
	 * @param Object		$object     Object
	 * @param User		    $user       Object user
	 * @param Translate 	$langs      Object langs
	 * @param conf		    $conf       Object conf
	 * @return int         				Return integer <0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (!isModEnabled('agenda')) {
			return 0; // Module not active, we do nothing
		}

		// Do not log events when trigger is for creating event (infinite loop)
		if (preg_match('/^ACTION_/', $action)) {
			return 0;
		}

		$key = 'MAIN_AGENDA_ACTIONAUTO_'.$action;
		//var_dump($action.' - '.$key.' - '.$conf->global->$key);exit;

		// Do not log events not enabled for this action
		// GUI allow to set this option only if entry exists into table llx_c_action_trigger
		if (!getDolGlobalString($key)) {
			return 0;
		}

		$langs->load("agenda");

		if (empty($object->actiontypecode)) {
			$object->actiontypecode = 'AC_OTH_AUTO';
		}

		// Actions
		if ($action == 'COMPANY_CREATE') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "companies"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("NewCompanyToGestimag", $object->name);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("NewCompanyToGestimag", $object->name);
			}

			$object->sendtoid = 0;
			$object->socid = $object->id;
		} elseif ($action == 'COMPANY_MODIFY') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "companies"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("COMPANY_MODIFYInGestimag", $object->name);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("COMPANY_MODIFYInGestimag", $object->name);
			}

			// For merge event, we add a mention
			if (!empty($object->context['mergefromname'])) {
				$object->actionmsg = dol_concatdesc($object->actionmsg, $langs->trans("DataFromWasMerged", $object->context['mergefromname'].' (id='.$object->context['mergefromname'].')'));
			}

			$object->sendtoid = 0;
			$object->socid = $object->id;
		} elseif ($action == 'COMPANY_SENTBYMAIL') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "orders"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					dol_syslog('Trigger called with property actionmsg2 and context[actionmsg2] on object not defined', LOG_ERR);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}

			// Parameters $object->sendtoid defined by caller
			//$object->sendtoid=0;
		} elseif ($action == 'CONTACT_CREATE') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "companies"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("CONTACT_CREATEInGestimag", $object->getFullName($langs));
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("CONTACT_CREATEInGestimag", $object->getFullName($langs));
			}

			$object->sendtoid = array($object->id => $object->id);
			// $object->socid = $object->socid;
		} elseif ($action == 'CONTACT_MODIFY') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "companies"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("CONTACT_MODIFYInGestimag", $object->name);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("CONTACT_MODIFYInGestimag", $object->name);
			}

			$object->sendtoid = array($object->id => $object->id);
			// $object->socid = $object->socid;
		} elseif ($action == 'CONTRACT_VALIDATE') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "contracts"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("ContractValidatedInGestimag", ($object->newref ? $object->newref : $object->ref));
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("ContractValidatedInGestimag", ($object->newref ? $object->newref : $object->ref));
			}

			$object->sendtoid = 0;
		} elseif ($action == 'CONTRACT_SENTBYMAIL') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "contracts"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("ContractSentByEMail", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("ContractSentByEMail", $object->ref);
			}

			// Parameters $object->sendtoid defined by caller
			//$object->sendtoid=0;
		} elseif ($action == 'PROPAL_VALIDATE') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "propal"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("PropalValidatedInGestimag", ($object->newref ? $object->newref : $object->ref));
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("PropalValidatedInGestimag", ($object->newref ? $object->newref : $object->ref));
			}

			$object->sendtoid = 0;
		} elseif ($action == 'PROPAL_MODIFY') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "propal"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("PropalBackToDraftInGestimag", ($object->newref ? $object->newref : $object->ref));
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("PropalBackToDraftInGestimag", ($object->newref ? $object->newref : $object->ref));
			}

			$object->sendtoid = 0;
		} elseif ($action == 'PROPAL_SENTBYMAIL') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "propal"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("ProposalSentByEMail", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("ProposalSentByEMail", $object->ref);
			}

			// Parameters $object->sendtoid defined by caller
			//$object->sendtoid=0;
		} elseif ($action == 'PROPAL_CLOSE_SIGNED') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "propal"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("PropalClosedSignedInGestimag", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("PropalClosedSignedInGestimag", $object->ref);
			}

			$object->sendtoid = 0;
		} elseif ($action == 'PROPAL_CLASSIFY_BILLED') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "propal"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("PropalClassifiedBilledInGestimag", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("PropalClassifiedBilledInGestimag", $object->ref);
			}

			$object->sendtoid = 0;
		} elseif ($action == 'PROPAL_CLOSE_REFUSED') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "propal"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("PropalClosedRefusedInGestimag", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("PropalClosedRefusedInGestimag", $object->ref);
			}

			$object->sendtoid = 0;
		} elseif ($action == 'ORDER_VALIDATE') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "orders"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("OrderValidatedInGestimag", ($object->newref ? $object->newref : $object->ref));
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("OrderValidatedInGestimag", ($object->newref ? $object->newref : $object->ref));
			}

			$object->sendtoid = 0;
		} elseif ($action == 'ORDER_CLOSE') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "orders"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("OrderDeliveredInGestimag", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("OrderDeliveredInGestimag", $object->ref);
			}

			$object->sendtoid = 0;
		} elseif ($action == 'ORDER_CLASSIFY_BILLED') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "orders"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("OrderBilledInGestimag", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("OrderBilledInGestimag", $object->ref);
			}

			$object->sendtoid = 0;
		} elseif ($action == 'ORDER_CANCEL') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "orders"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("OrderCanceledInGestimag", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("OrderCanceledInGestimag", $object->ref);
			}

			$object->sendtoid = 0;
		} elseif ($action == 'ORDER_SENTBYMAIL') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "orders"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("OrderSentByEMail", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("OrderSentByEMail", $object->ref);
			}

			// Parameters $object->sendtoid defined by caller
			//$object->sendtoid=0;
		} elseif ($action == 'BILL_VALIDATE') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "bills"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("InvoiceValidatedInGestimag", ($object->newref ? $object->newref : $object->ref));
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("InvoiceValidatedInGestimag", ($object->newref ? $object->newref : $object->ref));
			}

			$object->sendtoid = 0;
		} elseif ($action == 'BILL_UNVALIDATE') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "bills"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("InvoiceBackToDraftInGestimag", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("InvoiceBackToDraftInGestimag", $object->ref);
			}

			$object->sendtoid = 0;
		} elseif ($action == 'BILL_SENTBYMAIL') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "bills"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("InvoiceSentByEMail", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("InvoiceSentByEMail", $object->ref);
			}

			// Parameters $object->sendtoid defined by caller
			//$object->sendtoid=0;
		} elseif ($action == 'BILL_PAYED') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "bills"));

			// Values for this action can't be defined by caller.
			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("InvoicePaidInGestimag", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("InvoicePaidInGestimag", $object->ref);
			}

			$object->sendtoid = 0;
		} elseif ($action == 'BILL_CANCEL') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "bills"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("InvoiceCanceledInGestimag", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("InvoiceCanceledInGestimag", $object->ref);
			}

			$object->sendtoid = 0;
		} elseif ($action == 'FICHINTER_CREATE') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "interventions"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("InterventionCreatedInGestimag", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("InterventionCreatedInGestimag", $object->ref);
			}

			$object->sendtoid = 0;
			$object->fk_element = 0;
			$object->elementtype = '';
		} elseif ($action == 'FICHINTER_VALIDATE') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "interventions"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("InterventionValidatedInGestimag", ($object->newref ? $object->newref : $object->ref));
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("InterventionValidatedInGestimag", ($object->newref ? $object->newref : $object->ref));
			}

			$object->sendtoid = 0;
			$object->fk_element = 0;
			$object->elementtype = '';
		} elseif ($action == 'FICHINTER_MODIFY') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "interventions"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("InterventionModifiedInGestimag", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("InterventionModifiedInGestimag", $object->ref);
			}

			$object->sendtoid = 0;
			$object->fk_element = 0;
			$object->elementtype = '';
		} elseif ($action == 'FICHINTER_SENTBYMAIL') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "interventions"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("InterventionSentByEMail", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("InterventionSentByEMail", $object->ref);
			}

			// Parameters $object->sendtoid defined by caller
			//$object->sendtoid=0;
		} elseif ($action == 'FICHINTER_CLASSIFY_BILLED') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "interventions"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("InterventionClassifiedBilledInGestimag", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("InterventionClassifiedBilledInGestimag", $object->ref);
			}

			$object->sendtoid = 0;
		} elseif ($action == 'FICHINTER_CLASSIFY_UNBILLED') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "interventions"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("InterventionClassifiedUnbilledInGestimag", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("InterventionClassifiedUnbilledInGestimag", $object->ref);
			}

			$object->sendtoid = 0;
		} elseif ($action == 'FICHINTER_CLOSE') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "interventions"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("InterventionClosedInGestimag", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("InterventionClosedInGestimag", $object->ref);
			}

			$object->sendtoid = 0;
			$object->fk_element = 0;
			$object->elementtype = '';
		} elseif ($action == 'FICHINTER_DELETE') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "interventions"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("InterventionDeletedInGestimag", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("InterventionDeletedInGestimag", $object->ref);
			}

			$object->sendtoid = 0;
			$object->fk_element = 0;
			$object->elementtype = '';
		} elseif ($action == 'SHIPPING_VALIDATE') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "sendings"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("ShippingValidated", ($object->newref ? $object->newref : $object->ref));
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("ShippingValidated", ($object->newref ? $object->newref : $object->ref));
			}

			// Parameters $object->sendtoid defined by caller
			//$object->sendtoid=0;
		} elseif ($action == 'SHIPPING_SENTBYMAIL') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "sendings"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("ShippingSentByEMail", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("ShippingSentByEMail", $object->ref);
			}

			// Parameters $object->sendtoid defined by caller
			//$object->sendtoid=0;
		} elseif ($action == 'RECEPTION_VALIDATE') {
			$langs->load("agenda");
			$langs->load("other");
			$langs->load("receptions");

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("ReceptionValidated", ($object->newref ? $object->newref : $object->ref));
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("ReceptionValidated", ($object->newref ? $object->newref : $object->ref));
			}

			// Parameters $object->sendtoid defined by caller
			//$object->sendtoid=0;
		} elseif ($action == 'RECEPTION_SENTBYMAIL') {
			$langs->load("agenda");
			$langs->load("other");
			$langs->load("receptions");

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("ReceptionSentByEMail", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("ReceptionSentByEMail", $object->ref);
			}

			// Parameters $object->sendtoid defined by caller
			//$object->sendtoid=0;
		} elseif ($action == 'PROPOSAL_SUPPLIER_VALIDATE') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "propal"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("PropalValidatedInGestimag", ($object->newref ? $object->newref : $object->ref));
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("PropalValidatedInGestimag", ($object->newref ? $object->newref : $object->ref));
			}

			$object->sendtoid = 0;
		} elseif ($action == 'PROPOSAL_SUPPLIER_SENTBYMAIL') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "propal"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("ProposalSentByEMail", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("ProposalSentByEMail", $object->ref);
			}

			// Parameters $object->sendtoid defined by caller
			//$object->sendtoid=0;
		} elseif ($action == 'PROPOSAL_SUPPLIER_CLOSE_SIGNED') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "propal"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("PropalClosedSignedInGestimag", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("PropalClosedSignedInGestimag", $object->ref);
			}

			$object->sendtoid = 0;
		} elseif ($action == 'PROPOSAL_SUPPLIER_CLOSE_REFUSED') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "propal"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("PropalClosedRefusedInGestimag", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("PropalClosedRefusedInGestimag", $object->ref);
			}

			$object->sendtoid = 0;
		} elseif ($action == 'ORDER_SUPPLIER_CREATE') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "orders"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("OrderCreatedInGestimag", ($object->newref ? $object->newref : $object->ref));
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("OrderCreatedInGestimag", ($object->newref ? $object->newref : $object->ref));
			}

			$object->sendtoid = 0;
		} elseif ($action == 'ORDER_SUPPLIER_VALIDATE') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "orders"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("OrderValidatedInGestimag", ($object->newref ? $object->newref : $object->ref));
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("OrderValidatedInGestimag", ($object->newref ? $object->newref : $object->ref));
			}

			$object->sendtoid = 0;
		} elseif ($action == 'ORDER_SUPPLIER_APPROVE') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "orders"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("OrderApprovedInGestimag", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("OrderApprovedInGestimag", $object->ref);
			}

			$object->sendtoid = 0;
		} elseif ($action == 'ORDER_SUPPLIER_REFUSE') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "orders", "main"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("OrderRefusedInGestimag", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("OrderRefusedInGestimag", $object->ref);
			}

			if (!empty($object->refuse_note)) {
				$object->actionmsg .= '<br>';
				$object->actionmsg .= $langs->trans("Reason") . ': '.$object->refuse_note;
			}

			$object->sendtoid = 0;
		} elseif ($action == 'ORDER_SUPPLIER_CANCEL') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "orders", "main"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("OrderCanceledInGestimag", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("OrderCanceledInGestimag", $object->ref);
			}

			if (!empty($object->cancel_note)) {
				$object->actionmsg .= '<br>';
				$object->actionmsg .= $langs->trans("Reason") . ': '.$object->cancel_note;
			}

			$object->sendtoid = 0;
		} elseif ($action == 'ORDER_SUPPLIER_SUBMIT') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "orders"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("SupplierOrderSubmitedInGestimag", ($object->newref ?: $object->ref), $object->getInputMethod());
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("SupplierOrderSubmitedInGestimag", ($object->newref ? $object->newref : $object->ref));
			}

			if (!empty($object->context['comments'])) {
				$object->actionmsg .= '<br>';
				$object->actionmsg .= $langs->trans("Comment") . ': '.$object->context['comments'];
			}

			$object->sendtoid = 0;
		} elseif ($action == 'ORDER_SUPPLIER_RECEIVE') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "orders"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("SupplierOrderReceivedInGestimag", ($object->newref ? $object->newref : $object->ref));
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("SupplierOrderReceivedInGestimag", ($object->newref ? $object->newref : $object->ref));
			}

			$object->sendtoid = 0;
		} elseif ($action == 'ORDER_SUPPLIER_SENTBYMAIL') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "bills", "orders"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("SupplierOrderSentByEMail", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("SupplierOrderSentByEMail", $object->ref);
			}

			// Parameters $object->sendtoid defined by caller
			//$object->sendtoid=0;
		} elseif ($action == 'ORDER_SUPPLIER_CLASSIFY_BILLED') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "bills", "orders"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("SupplierOrderClassifiedBilled", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("SupplierOrderClassifiedBilled", $object->ref);
			}

			$object->sendtoid = 0;
		} elseif ($action == 'BILL_SUPPLIER_VALIDATE') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "bills"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("InvoiceValidatedInGestimag", ($object->newref ? $object->newref : $object->ref));
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("InvoiceValidatedInGestimag", ($object->newref ? $object->newref : $object->ref));
			}

			$object->sendtoid = 0;
		} elseif ($action == 'BILL_SUPPLIER_UNVALIDATE') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "bills"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("InvoiceBackToDraftInGestimag", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("InvoiceBackToDraftInGestimag", $object->ref);
			}

			$object->sendtoid = 0;
		} elseif ($action == 'BILL_SUPPLIER_SENTBYMAIL') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "bills", "orders"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("SupplierInvoiceSentByEMail", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("SupplierInvoiceSentByEMail", $object->ref);
			}

			// Parameters $object->sendtoid defined by caller
			//$object->sendtoid=0;
		} elseif ($action == 'BILL_SUPPLIER_PAYED') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "bills"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("InvoicePaidInGestimag", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("InvoicePaidInGestimag", $object->ref);
			}

			$object->sendtoid = 0;
		} elseif ($action == 'BILL_SUPPLIER_CANCELED') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "bills"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("InvoiceCanceledInGestimag", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("InvoiceCanceledInGestimag", $object->ref);
			}

			$object->sendtoid = 0;
		} elseif ($action == 'MEMBER_VALIDATE') {
			// Members
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "members"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("MemberValidatedInGestimag", $object->getFullName($langs));
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("MemberValidatedInGestimag", $object->getFullName($langs));
				$object->actionmsg .= "\n".$langs->transnoentities("Member").': '.$object->getFullName($langs);
				$object->actionmsg .= "\n".$langs->transnoentities("Type").': '.$object->type;
			}

			$object->sendtoid = 0;
		} elseif ($action == 'MEMBER_MODIFY') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "members"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("MemberModifiedInGestimag", $object->getFullName($langs));
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("MemberModifiedInGestimag", $object->getFullName($langs));
				$object->actionmsg .= "\n".$langs->transnoentities("Member").': '.$object->getFullName($langs);
				$object->actionmsg .= "\n".$langs->transnoentities("Type").': '.$object->type;
			}

			$object->sendtoid = 0;
		} elseif ($action == 'MEMBER_SUBSCRIPTION_CREATE') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "members"));

			$member = (isset($object->context['member']) ? $object->context['member'] : null);
			if (!is_object($member)) {	// This should not happen
				dol_syslog("Execute a trigger MEMBER_SUBSCRIPTION_CREATE with context key 'member' not an object");
				include_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
				$member = new Adherent($this->db);
				$member->fetch($object->fk_adherent);
			}

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("MemberSubscriptionAddedInGestimag", $object->id, $member->getFullName($langs));
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("MemberSubscriptionAddedInGestimag", $object->id, $member->getFullName($langs));
				$object->actionmsg .= "\n".$langs->transnoentities("Member").': '.$member->getFullName($langs);
				$object->actionmsg .= "\n".$langs->transnoentities("Type").': '.$object->fk_type;
				$object->actionmsg .= "\n".$langs->transnoentities("Amount").': '.$object->amount;
				$object->actionmsg .= "\n".$langs->transnoentities("Period").': '.dol_print_date($object->dateh, 'day').' - '.dol_print_date($object->datef, 'day');
			}

			$object->sendtoid = 0;
			if (isset($object->fk_soc) && $object->fk_soc > 0) {
				$object->socid = $object->fk_soc;
			}
		} elseif ($action == 'MEMBER_SUBSCRIPTION_MODIFY') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "members"));

			$member = $object->context['member'];
			if (!is_object($member)) {	// This should not happen
				include_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
				$member = new Adherent($this->db);
				$member->fetch($object->fk_adherent);
			}

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("MemberSubscriptionModifiedInGestimag", $object->id, $member->getFullName($langs));
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("MemberSubscriptionModifiedInGestimag", $object->id, $member->getFullName($langs));
				$object->actionmsg .= "\n".$langs->transnoentities("Member").': '.$member->getFullName($langs);
				$object->actionmsg .= "\n".$langs->transnoentities("Type").': '.$object->fk_type;
				$object->actionmsg .= "\n".$langs->transnoentities("Amount").': '.$object->amount;
				$object->actionmsg .= "\n".$langs->transnoentities("Period").': '.dol_print_date($object->dateh, 'day').' - '.dol_print_date($object->datef, 'day');
			}

			$object->sendtoid = 0;
			if (isset($object->fk_soc) && $object->fk_soc > 0) {
				$object->socid = $object->fk_soc;
			}
		} elseif ($action == 'MEMBER_SUBSCRIPTION_DELETE') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "members"));

			$member = $object->context['member'];
			if (!is_object($member)) {	// This should not happen but it happen when deleting a subscription from adherents/subscription/card.php
				dol_syslog("Execute a trigger MEMBER_SUBSCRIPTION_CREATE with context key 'member' not an object");
				include_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
				$member = new Adherent($this->db);
				$member->fetch($object->fk_adherent);
			}

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("MemberSubscriptionDeletedInGestimag", $object->ref, $member->getFullName($langs));
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("MemberSubscriptionDeletedInGestimag", $object->ref, $member->getFullName($langs));
				$object->actionmsg .= "\n".$langs->transnoentities("Member").': '.$member->getFullName($langs);
				$object->actionmsg .= "\n".$langs->transnoentities("Type").': '.$object->fk_type;
				$object->actionmsg .= "\n".$langs->transnoentities("Amount").': '.$object->amount;
				$object->actionmsg .= "\n".$langs->transnoentities("Period").': '.dol_print_date($object->dateh, 'day').' - '.dol_print_date($object->datef, 'day');
			}

			$object->sendtoid = 0;
			if (isset($object->fk_soc) && $object->fk_soc > 0) {
				$object->socid = $object->fk_soc;
			}
		} elseif ($action == 'MEMBER_RESILIATE') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "members"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("MemberResiliatedInGestimag", $object->getFullName($langs));
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("MemberResiliatedInGestimag", $object->getFullName($langs));
				$object->actionmsg .= "\n".$langs->transnoentities("Member").': '.$object->getFullName($langs);
				$object->actionmsg .= "\n".$langs->transnoentities("Type").': '.$object->type;
			}

			$object->sendtoid = 0;
		} elseif ($action == 'MEMBER_DELETE') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "members"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("MemberDeletedInGestimag", $object->getFullName($langs));
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("MemberDeletedInGestimag", $object->getFullName($langs));
				$object->actionmsg .= "\n".$langs->transnoentities("Member").': '.$object->getFullName($langs);
				$object->actionmsg .= "\n".$langs->transnoentities("Type").': '.$object->type;
			}

			$object->sendtoid = 0;
		} elseif ($action == 'MEMBER_EXCLUDE') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "members"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("MemberExcludedInGestimag", $object->getFullName($langs));
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("MemberExcludedInGestimag", $object->getFullName($langs));
				$object->actionmsg .= "\n".$langs->transnoentities("Member").': '.$object->getFullName($langs);
				$object->actionmsg .= "\n".$langs->transnoentities("Type").': '.$object->type;
			}

			$object->sendtoid = 0;
		} elseif ($action == 'PROJECT_CREATE') {
			// Projects
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "projects"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("ProjectCreatedInGestimag", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("ProjectCreatedInGestimag", $object->ref);
				$object->actionmsg .= "\n".$langs->transnoentities("Project").': '.$object->ref;
			}

			$object->sendtoid = 0;
		} elseif ($action == 'PROJECT_VALIDATE') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "projects"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("ProjectValidatedInGestimag", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("ProjectValidatedInGestimag", $object->ref);
				$object->actionmsg .= "\n".$langs->transnoentities("Project").': '.$object->ref;
			}

			$object->sendtoid = 0;
		} elseif ($action == 'PROJECT_MODIFY') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "projects"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("ProjectModifiedInGestimag", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("ProjectModifiedInGestimag", $object->ref);
			}

			//$object->actionmsg .= "\n".$langs->transnoentities("Task").': ???';
			if (!empty($object->usage_opportunity) && is_object($object->oldcopy) && $object->opp_status != $object->oldcopy->opp_status) {
				$object->actionmsg .= "\n".$langs->transnoentitiesnoconv("OpportunityStatus").': '.$object->oldcopy->opp_status.' -> '.$object->opp_status;
			}

			$object->sendtoid = 0;
		} elseif ($action == 'PROJECT_SENTBYMAIL') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "projects"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("ProjectSentByEMail", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("ProjectSentByEMail", $object->ref);
			}

			// Parameters $object->sendtoid defined by caller
			//$object->sendtoid=0;
		} elseif ($action == 'PROJECT_DELETE') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "projects"));

			if (empty($object->actionmsg2)) {
				$object->actionmsg2 = $langs->transnoentities("ProjectDeletedInGestimag", $object->ref);
			}
			$object->actionmsg = $langs->transnoentities("ProjectDeletedInGestimag", $object->ref);

			$object->sendtoid = 0;
		} elseif ($action == 'PROJECT_CLOSE') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "projects"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("ProjectClosedInGestimag", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("ProjectClosedInGestimag", $object->ref);
			}

			$object->sendtoid = 0;
		} elseif ($action == 'TASK_CREATE') {
			// Project tasks
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "projects"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("TaskCreatedInGestimag", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("TaskCreatedInGestimag", $object->ref);
				$object->actionmsg .= "\n".$langs->transnoentities("Task").': '.$object->ref;
			}

			$object->sendtoid = 0;
		} elseif ($action == 'TASK_MODIFY') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "projects"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("TaskModifiedInGestimag", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("TaskModifieddInGestimag", $object->ref);
				$object->actionmsg .= "\n".$langs->transnoentities("Task").': '.$object->ref;
			}

			$object->sendtoid = 0;
		} elseif ($action == 'TASK_DELETE') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "projects"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("TaskDeletedInGestimag", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("TaskDeletedInGestimag", $object->ref);
				$object->actionmsg .= "\n".$langs->transnoentities("Task").': '.$object->ref;
			}

			$object->sendtoid = 0;
		} elseif ($action == 'TICKET_ASSIGNED') {
			// Load translation files required by the page
			$langs->loadLangs(array("agenda", "other", "projects"));

			if (empty($object->actionmsg2)) {
				if (empty($object->context['actionmsg2'])) {
					$object->actionmsg2 = $langs->transnoentities("TICKET_ASSIGNEDInGestimag", $object->ref);
				} else {
					$object->actionmsg2 = $object->context['actionmsg2'];
				}
			}
			if (empty($object->actionmsg)) {
				$object->actionmsg = $langs->transnoentities("TICKET_ASSIGNEDInGestimag", $object->ref);
			}

			if ($object->oldcopy->fk_user_assign > 0) {
				$tmpuser = new User($this->db);
				$tmpuser->fetch($object->oldcopy->fk_user_assign);
				$object->actionmsg .= "\n".$langs->transnoentities("OldUser").': '.$tmpuser->getFullName($langs);
			} else {
				$object->actionmsg .= "\n".$langs->transnoentities("OldUser").': '.$langs->trans("None");
			}
			if ($object->fk_user_assign > 0) {
				$tmpuser = new User($this->db);
				$tmpuser->fetch($object->fk_user_assign);
				$object->actionmsg .= "\n".$langs->transnoentities("NewUser").': '.$tmpuser->getFullName($langs);
			} else {
				$object->actionmsg .= "\n".$langs->transnoentities("NewUser").': '.$langs->trans("None");
			}

			$object->sendtoid = 0;
		} else {
			// TODO Merge all previous cases into this generic one
			// $action = PASSWORD, BILL_DELETE, TICKET_CREATE, TICKET_MODIFY, TICKET_DELETE, CONTACT_SENTBYMAIL, RECRUITMENTCANDIDATURE_MODIFY, ...
			// Can also be a value defined by an external module like SENTBYSMS, COMPANY_SENTBYSMS, MEMBER_SENTBYSMS, ...
			// Note: We are here only if $conf->global->MAIN_AGENDA_ACTIONAUTO_action is on (tested at beginning of this function).
			// Note that these key can be set in agenda setup, only if defined into llx_c_action_trigger
			if (!empty($object->context['actionmsg']) && empty($object->actionmsg)) {	// For description
				$object->actionmsg = $object->context['actionmsg'];
			}
			if (!empty($object->context['actionmsg2']) && empty($object->actionmsg2)) {	// For label
				$object->actionmsg2 = $object->context['actionmsg2'];
			}

			if (empty($object->actionmsg2)) {
				// Load translation files required by the page
				$langs->loadLangs(array("agenda", "other"));
				if ($langs->transnoentities($action."InGestimag", (empty($object->newref) ? $object->ref : $object->newref)) != $action."InGestimag") {	// specific translation key
					$object->actionmsg2 = $langs->transnoentities($action."InGestimag", (empty($object->newref) ? $object->ref : $object->newref));
				} else {	// generic translation key
					$tmp = explode('_', $action);
					$object->actionmsg2 = $langs->transnoentities($tmp[count($tmp) - 1]."InGestimag", (empty($object->newref) ? $object->ref : $object->newref));
				}
			}
			if (empty($object->actionmsg)) {
				// Load translation files required by the page
				$langs->loadLangs(array("agenda", "other"));
				if ($langs->transnoentities($action."InGestimag", (empty($object->newref) ? $object->ref : $object->newref)) != $action."InGestimag") {	// specific translation key
					$object->actionmsg = $langs->transnoentities($action."InGestimag", (empty($object->newref) ? $object->ref : $object->newref));
				} else {	// generic translation key
					$tmp = explode('_', $action);
					$object->actionmsg = $langs->transnoentities($tmp[count($tmp) - 1]."InGestimag", (empty($object->newref) ? $object->ref : $object->newref));
				}
				if (isModEnabled('multicompany') && property_exists($object, 'entity') && $object->entity > 1) {
					$object->actionmsg .= ' ('.$langs->trans("Entity").' '.$object->entity.')';
				}
			}

			if (!isset($object->sendtoid) || !is_array($object->sendtoid)) {
				$object->sendtoid = 0;
			}
		}

		// If trackid is not defined, we set it.
		// Note that it should be set by caller. This is for compatibility purpose only.
		if (empty($object->trackid)) {
			// See also similar list into emailcollector.class.php
			if (preg_match('/^COMPANY_/', $action)) {
				$object->trackid = 'thi'.$object->id;
			} elseif (preg_match('/^CONTACT_/', $action)) {
				$object->trackid = 'ctc'.$object->id;
			} elseif (preg_match('/^CONTRACT_/', $action)) {
				$object->trackid = 'con'.$object->id;
			} elseif (preg_match('/^PROPAL_/', $action)) {
				$object->trackid = 'pro'.$object->id;
			} elseif (preg_match('/^ORDER_/', $action)) {
				$object->trackid = 'ord'.$object->id;
			} elseif (preg_match('/^BILL_/', $action)) {
				$object->trackid = 'inv'.$object->id;
			} elseif (preg_match('/^FICHINTER_/', $action)) {
				$object->trackid = 'int'.$object->id;
			} elseif (preg_match('/^SHIPPING_/', $action)) {
				$object->trackid = 'shi'.$object->id;
			} elseif (preg_match('/^RECEPTION_/', $action)) {
				$object->trackid = 'rec'.$object->id;
			} elseif (preg_match('/^PROPOSAL_SUPPLIER/', $action)) {
				$object->trackid = 'spr'.$object->id;
			} elseif (preg_match('/^ORDER_SUPPLIER_/', $action)) {
				$object->trackid = 'sor'.$object->id;
			} elseif (preg_match('/^BILL_SUPPLIER_/', $action)) {
				$object->trackid = 'sin'.$object->id;
			} elseif (preg_match('/^MEMBER_SUBSCRIPTION_/', $action)) {
				$object->trackid = 'sub'.$object->id;
			} elseif (preg_match('/^MEMBER_/', $action)) {
				$object->trackid = 'mem'.$object->id;
			} elseif (preg_match('/^PARTNERSHIP_/', $action)) {
				$object->trackid = 'pship'.$object->id;
			} elseif (preg_match('/^PROJECT_/', $action)) {
				$object->trackid = 'proj'.$object->id;
			} elseif (preg_match('/^TASK_/', $action)) {
				$object->trackid = 'tas'.$object->id;
			} elseif (preg_match('/^TICKET_/', $action)) {
				$object->trackid = 'tic'.$object->id;
			} elseif (preg_match('/^USER_/', $action)) {
				$object->trackid = 'use'.$object->id;
			} else {
				$object->trackid = '';
			}
		}

		dol_syslog("Trigger '".$this->name."' for action '".$action."' launched by ".__FILE__.". id=".$object->id);

		// Add entry in event table
		$now = dol_now();

		if (isset($_SESSION['listofnames-'.$object->trackid])) {
			$attachments = $_SESSION['listofnames-'.$object->trackid];
			if ($attachments && strpos($action, 'SENTBYMAIL')) {
				$object->actionmsg = dol_concatdesc($object->actionmsg, "\n".$langs->transnoentities("AttachedFiles").': '.$attachments);
			}
		}

		$societeforactionid = 0;
		$contactforactionid = 0;

		// Set $contactforactionid
		if (is_array($object->sendtoid)) {
			if (count($object->sendtoid) == 1) {
				$contactforactionid = reset($object->sendtoid);
			}
		} else {
			if ($object->sendtoid > 0) {
				$contactforactionid = $object->sendtoid;
			}
		}
		// Set $societeforactionid
		if (isset($object->socid) && $object->socid > 0) {
			$societeforactionid = $object->socid;
		} elseif (isset($object->fk_soc) && $object->fk_soc > 0) {
			$societeforactionid = $object->fk_soc;
		} elseif (isset($object->thirdparty) && isset($object->thirdparty->id) && $object->thirdparty->id > 0) {
			$societeforactionid = $object->thirdparty->id;
		}

		$projectid = isset($object->fk_project) ? $object->fk_project : 0;
		if ($object->element == 'project') {
			$projectid = $object->id;
		}

		$elementid = $object->id; // id of object
		$elementtype = $object->element;
		$elementmodule = (empty($object->module) ? '' : $object->module);
		if ($object->element == 'subscription') {
			$elementid = $object->fk_adherent;
			$elementtype = 'member';
		}
		//var_dump($societeforaction);var_dump($contactforaction);var_dump($elementid);var_dump($elementtype);exit;

		// Insertion action
		require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
		$actioncomm = new ActionComm($this->db);
		$actioncomm->type_code   = $object->actiontypecode; // Type of event ('AC_OTH', 'AC_OTH_AUTO', 'AC_XXX'...)
		$actioncomm->code        = 'AC_'.$action;
		$actioncomm->label       = $object->actionmsg2;		// Label of event
		$actioncomm->note_private = $object->actionmsg;		// Description
		$actioncomm->fk_project  = $projectid;
		$actioncomm->datep       = $now;
		$actioncomm->datef       = $now;
		$actioncomm->durationp   = 0;
		$actioncomm->percentage  = -1; // Not applicable
		$actioncomm->socid       = $societeforactionid;
		$actioncomm->contact_id  = $contactforactionid; // deprecated, now managed by setting $actioncomm->socpeopleassigned later
		$actioncomm->authorid    = $user->id; // User saving action
		$actioncomm->userownerid = $user->id; // Owner of action
		// Fields defined when action is an email (content should be into object->actionmsg to be added into event note, subject should be into object->actionms2 to be added into event label)
		if (!property_exists($object, 'email_fields_no_propagate_in_actioncomm') || empty($object->email_fields_no_propagate_in_actioncomm)) {
			$actioncomm->email_msgid   = empty($object->email_msgid) ? null : $object->email_msgid;
			$actioncomm->email_from    = empty($object->email_from) ? null : $object->email_from;
			$actioncomm->email_sender  = empty($object->email_sender) ? null : $object->email_sender;
			$actioncomm->email_to      = empty($object->email_to) ? null : $object->email_to;
			$actioncomm->email_tocc    = empty($object->email_tocc) ? null : $object->email_tocc;
			$actioncomm->email_tobcc   = empty($object->email_tobcc) ? null : $object->email_tobcc;
			$actioncomm->email_subject = empty($object->email_subject) ? null : $object->email_subject;
			$actioncomm->errors_to     = empty($object->errors_to) ? null : $object->errors_to;
		}

		// Object linked (if link is for thirdparty, contact or project, it is a recording error. We should not have links in link table
		// for such objects because there is already a dedicated field into table llx_actioncomm or llx_actioncomm_resources.
		if (!in_array($elementtype, array('societe', 'contact', 'project'))) {
			$actioncomm->fk_element  = $elementid;
			$actioncomm->elementtype = $elementtype.($elementmodule ? '@'.$elementmodule : '');
		}

		if (property_exists($object, 'attachedfiles') && is_array($object->attachedfiles) && count($object->attachedfiles) > 0) {
			$actioncomm->attachedfiles = $object->attachedfiles;
		}
		if (property_exists($object, 'sendtouserid') && is_array($object->sendtouserid) && count($object->sendtouserid) > 0) {
			$actioncomm->userassigned = $object->sendtouserid;
		}
		if (property_exists($object, 'sendtoid') && is_array($object->sendtoid) && count($object->sendtoid) > 0) {
			foreach ($object->sendtoid as $val) {
				$actioncomm->socpeopleassigned[$val] = $val;
			}
		}

		$ret = $actioncomm->create($user); // User creating action

		if ($ret > 0 && getDolGlobalString('MAIN_COPY_FILE_IN_EVENT_AUTO')) {
			if (property_exists($object, 'attachedfiles') && is_array($object->attachedfiles) && array_key_exists('paths', $object->attachedfiles) && count($object->attachedfiles['paths']) > 0) {
				foreach ($object->attachedfiles['paths'] as $key => $filespath) {
					$srcfile = $filespath;
					$destdir = $conf->agenda->dir_output.'/'.$ret;
					$destfile = $destdir.'/'.$object->attachedfiles['names'][$key];
					if (dol_mkdir($destdir) >= 0) {
						require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
						dol_copy($srcfile, $destfile);
					}
				}
			}
		}

		// Reset value set by caller
		unset($object->context['actionmsg']);
		unset($object->context['actionmsg2']);
		unset($object->actionmsg);
		unset($object->actionmsg2);
		unset($object->actiontypecode); // When several action are called on same object, we must be sure to not reuse value of first action.

		if ($ret > 0) {
			$_SESSION['LAST_ACTION_CREATED'] = $ret;
			return 1;
		} else {
			$this->error = "Failed to insert event : ".$actioncomm->error." ".implode(',', $actioncomm->errors);
			$this->errors = $actioncomm->errors;

			dol_syslog("interface_modAgenda_ActionsAuto.class.php: ".$this->error, LOG_ERR);
			return -1;
		}
	}
}
