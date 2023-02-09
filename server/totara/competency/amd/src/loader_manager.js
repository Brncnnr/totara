/**
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
 * @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
 * @package loader_manager
 */

define(['core/templates', 'core/notification'], function(templates, notification) {

    /**
     * Class constructor for the LoaderManager.
     * Handles the adding/removing of the page loading overlay UI
     * @class
     * @constructor
     */
    function LoaderManager() {
        if (!(this instanceof LoaderManager)) {
            return new LoaderManager();
        }

        this.classOverlay = 'tw-loader__overlay';
        this.loadTarget = '';
        this.loadText = '[data-tw-loader-text]';
    }

    /**
     * Add loading display
     *
     */
    LoaderManager.prototype.show = function() {
        var that = this;
        if (this.loadTarget) {
            this.loadTarget.classList.add(this.classOverlay);
            templates.renderAppend('totara_competency/loader', null, this.loadTarget).then(function() {
                // If loading was removed before being fully rendered, hide it
                if (!that.loadTarget.classList.contains(that.classOverlay)) {
                    that.hide();
                }
            });
        }
    };

    /**
     * Remove loading display
     *
     */
    LoaderManager.prototype.hide = function() {
        var loadingText = this.loadTarget.querySelectorAll(this.loadText),
            node;

        // Remove all instances of loading text
        if (loadingText) {
            for (var i = 0; i < loadingText.length; i++) {
                node = loadingText[i];
                node.parentNode.removeChild(node);
            }
        }

        if (this.loadTarget) {
            this.loadTarget.classList.remove(this.classOverlay);
        }
    };

    /**
     * Set loading target node
     *
     * @param {node} node
     */
    LoaderManager.prototype.setLoadTarget = function(node) {
        this.loadTarget = node;
    };

    /**
     * initialisation method
     *
     * @param {node} parent
     * @return {Object} wgt
     */
    var init = function(parent) {

        if (!parent || !parent.parentElement || !parent.parentElement.querySelector('[data-tw-loader-target]')) {
            notification.exception({
                fileName: 'loader_manager.js',
                message: 'No loader target found',
                name: 'Error initialising loader_manager',
            });
            return false;
        }

        var wgt = new LoaderManager();
        // Go up one level for when loading target is on the provided parent
        var node = parent.parentElement.querySelector('[data-tw-loader-target]');

        wgt.setLoadTarget(node);
        return wgt;
    };

    return {
        init: init
    };
 });