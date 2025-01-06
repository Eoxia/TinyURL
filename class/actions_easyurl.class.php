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
 * \file    class/actions_easyurl.class.php
 * \ingroup easyurl
 * \brief   EasyURL hook overload
 */

// Load EasyURL libraries
require_once __DIR__ . '/../lib/easyurl_function.lib.php';

/**
 * Class ActionsEasyurl
 */
class ActionsEasyurl
{
    /**
     * @var DoliDB Database handler
     */
    public DoliDB $db;

    /**
     * @var string Error code (or message)
     */
    public string $error = '';

    /**
     * @var array Errors.
     */
    public array $errors = [];

    /**
     * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
     */
    public array $results = [];

    /**
     * @var string|null String displayed by executeHook() immediately after return
     */
    public ?string $resprints;

    /**
     * Constructor
     *
     *  @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        $this->db = $db;
    }

    /**
     * Overloading the printCommonFooter function : replacing the parent's function with the one below
     *
     * @param  array     $parameters Hook metadatas (context, etc...)
     * @return int                   0 < on error, 0 on success, 1 to replace standard code
     * @throws Exception
     */
    public function printCommonFooter(array $parameters): int
    {
        global $object, $langs;

        if (in_array($parameters['currentcontext'], ['propalcard', 'ordercard', 'invoicecard', 'contractcard', 'interventioncard'])) {
            if ($object->status > $object::STATUS_DRAFT) {
                $cssPath = dol_buildpath('/saturne/css/saturne.min.css', 1);
                print '<link href="' . $cssPath . '" rel="stylesheet">';

                $pictoPath = dol_buildpath('/easyurl/img/easyurl_color.png', 1);
                $picto     = img_picto('', $pictoPath, '', 1, 0, 0, '', 'pictoModule');
                $urlTypes  = ['payment', 'signature'];
                foreach ($urlTypes as $urlType) {
                    $checkEasyUrlLink = get_easy_url_link($object, $urlType);
                    $jQueryElement    = '.' . $object->element . '_extras_easy_url_' . $urlType . '_link';
                    if ($checkEasyUrlLink == 0 && getDolGlobalInt('EASYURL_MANUAL_GENERATION')) {
                        $output  = $picto;
                        $output .= '<a class="reposition editfielda" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=set_easy_url&url_type=' . $urlType . '&token=' . newToken() . '">';
                        $output .= img_picto($langs->trans('SetEasyURLLink'), 'fontawesome_fa-redo_fas_#444', 'class="paddingright pictofixedwidth valignmiddle"') . '</a>';
                        $output .= '</span>' . img_picto($langs->trans('GetEasyURLErrors'), 'fontawesome_fa-exclamation-triangle_fas_#bc9526') . '</span>';
                    }
                    if (!empty($object->array_options['options_easy_url_' . $urlType . '_link']) && $checkEasyUrlLink > 0) {
                        $output = showValueWithClipboardCPButton($object->array_options['options_easy_url_' . $urlType . '_link'], 0, 'none');
                    } ?>
                    <script>
                        var objectElement = <?php echo "'" . $jQueryElement . "'"; ?>;
                        jQuery(objectElement).prepend(<?php echo json_encode($output); ?>);
                    </script>
                    <?php
                }
            }
        }

        require_once __DIR__ . '/../../saturne/lib/object.lib.php';

        $objectsMetadata = saturne_get_objects_metadata();
        if (!empty($objectsMetadata)) {
            foreach ($objectsMetadata as $objectMetadata) {
                if ($objectMetadata['link_name'] == $object->element || $objectMetadata['tab_type'] == $object->element) {
                    if ($parameters['currentcontext'] == $objectMetadata['hook_name_card']) {

                        $jsPath = dol_buildpath('/saturne/js/saturne.min.js', 1);
                        print '<script src="' . $jsPath . '" ></script>';
                        $jsPath = dol_buildpath('/easyurl/js/easyurl.min.js', 1);
                        print '<script src="' . $jsPath . '" ></script>';

                        require_once __DIR__ . '/shortener.class.php';

                        $shortener = new Shortener($this->db);
                        $output = $shortener->displayObjectDetails($object); ?>
                        <script>
                            jQuery('.fichehalfright').first().append(<?php echo json_encode($output); ?>);
                        </script>
                        <?php
                    }
                }
            }
        }

        if (in_array($parameters['currentcontext'], ['propallist', 'orderlist', 'invoicelist'])) {
            $cssPath = dol_buildpath('/saturne/css/saturne.min.css', 1);
            print '<link href="' . $cssPath . '" rel="stylesheet">';

            $pictoPath = dol_buildpath('/easyurl/img/easyurl_color.png', 1);
            $picto     = img_picto('', $pictoPath, '', 1, 0, 0, '', 'pictoModule');
            $urlTypes  = ['payment', 'signature'];
            foreach ($urlTypes as $urlType) {
                $jQueryElement = 'easy_url_' . $urlType . '_link'; ?>

                <script>
                    var objectElement = <?php echo "'" . $jQueryElement . "'"; ?>;
                    var outJS         = <?php echo json_encode($picto); ?>;
                    var cell          = $('.liste > tbody > tr.liste_titre').find('th[data-titlekey="' + objectElement + '"]');
                    cell.prepend(outJS);
                </script>
                <?php
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     *  Overloading the doActions function : replacing the parent's function with the one below
     *
     * @param  array        $parameters Hook metadatas (context, etc...)
     * @param  CommonObject $object     Current object
     * @param  string       $action     Current action
     * @return int                      0 < on error, 0 on success, 1 to replace standard code
     */
    public function doActions(array $parameters, $object, string $action): int
    {
        global $conf, $user;

        if (in_array($parameters['currentcontext'], ['propalcard', 'ordercard', 'invoicecard', 'contractcard', 'interventioncard'])) {
            if ($action == 'set_easy_url') {
                set_easy_url_link($object, GETPOST('url_type'));

                header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
                exit;
            }
        }

        require_once __DIR__ . '/../../saturne/lib/object.lib.php';

        $objectsMetadata = saturne_get_objects_metadata();
        if (!empty($objectsMetadata)) {
            foreach ($objectsMetadata as $objectMetadata) {
                if ($objectMetadata['link_name'] == $object->element || $objectMetadata['tab_type'] == $object->element) {
                    if ($parameters['currentcontext'] == $objectMetadata['hook_name_card']) {
                        if ($action == 'show_qrcode') {
                            $data = json_decode(file_get_contents('php://input'), true);

                            $showQRCode = $data['showQRCode'];

                            $tabParam['EASYURL_SHOW_QRCODE'] = $showQRCode;

                            dol_set_user_param($this->db, $conf, $user, $tabParam);
                        }
                    }
                }
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     *  Overloading the beforeLoginAuthentication function : replacing the parent's function with the one below
     *
     * @param  array        $parameters Hook metadatas (context, etc...)
     * @param  CommonObject $object     Current object
     * @param  string       $action     Current action
     * @return int                      0 < on error, 0 on success, 1 to replace standard code
     */
    public function beforeLoginAuthentication(array $parameters, $object, string $action) {

//			if ($_SERVER['REQUEST_URI']=='/register') {
//				header("Location: https://demodoli.digirisk.com/htdocs/custom/digiriskdolibarr/public/ticket/create_ticket.php?idmenu=3912&mainmenu=digiriskdolibarr&leftmenu=");
//				exit;
//			}

        return 0; // or return 1 to replace standard code
	}
}
