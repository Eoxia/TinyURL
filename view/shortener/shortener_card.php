<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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
 * \file    view/shortener/shortener_card.php
 * \ingroup easyurl
 * \brief   Page to create/edit/view shortener
 */

// Load EasyURL environment
if (file_exists('../../easyurl.main.inc.php')) {
    require_once __DIR__ . '/../../easyurl.main.inc.php';
} elseif (file_exists('../../../easyurl.main.inc.php')) {
    require_once __DIR__ . '/../../../easyurl.main.inc.php';
} else {
    die('Include of easyurl main fails');
}

// load EasyURL libraries
require_once __DIR__ . '/../../lib/easyurl_shortener.lib.php';
require_once __DIR__ . '/../../class/shortener.class.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $mysoc, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters.
$id                  = GETPOST('id', 'int');
$ref                 = GETPOST('ref', 'alpha');
$action              = GETPOST('action', 'aZ09');
$confirm             = GETPOST('confirm', 'alpha');
$cancel              = GETPOST('cancel', 'aZ09');
$contextPage         = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'shortenercard'; // To manage different context of search.
$backtopage          = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');

// Initialize technical objects
$object      = new Shortener($db);
$extraFields = new ExtraFields($db);

// Initialize view objects
$form = new Form($db);

$hookmanager->initHooks(['shortenercard', 'globalcard']); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extraFields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extraFields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criterias
$searchAll = GETPOST('search_all', 'alpha');
$search    = [];
foreach ($object->fields as $key => $val) {
    if (GETPOST('search_' . $key, 'alpha')) {
        $search[$key] = GETPOST('search_' . $key, 'alpha');
    }
}

if (empty($action) && empty($id) && empty($ref)) {
    $action = 'view';
}

// Load object
if (GETPOST('fromid', 'int')) {
    $id = GETPOST('fromid', 'int');
}
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be included, not include_once

// Security check - Protection if external user
$permissionToRead   = $user->rights->easyurl->shortener->read;
$permissiontoadd    = $user->rights->easyurl->shortener->write;
$permissiontodelete = $user->rights->easyurl->shortener->delete || ($permissiontoadd && isset($object->status) && $object->status == Shortener::STATUS_DRAFT);
saturne_check_access($permissionToRead);

/*
 * Actions
 */

$parameters = ['id' => $id];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
    $error = 0;

    $backurlforlist = dol_buildpath('/easyurl/view/shortener/shortener_list.php', 1);

    if (empty($backtopage) || ($cancel && empty($id))) {
        if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
            if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
                $backtopage = $backurlforlist;
            } else {
                $backtopage = dol_buildpath('/easyurl/view/shortener/shortener_card.php', 1) . '?id=' . ($id > 0 ? $id : '__ID__');
            }
        }
    }

    if ($action == 'update' && GETPOST('from_element', 'int') > 0) {
        $noback = 1;
    }

    // Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
    require_once DOL_DOCUMENT_ROOT . '/core/actions_addupdatedelete.inc.php';
}

/*
 * View
 */

$title    = $langs->trans(ucfirst($object->element));
$help_url = 'FR:Module_EasyURL';

saturne_header(0, '', $title, $help_url);

// Part to assign
if ($action == 'edit_assign') {
    if (empty($permissiontoadd)) {
        accessforbidden($langs->trans('NotEnoughPermissions'), 0);
        exit;
    }

    print load_fiche_titre($langs->trans('Assign' . ucfirst($object->element)), '', 'object_' . $object->picto);

    print '<form class="assign-form" method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="update">';
    print '<input type="hidden" name="from_element" value="' . GETPOST('from_element', 'int') . '">';
    print '<input type="hidden" name="element_type" value="' . GETPOST('element_type') . '">';
    print '<input type="hidden" name="fk_element" value="' . GETPOST('fk_element', 'int') . '">';
    if ($backtopage) {
        print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
    }
    if ($backtopageforcancel) {
        print '<input type="hidden" name="backtopageforcancel" value="'. $backtopageforcancel . '">';
    }

    print dol_get_fiche_head();

    print '<table class="border centpercent tableforfieldedit">';

    $object->fields['fromid']['type'] = 'integer:Shortener:easyurl/class/shortener.class.php::(t.status:=:' . Shortener::STATUS_VALIDATED . ')';

    if (dol_strlen($object->element_type) > 0 || GETPOST('element_type')) {
        $objectsMetadata = saturne_get_objects_metadata(dol_strlen($object->element_type) > 0 ? $object->element_type : GETPOST('element_type'));

        $object->fields['element_type']['picto']                                       = $objectsMetadata['picto'];
        $object->fields['element_type']['arrayofkeyval'][$objectsMetadata['tab_type']] = $langs->trans($objectsMetadata['langs']);

        $object->fields['fk_element']['type']  = 'integer:' . $objectsMetadata['class_name'] . ':' . $objectsMetadata['class_path'];
        $object->fields['fk_element']['picto'] = $objectsMetadata['picto'];
        $object->fields['fk_element']['label'] = $langs->trans($objectsMetadata['langs']);

        if (GETPOST('from_element', 'int') > 0) {
            $object->fields['fromid']['visible']       = 1;
            $object->fields['fromid']['label']         = 'Shortener';
            $object->fields['ref']['visible']          = 0;
            $object->fields['element_type']['visible'] = 0;
            $object->fields['fk_element']['visible']   = 0;
        }
    }

    // Common attributes
    require_once DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_edit.tpl.php';

    // Other attributes
    include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

    print '</table>';

    print dol_get_fiche_end();

    print $form->buttonsSaveCancel('Assign', 'Cancel', [], 0,'assign-button', 'assignShortener');

    print '</form>';
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
    print load_fiche_titre($langs->trans('Modify' . ucfirst($object->element)), '', 'object_' . $object->picto);

    print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="update">';
    print '<input type="hidden" name="id" value="' . $object->id . '">';
    print '<input type="hidden" name="previous_original_url" value="' . $object->original_url . '">';
    if ($backtopage) {
        print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
    }
    if ($backtopageforcancel) {
        print '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';
    }

    print dol_get_fiche_head();

    print '<table class="border centpercent tableforfieldedit">';

    if (dol_strlen($object->element_type) > 0 || GETPOST('element_type')) {
        $objectsMetadata = saturne_get_objects_metadata(dol_strlen($object->element_type) > 0 ? $object->element_type : GETPOST('element_type'));

        $object->fields['element_type']['picto']                                       = $objectsMetadata['picto'];
        $object->fields['element_type']['arrayofkeyval'][$objectsMetadata['tab_type']] = $langs->trans($objectsMetadata['langs']);

        $object->fields['fk_element']['type']  = 'integer:' . $objectsMetadata['class_name'] . ':' . $objectsMetadata['class_path'];
        $object->fields['fk_element']['picto'] = $objectsMetadata['picto'];
        $object->fields['fk_element']['label'] = $langs->trans($objectsMetadata['langs']);

        if (GETPOST('from_element', 'int') > 0) {
            $object->fields['label']['noteditable']        = 1;
            $object->fields['element_type']['noteditable'] = 1;
            $object->fields['fk_element']['picto']         = '';
            $object->fields['fk_element']['noteditable']   = 1;
        }
    }

    // Common attributes
    require_once DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_edit.tpl.php';

    // Other attributes
    require_once DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_edit.tpl.php';

    print '</table>';

    print dol_get_fiche_end();

    print $form->buttonsSaveCancel();

    print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
    $object->fetch_optionals();

    saturne_get_fiche_head($object, 'card', $title);
    saturne_banner_tab($object);

    $formConfirm = '';

    // Delete confirmation
    if ($action == 'delete') {
        $formConfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('DeleteObject', $langs->transnoentities('The' . ucfirst($object->element))), $langs->trans('ConfirmDeleteObject', $langs->transnoentities('The' . ucfirst($object->element))), 'confirm_delete', '', 'yes', 1);
    }

    // Call Hook formConfirm
    $parameters = ['formConfirm' => $formConfirm];
    $resHook    = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
    if (empty($resHook)) {
        $formConfirm .= $hookmanager->resPrint;
    } elseif ($resHook > 0) {
        $formConfirm = $hookmanager->resPrint;
    }

    // Print form confirm
    print $formConfirm;

    if ($conf->browser->layout == 'phone') {
        $onPhone = 1;
    } else {
        $onPhone = 0;
    }

    print '<div class="fichecenter">';
    print '<div class="fichehalfleft">';
    print '<table class="border centpercent tableforfield">';

    unset($object->fields['label']); // Hide field already shown in banner

    if (dol_strlen($object->element_type) > 0) {
        $objectsMetadata                         = saturne_get_objects_metadata($object->element_type);
        $object->fields['element_type']['picto'] = $objectsMetadata['picto'];
        $object->fields['fk_element']['type']    = 'integer:' . $objectsMetadata['class_name'] . ':' . $objectsMetadata['class_path'];
        $object->fields['fk_element']['picto']   = $objectsMetadata['picto'];
        $object->fields['fk_element']['label']   = $langs->trans($objectsMetadata['langs']);
    } else {
        $object->fields['element_type']['type'] = 'varchar(255)';
        unset($object->fields['element_type']['arrayofkeyval']);
        $object->element_type = $langs->trans('NoLinkedElement');
        $object->fk_element   = $langs->trans('NoLinkedElement');
    }

    // Common attributes
    require_once DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_view.tpl.php';

    // Other attributes. Fields from hook formObjectOptions and Extrafields
    require_once DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

    print '</table>';
    print '</div>';
    print '</div>';

    print '<div class="clearboth"></div>';

    print dol_get_fiche_end();

    // Buttons for actions
    print '<div class="tabsAction">';
    $parameters = [];
    $resHook    = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook.
    if ($resHook < 0) {
        setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
    }

    if (empty($resHook) && $permissiontoadd) {
        // Modify
        $displayButton = $onPhone ? '<i class="fas fa-edit fa-2x"></i>' : '<i class="fas fa-edit"></i>' . ' ' . $langs->trans('Modify');
        if ($object->status >= Shortener::STATUS_DRAFT) {
            print '<a class="butAction" id="actionButtonEdit" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . ((dol_strlen($object->element_type) > 0 && !$langs->trans('NoLinkedElement')) ? '&element_type=' . $object->element_type : '') . '&action=edit' . '">' . $displayButton . '</a>';
        } else {
            print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('ObjectMustBeDraft', ucfirst($langs->transnoentities('The' . ucfirst($object->element))))) . '">' . $displayButton . '</span>';
        }

        // Delete (need delete permission, or if draft, just need create/modify permission).
        $displayButton = $onPhone ? '<i class="fas fa-trash fa-2x"></i>' : '<i class="fas fa-trash"></i>' . ' ' . $langs->trans('Delete');
        print dolGetButtonAction($displayButton, '', 'delete', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=delete&token=' . newToken(), '', $permissiontodelete || ($object->status == Shortener::STATUS_DRAFT));
    }
    print '</div>';

    print '<div class="fichecenter"><div class="fichehalfright">';

    $moreHtmlCenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', dol_buildpath('/saturne/view/saturne_agenda.php', 1) . '?id=' . $object->id . '&module_name=EasyURL&object_type=' . $object->element);

    // List of actions on element
    require_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
    $formActions = new FormActions($db);
    $formActions->showactions($object, $object->element . '@' . $object->module, 0, 1, '', 10, '', $moreHtmlCenter);

    print '</div></div>';
}

// End of page
llxFooter();
$db->close();
