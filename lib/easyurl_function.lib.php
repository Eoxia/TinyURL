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
 * @param  Shortener         $shortener Shortener
 * @param  string            $urlType   Url type
 * @param  CommonObject|null $object    Object
 * @param  string            $urlMethod Url method
 * @return int|object        $data      Data error after curl
 */
function set_easy_url_link(Shortener $shortener, string $urlType, CommonObject $object = null, string $urlMethod = 'yourls')
{
    global $conf, $langs, $user;

    $useOnlinePayment = (isModEnabled('paypal') || isModEnabled('stripe') || isModEnabled('paybox'));
    $checkConf        = getDolGlobalString('EASYURL_URL_' . dol_strtoupper($urlMethod) . '_API') && getDolGlobalString('EASYURL_SIGNATURE_TOKEN_' . dol_strtoupper($urlMethod) . '_API');
    if ((($urlType == 'payment' && $useOnlinePayment) || $urlType == 'signature' || $urlType == 'none') && $checkConf) {
        // Load Dolibarr libraries
        require_once DOL_DOCUMENT_ROOT . '/core/lib/payments.lib.php';
        require_once DOL_DOCUMENT_ROOT . '/core/lib/signature.lib.php';
        require_once DOL_DOCUMENT_ROOT . '/core/lib/ticket.lib.php';

        $shortener->fetch($shortener->id);
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
                if ($type == 'proposal') {
                    $type = 'propal';
                }
                $shortener->element_type = $type;
                $shortener->fk_element   = $object->id;
                $shortener->status       = $shortener::STATUS_ASSIGN;
                break;
            case 'signature' :
                $onlineUrl = getOnlineSignatureUrl(0, $type, $object->ref);
                if ($type == 'proposal') {
                    $type = 'propal';
                }
                $shortener->element_type = $type;
                $shortener->fk_element   = $object->id;
                $shortener->status       = $shortener::STATUS_ASSIGN;
                break;
            default :
                if (property_exists($shortener, 'original_url') && dol_strlen($shortener->original_url) > 0) {
                    $onlineUrl = $shortener->original_url;
                } else {
                    $onlineUrl = getDolGlobalString('EASYURL_DEFAULT_ORIGINAL_URL');
                }
                break;
        }

        $title  = getDolGlobalInt('EASYURL_USE_MAIN_INFO_SOCIETE_NAME') ? dol_strtolower($conf->global->MAIN_INFO_SOCIETE_NOM) : '';
        $title .= getDolGlobalInt('EASYURL_USE_MAIN_INFO_SOCIETE_NAME') && getDolGlobalInt('EASYURL_USE_SHORTENER_REF') ? '-' : '';
        $title .= getDolGlobalInt('EASYURL_USE_SHORTENER_REF') ? dol_strtolower($shortener->ref) : '';
        $title .= (getDolGlobalInt('EASYURL_USE_MAIN_INFO_SOCIETE_NAME') || getDolGlobalInt('EASYURL_USE_SHORTENER_REF')) && getDolGlobalInt('EASYURL_USE_SHA_URL') ? '-' : '';
        $title .= getDolGlobalInt('EASYURL_USE_SHA_URL') ? generate_random_id(8) : '';
        $title  = dol_sanitizeFileName($title);

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
            $shortenerUrlTypeDictionaries = saturne_fetch_dictionary('c_shortener_url_type');
            if (is_array($shortenerUrlTypeDictionaries) && !empty($shortenerUrlTypeDictionaries)) {
                foreach ($shortenerUrlTypeDictionaries as $shortenerUrlTypeDictionary) {
                    if ($shortenerUrlTypeDictionary->ref == ucfirst($urlType)) {
                        $shortener->type = $shortenerUrlTypeDictionary->id;
                        break;
                    }
                }
            }
            if ($shortener->status == $shortener::STATUS_DRAFT) {
                $shortener->status = $shortener::STATUS_VALIDATED;
            }
            $shortener->label        = $title;
            $shortener->short_url    = $data->shorturl;
            $shortener->original_url = $onlineUrl;
            $shortener->update($user, true);

            require_once TCPDF_PATH . 'tcpdf_barcodes_2d.php';

            $barcode = new TCPDF2DBarcode($shortener->short_url, 'QRCODE,L');

            dol_mkdir($conf->easyurl->multidir_output[$conf->entity] . '/shortener/' . $shortener->ref . '/qrcode/');
            $file = $conf->easyurl->multidir_output[$conf->entity] . '/shortener/' . $shortener->ref . '/qrcode/' . 'barcode_' . $shortener->ref . '.png';

            $imageData = $barcode->getBarcodePngData();
            $imageData = imagecreatefromstring($imageData);
            imagepng($imageData, $file);
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
 * @param  string     $shortUrl  Short url
 * @param  string     $urlType   Url type
 * @return int|object $data      0 < on error, data
 */
function get_easy_url_link(string $shortUrl, string $urlType)
{
    global $conf;

    $useOnlinePayment = (isModEnabled('paypal') || isModEnabled('stripe') || isModEnabled('paybox'));
    $checkConf        = getDolGlobalString('EASYURL_URL_YOURLS_API') && getDolGlobalString('EASYURL_SIGNATURE_TOKEN_YOURLS_API');
    if ((($urlType == 'payment' && $useOnlinePayment) || $urlType == 'signature') && $checkConf) {
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
            'shorturl'  => $shortUrl
        ]);

        // Fetch and return content
        $data = curl_exec($ch);
        curl_close($ch);

        // Do something with the result
        $data = json_decode($data);
        if ($data->statusCode == 200) {
            return $data->link;
        } else {
            return -1;
        }
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
