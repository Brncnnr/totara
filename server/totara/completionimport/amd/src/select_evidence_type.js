/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_completionimport
 */

define(['core/ajax'], function(ajax) {
  /**
   * Display an information box about a given type
   *
   * @param {number} typeId
   */
  function displayTypeMetadata(typeId) {
    var divNode = document.querySelector(
      '.completionimport_evidencetype_customfields'
    );
    if (!divNode) {
      return;
    }
    if (!typeId || typeId == 0) {
      divNode.classList.add('hidden');
      return;
    }

    M.util.js_pending('totara_coursecompletion_evidence_type_fields_' + typeId);
    ajax
      .call([
        {
          methodname: 'totara_evidence_type_import_fields',
          args: {
            type_id: typeId,
          },
        },
      ])[0]
      .then(function(result) {
        if (!result.length) {
          divNode.classList.add('hidden');
          return;
        }

        var fieldList = result.join(',');

        var elementNodes = divNode.querySelectorAll('.felement.fstatic');
        if (elementNodes.length < 2) {
          return;
        }

        elementNodes[1].innerHTML = fieldList;
        divNode.classList.remove('hidden');

        M.util.js_complete(
          'totara_coursecompletion_evidence_type_fields_' + typeId
        );
      });
  }

  /**
   * Initialise the autocomplete element
   */
  function init() {
    var typeNode = document.getElementById('id_evidencetype');
    if (!typeNode) {
      return;
    }

    typeNode.addEventListener('change', function(e) {
      displayTypeMetadata(e.currentTarget.value);
    });

    var initValue = typeNode.value;
    displayTypeMetadata(initValue);
  }

  return {
    init: init,
  };
});
