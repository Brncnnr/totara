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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package pathway_criteria_group
 */

define(['core/str', 'core/notification', 'core/templates', 'totara_competency/loader_manager'],
function(str, notification, templates, Loader) {

    /**
     * Class constructor for the PwCriteriaGroup.
     *
     * @class
     * @constructor
     */
    function PwCriteriaGroup() {
        if (!(this instanceof PwCriteriaGroup)) {
            return new PwCriteriaGroup();
        }

        this.widget = '';

        /**
         * Pathway data.
         * This object should only contain the data to be sent on the save api endpoint.
         */
        this.pathway = {
            id: 0,
            type: 'criteria_group',
            scalevalue: 0,
            sortorder: 0,
            criteria: [],
        };

        // Key to use in achievementPath events
        this.pwKey = '';

        // Unique criteria key
        this.lastCriterionKey = 0;

        // Criteria is indexed by the criteria key.
        // The values received via the bubbled events are added in a 'detail' attribute
        // which is packed into the pathway.criteria array as that is the only information
        // we must send when saving the criteria
        this.criteria = {};
        this.criteriaLength = 0;
        this.markedForDeletionCriteria = {};

        this.typeModal = null;

        this.endpoints = {
            create: 'pathway_criteria_group_create',
            update: 'pathway_criteria_group_update',
        };

        this.filename = 'criteria_group.js';
        this.busyAddCriterion = '';
        this.loader = null;
    }

    PwCriteriaGroup.prototype = {

        /**
         * Add event listeners for PwCriteriaGroups
         *
         */
        events: function() {
            var that = this,
                action;

            this.widget.addEventListener('click', function(e) {
                if (!e.target) {
                    return false;
                }

                if (e.target.closest('[data-cg-action]')) {
                    action = e.target.closest('[data-cg-action]').getAttribute('data-cg-action');

                    if (action === 'addcriterion') {
                        that.showCriteriaTypeOptions();
                    }
                } else if (e.target.closest('[data-tw-editScaleValuePaths-dropDown-level="criteria_group"]')) {
                    var selectedOption = e.target.closest('[data-tw-editScaleValuePaths-dropDown-level="criteria_group"]');

                    that.hideCriteriaTypeSelectors();
                    that.addCriterion(that.pwKey, selectedOption);
                } else if (e.target.closest('[data-tw-editScaleValuePaths-criterion-action]')) {
                    var wgt = e.target.closest('[data-tw-editScaleValuePaths-criterion-action]'),
                        keyWgt = wgt.closest('[data-tw-editScaleValuePaths-criterion-key]'),
                        buttonWgt = keyWgt.querySelector('button'),
                        criterionKey;

                    action = wgt.getAttribute('data-tw-editScaleValuePaths-criterion-action');
                    if (!keyWgt) {
                        // Something went wrong - we can't determine which criterion to perform the action on
                        notification.exception({
                            fileName: that.filename,
                            message: "Can't determine target of the " + action + " criterion action",
                            name: 'No criterion action target'
                        });
                        return false;
                    }

                    that.hideCriteriaTypeSelectors();
                    criterionKey = keyWgt.getAttribute('data-tw-editScaleValuePaths-criterion-key');

                    if (action === 'toggle-detail') {
                        that.toggleCriterionDetail(criterionKey);

                        var ariaExpandedValue = buttonWgt.getAttribute('aria-expanded');
                        var toggledAriaExpandedValue = ariaExpandedValue === 'true' ? 'false' : 'true';
                        buttonWgt.setAttribute('aria-expanded', toggledAriaExpandedValue);
                    } else if (action === 'remove') {
                        that.removeCriterion(criterionKey);
                    } else if (action === 'undo') {
                        that.undoCriterionRemoval(criterionKey);
                    }
                }

                return false;
            });

            window.addEventListener('click', this.hideCriteriaTypeSelectors.bind(this));
        },

        // Listen for propagated events
        bubbledEventsListener: function() {
            var that = this,
                criteriaEvents = 'totara_criteria/criterion:',
                criterionKey,
                criterion;

            this.widget.addEventListener(criteriaEvents + 'update', function(e) {
                criterionKey = e.detail.key;
                criterion = e.detail.criterion;

                if (criterion) {
                    // Using an associative array with the string key.
                    // We store this associative array in a separate variable and ensure that the
                    // pathway contains data that will serialize as expected

                    if (!that.criteria[criterionKey]) {
                        that.criteria[criterionKey] = {
                            'id': criterion.id ? criterion.id : 0,
                            'type': criterion.type ? criterion.type : '',
                            'title': criterion.title ? criterion.title : '',
                            'singleuse': criterion.singleuse || false,
                            'expandable': criterion.expandable || false
                        };

                        // If previous marked for deletion, remove from that list
                        if (that.markedForDeletionCriteria[criterionKey]) {
                            delete that.markedForDeletionCriteria[criterionKey];
                        }

                        that.criteriaLength += 1;
                    }

                    if (criterion.singleuse) {
                        that.hideAddCriteriaDropdown();
                        that.triggerEvent('singleUseCriterion', {used: true, scalevalue: that.pathway.scalevalue});
                    }

                    // Remove attributes not needed for APIs
                    delete criterion.singleuse;
                    delete criterion.expandable;
                    that.criteria[criterionKey].detail = criterion;
                }

                // Propagate up to achievementPaths
                that.packCriteria();
                that.unsetBusyAddCriterion();
                that.triggerEvent('update', {pathway: that.pathway});
            });

            this.widget.addEventListener(criteriaEvents + 'dirty', function(e) {
                criterionKey = e.detail.key;

                if (that.criteria[criterionKey]) {
                    that.criteria[criterionKey].dirty = true;

                    // Also propagate up
                    that.triggerEvent('dirty', {});
                }
            });

        },

        /**
         * Set parent
         *
         * @param {node} parent
         */
        setParent: function(parent) {
            this.widget = parent;
        },

        /**
         * Initialise the data and display it
         *
         * @return {Promise}
         */
        initData: function() {
            var that = this,
                pwWgt = this.widget.closest('[data-tw-editAchievementPaths-pathway-key]'),
                svWgt = this.widget.closest('[data-tw-editScaleValuePaths-value]'),
                pwKey = 0,
                pwId = 0,
                pendingJsKey = 'pathwayCriteriaGroupInit_';

            return new Promise(function(resolve) {
                if (pwWgt) {
                    pwKey = pwWgt.getAttribute('data-tw-editAchievementPaths-pathway-key') ? pwWgt.getAttribute('data-tw-editAchievementPaths-pathway-key') : 0;
                    pwId = pwWgt.getAttribute('data-tw-editAchievementPaths-pathway-id') ? pwWgt.getAttribute('data-tw-editAchievementPaths-pathway-id') : 0;
                    pendingJsKey += pwKey;
                }

                M.util.js_pending(pendingJsKey);

                // Obtain the pathway detail from the dom

                that.pwKey = pwKey;
                if (svWgt) {
                    that.pathway.scalevalue = svWgt.getAttribute('data-tw-editScaleValuePaths-value') ? svWgt.getAttribute('data-tw-editScaleValuePaths-value') : 1;
                }

                if (pwId === 0) {
                    delete that.pathway.id;

                    // New pw - we need the competency id
                    // Get the competency ID from higher up in the DOM
                    var competencyIdNode = document.querySelector('[data-tw-editAchievementPaths-competency]'),
                        competencyId = 1;

                    if (competencyIdNode) {
                        competencyId = competencyIdNode.getAttribute('data-tw-editAchievementPaths-competency');
                    }

                    that.pathway.competency_id = competencyId;
                    that.widget.setAttribute('data-tw-editAchievementPaths-save-endPoint', that.endpoints.create);

                } else {
                    that.widget.setAttribute('data-tw-editAchievementPaths-save-endPoint', that.endpoints.update);
                    that.pathway.id = pwId;
                }

                // We can't add singleuse criteria with any other criteria
                that.toggleSingleUse(false);

                that.triggerEvent('update', {pathway: that.pathway});
                M.util.js_complete(pendingJsKey);
                resolve();
            });
        },

        /**
         * Return the next unique key for indexing in the criteria structure
         *
         * @return {string} Next key
         */
        getNextCriterionKey: function() {
            this.lastCriterionKey++;
            return this.pwKey + '_new_criterion_' + this.lastCriterionKey;
        },


        /**
         * Pack the pathway criteria data to ensure that it will be serialize as expected
         * when sent to the save api endpoint
         */
        packCriteria: function() {
            this.pathway.criteria = [];

            for (var criterionKey in this.criteria) {
                if (this.criteria[criterionKey].detail) {
                    this.pathway.criteria.push(this.criteria[criterionKey].detail);
                }
            }
        },

        /**
         * Hide the bottom actions
         */
        hideBottomActions: function() {
            var wgt = this.widget.closest('[data-tw-editAchievementPaths-pathway-key]').querySelector('[data-tw-editScaleValuePaths-group-add]');
            wgt.classList.add('tw-editAchievementPaths--hidden');
        },

        /**
         * Show the bottom actions
         */
        showBottomActions: function() {
            var wgt = this.widget.closest('[data-tw-editAchievementPaths-pathway-key]').querySelector('[data-tw-editScaleValuePaths-group-add]');
            wgt.classList.remove('tw-editAchievementPaths--hidden');
        },

        /**
         * Hide all criteria type selectors
         *
         * @param {MouseEvent} event a click event if this called from an event handler
         */
        hideCriteriaTypeSelectors: function(event) {
            var isCurrent = event && event.target && event.target.closest('[data-cg-action="addcriterion"]');
            if (isCurrent) {
                return;
            }
            // We also want to close the options lists in parent templates
            var criteriaTypeNodes = document.querySelectorAll('[data-tw-editScaleValuePaths-dropDown="criteria_group"]');

            for (var a = 0; a < criteriaTypeNodes.length; a++) {
                criteriaTypeNodes[a].classList.add('tw-editAchievementPaths--hidden');
            }

            // We also want to set the associated buttons to aria-expanded="false"
            var criteriaTypeNodesButtons =  document.querySelectorAll('[data-cg-action="addcriterion"]');

            for (var a = 0; a < criteriaTypeNodesButtons.length; a++) {
                criteriaTypeNodesButtons[a].setAttribute('aria-expanded', false);
            }
        },

        /**
         * Show the criteria type dropdown for the specific scalevalue
         */
        showCriteriaTypeOptions: function() {
            var toOpen = this.widget.querySelector('[data-tw-editScaleValuePaths-dropDown="criteria_group"]'),
                expanded = toOpen ? !toOpen.classList.contains('tw-editAchievementPaths--hidden') : false,
                dropDownButton = toOpen.previousElementSibling;

            this.hideCriteriaTypeSelectors();

            // Now show the correct list
            if (toOpen && !expanded) {
                toOpen.classList.remove('tw-editAchievementPaths--hidden');

                // And set the button to aria state
                if(dropDownButton) {
                    dropDownButton.setAttribute('aria-expanded', 'true');
                }
            }
        },

        /**
         * Add a new criterion
         *
         * @param {string} pwKey Key of pathway to add the new criterion to
         * @param {string} criterionOptionNode Criterion option node
         */
        addCriterion: function(pwKey, criterionOptionNode) {
            var that = this,
                target = this.widget.querySelector('[data-tw-editScaleValuePaths-group-criteria]'),
                templateName = 'pathway_criteria_group/scalevalue_group_criteria',
                criterionType = criterionOptionNode.getAttribute('data-tw-editScaleValuePaths-dropDown-item-type'),
                criterionTitle = criterionOptionNode.getAttribute('data-tw-editScaleValuePaths-dropDown-item-title'),
                criterionTemplateName = criterionOptionNode.getAttribute('data-tw-editScaleValuePaths-dropDown-item-template'),
                criterionSingleUse = criterionOptionNode.getAttribute('data-tw-editScaleValuePaths-dropDown-item-singleUse'),
                criterionKey;

            if (this.busyAddCriterion !== '') {
                return;
            }

            this.setBusyAddCriterion(criterionType);

            criterionKey = that.getNextCriterionKey();

            this.criteria[criterionKey] = {
                'key': criterionKey,
                'type': criterionType,
                'title': criterionTitle,
                'criterion_templatename': criterionTemplateName,
                'singleuse': !!+criterionSingleUse,
            };

            this.criteria[criterionKey].expandable = !this.criteria[criterionKey].singleuse;
            if (this.criteriaLength > 0) {
                this.criteria[criterionKey].showand = true;
            }

            this.criteriaLength += 1;

            // Display the criterion
            templates.renderAppend(templateName, {criteria: this.criteria[criterionKey]}, target).then(function() {
                templates.runTemplateJS('');
                // js_complete will be triggered in 'update' event listener
                that.triggerEvent('dirty', {});
            }).catch(function(e) {
                e.fileName = that.filename;
                e.name = 'Error displaying ' + criterionType;
                notification.exception(e);
                that.unsetBusyAddCriterion();
            });
        },

        /**
         * Toggle disabling / enabling of singleUse criterion types in this group
         *
         * @param {bool} allowSingleUse
         */
        toggleSingleUse: function(allowSingleUse) {
            var singleUseOptions = this.widget.querySelectorAll('[data-tw-editScaleValuePaths-dropDown-item-singleUse="1"]');

            if (singleUseOptions.length > 0) {
                if (allowSingleUse) {
                    for (var a = 0; a < singleUseOptions.length; a++) {
                        singleUseOptions[a].removeAttribute('disabled');
                    }
                } else {
                    for (var b = 0; b < singleUseOptions.length; b++) {
                        singleUseOptions[b].setAttribute('disabled', '');
                    }
                }
            }
        },

        /**
         * Hide criteria dropdown
         */
        hideAddCriteriaDropdown: function() {
            var addButton = this.widget.querySelector('.tw-editScaleValuePaths__addButton');

            if (addButton) {
                addButton.classList.add('tw-editAchievementPaths--hidden');
            }
        },

        /**
         * Toggle the display of the criterion detail
         *
         * @param  {String} criterionKey Key of criterion whose detail display should be toggled
         */
        toggleCriterionDetail: function(criterionKey) {
            var criterionTarget = this.widget.querySelector('[data-tw-editScaleValuePaths-criterion-key="' + criterionKey + '"]'),
                expandTarget = this.widget.querySelector('[data-tw-editScaleValuePaths-criterion-detail="' + criterionKey + '"]'),
                isExpanded = criterionTarget.hasAttribute('data-tw-editScaleValuePaths-criterion-detail-expanded')
                    ? criterionTarget.getAttribute('data-tw-editScaleValuePaths-criterion-detail-expanded')
                    : 0,
                expandedIcon = criterionTarget.querySelector('[data-tw-editScaleValuePaths-criterion-detail-icon="expanded"]'),
                collapsedIcon = criterionTarget.querySelector('[data-tw-editScaleValuePaths-criterion-detail-icon="collapsed"]');

            if (isExpanded == 1) {
                expandTarget.classList.add('tw-editAchievementPaths--hidden');
                expandedIcon.classList.add('tw-editAchievementPaths--hidden');
                collapsedIcon.classList.remove('tw-editAchievementPaths--hidden');
                criterionTarget.removeAttribute('data-tw-editScaleValuePaths-criterion-detail-expanded');
            } else {
                expandTarget.classList.remove('tw-editAchievementPaths--hidden');
                expandedIcon.classList.remove('tw-editAchievementPaths--hidden');
                collapsedIcon.classList.add('tw-editAchievementPaths--hidden');
                criterionTarget.setAttribute('data-tw-editScaleValuePaths-criterion-detail-expanded', "1");
            }
        },

        /**
         * Remove the criterion.
         * If it has an id (exists on the database), its title will still be shown
         * to indicate that final removal will only happen when changes are applied
         *
         * @param  {String} criterionKey Key of criterion to remove
         */
        removeCriterion: function(criterionKey) {
            var criterionTarget = this.widget.querySelector('[data-tw-editScaleValuePaths-criterion-key="' + criterionKey + '"]'),
                criterionAndTarget = this.widget.querySelector('[data-tw-editAchievementPaths-and-key="' + criterionKey + '"]'),
                activeNode = criterionTarget.querySelector('[data-tw-editScaleValuePaths-criterion-active]'),
                deletedNode = criterionTarget.querySelector('[data-tw-editScaleValuePaths-criterion-deleted]'),
                removeIconWgt = criterionTarget.querySelector('[data-tw-editScaleValuePaths-criterion-action="remove"]'),
                undoIconWgt = criterionTarget.querySelector('[data-tw-editScaleValuePaths-criterion-action="undo"]'),
                copyObj = {},
                pendingJsKey = 'removeCriterion' + criterionKey;

            M.util.js_pending(pendingJsKey);

            if (this.criteria[criterionKey]) {
                // If it is a single use criterion, bubble event up to indicate that we
                // are no longer using a single-use criterion
                if (this.criteria[criterionKey].singleuse) {
                    this.triggerEvent('singleUseCriterion', {used: false, scalevalue: this.pathway.scalevalue});
                    this.showBottomActions();
                }

                // If it has an id,
                //      move the criteria to the 'markedForDeletion' array
                //      indicate pending deletion through css
                // else
                //      simply delete the criterion

                if (this.criteria[criterionKey].id && this.criteria[criterionKey].id != 0) {
                    // Existing
                    // Replace content of the criterion div to display deleted name only

                    copyObj[criterionKey] = this.criteria[criterionKey];
                    Object.assign(this.markedForDeletionCriteria, copyObj);
                    delete this.criteria[criterionKey];
                    this.packCriteria();

                    if (activeNode) {
                        activeNode.classList.add('tw-editAchievementPaths--hidden');
                    }

                    if (deletedNode) {
                        deletedNode.classList.remove('tw-editAchievementPaths--hidden');
                    }

                    // Show undo action
                    if (removeIconWgt) {
                        removeIconWgt.classList.add('tw-editAchievementPaths--hidden');
                    }
                    if (undoIconWgt) {
                        undoIconWgt.classList.remove('tw-editAchievementPaths--hidden');
                    }

                    this.triggerEvent('update', {pathway: this.pathway});
                    this.triggerEvent('dirty', {});

                    M.util.js_complete(pendingJsKey);
                } else {
                    // Remove the whole criterion and AND divider
                    if (criterionTarget) {
                        criterionTarget.remove();
                    }

                    if (criterionAndTarget) {
                        criterionAndTarget.remove();
                    } else {
                        // If we removed the top criterion and there are more criteria,
                        // we need now to remove the new top criterion's AND
                        if (this.pathway.criteria.length > 1) {
                            criterionAndTarget = this.widget.querySelector('[data-tw-editAchievementPaths-and-key]');
                            if (criterionAndTarget) {
                                criterionAndTarget.remove();
                            }
                        }
                    }

                    delete this.criteria[criterionKey];
                    this.packCriteria();

                    if (this.pathway.criteria.length == 0) {
                        // We need to ensure pending_js waits for js code removing the pathway
                        this.triggerEvent('remove', {pendingJsKey: pendingJsKey});
                    } else {
                        this.triggerEvent('update', {pathway: this.pathway});
                        M.util.js_complete(pendingJsKey);
                    }

                    this.triggerEvent('dirty', {});
                }

                this.criteriaLength -= 1;
            } else {
                M.util.js_complete(pendingJsKey);
            }
        },

        /**
         * Undo the removal of the criterion.
         *
         * @param  {String} criterionKey Key of criterion to remove
         */
        undoCriterionRemoval: function(criterionKey) {
            if (!this.markedForDeletionCriteria[criterionKey]) {
                return;
            }

            var criterionTarget = this.widget.querySelector('[data-tw-editScaleValuePaths-criterion-key="' + criterionKey + '"]'),
                activeNode = criterionTarget.querySelector('[data-tw-editScaleValuePaths-criterion-active]'),
                deletedNode = criterionTarget.querySelector('[data-tw-editScaleValuePaths-criterion-deleted]'),
                removeIconWgt = criterionTarget.querySelector('[data-tw-editScaleValuePaths-criterion-action="remove"]'),
                undoIconWgt = criterionTarget.querySelector('[data-tw-editScaleValuePaths-criterion-action="undo"]'),
                copyObj = {},
                pendingJsKey = 'undoRemoveCriterion' + criterionKey;

            M.util.js_pending(pendingJsKey);

            // Handle the case where an existing single-use has been removed, another one added
            // and then the user tries to undo removal of the original criterion
            if (this.markedForDeletionCriteria[criterionKey].singleuse) {
                var hasSingleUse = this.hasSingleUseCriteria();

                if (hasSingleUse) {
                    notification.clearNotifications();

                    str.get_string('error_cant_undo_single_use', 'pathway_criteria_group').done(function(message) {
                        notification.addNotification({
                            message: message,
                            type: 'error'
                        });

                        // Scroll to top to make sure that the notification is visible
                        window.scrollTo(0, 0);
                    }).fail(notification.exception);

                    M.util.js_complete(pendingJsKey);
                    return false;
                }
            }

            copyObj[criterionKey] = this.markedForDeletionCriteria[criterionKey];
            Object.assign(this.criteria, copyObj);
            delete this.markedForDeletionCriteria[criterionKey];
            this.packCriteria();

            // If it is a single use criterion, bubble event up to indicate that we
            // are using a single-use criterion
            if (this.criteria[criterionKey].singleuse) {
                this.triggerEvent('singleUseCriterion', {used: true, scalevalue: this.pathway.scalevalue});
            } else {
                // Just this pw is affected
                this.toggleSingleUse(false);
            }

            if (activeNode) {
                activeNode.classList.remove('tw-editAchievementPaths--hidden');
            }

            if (deletedNode) {
                deletedNode.classList.add('tw-editAchievementPaths--hidden');
            }

            // Hide undo action
            if (removeIconWgt) {
                removeIconWgt.classList.remove('tw-editAchievementPaths--hidden');
            }
            if (undoIconWgt) {
                undoIconWgt.classList.add('tw-editAchievementPaths--hidden');
            }

            this.triggerEvent('update', {pathway: this.pathway});
            this.triggerEvent('dirty', {});

            this.criteriaLength += 1;

            M.util.js_complete(pendingJsKey);
        },

        /**
         * Determine whether we have any single use criteria
         *
         * @return {bool}
         */
        hasSingleUseCriteria: function() {
            var singleUseNode = document.querySelector('[data-tw-editAchievementPaths-criteria-singleUse]'),
                hasSingleUse = 0;

            if (singleUseNode) {
                hasSingleUse = singleUseNode.getAttribute('data-tw-editAchievementPaths-criteria-singleUse');
            }

            return !!+hasSingleUse;
        },


        /**
         * Trigger event
         *
         * @param {string} eventName
         * @param {object} data
         */
        triggerEvent: function(eventName, data) {
            data.key = this.pwKey;

            var propagateEvent = new CustomEvent('totara_competency/pathway:' + eventName, {
                bubbles: true,
                detail: data
            });

            this.widget.dispatchEvent(propagateEvent);
        },

        /**
         * set busyAddCriterion to handle slow execution of js and show loading spinner
         * @param criterionType
         */
        setBusyAddCriterion: function (criterionType) {
            this.busyAddCriterion = criterionType;
            M.util.js_pending(this.busyAddCriterion);
            this.loader.show();
        },

        /**
         * unset busyAddCriterion and hide loading spinner
         */
        unsetBusyAddCriterion: function () {
            this.loader.hide();
            M.util.js_complete(this.busyAddCriterion);
            this.busyAddCriterion = '';
        }

    };

    /**
     * Initialisation method
     *
     * @param {node} parent
     * @returns {Object} promise
     */
    var init = function(parent) {
        return new Promise(function(resolve) {
            var wgt = new PwCriteriaGroup();
            wgt.setParent(parent);
            wgt.events();
            wgt.bubbledEventsListener();
            wgt.loader = Loader.init(parent);
            resolve(wgt);

            M.util.js_pending('pathwayCriteriaGroup');
            wgt.loader.show();
            wgt.initData().then(function() {
                wgt.loader.hide();
                M.util.js_complete('pathwayCriteriaGroup');
            }).catch(function() {
                // Failed
            });
        });
    };

    return {
        init: init
    };
 });
