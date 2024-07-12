/* Copyright (C) 2022-2023 EVARISK <technique@evarisk.com>
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
 *
 * Library javascript to enable Browser notifications
 */

/**
 * \file    js/easyurl.js
 * \ingroup easyurl
 * \brief   JavaScript file for module EasyURL
 */

'use strict';

if (!window.easyurl) {
  /**
   * Init EasyURL JS
   *
   * @memberof EasyURL_Init
   *
   * @since   1.1.0
   * @version 1.1.0
   *
   * @type {Object}
   */
  window.easyurl = {};

  /**
   * Init scriptsLoaded EasyURL
   *
   * @memberof EasyURL_Init
   *
   * @since   1.1.0
   * @version 1.1.0
   *
   * @type {Boolean}
   */
  window.easyurl.scriptsLoaded = false;
}

if (!window.easyurl.scriptsLoaded) {
  /**
   * EasyURL init
   *
   * @memberof EasyURL_Init
   *
   * @since   1.1.0
   * @version 1.1.0
   *
   * @returns {void}
   */
  window.easyurl.init = function() {
    window.easyurl.load_list_script();
  };

  /**
   * Load all modules' init
   *
   * @memberof EasyURL_Init
   *
   * @since   1.1.0
   * @version 1.1.0
   *
   * @returns {void}
   */
  window.easyurl.load_list_script = function() {
    if (!window.easyurl.scriptsLoaded) {
      let key = undefined, slug = undefined;
      for (key in window.easyurl) {
        if (window.easyurl[key].init) {
          window.easyurl[key].init();
        }
        for (slug in window.easyurl[key]) {
          if (window.easyurl[key] && window.easyurl[key][slug] && window.easyurl[key][slug].init) {
            window.easyurl[key][slug].init();
          }
        }
      }
      window.easyurl.scriptsLoaded = true;
    }
  };

  /**
   * Refresh and reload all modules' init
   *
   * @memberof EasyURL_Init
   *
   * @since   1.1.0
   * @version 1.1.0
   *
   * @returns {void}
   */
  window.easyurl.refresh = function() {
    let key = undefined;
    let slug = undefined;
    for (key in window.easyurl) {
      if (window.easyurl[key].refresh) {
        window.easyurl[key].refresh();
      }
      for (slug in window.easyurl[key]) {
        if (window.easyurl[key] && window.easyurl[key][slug] && window.easyurl[key][slug].refresh) {
          window.easyurl[key][slug].refresh();
        }
      }
    }
  };
  $(document).ready(window.easyurl.init);
}
