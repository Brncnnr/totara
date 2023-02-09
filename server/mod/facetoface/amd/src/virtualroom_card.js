/*
 * This file is part of Totara LMS
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
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 * @package mod_facetoface
 */

define([], function() {
    /**
     * Internal class to copy the URL
     * @constructor
     * @param {HTMLElement} element
     */
    function SeminarResourceCard(element) {
        // Initialise base class.
        this.element = element;
        this.copyBox = element.querySelector('.mod_facetoface__resource-card__copy-container');
        this.copyLink = element.querySelector('.mod_facetoface__resource-card__copy-link');
    }

    SeminarResourceCard.prototype = {
        /** @type {HTMLElement} The parent element */
        element: null,

        /** @type {InputElement} A text box containing the URL to copy */
        copyBox: null,

        /**
         * Sets up the copy event
         */
        setupEvents: function() {
            var that = this;

            this.copyLink.addEventListener('click', function(e) {
                /** @type {Selection} */
                var selection = window.getSelection();

                /** @type {Range} */
                var range;
                e.preventDefault();

                if (selection.rangeCount) {
                    range = selection.getRangeAt(0);
                }
                that.copyBox.select();
                document.execCommand('copy');

                // Revert selected text
                if (range) {
                    window.getSelection().removeAllRanges();
                    window.getSelection().addRange(range);
                }
            });
        }
    };

    /**
     * Initialise our widget.
     * @param {HTMLElement} element
     * @return {Promise} resolved once initialisation is complete
     */
    function init(element) {
        return new Promise(function(resolve) {
            var controller = new SeminarResourceCard(element);
            controller.setupEvents();
            resolve(controller);
        });
    }

    return {
        init: init
    };
});
