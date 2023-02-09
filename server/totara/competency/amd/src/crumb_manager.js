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
 * @package totara_competency
 * @subpackage crumbtrail_manager
 */

 /* The crumb manager, handles common actions for crumb hierarchy view.
  * It also manages events propagated from the crumb_with_title AMD including changing hierarchy level.
  */
define(['core/str', 'core/templates'], function(str, templates) {
    /**
     * Class constructor for the CrumbManager.
     *
     * @class
     * @constructor
     */
    function CrumbManager() {
        if (!(this instanceof CrumbManager)) {
            return new CrumbManager();
        }

        this.args = {
            id: null,
            include: {
                crumbs: 1
            }
        };
        this.eventListener = 'totara_core/crumbtrail';
        this.eventNode = '';
        this.heading = '';
        this.strings = {
            levelTop: '',
            top: '',
        };
        this.template = 'totara_competency/crumb_with_title_body';
        this.webservice = null;
        this.widget = null;
        this.stringList = [];
    }

    // Listen for propagated events
    CrumbManager.prototype.bubbledEventsListener = function() {
        var that = this;

        this.eventNode.addEventListener(this.eventListener + ':click', function(e) {
            that.onCrumbtrailChange(e.detail);
        });
    };

    /**
     * Clear the crumbtrail content
     *
     */
    CrumbManager.prototype.clearCrumbtrail = function() {
        if (this.widget) {
            this.widget.innerHTML = '';
        }
    };

    /**
     * Correctly format crumb data
     *
     * @param {JSON} data
     * @return {JSON}
     */
    CrumbManager.prototype.formatCrumbData = function(data) {
        return new Promise(function(resolve) {
            resolve(data);
        });
    };

    /**
     * Return webservice string
     *
     * @return {string} webservice
     */
    CrumbManager.prototype.getWebservice = function() {
        return this.webservice;
    };

    /* Clear basket selection */
    CrumbManager.prototype.onCrumbtrailChange = function(data) {
        if (data.isTopLevel) {
            this.onCrumbtrailChangeToTopLevel(data);
        } else {
            this.onCrumbtrailChangeLevel(data);
        }
     };

     /* on crumbtrail changed, do any additional changes */
    CrumbManager.prototype.onCrumbtrailChanged = function() { /* Null */ };

    /* Change crumb list level based on clicked crumbtrail item */
    CrumbManager.prototype.onCrumbtrailChangeLevel = function() { /* Null */ };

    /* Change crumb to top level */
    CrumbManager.prototype.onCrumbtrailChangeToTopLevel = function() { /* Null */ };

    /**
     * Prepare required crumb strings
     *
     * @param {array} stringList
     * @return {promise}
     */
    CrumbManager.prototype.loadCrumbStrings = function() {
        var that = this;

        return new Promise(function(resolve) {
            str.get_strings(that.stringList).then(function(fetchedStrings) {
                that.strings.top = fetchedStrings[0];
                that.strings.levelTop = fetchedStrings[1];
                that.setHeadingToTop();
                resolve();
            });
        });
    };

    /**
     * Render the crumbtrail with the provided data
     *
     * @param {Object} data needs the crumbtrail property
     * @return {Promise}
     */
    CrumbManager.prototype.renderCrumbtrail = function(data) {
        var that = this;
        return new Promise(function(resolve) {
            if (!that.widget || !data.crumbtrail) {
                that.clearCrumbtrail();
                resolve();
                return;
            }

            that.formatCrumbData(data).then(function(crumbData) {
                templates.renderReplace(that.template, crumbData, that.widget).then(function() {
                    resolve();
                });
            });
        });
    };

    /**
     * Render the list level header with the provided data
     *
     * @param {Object} data needs the fullname property
     * @return {Promise}
     */
    CrumbManager.prototype.renderLevelHeader = function(data) {
        var heading = data.fullname,
            that = this;

        return new Promise(function(resolve) {
            if (heading) {
                that.setHeading(heading);
            }
            resolve();
        });
    };

    /**
     * Set crumb heading node
     *
     * @param {string} heading
     */
    CrumbManager.prototype.setHeading = function(heading) {
        if (this.heading) {
            this.heading.innerHTML = heading;
        }
    };

    /**
     * Set crumb heading to top string
     *
     */
    CrumbManager.prototype.setHeadingToTop = function() {
        if (this.heading) {
            this.heading.innerHTML = this.strings.top;
        }
    };

    /**
     * Set crumb heading to top string for level
     *
     */
    CrumbManager.prototype.setHeadingToLevelTop = function() {
        if (this.heading) {
            this.heading.innerHTML = this.strings.levelTop;
        }
    };


    /**
     * Set expand id for list request
     *
     * @param {string} id
     */
    CrumbManager.prototype.setParentId = function(id) {
        this.args.id = id;
    };

    /**
     * Set parent node
     *
     * @param {node} parent
     */
    CrumbManager.prototype.setParent = function(parent) {
        this.widget = parent;
    };

    /**
     * Set node for event listening
     *
     * @param {node} parent
     */
    CrumbManager.prototype.setParentEventNode = function(parent) {
        this.eventNode = parent;
    };

    /**
     * Set heading node
     *
     * @param {node} node
     */
    CrumbManager.prototype.setHeadingNode = function(node) {
        this.heading = node;
    };

    /**
     * Set webservice for crumbtrail data
     *
     * @param {node} webservice
     */
    CrumbManager.prototype.setWebservice = function(webservice) {
        this.webservice = webservice;
    };

    /**
     * Toggle class on crumbtrail
     *
     * @param {string} toggleClass
     */
    CrumbManager.prototype.toggleClass = function(toggleClass) {
        this.widget.classList.toggle(toggleClass);
    };

    /**
     * Remove class on crumbtrail
     *
     * @param {string} removeClass
     */
    CrumbManager.prototype.removeClass = function(removeClass) {
        this.widget.classList.remove(removeClass);
    };

    /**
     * Toggle class on crumbtrail
     *
     * @param {string} toggleClass
     */
    CrumbManager.prototype.headingToggleClass = function(toggleClass) {
        if (this.heading) {
            this.heading.classList.toggle(toggleClass);
        }
    };

    /**
     * Remove class on crumbtrail heading
     *
     * @param {string} removeClass
     */
    CrumbManager.prototype.headingRemoveClass = function(removeClass) {
        if (this.heading) {
            this.heading.classList.remove(removeClass);
        }
    };

    /**
     * Return the list expand request args to update crumbtrail and header
     *
     * @return {Object}
     */
    CrumbManager.prototype.updateCrumbAndHeader = function() {
        var that = this;
        return {
            args: that.args,
            callback: [that.renderCrumbtrail.bind(that), that.renderLevelHeader.bind(that), that.onCrumbtrailChanged],
            methodname: that.getWebservice()
        };
    };

    /**
     * initialisation method
     *
     * @param {node} parent
     * @param {string} data
     * @returns {Object} promise
     */
    var init = function(parent, data) {
        var wgt = new CrumbManager();
        var crumbNode = parent.querySelector('[data-tw-crumbtrailWithTitle]'),
            headingNode = parent.querySelector('[data-tw-crumbtrailWithTitle-heading]');

        wgt.setParent(crumbNode);
        wgt.setParentEventNode(parent);
        wgt.setHeadingNode(headingNode);
        wgt.setWebservice(data.service);
        wgt.stringList = data.stringList;
        wgt.bubbledEventsListener();
        return wgt;
    };

    return {
        init: init
    };

 });