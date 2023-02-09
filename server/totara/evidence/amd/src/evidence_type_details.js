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

define(['core/modal_factory', 'core/modal_events', 'core/ajax'], function(ModalFactory, ModalEvents, ajax) {

    var modalLoading = false;

    return {
        /**
         * Creates a popup modal showing the name and description for an evidence type.
         *
         * @param {HTMLElement} link
         */
        init: function(link) {
            link.addEventListener('click', function(e) {
                if (!modalLoading) {
                    e.preventDefault();

                    var typeId = link.getAttribute('data-evidence-type-details');
                    var userId = link.getAttribute('data-evidence-user-id');
                    modalLoading = true;
                    M.util.js_pending('totara_evidence_type_show_details_' + typeId);

                    ajax.call([{
                        methodname: 'totara_evidence_type_details',
                        args: {
                            type_id: typeId,
                            user_id: userId
                        }
                    }])[0].done(function(result) {
                        return ModalFactory.create({
                            title: result.name,
                            body: result.description,
                            type: ModalFactory.types.CANCEL
                        }).then(function(modal) {
                            modal.show();
                            modalLoading = false;
                            M.util.js_complete('totara_evidence_type_show_details_' + typeId);
                        });
                    });
                }
            });
        }
    };
});