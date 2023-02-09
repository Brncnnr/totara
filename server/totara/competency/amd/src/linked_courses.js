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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package totara_competency
 */

define(['core/str', 'core/templates', 'totara_competency/modal_list', 'core/ajax', 'core/notification', 'totara_competency/loader_manager'],
function (str, templates, ModalList, ajax, notification, Loader) {

    /**
     * Class constructor for managing LinkedCourses.
     *
     * @class
     * @constructor
     */
    function LinkedCourses() {
        if (!(this instanceof LinkedCourses)) {
            return new LinkedCourses();
        }

        this.competencyID = 0; // The id of the competency these courses are linked to.
        this.courseAdderModal = {}; // The Modal that will show for the user to select new linked courses to be added to the list.
        this.courses = []; // Array of courses that are or will be linked to this competency
        this.initialCourses = []; // Initial courses state for change detection
        this.existingCourses = []; // Any existing linked courses (different delete UI)
        this.iconList = [];
        this.loader = null; // Loading overlay manager
        this.removedClass = 'tw-editLinkedCourses__list_removed';
        this.removedCourses = []; // Any existing courses to be removed
        this.strings = {};
    }

    LinkedCourses.prototype = {
        /**
         * Add event listeners
         *
         */
        events: function () {
            var that = this;

            this.widget.addEventListener('click', function (e) {
                if (!e.target) {
                    return;
                }

                // Save changes btn clicked
                if (e.target.closest('[data-tw-editLinkedCourses-save]')) {
                    e.preventDefault();
                    that.saveChanges();

                // course adder btn clicked
                } else if (e.target.closest('[data-tw-editLinkedCourses-adder]')) {
                    e.preventDefault();
                    // pass the currently selected course ids to the modal to make the disabled by default
                    that.courseAdderModal.show(that.getCourseIds());

                // link type checkbox clicked
                } else if (e.target.closest('[data-linked-courses-checkbox]')) {
                    var checkbox = e.target.closest('[data-linked-courses-checkbox]'),
                        courseID = checkbox.closest('[data-tw-list-row]').getAttribute('data-tw-list-row'),
                        course = that.getCourseById(courseID);
                    course.mandatory = checkbox.checked;

                // Cancel page btn clicked`
                } else if (e.target.closest('[data-tw-editLinkedCourses-cancel]')) {
                    e.preventDefault();
                    var btn = e.target.closest('[data-tw-editLinkedCourses-cancel]'),
                        url = btn.getAttribute('data-tw-editLinkedCourses-cancel');

                    if (url) {
                        // This will trigger the beforeunload handler.
                        window.location.href = url;
                    }
                }
            });

            this.widget.addEventListener('totara_core/lists:action', function (e) {
                var actionKey = e.detail.key,
                    id = parseInt(e.detail.val);

                if (document.querySelector('#user-notifications')) {
                    notification.clearNotifications();
                }

                switch (actionKey) {
                    case 'deleteClicked':
                        // Check if previously saved course
                        if (that.existingCourses.indexOf(id) > -1) {
                            that.removeSavedRow(id);
                        } else {
                            that.removeUnsavedRow(id);
                            that.loader.show();
                            that.refreshRowsDisplay();
                        }
                        break;
                    case 'undoDeleteClicked':
                        that.undoRemoveSavedRow(id);
                }
            });

            window.addEventListener('beforeunload', function(e) {
                var modified = that.haveLinkedCoursesChanged();
                var str = M.util.get_string('unsaved_changes_warning', 'totara_competency');

                if (modified) {
                    e.returnValue = str; // For IE and Firefox (before version 4)
                    return str; // For Safari
                }
            });
        },

        /**
         * Return data for consturcting course row
         *
         * @param {Object} course
         * @return {Object}
         */
        getRowData: function (course) {
            var actionBtn,
                ariaLabel,
                customClass;

            // Row is to be deleted on save
            if (this.removedCourses.indexOf(course.id) > -1) {
                actionBtn = {
                    event_key: 'undoDeleteClicked',
                    icon: this.iconList.undo
                };
                ariaLabel = this.strings.removedLinkedCourse;
                customClass = this.removedClass;

            } else {
                actionBtn = {
                    event_key: 'deleteClicked',
                    icon: this.iconList.delete
                };
            }

            var row = {
                actions: [actionBtn],
                actions_width: 'xxsm',
                aria_label: ariaLabel || course.fullname,
                columns: [
                    {
                        value: course.fullname
                    },
                    {
                        column_template: 'totara_competency/edit_linkedcourse_rows_checkbox',
                        label: this.strings.mandatory,
                        width: 'xsm'
                    }
                ],
                id: course.id,
                mandatory: course.mandatory,
                row_custom_class: customClass,
            };
            return row;
        },

        /**
         * Return course data
         *
         * @param {int} id for course
         * @return {Object|null}
         */
        getCourseById: function (id) {
            for (var i = 0; i < this.courses.length; i++) {
                if (this.courses[i].id == id) {
                    return this.courses[i];
                }
            }
            return null;
        },

        /**
         * Get ids of linked courses
         * @return {Array}
         */
        getCourseIds: function () {
            if (this.courses.length === 0) {
                return [];
            }
            return this.courses.map(function (course) {
                return course.id;
            });
        },

        /**
         * Get existing linked courses
         * @return {Promise}
         */
        getLinkedCourses: function () {
            var that = this;
            var webserviceRequestObject = {
                args: {'competency_id': this.competencyID},
                methodname: 'totara_competency_get_linked_courses'
            };
            that.existingCourses = [];

            return new Promise(function (resolve) {
                ajax.getData(webserviceRequestObject).then(function (data) {
                    that.courses = data.results.items;
                    that.resetInitialCourses();

                    // Crate an array of pre-exising linked course ids
                    for (var s = 0; s < data.results.items.length; s++) {
                        var id = parseInt(data.results.items[s].id);
                        that.existingCourses.push(id);
                    }
                    resolve();
                });
            });
        },

        /**
         * Get data for creating course modal list
         * @return {Object}
         */
        getModalListData: function () {
            var that = this;

            return {
                key: 'totara_competency_linked_courses',
                title: [{
                    component: 'totara_competency',
                    key: 'select_courses'
                }],
                list: {
                    map: {
                        cols: [{
                            dataPath: 'fullname',
                            headerString: {
                                key: 'fullname',
                                component: 'totara_competency'
                            }
                        }]
                    },
                    service: 'totara_competency_get_courses',
                },
                onSaved: function (modal, items, data) {
                    var coursesToAdd = [];

                    if (document.querySelector('#user-notifications')) {
                        notification.clearNotifications();
                    }

                    for (var i = 0; i < data.length; i++) {
                        var isMandatory = 1; // Defaulting to mandatory.
                        var existingCourse = that.getCourseById(data[i].id);
                        if (existingCourse !== null) {
                            isMandatory = existingCourse.mandatory;
                        }
                        coursesToAdd.push({
                            id: data[i].id,
                            fullname: data[i].fullname,
                            mandatory: isMandatory
                        });
                    }
                    that.courses = coursesToAdd;
                    that.loader.show();
                    that.refreshRowsDisplay();
                },
                primaryDropDown: {
                    filterKey: 'category',
                    serviceLabelKey: 'fullname',
                    placeholderString: [{
                        component: 'totara_competency',
                        key: 'all_categories',
                    }],
                    service: 'totara_competency_get_categories',
                    serviceArgs: {}
                },
                primarySearch: {
                    filterKey: 'name',
                    placeholderString: [{
                        component:  'totara_competency',
                        key: 'search_courses'
                    }]
                }
            };
        },

        /**
         * Create course modal list added
         * @return {Promise}
         */
        loadCourseModalList: function () {
            var that = this;
            return new Promise(function (resolve) {
                ModalList.adder(that.getModalListData()).then(function (modal) {
                    that.courseAdderModal = modal;
                    resolve();
                }).catch(function (e) {
                    notification.exception({message: 'Error adding course modal list adder' + e[0] + ' ' + e[1]});
                });
            });
        },

        /**
         * Render required icons
         * @return {Promise}
         */
        loadIcons: function () {

            var promises = [];
            var that = this;

            var iconData = [{
                classes: 'tw-list__hover_warning',
                key: 'delete',
                name: 'remove',
                string: this.strings.removeLinkedCourse
            },
            {
                classes: '',
                key: 'undo',
                name: 'undo',
                string: this.strings.undoRemoveLinkedCourse
            }];

            return new Promise(function (resolve) {
                for (var i = 0; i < iconData.length; i++) {
                    var data = iconData[i];
                    promises.push(templates.renderIcon(data.name, data.string, data.classes));
                }

                // Only if all icons are loaded then continue
                Promise.all(promises).then(function (s) {
                    for (var i = 0; i < iconData.length; i++) {
                        that.iconList[iconData[i].key] = s[i];
                    }
                    resolve();
                });
            });
        },

        /**
         * Render required strings
         * @return {Promise}
         */
        loadStrings: function () {
            var that = this;
            var stringData = [
                {
                    component: 'totara_competency',
                    key: 'linked_courses_saved',
                },
                {
                    component: 'totara_competency',
                    key: 'mandatory',
                },
                {
                    component: 'core',
                    key: 'courses',
                },
                {
                    component: 'totara_competency',
                    key: 'remove_linked_course',
                },
                {
                    component: 'totara_competency',
                    key: 'removed_linked_course',
                },
                {
                    component: 'totara_competency',
                    key: 'undo_remove_linked_course',
                },
                {
                    component: 'totara_competency',
                    key: 'no_courses_linked_yet',
                },
                {
                    component: 'totara_competency',
                    key: 'unsaved_changes_warning',
                },
            ];

            return new Promise(function (resolve, reject) {
                str.get_strings(stringData).then(function (stringList) {
                    that.strings = {
                        savedMsg: stringList[0],
                        mandatory: stringList[1],
                        courses: stringList[2],
                        removeLinkedCourse: stringList[3],
                        removedLinkedCourse: stringList[4],
                        undoRemoveLinkedCourse: stringList[5],
                        noResults: stringList[6],
                    };
                }).then(function () {
                    resolve();
                }).catch(function () {
                    reject();
                });
            });
        },

        refreshRowsDisplay: function () {
            var that = this,
                listNode = this.widget.querySelector('[data-tw-list]');

            var templateData = {
                has_actions: true,
                noResultsText: this.strings.noResults,
                rows: [],
                row_header: {
                    columns: [{
                        value: this.strings.courses
                    },
                    {
                        value: this.strings.mandatory,
                        width: 'xsm'
                    }],
                    header: true
                },
            };

            for (var i = 0; i < this.courses.length; i++) {
                templateData.rows.push(this.getRowData(this.courses[i]));
            }

            templates.renderReplace('totara_competency/lists_rows', templateData, listNode).then(function () {
                that.loader.hide();
            });
        },

        /**
         * Remove saved row
         *
         * @param {int} id
         */
        removeSavedRow: function (id) {
            this.removedCourses.push(id);
            this.loader.show();
            this.refreshRowsDisplay();
        },

        /**
         * Remove unsaved row
         *
         * @param {int} id
         */
        removeUnsavedRow: function (id) {
            var courseID;

            for (var i = 0; i < this.courses.length; i++) {
                courseID = this.courses[i].id;
                if (courseID === id) {
                    this.courses.splice(i, 1);
                    break;
                }
            }
        },

        /**
         * Save changes
         *
         */
        saveChanges: function () {
            var that = this,
                i;

            that.loader.show();

            if (document.querySelector('#user-notifications')) {
                notification.clearNotifications();
            }

            // Remove any pre-existing courses that have been deleted
            for (i = 0; i < that.removedCourses.length; i++) {
                var removedId = that.removedCourses[i];
                that.removeUnsavedRow(removedId);
            }
            that.removedCourses = [];

            var toSend = [];
            for (i = 0; i < that.courses.length; i++) {
                toSend[i] = {
                    id: that.courses[i].id,
                    mandatory: that.courses[i].mandatory
                };
            }

            ajax.getData({
                args: {
                    'competency_id': that.competencyID,
                    courses: toSend
                },
                methodname: 'totara_competency_set_linked_courses',
            }).then(function () {
                that.getLinkedCourses().then(function () {
                    that.refreshRowsDisplay();
                    that.loader.hide();
                    notification.addNotification({
                        message: that.strings.savedMsg,
                        type: 'success'
                    });
                });
            });
        },

        /**
         * Set competency
         */
        setCompetency: function () {
            var comp = this.widget.closest('[data-tw-editLinkedCourses-comp-id]');
            if (comp) {
                this.competencyID = comp.getAttribute('data-tw-editLinkedCourses-comp-id');
            }
        },

        /**
         * Set parent
         *
         * @param {node} parent
         */
        setParent: function (parent) {
            this.widget = parent;
        },

        /**
         * undo remove saved row
         *
         * @param {int} id
         */
        undoRemoveSavedRow: function (id) {
            var index = this.removedCourses.indexOf(id);
            if (index > -1) {
                this.removedCourses.splice(index, 1);
            }

            this.loader.show();
            this.refreshRowsDisplay();
        },

        /**
         * Have the linked courses changed
         *
         * @return {boolean}
         */
        haveLinkedCoursesChanged: function () {
            if (this.courses.length !== this.initialCourses.length) {
                return true;
            }

            var initialCourses = this.initialCourses;
            return this.courses.some(function (course, index) {
                return course.id !== initialCourses[index].id ||
                    course.mandatory !== initialCourses[index].mandatory;
            });
        },

        /**
         * Create a copy of courses for state change detection.
         */
        resetInitialCourses: function () {
            this.initialCourses = this.courses.map(function (item) {
                return {
                    id: item.id,
                    mandatory: item.mandatory
                };
            });
        }
    };

    /**
     * Initialisation method
     *
     * @param {node} parent
     * @returns {Object} promise
     */
    var init = function (parent) {
        return new Promise(function (resolve) {
            var wgt = new LinkedCourses();
            wgt.setParent(parent);
            wgt.setCompetency();
            wgt.events();
            wgt.loader = Loader.init(parent);
            wgt.loader.show();
            resolve(wgt);

            M.util.js_pending('linkedCourses');
            Promise.all([wgt.loadStrings(), wgt.getLinkedCourses(), wgt.loadCourseModalList()]).then(function () {
                wgt.loadIcons().then(function () {
                    wgt.refreshRowsDisplay();
                    M.util.js_complete('linkedCourses');
                });
            });
        });
    };

    return {
        init: init,
    };
});
