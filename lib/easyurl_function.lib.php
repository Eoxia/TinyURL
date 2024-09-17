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
 * \file    lib/easyurl_function.lib.php
 * \ingroup easyurl
 * \brief   Library files with common functions for EasyURL
 */

/**
 * Set easy url link
 *
 * @param  CommonObject $object    Object
 * @param  string       $urlType   Url type
 * @param  string       $urlMethod Url method
 * @return int|object   $data      Data error after curl
 */
function set_easy_url_link(CommonObject $object, string $urlType, string $urlMethod = 'yourls')
{
    global $conf, $langs, $user;

    $useOnlinePayment = (isModEnabled('paypal') || isModEnabled('stripe') || isModEnabled('paybox'));
    $checkConf        = getDolGlobalString('EASYURL_URL_' . dol_strtoupper($urlMethod) . '_API') && getDolGlobalString('EASYURL_SIGNATURE_TOKEN_' . dol_strtoupper($urlMethod) . '_API');
    if ((($urlType == 'payment' && $useOnlinePayment) || $urlType == 'signature' || $urlType == 'none') && $checkConf) {
        // Load Dolibarr libraries
        require_once DOL_DOCUMENT_ROOT . '/core/lib/payments.lib.php';
        require_once DOL_DOCUMENT_ROOT . '/core/lib/signature.lib.php';
        require_once DOL_DOCUMENT_ROOT . '/core/lib/ticket.lib.php';

        $object->fetch($object->id);
        switch ($object->element) {
            case 'propal' :
                $type = 'proposal';
                break;
            case 'commande' :
                $type = 'order';
                break;
            case 'facture' :
                $type = 'invoice';
                break;
            case 'contrat' :
                $type = 'contract';
                break;
            default :
                $type = $object->element;
                break;
        }
        switch ($urlType) {
            case 'payment' :
                $onlineUrl = getOnlinePaymentUrl(0, $type, $object->ref);
                break;
            case 'signature' :
                $onlineUrl = getOnlineSignatureUrl(0, $type, $object->ref);
                break;
            default :
                if (property_exists($object, 'original_url') && dol_strlen($object->original_url) > 0) {
                    $onlineUrl = $object->original_url;
                } else {
                    $onlineUrl = getDolGlobalString('EASYURL_DEFAULT_ORIGINAL_URL');
                }
                break;
        }

        $substitutionArray = getCommonSubstitutionArray($langs, 0, null, $shortener);
        complete_substitutions_array($substitutionArray, $langs, $shortener);
        $shortenerSetLabel = make_substitutions($langs->transnoentities($conf->global->EASYURL_SHORTENER_SET_LABEL), $substitutionArray);

        $title = dol_sanitizeFileName(dol_strtolower(dol_strlen($shortenerSetLabel) > 0 ? $shortenerSetLabel : $langs->transnoentities('ShortenerSetLabel')));

        // Init the CURL session
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, getDolGlobalString('EASYURL_URL_' . dol_strtoupper($urlMethod) . '_API'));
        curl_setopt($ch, CURLOPT_HEADER, 0);            // No header in the result
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return, do not echo result
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, 1);              // This is a POST request
        switch ($urlMethod) {
            case 'yourls' :
                curl_setopt($ch, CURLOPT_POSTFIELDS, [               // Data to POST
                    'action'    => 'shorturl',
                    'signature' => getDolGlobalString('EASYURL_SIGNATURE_TOKEN_YOURLS_API'),
                    'format'    => 'json',
                    'title'     => $title,
                    'keyword'   => $title,
                    'url'       => $onlineUrl
                ]);
                break;
            case 'wordpress' :
                break;
        }

        // Fetch and return content
        $data = curl_exec($ch);
        curl_close($ch);

        // Do something with the result
        $data = json_decode($data);

        if ($data->status == 'success') {
            if ($urlType != 'none') {
                $object->array_options['options_easy_url_' . $urlType . '_link'] = $data->shorturl;
                $object->updateExtraField('easy_url_' . $urlType . '_link');
                setEventMessage($langs->trans('SetEasyURLSuccess'));
            } else {
                // Shortener object in 100% of cases
                $object->status       = $object::STATUS_VALIDATED;
                $object->label        = $title;
                $object->short_url    = $data->shorturl;
                $object->original_url = $onlineUrl;
                $object->update($user, true);

                require_once TCPDF_PATH . 'tcpdf_barcodes_2d.php';

                $barcode = new TCPDF2DBarcode($object->short_url, 'QRCODE,L');

                dol_mkdir($conf->easyurl->multidir_output[$conf->entity] . '/shortener/' . $object->ref . '/qrcode/');
                $file = $conf->easyurl->multidir_output[$conf->entity] . '/shortener/' . $object->ref . '/qrcode/' . 'barcode_' . $object->ref . '.png';

                $imageData = $barcode->getBarcodePngData();
                $imageData = imagecreatefromstring($imageData);
                imagepng($imageData, $file);
            }
            return 1;
        } else {
            setEventMessage($langs->trans('SetEasyURLErrors'), 'errors');
            return $data;
        }
    }
}

/**
 * get easy url link
 *
 * @param  CommonObject $object  Object
 * @param  string       $urlType Url type
 * @return int                   0 < on error, 1 = statusCode 200, 0 = other statusCode (ex : 404)
 */
function get_easy_url_link(CommonObject $object, string $urlType): int
{
    global $conf;

    $useOnlinePayment = (isModEnabled('paypal') || isModEnabled('stripe') || isModEnabled('paybox'));
    $checkConf        = getDolGlobalString('EASYURL_URL_YOURLS_API') && getDolGlobalString('EASYURL_SIGNATURE_TOKEN_YOURLS_API');
    if ((($urlType == 'payment' && $useOnlinePayment) || $urlType == 'signature') && $checkConf) {
        $object->fetch($object->id);

        // Init the CURL session
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $conf->global->EASYURL_URL_YOURLS_API);
        curl_setopt($ch, CURLOPT_HEADER, 0);            // No header in the result
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return, do not echo result
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, 1);              // This is a POST request
        curl_setopt($ch, CURLOPT_POSTFIELDS, [               // Data to POST
            'action'    => 'url-stats',
            'signature' => $conf->global->EASYURL_SIGNATURE_TOKEN_YOURLS_API,
            'format'    => 'json',
            'shorturl'  => $object->array_options['options_easy_url_' . $urlType . '_link']
        ]);

        // Fetch and return content
        $data = curl_exec($ch);
        curl_close($ch);

        // Do something with the result
        $data = json_decode($data);
        return $data->statusCode == 200 ? 1 : 0;
    } else {
        return -1;
    }
}

/**
 * Update easy url link
 *
 * @param  CommonObject $object Object
 * @return int                  0 < on error, 1 = statusCode 200, 0 = other statusCode (ex : 404)
 */
function update_easy_url_link(CommonObject $object): int
{
    global $conf;

    // Init the CURL session
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $conf->global->EASYURL_URL_YOURLS_API);
    curl_setopt($ch, CURLOPT_HEADER, 0);            // No header in the result
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return, do not echo result
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POST, 1);              // This is a POST request
    curl_setopt($ch, CURLOPT_POSTFIELDS, [               // Data to POST
        'action'    => 'update',
        'signature' => $conf->global->EASYURL_SIGNATURE_TOKEN_YOURLS_API,
        'format'    => 'json',
        'shorturl'  => $object->label,
        'url'       => $object->original_url
    ]);

    // Fetch and return content
    $data = curl_exec($ch);
    curl_close($ch);

    // Do something with the result
    $data = json_decode($data);
    return $data->statusCode == 200 ? 1 : 0;
}
