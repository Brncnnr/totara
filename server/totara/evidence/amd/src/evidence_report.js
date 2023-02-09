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

define(['core/modal_factory', 'core/modal_events', 'core/ajax', 'core/str', 'core/notification', 'core/templates'],
function(ModalFactory, ModalEvents, ajax, str, notification, templates) {

    var modalLoading = false;

    /**
     * Creates a modal popup window for deleting the selected evidence item or type
     *
     * @param {HTMLElement} row Table row
     * @param {String} itemOrType What to do
     */
    function showDeleteModal(row, itemOrType) {
        modalLoading = true;
        var id = row.getAttribute('data-evidence-' + itemOrType + '-actions');
        ajax.call([{
            methodname: itemOrType === 'type' ? 'totara_evidence_type_data' : 'totara_evidence_item_info',
            args: {
                id: id
            }
        }])[0].then(function(result) {
            var bodyParam = itemOrType === 'type' ? result.fields.length : null;
            str.get_strings([
                {key: 'confirm_delete_' + itemOrType, component: 'totara_evidence', param: result.display_name},
                {key: 'confirm_delete_' + itemOrType + '_body', component: 'totara_evidence', param: bodyParam},
            ]).then(function(strings) {
                return ModalFactory.create({
                    title: strings[0],
                    body: strings[1],
                    type: ModalFactory.types.CONFIRM
                }).then(function(modal) {
                    modal.show();
                    modalLoading = false;
                    modal.getRoot().on(ModalEvents.yes, function() {
                        deleteRow(result, itemOrType);
                    });
                });
            });
        }).catch(function() {
            str.get_string('error_notification_generic', 'totara_evidence').then(function(string) {
                notification.clearNotifications();
                notification.addNotification({
                    message: string,
                    type: 'error'
                });
            });
        });
    }

    /**
     * Delete the evidence item or type row and reload the report
     *
     * @param {Object} data Evidence data
     * @param {String} method What to do
     */
    function deleteRow(data, method) {
        M.util.js_pending('totara_evidence_delete_' + data.id);
        ajax.call([{
            methodname: 'totara_evidence_' + method + '_delete',
            args: {
                id: data.id
            }
        }])[0].then(function() {
            M.util.js_complete('totara_evidence_delete_' + data.id);
            window.location.reload(true);
        }).catch(function() {
            str.get_string(
                'error_notification_delete_' + method,
                'totara_evidence',
                data.display_name
            ).then(function(string) {
                notification.addNotification({
                    message: string,
                    type: 'error'
                });
                M.util.js_complete('totara_evidence_' + method + '_delete_' + data.id);
            });
        });
    }

    /**
     * Hide or show a type
     *
     * @param {HTMLElement} row Table row
     * @param {boolean} visible Should it be visible
     */
    function setTypeVisibility(row, visible) {
        var id = row.getAttribute('data-evidence-type-actions');
        M.util.js_pending('totara_evidence_type_set_visibility_' + id);
        ajax.call([{
            methodname: 'totara_evidence_type_set_visibility',
            args: {
                id: id,
                visible: visible
            }
        }])[0].then(function() {
            ajax.call([{
                methodname: 'totara_evidence_type_data',
                args: {
                    id: id
                }
            }])[0].then(function(type) {
                Promise.all([
                    str.get_string('notification_type_' + (visible ? 'visible' : 'hidden'), 'totara_evidence', type.display_name),
                    templates.render('totara_evidence/type_list_actions', type)
                ]).then(function(values) {
                    templates.replaceNodeContents(row.parentElement, values[1]);
                    notification.clearNotifications();
                    notification.addNotification({
                        message: values[0],
                        type: 'success'
                    });
                    M.util.js_complete('totara_evidence_type_set_visibility_' + id);
                });
            });
        }).catch(function() {
            str.get_string('error_notification_generic', 'totara_evidence').then(function(string) {
                notification.clearNotifications();
                notification.addNotification({
                    message: string,
                    type: 'error'
                });
            });
        });
    }

    /**
     * Creates event listeners for the action icons
     */
    function eventListeners() {
        document.querySelector('#region-main').addEventListener('click', function(e) {
            if (modalLoading) {
                return;
            }
            if (e.target.closest('[data-evidence-item-delete]')) {
                e.preventDefault();
                showDeleteModal(e.target.closest('[data-evidence-item-actions]'), 'item');
            } else if (e.target.closest('[data-evidence-type-delete]')) {
                e.preventDefault();
                showDeleteModal(e.target.closest('[data-evidence-type-actions]'), 'type');
            } else if (e.target.closest('[data-evidence-type-hide]')) {
                e.preventDefault();
                setTypeVisibility(e.target.closest('[data-evidence-type-actions]'), false);
            } else if (e.target.closest('[data-evidence-type-show]')) {
                e.preventDefault();
                setTypeVisibility(e.target.closest('[data-evidence-type-actions]'), true);
            }
        });
    }

    return {
        init: eventListeners
    };
});