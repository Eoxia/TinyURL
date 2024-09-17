<?php
/* Copyright (C) 2023 EVARISK <technique@evarisk.com>
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
 * or see https://www.gnu.org/
 */

/**
 * \file    core/substitutions/functions_easyurl.lib.php
 * \ingroup functions_easyurl
 * \brief   File of functions to substitutions array
 */

/** Function called to complete substitution array (before generating on ODT, or a personalized email)
 * functions xxx_completesubstitutionarray are called by make_substitutions() if file
 * is inside directory htdocs/core/substitutions
 *
 * @param  array     $substitutionarray Array with substitution key => val
 * @param  Translate $langs             Output langs
 * @param  Object    $object            Object to use to get values
 * @return void                         The entry parameter $substitutionarray is modified
 */
function easyurl_completesubstitutionarray(&$substitutionarray, $langs, $object)
{
    $langs->load('easyurl@easyurl');
    if (isset($object->element)) {
        switch ($object->element) {
            case 'propal' :
            case 'contrat' :
            case 'fichinter' :
                $substitutionarray['__EASY_URL_SIGNATURE_LINK__'] = $object->array_options['options_easy_url_signature_link'];
                break;
            case 'commande' :
            case 'facture' :
                $substitutionarray['__EASY_URL_PAYMENT_LINK__'] = $object->array_options['options_easy_url_payment_link'];
                break;
            default :
                $substitutionarray['__EASY_URL_SIGNATURE_LINK__'] = $langs->trans('EasyUrlSignatureLink');
                $substitutionarray['__EASY_URL_PAYMENT_LINK__'] = $langs->trans('EasyUrlPaymentLink');
                break;
        }
    }

    $shortenerLabel = dol_strlen(getDolGlobalString('EASYURL_SHORTENER_SET_LABEL')) > 0 ? getDolGlobalString('EASYURL_SHORTENER_SET_LABEL') : $langs->transnoentities('ShortenerSetLabel');
    if (strpos($shortenerLabel, '__EASYURL_SHA_') !== false) {
        require_once DOL_DOCUMENT_ROOT . '/core/lib/ticket.lib.php';

        $SHAshortenerLabel = explode('__EASYURL_SHA_', $shortenerLabel);
        $SHAshortenerLabel = explode('__', $SHAshortenerLabel[1]);
        $substitutionarray['__EASYURL_SHA_' . $SHAshortenerLabel[0] . '__'] = generate_random_id($SHAshortenerLabel[0]);
    }
}
