/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Michael Dunstan <michael.dunstan@androgogic.com>
 * @package totara_contentmarketplace
 */

/**
 *
 * @module     totara_contentmarketplace/disable
 * @class      disable
 * @package    totara_contentmarketplace
 */

define(['jquery', 'core/modal_factory', 'core/modal_events', 'core/str'], function($, ModalFactory, ModalEvents, Str) {

    var disable = {};

    disable.init = function() {
        $('.tcm-disable, .tcm-enable').each(function() {
            var context = $(this);
            var marketplace = context.data('marketplace');
            var action = context.data('action') ? context.data('action') : 'disable';
            var component = 'contentmarketplace_' + marketplace;
            /*
             * Strings used:
             * warningdisablemarketplace:title, warningdisablemarketplace:body:html, enable
             * warningenablemarketplace:title, warningenablemarketplace:body:html, disable
             */
            var requiredStrings = [
                {key: 'warning' + action + 'marketplace:title', component: component},
                {key: 'warning' + action + 'marketplace:body:html', component: component},
                {key: action, component: 'totara_contentmarketplace'},
                {key: 'cancel'}
            ];
            Str.get_strings(requiredStrings).done(function(strings) {
                ModalFactory.create({
                        type: ModalFactory.types.CONFIRM,
                        title: strings[0],
                        body: strings[1]
                    },
                    context,
                    {
                        yesstr: strings[2],
                        nostr: strings[3]
                    }
                ).done(function(modal) {
                    var root = modal.getRoot();
                    root.on(ModalEvents.yes, function() {
                        window.location = context.attr('href');
                    });
                });
            });
        });
    };

    return disable;
});
