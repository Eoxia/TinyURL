<?php
/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
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
 * \file    admin/publicinterface.php
 * \ingroup easyurl
 * \brief   EasyURL publicinterface config page
 */

// Load EasyURL environment
if (file_exists('../easyurl.main.inc.php')) {
    require_once __DIR__ . '/../easyurl.main.inc.php';
} elseif (file_exists('../../easyurl.main.inc.php')) {
    require_once __DIR__ . '/../../easyurl.main.inc.php';
} else {
    die('Include of easyurl main fails');
}

// Load EasyURL libraries
require_once __DIR__ . '/../lib/easyurl.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $moduleName, $moduleNameLowerCase, $user;

// Load translation files required by the page
saturne_load_langs();

$hookmanager->initHooks(['publicinterfaceadmin', 'globalcard']); // Note that conf->hooks_modules contains array

// Security check - Protection if external user
$permissiontoread = $user->rights->$moduleNameLowerCase->adminpage->read;
saturne_check_access($permissiontoread);

/*
 * View
 */

$title   = $langs->trans('ModuleSetup', $moduleName);
$helpUrl = 'FR:Module_EasyURL';

saturne_header(0,'', $title, $helpUrl);

// Subheader
$linkBack = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans('BackToModuleList') . '</a>';
print load_fiche_titre($title, $linkBack, 'title_setup');

// Configuration header
$head = easyurl_admin_prepare_head();
print dol_get_fiche_head($head, 'publicinterface', $title, -1, 'easyurl_color@easyurl');

$publicInterfaceUrl = dol_buildpath('custom/easyurl/public/shortener/public_shortener.php?entity=' . $conf->entity, 3);
print '<a class="marginrightonly" href="' . $publicInterfaceUrl . '" target="_blank">' . img_picto('', 'url', 'class="pictofixedwidth"') . $langs->trans('PublicInterfaceObject', $langs->transnoentities('OfAssignShortener')) . '</a>';
print showValueWithClipboardCPButton($publicInterfaceUrl, 0, 'none');

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
