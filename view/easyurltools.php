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
 * \file    view/easyurltools.php
 * \ingroup easyurl
 * \brief   Tools page of EasyURL top menu
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
require_once __DIR__ . '/../class/shortener.class.php';
require_once __DIR__ . '/../lib/easyurl_function.lib.php';

// Global variables definitions
global $conf, $db, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$action = (GETPOSTISSET('action') ? GETPOST('action', 'aZ09') : 'view');

// Initialize view objects
$form = new Form($db);

// Security check - Protection if external user
$permissionToRead = $user->rights->easyurl->adminpage->read;
$permissionToAdd  = $user->rights->easyurl->shortener->write;
saturne_check_access($permissionToRead);

/*
 * Actions
 */

if ($action == 'generate_url' && $permissionToAdd) {
    $error                 = 0;
    $urlMethode            = GETPOST('url_methode');
    $NbUrl                 = GETPOST('nb_url');
    $originalUrl           = GETPOST('original_url');
    $originalUrlParameters = GETPOST('original_url_parameters');
    if ((dol_strlen($originalUrl) > 0 || dol_strlen(getDolGlobalString('EASYURL_DEFAULT_ORIGINAL_URL')) > 0) && $NbUrl > 0)  {
        for ($i = 1; $i <= $NbUrl; $i++) {
            $shortener = new Shortener($db);
            $shortener->ref = $shortener->getNextNumRef();
            if (dol_strlen($originalUrl) > 0) {
                $shortener->original_url = $originalUrl . $originalUrlParameters;
            } else {
                $shortener->original_url = getDolGlobalString('EASYURL_DEFAULT_ORIGINAL_URL') . $originalUrlParameters;
            }
            $shortener->methode = $urlMethode;

            $shortener->create($user);

            // UrlType : none because we want mass generation url (all can be use but need to change this code)
            $result = set_easy_url_link($shortener, 'none', $urlMethode);
            if (!empty($result) && is_object($result)) {
                setEventMessage($result->message, 'errors');
                $error++;
            }
        }
        if ($error == 0) {
            setEventMessage($langs->trans('GenerateUrlSuccess', $i - 1));
        }
    } else {
        setEventMessage($langs->trans('OriginalUrlFail'), 'errors');
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

/*
 * View
 */

$title   = $langs->trans('Tools');
$helpUrl = 'FR:Module_EasyURL';

saturne_header(0,'', $title, $helpUrl);

print load_fiche_titre($title, '', 'wrench');

if (!getDolGlobalString('EASYURL_DEFAULT_ORIGINAL_URL')) : ?>
<div class="wpeo-notice notice-warning">
    <div class="notice-content">
        <div class="notice-title">
            <a href="<?php echo dol_buildpath('/custom/easyurl/admin/setup.php', 1); ?>"><strong><?php echo $langs->trans('DefaultOriginalUrlConfiguration'); ?></strong></a>
        </div>
    </div>
</div>
<?php endif;

print load_fiche_titre($langs->trans('GenerateUrlManagement'), '', '');

print '<form name="generate-url-from" id="generate-url-from" action="' . $_SERVER['PHP_SELF'] . '" method="POST">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="generate_url">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Parameters') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td>' . $langs->trans('Value') . '</td>';
print '</tr>';

$urlMethode = ['yourls' => 'YOURLS', 'wordpress' => 'WordPress'];
print '<tr class="oddeven"><td>';
print $langs->trans('UrlMethode');
print '</td><td>';
print $langs->trans('UrlMethodeDescription');
print '<td>';
print $form::selectarray('url_methode', $urlMethode, 'yourls');
print '</td></tr>';

print '<tr class="oddeven"><td><label for="nb_url">' . $langs->trans('NbUrl') . '</label></td>';
print '<td>' . $langs->trans('NbUrlDescription') . '</td>';
print '<td><input class="minwidth100" type="number" name="nb_url" min="0"></td>';
print '</tr>';

print '<tr class="oddeven"><td><label for="original_url">' . $langs->trans('OriginalUrl') . '</label></td>';
print '<td>' .  $langs->trans('OriginalUrlDescription') . (getDolGlobalString('EASYURL_DEFAULT_ORIGINAL_URL') ? $langs->trans('OriginalUrlMoreDescription', getDolGlobalString('EASYURL_DEFAULT_ORIGINAL_URL')) : '') . '</td>';
print '<td><input class="minwidth300" type="text" name="original_url"></td>';
print '</tr>';

print '<tr class="oddeven"><td><label for="original_url_parameters">' . $langs->trans('OriginalUrlParameters') . '</label></td>';
print '<td>' . $langs->trans('OriginalUrlParametersDescription') . '</td>';
print '<td><input class="minwidth300" type="text" name="original_url_parameters"></td>';
print '</tr>';

print '</table>';
print $form->buttonsSaveCancel('Generate', '');
print '</form>';

// End of page
llxFooter();
$db->close();
