/* Copyright (C) 2021-2024 EVARISK <technique@evarisk.com>
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
 * \file    js/urlexport.js
 * \ingroup easyurl
 * \brief   JavaScript shortener file for module EasyURL
 */

/**
 * Init UrlExport JS
 *
 * @memberof EasyURL_UrlExport
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @type {Object}
 */
window.easyurl.urlexport = {};

/**
 * UrlExport init
 *
 * @memberof EasyURL_UrlExport
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @returns {void}
 */
window.easyurl.urlexport.init = function() {
  window.easyurl.urlexport.event();
};

/**
 * UrlExport event
 *
 * @memberof EasyURL_UrlExport
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @returns {void}
 */
window.easyurl.urlexport.event = function() {
  $('.button-save', '#generate-url-from').on('click', window.easyurl.urlexport.saveButton);
};

/**
 * UrlExport Save Button action
 *
 * @memberof EasyURL_UrlExport
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @returns {void}
 */
window.easyurl.urlexport.saveButton = function(e) {
  e.preventDefault();
  window.easyurl.urlexport.hideNotice();

  let nbUrls = $('#generate-url-from').serializeArray().find((e) => e.name == 'nb_url')

  $.ajax({
    method: 'POST',
    url: '',
    data: $('#generate-url-from').serialize(),
    success: (data) => {
      let date = new Date().toLocaleString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: false }).replace(',', '');
      $('<tr class="oddeven">' +
        '<td class="tab-ref">' + data.ref + '</td>' +
        '<td class="tab-count">-</td>' +
        '<td class="tab-first">-</td>' +
        '<td class="tab-end">-</td>' +
        '<td class="tab-date">' + data.date + '</td>' +
        '<td class="tab-url">-</td>' +
        '<td class="tab-uses">0</td>' +
        '<td class="tab-download"><div class="wpeo-loader"><span class="loader-spin"></span></div></td>' +
        '</tr>').insertAfter($('tr:nth-child(1)', '.tab-export'));
      $('.button-save', '#generate-url-from').addClass('wpeo-loader');
      window.easyurl.urlexport.createUrls(data.data, 0, parseInt(nbUrls.value));
    },
    error: function (r, s, e) {
      window.easyurl.urlexport.displayNotice(r.responseJSON.title, r.responseJSON.message, 'error');
      $('.button-save', '#generate-url-from').removeClass('wpeo-loader');
    }
  })
}

/**
 * UrlExport Create shortlink url and export
 *
 * @memberof EasyURL_UrlExport
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @returns {void}
 */
window.easyurl.urlexport.createUrls = function (id, number, total) {
  if (number != total) {
    $.ajax({
      method: 'POST',
      url: '',
      data: $('#generate-url-from').serialize() + '&export_url_id='+id,
      success: (data) => {
        $('tr:nth-child(2)', '.tab-export').find('.tab-count').html(number + 1);
        if (number == 0) {
          $('tr:nth-child(2)', '.tab-export').find('.tab-first').html(data.data);
          $('tr:nth-child(2)', '.tab-export').find('.tab-url').html('<span class="fas fa-external-link-alt paddingrightonly" style=""></span><span>' + data.url + '</span>');
        }
        $('tr:nth-child(2)', '.tab-export').find('.tab-end').html(data.data);
        window.easyurl.urlexport.createUrls(id, number + 1, total)
      },
      error: function (r, s, e) {
        window.easyurl.urlexport.displayNotice(r.responseJSON.title, r.responseJSON.message, 'error');
        $('tr:nth-child(2)', '.tab-export').remove();
        $('.button-save', '#generate-url-from').removeClass('wpeo-loader');
      }
    })
  } else {
    $.ajax({
      method: 'POST',
      url: '',
      data: $('#generate-url-from').serialize() + '&export_url_id='+id + "&export_file=1",
      success: (e) => {
        window.easyurl.urlexport.displayNotice(e.title, e.message, 'success');
        $('tr:nth-child(2)', '.tab-export').find('.tab-download').html(e.download);
        $('.button-save', '#generate-url-from').removeClass('wpeo-loader');
        window.open(e.redirect, '_blank').focus();
      },
      error: function (r, s, e) {
        window.easyurl.urlexport.displayNotice(r.responseJSON.title, r.responseJSON.message, 'error');
        $('tr:nth-child(2)', '.tab-export').remove();
        $('.button-save', '#generate-url-from').removeClass('wpeo-loader');
      }
    })
  }
}

/**
 * UrlExport Display notice
 *
 * @memberof EasyURL_UrlExport
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @returns {void}
 */
window.easyurl.urlexport.displayNotice = function (title, message, type) {
  let notice = $('.global-infos');

  notice.removeClass('notice-error');
  notice.removeClass('notice-info');
  notice.removeClass('notice-success');
  notice.removeClass('hidden');
  notice.addClass('notice-' + type);

  $('span', notice).remove();

  notice.find('.notice-title').append('<span>' + title + '</span>');
  notice.find('.notice-content').append('<span>' + message + '</span>');

  notice.show();
}

/**
 * UrlExport Hide notice
 *
 * @memberof EasyURL_UrlExport
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @returns {void}
 */
window.easyurl.urlexport.hideNotice = function () {
  $('.global-infos').hide();
}
