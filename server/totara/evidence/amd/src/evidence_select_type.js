/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_evidence
 */

define([
  'jquery',
  'core/templates',
  'core/ajax',
  'core/str',
  'core/form-autocomplete',
  'core/url',
], function($, templates, ajax, str, autocomplete, url) {
  /**
   * No results processing required
   *
   * @param {String} selector
   * @param {Array} data
   * @return {Array}
   */
  function processResults(selector, data) {
    return data;
  }

  /**
   * Fetch results based on the current query
   *
   * @param {String} selector Selector for the original select element
   * @param {String} query Current search string
   * @param {Function} success Success handler
   * @param {Function} failure Failure handler
   */
  function transport(selector, query, success, failure) {
    M.util.js_pending('totara_evidence_type_search_' + query);
    ajax
      .call([
        {
          methodname: 'totara_evidence_type_search',
          args: {
            string: query.trim(),
          },
        },
      ])[0]
      .then(function(result) {
        success(result);
        M.util.js_complete('totara_evidence_type_search_' + query);
      })
      .catch(failure);
  }

  /**
   * Display an information box about a given type
   *
   * @param {number} typeId
   */
  function displayTypeMetadata(typeId) {
    M.util.js_pending('totara_evidence_type_details_' + typeId);
    ajax
      .call([
        {
          methodname: 'totara_evidence_type_details',
          args: {
            type_id: typeId,
            user_id: document
              .querySelector('[data-user-id]')
              .getAttribute('data-user-id'),
          },
        },
      ])[0]
      .then(function(result) {
        templates
          .render('totara_evidence/_select_type_metadata', result)
          .then(function(templateHtml) {
            templates.replaceNodeContents(
              document.querySelector('[data-type-metadata]'),
              templateHtml
            );

            document
              .querySelector('[data-type-submit]')
              .setAttribute('data-type-id', typeId);
            document
              .querySelector('[data-type-infobox]')
              .classList.remove('tw-evidence__hidden');

            M.util.js_complete('totara_evidence_type_details_' + typeId);
          });
      });
  }

  /**
   * Initialise the autocomplete element
   */
  function init() {
    str
      .get_string('select_type', 'totara_evidence')
      .then(function(placeholderText) {
        autocomplete.enhance(
          '[data-type-autocomplete]',
          false,
          'totara_evidence/evidence_select_type',
          placeholderText
        );

        // autocomplete doesn't trigger native js events, only jquery ones
        $('[data-type-autocomplete]').change(function(e) {
          displayTypeMetadata(e.currentTarget.value);
        });

        document
          .querySelector('[data-type-buttons]')
          .addEventListener('click', function(e) {
            e.preventDefault();
            if (e.target.closest('[data-type-submit]')) {
              var params = {
                typeid: e.target.getAttribute('data-type-id'),
              };
              if (e.target.getAttribute('data-user-id')) {
                params.user_id = e.target.getAttribute('data-user-id');
              }
              window.location.href = url.relativeUrl(
                '/totara/evidence/create.php',
                params
              );
            } else if (e.target.closest('[data-type-cancel]')) {
              document
                .querySelector('[data-type-infobox]')
                .classList.add('tw-evidence__hidden');
              location.reload();
            }
          });
      });
  }

  return {
    init: init,
    processResults: processResults,
    transport: transport,
  };
});
