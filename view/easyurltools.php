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
require_once __DIR__ . '/../class/urlexport.class.php';
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

    header('Content-type: application/json');

    $error         = 0;
    $urlMethode    = GETPOST('url_methode');
    $NbUrl         = GETPOST('nb_url');
    $originalUrl   = GETPOST('original_url');
    $urlParameters = GETPOST('url_parameters');
    $exportUrlId   = GETPOST('export_url_id');
    $exportFile    = GETPOST('export_file');

    if ($exportUrlId == '') {
        $exportUrl = new UrlExport($db);
        $exportUrl->original_url = $originalUrl . $urlParameters;

        if ($exportUrl->create($user) == -1) {
            http_response_code(500);
            print json_encode(['message' => 'Error during exporting url parameters']);
            exit;
        } else {
            http_response_code(201);
            print json_encode(['message' => 'Ok', 'data' => $exportUrl->id, 'date' => dol_print_date($exportUrl->date_creation, 'dayhour'), 'ref' => $exportUrl->ref]);
            exit;
        }
    } else {
        if ($exportFile == '') {
            if ((dol_strlen($originalUrl) > 0 || dol_strlen(getDolGlobalString('EASYURL_DEFAULT_ORIGINAL_URL')) > 0) && $NbUrl > 0) {
                $shortener = new Shortener($db);
                $shortener->ref = $shortener->getNextNumRef();
                $shortener->fk_easyurl_urlexport = $exportUrlId;

                if (dol_strlen($originalUrl) > 0) {
                    $shortener->original_url = $originalUrl . $urlParameters;
                } else {
                    $shortener->original_url = getDolGlobalString('EASYURL_DEFAULT_ORIGINAL_URL') . $urlParameters;
                }
                $shortener->methode = $urlMethode;

                $shortener->create($user);

                $result = set_easy_url_link($shortener, 'none', $urlMethode);
                if (!empty($result) && is_object($result)) {
                    http_response_code(500);
                    print json_encode(['message' => $result->message, 'title' => $langs->trans("Error")]);
                    exit;
                } else {
                    http_response_code(201);
                    print json_encode(['message' => 'Ok', 'data' => $shortener->id, 'url' => $shortener->original_url]);
                    exit;
                }
            } else {
                http_response_code(400);
                print json_encode(['message' => $langs->trans('OriginalUrlFail'), 'title' => $langs->trans("Error")]);
                exit;
            }
        } else {
            $exportUrl = new UrlExport($db);
            $exportUrl = $exportUrl->fetchAll('', '', 0, 0, ['rowId' => $exportUrlId]);

            if (count($exportUrl) != -1) {
                if (empty($exportUrl)) {
                    http_response_code(404);
                    print json_encode(['message' => 'Error during exporting url parameters', 'title' => $langs->trans("Error")]);
                    exit;
                } else {
                    $exportUrl = current($exportUrl);
                    $exportUrl->generateFile();

                    $uploadDir = $conf->easyurl->multidir_output[$conf->entity ?? 1];
                    $fileDir   = $uploadDir . '/' . $exportUrl->element;
                    if (dol_is_file($fileDir . '/' . $exportUrl->last_main_doc)) {
                        $documentUrl = DOL_URL_ROOT . '/document.php';
                        $fileUrl = $documentUrl . '?modulepart=easyurl&file=' . urlencode($exportUrl->element . '/' . $exportUrl->last_main_doc);
                        http_response_code(201);
                        print json_encode(['message' => $langs->trans('ExportSuccess'), 'title' => $langs->trans("Success"), 'download' => '<div><a class="marginleftonly" href="' . $fileUrl . '" download>' . img_picto($langs->trans('File') . ' : ' . $exportUrl->last_main_doc, 'fa-file-csv') . '</a></div>', 'redirect' => dol_buildpath('/custom/easyurl/view/shortener/shortener_list.php', 1)]);
                        exit;
                    } else {
                        http_response_code(500);
                        print json_encode(['message' => 'Error during exporting url parameters', 'title' => $langs->trans("Error")]);
                        exit;
                    }
                }
            } else {
                http_response_code(500);
                print json_encode(['message' => 'Error during exporting url parameters', 'title' => $langs->trans("Error")]);
                exit;
            }
        }
    }
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

print '
<div class="wpeo-notice notice-success global-infos notice" style="display: none;">
    <div class="notice-content">
        <div class="notice-title"></div>
    </div>
    <div class="notice-close"><i class="fas fa-times"></i></div>
</div>
';

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

print '<tr class="oddeven"><td><label for="url_parameters">' . $langs->trans('UrlParameters') . '</label></td>';
print '<td>' . $langs->trans('UrlParametersDescription') . '</td>';
print '<td><input class="minwidth300" type="text" name="url_parameters"></td>';
print '</tr>';

print '</table>';
print '<div class="right">';
print $form->buttonsSaveCancel('Generate', '', [], true);
print '</div>';
print '</form>';

print load_fiche_titre($langs->trans('GeneratedExport'), '', '');
print '<table class="noborder centpercent tab-export">';

print '<tr class="liste_titre">';
print '<td>' . $langs->trans('ExportId') . '</td>';
print '<td>' . $langs->trans('ExportNumber') . '</td>';
print '<td>' . $langs->trans('ExportStart') . '</td>';
print '<td>' . $langs->trans('ExportEnd') . '</td>';
print '<td>' . $langs->trans('ExportDate') . '</td>';
print '<td>' . $langs->trans('ExportOrigin') . '</td>';
print '<td>' . $langs->trans('ExportConsume') . '</td>';
print '<td></td>';
print '</tr>';
$urlExport = new UrlExport($db);
$urlExport = $urlExport->fetchAll('DESC', 'rowid');

foreach ($urlExport as $row) {
    $shortener = new Shortener($db);
    $shortener = $shortener->fetchAll('', '', 0, 0, ['t.fk_easyurl_urlexport' => $row->id]);

    $uploadDir = $conf->easyurl->multidir_output[$conf->entity ?? 1];
    $fileDir   = $uploadDir . '/' . $row->element;
    if (dol_is_file($fileDir . '/' . $row->last_main_doc)) {
        $documentUrl = DOL_URL_ROOT . '/document.php';
        $fileUrl     = $documentUrl . '?modulepart=easyurl&file=' . urlencode($row->element . '/' . $row->last_main_doc);
        print '<tr class="oddeven">';
        print '<td class="tab-ref">' . $row->ref . '</td>';
        print '<td class="tab-count">' . count($shortener) . '</td>';
        print '<td class="tab-first">' . current($shortener)->id . '</td>';
        print '<td class="tab-end">' . end($shortener)->id . '</td>';
        print '<td class="tab-date">' . dol_print_date($row->date_creation, 'dayhour') . '</td>';
        print '<td class="tab-url"><a href="' . current($shortener)->original_url . '"><span class="fas fa-external-link-alt paddingrightonly" style=""></span><span>' . current($shortener)->original_url . '<span></a></td>';
        print '<td class="tab-uses">' . count(array_filter($shortener, function($elem) {return $elem->status == 0;})) . '</td>';
        print '<td class="tab-download"><div><a class="marginleftonly" href="' . $fileUrl . '" download>' . img_picto($langs->trans('File') . ' : ' . $row->last_main_doc, 'fa-file-csv') . '</a></div></td>';
        print '</tr>';
    }
}
print '</table>';

// End of page
llxFooter();
$db->close();
