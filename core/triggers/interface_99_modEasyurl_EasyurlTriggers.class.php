<?php
/* Copyright (C) 2023 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    core/triggers/interface_99_modEasyurl_EasyurlTriggers.class.php
 * \ingroup easyurl
 * \brief   EasyURL trigger
 */

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/triggers/dolibarrtriggers.class.php';

// Load EasyURL libraries
require_once __DIR__ . '/../../lib/easyurl_function.lib.php';

/**
 * Class of triggers for EasyURL module
 */
class InterfaceEasyURLTriggers extends DolibarrTriggers
{
    /**
     * @var DoliDB Database handler
     */
    protected $db;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        $this->db = $db;

        $this->name        = preg_replace('/^Interface/i', '', get_class($this));
        $this->family      = 'demo';
        $this->description = 'EasyURL triggers';
        $this->version     = '1.0.0';
        $this->picto       = 'easyurl@easyurl';
    }

    /**
     * Trigger name
     *
     * @return string Name of trigger file
     */
    public function getName(): string
    {
        return parent::getName();
    }

    /**
     * Trigger description
     *
     * @return string Description of trigger file
     */
    public function getDesc(): string
    {
        return parent::getDesc();
    }

    /**
     * Function called when a Dolibarr business event is done
     * All functions "runTrigger" are triggered if file
     * is inside directory core/triggers
     *
     * @param  string       $action Event action code
     * @param  CommonObject $object Object
     * @param  User         $user   Object user
     * @param  Translate    $langs  Object langs
     * @param  Conf         $conf   Object conf
     * @return int                  0 < if KO, 0 if no triggered ran, >0 if OK
     * @throws Exception
     */
    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf): int
    {
        if (!isModEnabled('easyurl')) {
            return 0; // If module is not enabled, we do nothing
        }

        saturne_load_langs();

        // Data and type of action are stored into $object and $action.
        dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . '. id=' . $object->id);

        require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
        $now        = dol_now();
        $actioncomm = new ActionComm($this->db);

        $actioncomm->elementtype = $object->element . '@easyurl';
        $actioncomm->type_code   = 'AC_OTH_AUTO';
        $actioncomm->code        = 'AC_' . $action;
        $actioncomm->datep       = $now;
        $actioncomm->fk_element  = $object->id;
        $actioncomm->userownerid = $user->id;
        $actioncomm->percentage  = -1;

        if (getDolGlobalInt('EASYURL_ADVANCED_TRIGGER') && !empty($object->fields)) {
            $actioncomm->note_private = method_exists($object, 'getTriggerDescription') ? $object->getTriggerDescription($object) : '';
        }

        switch ($action) {
            case 'PROPAL_VALIDATE':
            case 'CONTRACT_VALIDATE':
            case 'FICHINTER_VALIDATE':
                if (getDolGlobalInt('EASYURL_AUTOMATIC_GENERATION')) {
                    set_easy_url_link($object, 'signature');
                }
                break;
            case 'ORDER_VALIDATE':
            case 'BILL_VALIDATE':
                if (getDolGlobalInt('EASYURL_AUTOMATIC_GENERATION')) {
                    set_easy_url_link($object, 'payment');
                }
                break;

            // CREATE
            case 'SHORTENER_CREATE' :
                $actioncomm->label = $langs->trans('ObjectCreateTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actioncomm->create($user);
                break;

            // MODIFY
            case 'SHORTENER_MODIFY' :
                if (!empty($object->element_type)) {
                    $objectsMetadata = saturne_get_objects_metadata($object->element_type);
                    $className       = $objectsMetadata['class_name'];
                    $objectLinked    = new $className($this->db);
                    $objectLinked->fetch($object->fk_element);
                    $objectLinked->array_options['options_easy_url_all_link'] = $object->short_url;
                    $objectLinked->updateExtraField('easy_url_all_link');

                    $object->status = Shortener::STATUS_ASSIGN;
                    $object->setValueFrom('status', $object->status, '', null, 'int');
                } else {
                    // Special case/Protection case
                    if ($object->element_type == 0) {
                        $object->element_type = '';
                        $object->fk_element   = '';
                    }
                    if ($object->fk_element == -1) {
                        $object->fk_element = '';
                    }
                    $object->update($user, true);
                }

                if ($object->original_url != GETPOST('previous_original_url')) {
                    update_easy_url_link($object);
                }

                $actioncomm->label = $langs->trans('ObjectModifyTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actioncomm->create($user);
                break;

            // UNASSIGN
            case 'SHORTENER_UNASSIGN' :
                $actioncomm->label = $langs->trans('ObjectUnAssignTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actioncomm->create($user);
                break;

            // DELETE
            case 'SHORTENER_DELETE' :
                $actioncomm->label = $langs->trans('ObjectDeleteTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actioncomm->create($user);
                break;
        }
        return 0;
    }
}
