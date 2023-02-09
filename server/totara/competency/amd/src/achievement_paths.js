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
 * @package totara_competency
 */

define(['core/templates', 'core/ajax', 'core/modal_factory', 'core/modal_events', 'core/notification', 'core/str', 'totara_competency/loader_manager'],
function(templates, ajax, modalFactory, modalEvents, notification, str, Loader) {

    /**
     * Class constructor for the AchievementPaths.
     *
     * @class
     * @constructor
     */
    function AchievementPaths() {
        if (!(this instanceof AchievementPaths)) {
            return new AchievementPaths();
        }
        this.widget = '';
        this.aggFunction = '';
        this.aggType = '';
        this.competencyId = '';
        this.criteriaTypes = [];
        this.dirty = false;
        this.draggedNode = '';
        this.filename = 'achievement_paths.js';
        this.lastKey = 0;
        this.loader = null;
        this.markedForDeletionPathways = [];
        this.nDeletedPaths = 0;
        this.nPaths = 0;
        this.pathways = [];
        this.singlevalShown = false;
        this.busyAddPathway = '';
        this.dropPlaceholder = document.createElement('div');
        this.dropPlaceholder.className = 'tw-editAchievementPaths__dropPlaceholder';
        this.dropPlaceholder.innerText = '\xa0'; // nbsp

        this.endpoints = {
            criteriaTypes: 'pathway_criteria_group_get_criteria_types',
            pathways: 'totara_competency_get_pathways',
            deletePathways: 'totara_competency_delete_pathways',
            setOverallAggregation: 'totara_competency_set_overall_aggregation'
        };

        this.handleDrop = this.handleDrop.bind(this);
    }

    AchievementPaths.prototype = {

        /**
         * Add event listeners for Achievement Paths
         *
         */
        events: function() {
            var that = this;

            this.widget.addEventListener('click', function(e) {
                if (!e.target || that.busyAddPathway !== '') {
                    return;
                }

                // Page buttons
                if (e.target.closest('[data-tw-editAchievementPaths-action]')) {
                    var pageAction = e.target.closest('[data-tw-editAchievementPaths-action]').getAttribute('data-tw-editAchievementPaths-action');

                    if (pageAction === 'apply') {
                        that.applyChanges();
                    } else if (pageAction === 'cancel') {
                        that.cancelChanges();
                    }

                // Add scale value path dropdown
                } else if (e.target.closest('[data-tw-editScaleValuePaths-add')) {
                    var addScaleValueTrigger = e.target.closest('[data-tw-editScaleValuePaths-add]'),
                        scaleValueNode = e.target.closest('[data-tw-editScaleValuePaths-scale-id]');

                    if (addScaleValueTrigger) {
                        that.showCriteriaTypeDropDown(scaleValueNode);
                    }

                } else if (e.target.closest('[data-tw-editScaleValuePaths-dropDown-level="scalevalue"]')) {
                    var optionNode = e.target.closest('[data-tw-editScaleValuePaths-dropDown-level="scalevalue"]'),
                        scaleValueNodeB = e.target.closest('[data-tw-editScaleValuePaths-scale-id]');

                    that.addSingleValuePath(scaleValueNodeB, optionNode);

                } else if (e.target.closest('[data-tw-editAchievementPaths-pathway-action]')) {
                    var actionC = e.target.closest('[data-tw-editAchievementPaths-pathway-action]').getAttribute('data-tw-editAchievementPaths-pathway-action'),
                        pwKey = e.target.closest('[data-tw-editAchievementPaths-pathway-key]').getAttribute('data-tw-editAchievementPaths-pathway-key');

                    if (actionC === 'remove') {
                        that.removePathway(pwKey, '');
                    } else if (actionC === 'undo') {
                        that.undoRemovePathway(pwKey);
                    }

                // Aggregation edit/drag&drop setting buttons
                } else if (e.target.closest('[data-tw-editAchievementPaths-aggregation-action]')) {
                    var triggerNode = e.target.closest('[data-tw-editAchievementPaths-aggregation-action]'),
                        actionD = triggerNode.getAttribute('data-tw-editAchievementPaths-aggregation-action');

                    // If already active, abort.
                    if (triggerNode.classList.contains('tw-editAchievementPaths__btn-active')) {
                        return;
                    }

                    // Update active button
                    that.widget.querySelector('.tw-editAchievementPaths__btn-active').classList.remove('tw-editAchievementPaths__btn-active');
                    triggerNode.classList.add('tw-editAchievementPaths__btn-active');

                    if (actionD === 'edit') {
                        that.disableOrdering();
                    } else if (actionD === 'move') {
                        that.enableOrdering();
                    }
                }
            });

            this.widget.addEventListener('change', function(e) {
                if (!e.target || that.busyAddPathway !== '') {
                    return;
                }

                if (e.target.closest('[data-tw-editAchievementPaths-add-pathway]')) {
                    var addPathwayNode = e.target.closest('[data-tw-editAchievementPaths-add-pathway]'),
                        selectedOption = addPathwayNode.querySelector('option:checked');
                    if (selectedOption.value != '0') {
                        that.addPath(selectedOption);
                        addPathwayNode.value = '0';
                        var addPathwaySrOnly = document.querySelector(
                          '#add-pathway-sr-only'
                        );
                        addPathwaySrOnly.innerText = selectedOption.innerText + ' ' + addPathwaySrOnly.dataset.ariaLiveExtraText;
                    }
                } else if (e.target.closest('[data-tw-editAchievementPaths-aggregation-change]')) {
                    that.setOverallAggregation();
                    that.dirty = true;
                    that.enableApplyChanges();
                }
            });

            // Listen for drag start event
            this.widget.addEventListener('dragstart', function(e) {
                // If a draggable item
                if (e.target.hasAttribute('data-tw-editAchievementPaths-draggable')) {
                    that.draggedNode = e.target;
                    setTimeout(function() {
                        that.dropPosition = that.getDropPosition(that.draggedNode, e.clientY);
                        if (that.dropPosition) {
                            that.insertChildAtIndex(that.dropPosition.groupNode, that.dropPosition.index, that.dropPlaceholder);
                        }
                        if (that.draggedNode === e.target) {
                            that.draggedNode.style.display = 'none';
                        }
                    }, 0);
                }
            });

            // Listen for drag end event
            this.widget.addEventListener('dragend', function(e) {
                that.handleDrop(e);
                that.draggedNode.style.display = '';
                that.draggedNode = '';
                that.dropPlaceholder.remove();
            });

            // Listen for dragged item over drop zone event
            this.widget.addEventListener('drag', function(e) {
                e.preventDefault();
            });

            this.widget.addEventListener('dragover', function(e) {
                if (!that.draggedNode) {
                    return;
                }

                that.dropPosition = that.getDropPosition(that.draggedNode, e.clientY);
                if (that.dropPosition) {
                    that.insertChildAtIndex(that.dropPosition.groupNode, that.dropPosition.index, that.dropPlaceholder);
                }
            });

            window.addEventListener('click', this.hideCriteriaTypeSelectors.bind(this));

            window.addEventListener('beforeunload', function(e) {
                if (that.dirty) {
                    e.preventDefault();
                    e.returnValue = '';
                    return '';
                }
                return '';
            });
        },

        // Listen for propagated events
        bubbledEventsListener: function() {
            var that = this,
                pwEvents = 'totara_competency/pathway:';

            this.widget.addEventListener(pwEvents + 'dirty', function(e) {
                var key = e.detail.key;

                if (that.pathways[key]) {
                    that.pathways[key].dirty = true;
                }

                that.dirty = true;
                that.enableApplyChanges();
            });

            this.widget.addEventListener(pwEvents + 'update', function(e) {
                var key = e.detail.key,
                    pw = e.detail.pathway;

                if (!that.pathways[key]) {
                    that.pathways[key] = {'id': pw.id || 0, 'type': pw.type || ''};
                    if (pw.scalevalue) {
                        that.singlevalShown = true;
                        that.toggleSingleUseCriteriaTypes(false, pw.scalevalue);
                    }
                    that.nPaths += 1;
                    that.showHideNoPaths();
                }

                // Not used in APIs
                if (pw.type) {
                    delete pw.type;
                }

                that.pathways[key].singleuse = pw.singleuse || 0;
                if (that.pathways[key].singleuse) {
                    that.toggleSingleUsePathwayTypes(false, that.pathways[key].type);
                }
                delete pw.singleuse;

                that.pathways[key].detail = pw;

                // Reset busyAddPw to allow adding of another pw
                that.unsetBusyAddPathway();
            });

            this.widget.addEventListener(pwEvents + 'remove', function(e) {
                var pwKey = e.detail.key,
                    pendingJsKey = e.detail.pendingJsKey || '';

                that.removePathway(pwKey, pendingJsKey);
            });

            this.widget.addEventListener(pwEvents + 'singleUseCriterion', function(e) {
                var isUsed = e.detail.used,
                    scaleValueId = e.detail.scalevalue;

                that.widget.setAttribute('data-tw-editAchievementPaths-criteria-singleUse', isUsed ? '1' : '0');
                that.toggleAllSingleUseCriteriaTypes(!isUsed);
                if (isUsed) {
                    that.hideAddScaleValuePaths(scaleValueId);
                } else {
                    that.showAddScaleValuePaths(scaleValueId);
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
         * Show notification
         *
         * @param {String} type success, warning, error, etc/
         * @param {String} messageStringKey used for get_string
         * @param {String} messageComponent used for get_string
         * @param {Object} messageParams used for get_string, optional
         */
        showNotification: function(type, messageStringKey, messageComponent, messageParams) {
            if (typeof messageParams === 'undefined') {
                messageParams = {};
            }
            // Clear old notifications.
            notification.clearNotifications();

            str.get_string(messageStringKey, messageComponent, messageParams).done(function(message) {
                notification.addNotification({
                    message: message,
                    type: type
                });

                // Scroll to top to make sure that the notification is visible
                window.scrollTo(0, 0);
            }).fail(notification.exception);
        },


        /**
         * Retrieve the pathways, store the data in the
         *
         * @return {Promise}
         */
        initData: function() {
            var that = this;

            return new Promise(function(resolve, reject) {
                that.competencyId = that.widget.getAttribute('data-tw-editAchievementPaths-competency');

                that.getCriteriaTypes().then(function() {
                    that.setOverallAggregation();
                    resolve();
                }).catch(function(e) {
                    e.fileName = that.filename;
                    e.name = 'Error retrieving criteria types';
                    notification.exception(e);
                    reject();
                });
            });
        },

        /**
         * Return the next unique key for indexing in the pathways structure
         *
         * @return {string} Next key
         */
        getNextKey: function() {
            this.lastKey++;
            return 'pw_new_' + this.lastKey;
        },

        /**
         * Update the detail on this page
         *
         * @return {Promise}
         */
        updatePage: function() {
            var that = this,
                templateName = 'totara_competency/achievement_paths_group',
                target = this.widget.querySelector('[data-tw-editAchievementPaths-groups]'),
                apiArgs = {
                    'args': {
                        'competency_id': that.competencyId
                    },
                    'methodname': that.endpoints.pathways
                };

            return new Promise(function(resolve, reject) {
                // Get all the pathways and its detail
                ajax.getData(apiArgs).then(function(responses) {
                    var pwGroups = responses.results;

                    that.pathways = [];
                    that.nPaths = 0;

                    templates.renderReplace(templateName, {
                        'criteria_types': that.criteriaTypes,
                        'pathway_groups': pwGroups
                    }, target).then(function() {
                        that.singlevalShown = that.hasSingleValuePaths();
                        that.showHideNoPaths();
                        resolve();
                    }).catch(function(e) {
                        e.fileName = that.filename;
                        e.name = 'Error updating pathways';
                        notification.exception(e);
                        reject();
                    });
                }).catch(function(e) {
                    e.fileName = that.filename;
                    e.name = 'Error retrieving pathways';
                    notification.exception(e);
                    reject();
                });
            });
        },

        /**
         * Add a new pathway
         * If the single value block is shown, new pathways are added below it
         *
         * @param {node} selectedOption type option
         */
        addPath: function(selectedOption) {
            var pathType = selectedOption.value,
                pathTitle = selectedOption.text,
                pathTemplate = '';

            if (this.busyAddPathway !== '') {
                return;
            }

            if (selectedOption.hasAttribute('data-tw-editAchievementPaths-path-template')) {
                pathTemplate = selectedOption.getAttribute('data-tw-editAchievementPaths-path-template');
            }

            if (pathType == '0') {
                return;
            }

            if (pathType === 'singlevalue') {
                // Just open the single value block.
                // User has to add the actual path the the appropriate scalevalue
                this.singlevalShown = true;
                this.showHideNoPaths();
                return;
            }

            this.setBusyAddPathway(pathType);

            var that = this,
                target,
                key = that.getNextKey(),
                templatename = 'totara_competency/partial_pathway',
                pw;

            pw = {
                'key': key,
                'id': 0,
                'type': pathType,
                'title': pathTitle,
                'sortorder': 0,
                'pathway_templatename': pathTemplate,
            };

            this.pathways[key] = pw;
            this.nPaths += 1;

            if (that.singlevalShown) {
                target = that.widget.querySelector('[data-tw-editAchievementPaths-group="high-sortorder"]');
            } else {
               target = that.widget.querySelector('[data-tw-editAchievementPaths-group="low-sortorder"]');
            }

            // Display the pathway in the correct div
            templates.renderAppend(templatename, this.pathways[key], target).then(function() {
                that.calculateSortOrderFromDisplay();
                that.dirty = true;
                that.showHideNoPaths();
                that.enableApplyChanges();
                templates.runTemplateJS('');
            }).catch(function(e) {
                e.fileName = that.filename;
                e.name = 'Error displaying ' + pathType;
                notification.exception(e);
            });
        },

        /**
         * Hide all criteria type selectors and set their associated buttons to the correct aria state
         *
         * @param {MouseEvent} event a click event if this called from an event handler
         */
        hideCriteriaTypeSelectors: function(event) {
            var isCurrent = event && event.target && event.target.closest('[data-tw-editscalevaluepaths-add]');
            if (isCurrent) {
                return;
            }
            var criteriaDropDowns = this.widget.querySelectorAll('[data-tw-editScaleValuePaths-dropDown="scalevalue"]');

            for (var a = 0; a < criteriaDropDowns.length; a++) {
                criteriaDropDowns[a].classList.add('tw-editAchievementPaths--hidden');

            }

            var criteriaDropDownsButtons = document.querySelectorAll('[data-tw-editscalevaluepaths-add]');

            for (var a = 0; a < criteriaDropDownsButtons.length; a++) {
                criteriaDropDownsButtons[a].setAttribute('aria-expanded', 'false');
            }
        },

        /**
         * Show the criteria type dropdown for the specific scalevalue
         *
         * @param {node} scaleValueNode
         */
        showCriteriaTypeDropDown: function(scaleValueNode) {
            var openDropDown = scaleValueNode.querySelector('[data-tw-editScaleValuePaths-dropDown="scalevalue"]'),
                alreadyExpanded = !openDropDown.classList.contains('tw-editAchievementPaths--hidden'),
                dropDownButton = scaleValueNode.querySelector('[data-tw-editscalevaluepaths-add]');

            // Hide existing open lists
            this.hideCriteriaTypeSelectors();

            // Now show the correct list
            if (openDropDown && !alreadyExpanded) {
                openDropDown.classList.remove('tw-editAchievementPaths--hidden');

                // And set the button to aria state
                if(dropDownButton) {
                    dropDownButton.setAttribute('aria-expanded', 'true');
                }
            }
        },

        /**
         * Add a new single value pathway for the specific scalevalue
         *
         * @param {node} svWgt Scale value widget to add the path to
         * @param {node} criterionOptionNode option node
         */
        addSingleValuePath: function(svWgt, criterionOptionNode) {
            // In v1 we assume the only single value pathway is criteria_group.
            // If there is ever a need for additional single value pathways, the
            // implementation must be enhanced to cater for that

            var that = this,
                key,
                pathType = 'criteria_group',
                target = svWgt,
                numPathways = parseInt(target.getAttribute('data-tw-editScaleValuePaths-pathway-list')),
                templatename = 'totara_competency/partial_pathway',
                pw,
                criterionType = criterionOptionNode.getAttribute('data-tw-editScaleValuePaths-dropDown-item-type'),
                criterionKey,
                criterion,
                scaleValueId = svWgt.getAttribute('data-tw-editScaleValuePaths-scale-id');

            if (this.busyAddPathway !== '') {
                return;
            }
            that.setBusyAddPathway('criteria_group');

            key = this.getNextKey();
            criterionKey = key + '_criterion_1';
            criterion = this.criteriaTypes.find(function(criterion) {
                return criterion.type === criterionType;
            });
            criterion.key = criterionKey;
            criterion.expandable = !criterion.singleuse;

            pw = {
                'key': key,
                'type': pathType,
                'scalevalue': scaleValueId,
                'sortorder': 0,
                'pathway_templatename': 'pathway_criteria_group/pathway_criteria_group_edit',
                'criteria_type_level': pathType,
                'criteria_types': this.criteriaTypes,
                'criteria': [criterion],
                'showor': numPathways > 0,
            };

            this.pathways[key] = pw;
            this.nPaths += 1;

            // Display the pathway in the correct div
            templates.renderAppend(templatename, pw, target).then(function() {
                target.setAttribute('data-tw-editScaleValuePaths-pathway-list', numPathways + 1);
                that.hideCriteriaTypeSelectors();
                that.toggleSingleUseCriteriaTypes(false, scaleValueId);
                that.calculateSortOrderFromDisplay();
                that.dirty = true;
                that.enableApplyChanges();
                templates.runTemplateJS('');
            }).catch(function(e) {
                e.fileName = that.filename;
                e.name = 'Error displaying ' + pathType;
                notification.exception(e);
            });
        },

        /**
         * Apply all changes
         * - Find all elements with data-tw-editAchievementPaths-save-endPoint
         * - Foreach
         *     - find the pathway type and key from the closest data-tw-editAchievementPaths-pathway-key
         *     - Call the save api endpoint
         *
         */
        applyChanges: function() {
            var that = this,
                pwList = this.widget.querySelectorAll('[data-tw-editAchievementPaths-save-endPoint]'),
                apiEndpoint,
                apiReqSortOrder,
                pwKey,
                pwSortOrderWgt,
                apiArgs,
                promiseArr = [];

            if (!this.dirty) {
                return;
            }

            M.util.js_pending('competencyAchievementPathsApplyChanges');
            this.loader.show();

            // Disable ordering so that the form elements get enabled again
            this.disableOrdering();

            // Resetting dirty early to avoid doubble submissions
            this.dirty = false;
            this.disableApplyChanges();

            // Generate a key to group all changes together for logging
            // Must convert to seconds to be compatible with PHP timestamps
            var actionTime = Math.round(Date.now() / 1000);

            for (var a = 0; a < pwList.length; a++) {
                apiEndpoint = pwList[a].getAttribute('data-tw-editAchievementPaths-save-endPoint');
                apiReqSortOrder = pwList[a].getAttribute('data-tw-editAchievementPaths-save-req-sortOrder');
                pwKey = pwList[a].closest('[data-tw-editAchievementPaths-pathway-key]').getAttribute('data-tw-editAchievementPaths-pathway-key');

                if (this.pathways[pwKey] && this.pathways[pwKey].dirty && this.pathways[pwKey].detail) {
                    apiArgs = {
                        args: this.pathways[pwKey].detail,
                        methodname: apiEndpoint
                    };

                    // Add applyKey
                    apiArgs.args.actiontime = actionTime;

                    // If the pathway require the sortorder on the save endpoint, add it to args
                    if (apiReqSortOrder) {
                        pwSortOrderWgt = pwList[a].closest('[data-tw-editAchievementPaths-pathway-sortOrder]');
                        if (pwSortOrderWgt) {
                            apiArgs.args.sortorder = pwSortOrderWgt.getAttribute('data-tw-editAchievementPaths-pathway-sortOrder');
                        }
                    }

                    promiseArr.push(ajax.getData(apiArgs));
                }
            }

            // Deleted pathways
            if (this.nDeletedPaths > 0) {
                var toDelete = [];
                for (pwKey in this.markedForDeletionPathways) {
                    toDelete.push({type: this.markedForDeletionPathways[pwKey].type, id: this.markedForDeletionPathways[pwKey].id});
                }

                apiArgs = {
                    args: {
                        'competency_id': this.competencyId,
                        pathways: toDelete,
                        actiontime: actionTime
                    },
                    methodname: this.endpoints.deletePathways
                };
                promiseArr.push(ajax.getData(apiArgs));
            }

            // Overall aggregation
            apiArgs = {
                args: {
                    'competency_id': this.competencyId,
                    type: this.aggType,
                    actiontime: actionTime
                },
                methodname: this.endpoints.setOverallAggregation
            };
            promiseArr.push(ajax.getData(apiArgs));

            if (promiseArr.length > 0) {
                Promise.all(promiseArr).then(function() {
                    that.updatePage().then(function() {
                        that.showNotification('success', 'apply_success', 'totara_competency', {});
                        that.loader.hide();
                        M.util.js_complete('competencyAchievementPathsApplyChanges');
                    }).catch(function(e) {
                        e.fileName = that.filename;
                        e.name = 'Error updating the page';
                        notification.exception(e);
                        that.loader.hide();
                        M.util.js_complete('competencyAchievementPathsApplyChanges');
                    });
                }).catch(function(e) {
                    // If an error occurred while applying - reset dirty flag to allow retry
                    that.dirty = true;
                    that.enableApplyChanges();

                    e.fileName = that.filename;
                    e.name = 'Error applying changes';
                    notification.exception(e);
                    that.loader.hide();
                    M.util.js_complete('competencyAchievementPathsApplyChanges');
                });
            }
        },

        /**
         * Show / hide sections depending on the presence of paths
         */
        showHideNoPaths: function() {
            var aggregationNode = this.widget.querySelector('[data-tw-editAchievementPaths-aggregation]'),
                noPathsNode = this.widget.querySelector('[data-tw-editAchievementPaths-empty]'),
                singlevalDiv = this.widget.querySelector('[data-tw-editAchievementPaths-group="singlevalue"]'),
                singleValueOption = this.widget.querySelector('[data-tw-editAchievementPaths-add-pathway] option[value="singlevalue"]');

            if (this.nPaths == 0 && !this.singlevalShown) {
                if (aggregationNode) {
                    aggregationNode.classList.add('tw-editAchievementPaths--hidden');
                }
                if (noPathsNode) {
                    noPathsNode.classList.remove('tw-editAchievementPaths--hidden');
                }
            } else {
                if (aggregationNode) {
                    aggregationNode.classList.remove('tw-editAchievementPaths--hidden');
                }
                if (noPathsNode) {
                    noPathsNode.classList.add('tw-editAchievementPaths--hidden');
                }
            }

            if (this.singlevalShown) {
                if (singlevalDiv) {
                    singlevalDiv.classList.remove('tw-editAchievementPaths--hidden');
                }
                if (singleValueOption) {
                    singleValueOption.disabled = true;
                }
            } else {
                if (singlevalDiv) {
                    singlevalDiv.classList.add('tw-editAchievementPaths--hidden');
                }
                if (singleValueOption) {
                    singleValueOption.disabled = false;
                }
            }
        },

        /**
         * Calculate and set the sort orders from the order the pathways appear on the screen
         */
        calculateSortOrderFromDisplay: function() {
            var pwNodes = this.widget.querySelectorAll('[data-tw-editAchievementPaths-pathway-key]'),
                lastOrder = 0,
                pwKey,
                pw;

            for (var a = 0; a < pwNodes.length; a++) {
                pwKey = pwNodes[a].getAttribute('data-tw-editAchievementPaths-pathway-key');
                if (this.pathways[pwKey]) {
                    pw = this.pathways[pwKey];
                } else if (this.markedForDeletionPathways[pwKey]) {
                    pw = this.markedForDeletionPathways[pwKey];
                }

                if (pw.sortorder != lastOrder + 1) {
                    if (this.pathways[pwKey]) {
                        this.pathways[pwKey].dirty = true;
                    } else if (this.markedForDeletionPathways[pwKey]) {
                        this.markedForDeletionPathways[pwKey].dirty = true;
                    }
                    // If re-calculating the sortorder is the only change, no user changes so not setting overall dirty flag
                    // When a user makes changes, that change will result in the overall dirty flag being set
                }

                pw.sortorder = ++lastOrder;

                pwNodes[a].setAttribute('data-tw-editAchievementPaths-pathway-sortOrder', pw.sortorder);
            }
        },

        /**
         * Discard changes and navigate to the applicable url
         * Confirmation is handled through the 'beforeunload' event handler
         */
        cancelChanges: function() {
            var backUrl = this.widget.getAttribute('data-tw-editAchievementPaths-back-url');
            if (backUrl) {
                window.location.href = backUrl;
            }
        },

        /**
         * Remove the specific pathway
         * If it has an id (exists on the database), show summary detail
         * to indicate that final removal will only happen when changes are applied
         *
         * @param {number} pwKey
         * @param {string} pendingJsKey
         */
        removePathway: function(pwKey, pendingJsKey) {
            var pwTarget = this.widget.querySelector('[data-tw-editAchievementPaths-pathway-key="' + pwKey + '"]');

            notification.clearNotifications();

            if (this.pathways[pwKey]) {
                this.dirty = true;
                this.enableApplyChanges();

                if (this.pathways[pwKey].singleuse) {
                    this.toggleSingleUsePathwayTypes(true, this.pathways[pwKey].type);
                }

                if (this.pathways[pwKey].id && this.pathways[pwKey].id != 0) {
                    // Exists on db - show indication that path will be deleted when changes are applied
                    // Show the pw summary detail which have no actions

                    var copyObj = {};

                    copyObj[pwKey] = this.pathways[pwKey];
                    Object.assign(this.markedForDeletionPathways, copyObj);
                    this.nDeletedPaths += 1;
                    delete this.pathways[pwKey];

                    var deleteNodes = pwTarget.querySelectorAll('[data-pw-on-delete]'),
                        deleteAction;
                    for (var a = 0; a < deleteNodes.length; a++) {
                        deleteAction = deleteNodes[a].getAttribute('data-pw-on-delete');
                        if (deleteAction == 'mark') {
                            deleteNodes[a].classList.add('tw-editAchievementPaths--deleted');
                        } else if (deleteAction == 'hide') {
                            deleteNodes[a].classList.add('tw-editAchievementPaths--hidden');
                        }
                    }

                    // Show undo action
                    var removeWgt = pwTarget.querySelector('[data-tw-editAchievementPaths-pathway-action="remove"]'),
                        undoWgt = pwTarget.querySelector('[data-tw-editAchievementPaths-pathway-action="undo"]');
                    removeWgt.classList.add('tw-editAchievementPaths--hidden');
                    undoWgt.classList.remove('tw-editAchievementPaths--hidden');
                } else {
                    // Remove it totally
                    if (this.pathways[pwKey].scalevalue) {
                        // For single value pathways, we also need to remove the OR where applicable.
                        var svNode = this.widget.querySelector('[data-tw-editScaleValuePaths-scale-id="' + this.pathways[pwKey].scalevalue + '"]'),
                            pwOrNode = this.widget.querySelector('[data-pw-or="' + pwKey + '"]'),
                            pwListNode = svNode,
                            numPathways = parseInt(pwListNode.getAttribute('data-tw-editScaleValuePaths-pathway-list'));

                        if (pwOrNode) {
                            pwOrNode.remove();
                        } else {
                            // Top pathway. We need to find the first OR and remove it if it exists
                            var pwOrNodes = svNode.querySelectorAll('[data-pw-or]');
                            if (pwOrNodes.length > 0) {
                                pwOrNodes[0].remove();
                            }
                        }

                        numPathways -= 1;
                        pwListNode.setAttribute('data-tw-editScaleValuePaths-pathway-list', numPathways);

                        if (numPathways == 0) {
                            var hasSingleUseCriteria = this.hasSingleUseCriteria();
                            if (!hasSingleUseCriteria) {
                                this.toggleSingleUseCriteriaTypes(true, this.pathways[pwKey].scalevalue);
                            }
                        }
                    }

                    delete this.pathways[pwKey];
                    this.nPaths -= 1;
                    this.showHideNoPaths();

                    if (pwTarget) {
                        pwTarget.remove();
                    }
                }
            }

            if (pendingJsKey !== '') {
                M.util.js_complete(pendingJsKey);
            }
        },

        /**
         * Undo the removal of a specific pathway
         *
         * @param {number} pwKey
         */
        undoRemovePathway: function(pwKey) {
            var pwTarget = this.widget.querySelector('[data-tw-editAchievementPaths-pathway-key="' + pwKey + '"]'),
                copyObj = {};

            if (!this.markedForDeletionPathways[pwKey]) {
                return;
            }

            notification.clearNotifications();

            // Handle the case where an existing single-use pathway has been removed, another one added
            // and then the user tries to undo removal of the original pathway
            if (this.markedForDeletionPathways[pwKey].singleuse) {
                var allPathwaysOfThisType = this.widget.querySelectorAll('[data-tw-editachievementpaths-pathway-type="' + this.markedForDeletionPathways[pwKey].type + '"]');
                if (allPathwaysOfThisType.length > 1) {
                    str.get_string('error_cant_undo_single_use', 'totara_competency').done(function(message) {
                        notification.addNotification({
                            message: message,
                            type: 'error'
                        });

                        // Scroll to top to make sure that the notification is visible
                        window.scrollTo(0, 0);
                    }).fail(notification.exception);

                    return false;
                } else {
                    this.toggleSingleUsePathwayTypes(false, this.markedForDeletionPathways[pwKey].type);
                }
            }

            copyObj[pwKey] = this.markedForDeletionPathways[pwKey];
            Object.assign(this.pathways, copyObj);
            delete this.markedForDeletionPathways[pwKey];
            this.nDeletedPaths -= 1;

            var deleteNodes = pwTarget.querySelectorAll('[data-pw-on-delete]'),
                deleteAction;
            for (var a = 0; a < deleteNodes.length; a++) {
                deleteAction = deleteNodes[a].getAttribute('data-pw-on-delete');
                if (deleteAction == 'mark') {
                    deleteNodes[a].classList.remove('tw-editAchievementPaths--deleted');
                } else if (deleteAction == 'hide') {
                    deleteNodes[a].classList.remove('tw-editAchievementPaths--hidden');
                }
            }

            // Show undo action
            var removeWgt = pwTarget.querySelector('[data-tw-editAchievementPaths-pathway-action="remove"]'),
                undoWgt = pwTarget.querySelector('[data-tw-editAchievementPaths-pathway-action="undo"]');
            removeWgt.classList.remove('tw-editAchievementPaths--hidden');
            undoWgt.classList.add('tw-editAchievementPaths--hidden');
        },

        /**
         * Retrieve the criteria types if not yet done
         *
         * @return {Promise}
         */
        getCriteriaTypes: function() {
            var that = this,
                hasSingleUseCriteria = this.hasSingleUseCriteria();

            return new Promise(function(resolve, reject) {
                if (that.criteriaTypes && that.criteriaTypes.length > 0) {
                    resolve();
                } else {
                    var apiArgs = {
                        args: {},
                        methodname: that.endpoints.criteriaTypes
                    };

                    ajax.getData(apiArgs).then(function(responses) {
                        var criteriaTypes = responses.results;

                        that.criteriaTypes = [];
                        for (var a = 0; a < criteriaTypes.length; a++) {
                            criteriaTypes[a].disabled = !!+criteriaTypes[a].singleuse && hasSingleUseCriteria;
                            that.criteriaTypes.push(criteriaTypes[a]);
                        }

                        resolve();
                    }).catch(function(e) {
                        e.fileName = that.filename;
                        e.name = 'Error getting criteria types';
                        notification.exception(e);
                        reject();
                    });
                }
            });
        },

        /**
         * Toggle availability of single use pathway types
         *
         * @param {bool} allowSingleUse
         * @param {String} pwType the pathway type
         */
        toggleSingleUsePathwayTypes: function(allowSingleUse, pwType) {
            var pathwayTypeDropDownNode = this.widget.querySelector('[data-tw-editAchievementPaths-add-pathway]'),
                singleUseOptions;

            if (pathwayTypeDropDownNode) {
                singleUseOptions = pathwayTypeDropDownNode.querySelectorAll('[data-tw-editachievementpaths-path-singleUse="1"]');
            }

            if (singleUseOptions) {
                for (var b = 0; b < singleUseOptions.length; b++) {
                    if (singleUseOptions[b].getAttribute('data-tw-editachievementpaths-path-type') == pwType) {
                        if (allowSingleUse) {
                            singleUseOptions[b].removeAttribute('disabled');
                        } else {
                            singleUseOptions[b].setAttribute('disabled', '');
                        }
                    }
                }
            }
        },


        /**
         * Toggle availability of single use criteria types on the scalevalue level
         *
         * @param {bool} allowSingleUse
         */
        toggleAllSingleUseCriteriaTypes: function(allowSingleUse) {
            var criteriaDropDownNodes = this.widget.querySelectorAll('[data-tw-editScaleValuePaths-dropDown="scalevalue"]'),
                scaleValueNode,
                scaleValueId,
                singleUseOptions,
                nPathways;

            // Update all criteria type drop downs on the top level only
            for (var a = 0; a < criteriaDropDownNodes.length; a++) {
                scaleValueNode = criteriaDropDownNodes[a].closest('[data-tw-editScaleValuePaths-scale-id]');
                scaleValueId = null;
                nPathways = 0;
                singleUseOptions = criteriaDropDownNodes[a].querySelectorAll('[data-tw-editScaleValuePaths-dropDown-item-singleUse="1"]');

                if (scaleValueNode) {
                    scaleValueId = scaleValueNode.getAttribute('data-tw-editScaleValuePaths-scale-id');
                    nPathways = parseInt(scaleValueNode.getAttribute('data-tw-editscalevaluepaths-pathway-list') || '0');
                }

                if (!scaleValueId || !singleUseOptions.length || nPathways > 0) {
                    continue;
                }

                if (allowSingleUse) {
                    for (var b = 0; b < singleUseOptions.length; b++) {
                        singleUseOptions[b].removeAttribute('disabled');
                    }
                } else {
                    for (var c = 0; c < singleUseOptions.length; c++) {
                        singleUseOptions[c].setAttribute('disabled', '');
                    }
                }
            }
        },

        /**
         * Toggle availability of single use criteria types on a specific scalevalue level
         *
         * @param {bool} allowSingleUse
         * @param {int} scalevalueId
         */
        toggleSingleUseCriteriaTypes: function(allowSingleUse, scalevalueId) {
            var scaleValueNode = this.widget.querySelector('[data-tw-editScaleValuePaths-scale-id="' + scalevalueId + '"]'),
                criteriaDropDownNode,
                singleUseOptions;

            if (scaleValueNode) {
                criteriaDropDownNode = scaleValueNode.querySelector('[data-tw-editScaleValuePaths-dropDown="scalevalue"]');
            }

            if (!criteriaDropDownNode) {
                return;
            }

            singleUseOptions = criteriaDropDownNode.querySelectorAll('[data-tw-editScaleValuePaths-dropDown-item-singleUse="1"]');
            if (singleUseOptions) {
                if (allowSingleUse) {
                    for (var b = 0; b < singleUseOptions.length; b++) {
                        singleUseOptions[b].removeAttribute('disabled');
                    }

                } else {
                    for (var c = 0; c < singleUseOptions.length; c++) {
                        singleUseOptions[c].setAttribute('disabled', '');
                    }
                }
            }
        },

        /**
         * Hide Add paths button for a specific scalevalue
         *
         * @param {int} scaleValueId
         */
        hideAddScaleValuePaths: function(scaleValueId) {
            var scaleValueNode = this.widget.querySelector('[data-tw-editScaleValuePaths-scale-id="' + scaleValueId + '"]'),
                addButton;

            if (scaleValueNode) {
                addButton = scaleValueNode.querySelector('[data-tw-editScaleValuePaths-add]');
            }

            if (addButton) {
                addButton.classList.add('tw-editAchievementPaths--hidden');
            }
        },

        /**
         * Show Add paths button for a specific scalevalue
         *
         * @param {int} scaleValueId
         */
        showAddScaleValuePaths: function(scaleValueId) {
            var scaleValueNode = this.widget.querySelector('[data-tw-editScaleValuePaths-scale-id="' + scaleValueId + '"]'),
                addButton;

            if (scaleValueNode) {
                addButton = scaleValueNode.querySelector('[data-tw-editScaleValuePaths-add]');
            }

            if (addButton) {
                addButton.classList.remove('tw-editAchievementPaths--hidden');
            }
        },

        /**
         *
         */
        setOverallAggregation: function() {
            var aggWgt = this.widget.querySelector('[data-tw-editAchievementPaths-aggregation-change]'),
                selectedType = aggWgt.querySelector('option:checked'),
                aggregationActions = this.widget.querySelector('[data-tw-editAchievementPaths-aggregation-actions]');

            this.aggType = selectedType.value;

            if (selectedType.hasAttribute('data-tw-editAchievementPaths-aggregation-function')) {
                this.aggFunction = selectedType.getAttribute('data-tw-editAchievementPaths-aggregation-function');

                if (aggregationActions) {
                    aggregationActions.classList.remove('tw-editAchievementPaths--hidden');
                }
            } else {
                this.aggFunction = '';
                if (aggregationActions) {
                    aggregationActions.classList.add('tw-editAchievementPaths--hidden');
                }
            }
        },

        /**
         * Enable pathway ordering through drag and drop
         *
         * Action items that should not be available during ordering should have
         * the data attribute data-tw-editAchievementPaths-on-ordering with either a value 'disable', 'hide' or 'show'
         *
         * Draggable items should have the data attribute data-tw-editAchievementPaths-draggable
         */
        enableOrdering: function() {
            var orderableItems = this.widget.querySelectorAll('[data-tw-editAchievementPaths-draggable]'),
                onOrderingItems = this.widget.querySelectorAll('[data-tw-editAchievementPaths-on-ordering]'),
                action;

            for (var a = 0; a < orderableItems.length; a++) {
                orderableItems[a].classList.add('tw-editAchievementPaths__activeDropZone');
                orderableItems[a].draggable = true;
            }

            for (var b = 0; b < onOrderingItems.length; b++) {
                action = onOrderingItems[b].getAttribute('data-tw-editAchievementPaths-on-ordering');

                if (action == 'hide') {
                    onOrderingItems[b].classList.add('tw-editAchievementPaths--hidden');
                } else if (action == 'show') {
                    onOrderingItems[b].classList.remove('tw-editAchievementPaths--hidden');
                } else if (action == 'disable') {
                    onOrderingItems[b].disabled = true;
                }
            }
        },

        /**
         * Disable ordering of the pathways
         *   - Reverse the 'show/hide/disable' actions
         *   - Revert draggable
         *   - Remove draggable styles
         *
         * Calculate new pathway sort orders
         *
         */
        disableOrdering: function() {
            // This function also gets called from applyChanges, make sure we update the edit toggle to be correct.
            this.widget.querySelector('.tw-editAchievementPaths__btn-active')
                .classList.remove('tw-editAchievementPaths__btn-active');
            this.widget.querySelector('.tw-editAchievementPaths__btn[data-tw-editachievementpaths-aggregation-action=edit]')
                .classList.add('tw-editAchievementPaths__btn-active');

            var orderableItems = this.widget.querySelectorAll('[data-tw-editAchievementPaths-draggable]'),
                onOrderingItems = this.widget.querySelectorAll('[data-tw-editAchievementPaths-on-ordering]'),
                action;

            for (var a = 0; a < orderableItems.length; a++) {
                orderableItems[a].classList.remove('tw-editAchievementPaths__activeDropZone');
                orderableItems[a].draggable = false;
            }

            for (var b = 0; b < onOrderingItems.length; b++) {
                action = onOrderingItems[b].getAttribute('data-tw-editAchievementPaths-on-ordering');

                if (action == 'hide') {
                    onOrderingItems[b].classList.remove('tw-editAchievementPaths--hidden');
                } else if (action == 'show') {
                    onOrderingItems[b].classList.add('tw-editAchievementPaths--hidden');
                } else if (action == 'disable') {
                    onOrderingItems[b].disabled = false;
                }
            }

            if (this.aggFunction) {
                if (typeof this[this.aggFunction] === 'function') {
                    this[this.aggFunction]();
                }
            }
        },

        /**
         * Determine whether we have any single value paths
         *
         * @return {bool}
         */
        hasSingleValuePaths: function() {
            if (this.widget.querySelectorAll('.tw-editScaleValuePathsGroup').length) {
                return true;
            }

            return false;
        },

        /**
         * Determine whether we have any single use criteria
         *
         * @return {bool}
         */
        hasSingleUseCriteria: function() {
            var hasSingleUse = this.widget.getAttribute('[data-tw-editAchievementPaths-criteria-singleUse]') || 0;
            return !!+hasSingleUse;
        },

        /**
         * Enable the Apply changes button
         */
        enableApplyChanges: function() {
            var pendingBulkCopyId = this.widget.getAttribute('data-tw-editAchievementPaths-pendingBulkCopyId') || 0;
            if (pendingBulkCopyId != 0) {
                return;
            }

            var applyChangesButton = this.widget.querySelector('[data-tw-editAchievementPaths-action="apply"]');
            if (applyChangesButton && this.dirty) {
                applyChangesButton.removeAttribute('disabled');
            }
        },

        /**
         * Disable the Apply changes button
         */
        disableApplyChanges: function() {
            var applyChangesButton = this.widget.querySelector('[data-tw-editAchievementPaths-action="apply"]');
            if (applyChangesButton) {
                applyChangesButton.setAttribute('disabled', '');
            }
        },

        /**
         * Get position to drop the dragging node at.
         *
         * @param {Element} node dragging node
         * @param {Number} mouseY
         * @returns {?{groupNode: Element, index: Number}}
         */
        getDropPosition: function(node, mouseY) {
            if (!mouseY) {
                return null;
            }

            var highOrderGroup = this.widget.querySelector('[data-tw-editAchievementPaths-group="high-sortorder"]'),
                lowOrderGroup = this.widget.querySelector('[data-tw-editAchievementPaths-group="low-sortorder"]');

            var allDraggables = Array.prototype.slice.call(this.widget.querySelectorAll('.tw-editAchievementPaths__activeDropZone'));
            // default to start of first group
            var result = {groupNode: lowOrderGroup, index: 0};
            // find new position from mouse
            allDraggables.forEach(function(draggable) {
                var bcr = draggable.getBoundingClientRect();
                if (bcr.height === 0) {
                    return; // not visible. continue
                }
                var centerpoint = bcr.top + bcr.height / 2;
                if (mouseY > centerpoint) {
                    var container = draggable.closest('[data-tw-editAchievementPaths-group]');
                    var containerGroup = container.getAttribute('data-tw-editAchievementPaths-group');
                    if (containerGroup === 'singlevalue') {
                        result = {groupNode: highOrderGroup, index: 0};
                        return; // continue
                    }
                    // get index
                    var parentChildren = Array.prototype.slice.call(container.children);
                    var draggableIndex = parentChildren.indexOf(draggable);
                    result = {
                        groupNode: container,
                        index: draggableIndex + 1
                    };
                }
            });
            return result;
        },

        /**
         * Insert an element at the specified index in parent
         *
         * @param {Element} parent
         * @param {Number} index
         * @param {Element} child
        */
        insertChildAtIndex: function(parent, index, child) {
            if (!parent.children[index]) {
                parent.appendChild(child);
            } else {
                parent.insertBefore(child, parent.children[index]);
            }
        },

        /**
         * Listen for dragged item dropped event
         *
         * @param {DragEvent} e
         */
        handleDrop: function(e) {
            var that = this;
            e.preventDefault();
            e.stopPropagation();

            var dropPosition = this.draggedNode && (this.dropPosition || this.getDropPosition(this.draggedNode, e.clientY));

            if (this.draggedNode && dropPosition) {
                var dropTargetGroup = dropPosition.groupNode.getAttribute('data-tw-editAchievementPaths-group');
                var draggedGroup = this.draggedNode.closest('[data-tw-editAchievementPaths-group]').getAttribute('data-tw-editAchievementPaths-group');
                var highOrderGroup = this.widget.querySelector('[data-tw-editAchievementPaths-group="high-sortorder"]');
                var lowOrderGroup = this.widget.querySelector('[data-tw-editAchievementPaths-group="low-sortorder"]');

                this.draggedNode.style.display = '';

                // If single value group moved reorder other nodes
                if (draggedGroup === 'singlevalue') {
                    var groupNodes = Array.prototype.slice.call(dropPosition.groupNode.children);

                    // Move all items below the drop to the high group
                    if (dropTargetGroup === 'low-sortorder') {
                        groupNodes.slice(dropPosition.index).forEach(function(node, index) {
                            that.insertChildAtIndex(highOrderGroup, index, node);
                        });
                    } else if (dropTargetGroup === 'high-sortorder') {
                        groupNodes.slice(0, dropPosition.index).forEach(function(node) {
                            lowOrderGroup.appendChild(node);
                        });
                    }
                } else {
                    this.insertChildAtIndex(dropPosition.groupNode, dropPosition.index, this.draggedNode);
                }

                this.dirty = true;
                this.enableApplyChanges();
            }

            // must remove dropPlaceholder after (not before) or it it will throw off indexes
            if (this.dropPlaceholder.parentNode) {
                this.dropPlaceholder.remove();
            }
        },

        /**
         * set busyAddPathway to handle slow execution of js and show loading spinner
         * @param pathType
         */
        setBusyAddPathway: function (pathType) {
            this.busyAddPathway = pathType;
            M.util.js_pending(this.busyAddPathway);
            this.loader.show();
        },

        /**
         * unset busyAddPathway and hide loading spinner
         */
        unsetBusyAddPathway: function () {
            if (this.busyAddPathway !== '') {
                this.loader.hide();
                M.util.js_complete(this.busyAddPathway);
                this.busyAddPathway = '';
            }
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
            var wgt = new AchievementPaths();
            wgt.setParent(parent);
            wgt.events();
            wgt.bubbledEventsListener();
            wgt.loader = Loader.init(parent);
            resolve(wgt);

            M.util.js_pending('competencyAchievementPaths');
            wgt.loader.show();
            wgt.initData().then(function() {
                wgt.loader.hide();
                M.util.js_complete('competencyAchievementPaths');
            }).catch(function() {
                // Failed
            });
        });
    };

    return {
        init: init,
    };
});
