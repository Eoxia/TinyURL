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
    global $db;

    $langs->load('easyurl@easyurl');

    require_once __DIR__ . '/../../class/shortener.class.php';

    $shortener = new Shortener($db);

    $shorteners = $shortener->fetchAll('', '', 0,0, ['customsql' => 'fk_element = ' . $object->id . ' AND type > 0']);
    if (is_array($shorteners) && !empty($shorteners)) {
        foreach ($shorteners as $shortener) {
            $substitutionarray['__EASY_URL_' . dol_strtoupper(getDictionaryValue('c_shortener_url_type', 'label', $shortener->type)) . '_LINK__'] = $shortener->short_url;
        }
    }
}
