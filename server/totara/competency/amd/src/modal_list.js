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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
 * @package totara_core
 */

 /* Modal list AMD, Exposes a library for displaying a list within a modal.
  * The modal list has three modes for different use cases (adder, selector & viewer).
  * The list content is populated by Ajax driven data and can be filtered, paginated
  * and in certain modes, selected.
  */
define(['core/str', 'core/templates', 'core/modal_factory', 'core/ajax'],
function(str, TemplatesManager, ModalFactory, ajax) {

    /**
    * Class constructor for the ModalList
    * @class
    * @constructor
    */
    function ModalList() {
        if (!(this instanceof ModalList)) {
            return new ModalList();
        }
        this.externalBasket = null;
    }

    /**
    * Returns key
    * @returns {String} key
    */
    ModalList.prototype.getKey = function() {
        return this.settings.key;
    };

    /**
    * Function for extending initializer
    *
    */
    ModalList.prototype.initExtend = function() {
        this.setExtendingFunctions();
        this.propagatedModalEvents();
    };

    /**
    * Event listeners for when modal is saved/closed
    */
    ModalList.prototype.propagatedModalEvents = function() {
        var modalRoot = this.modal.getRoot(),
            that = this;

        modalRoot.on('modal-save-cancel:save', function() {
            // Load selected items from basket
            // Load data for selected items
            // trigger onSaved callback

            var basketPromise = new Promise(function(resolve) {
                if (that.settings.mode === 'adder') {
                    that.basketManager.load().then(function(values) {
                        if (values.length === 0) {
                            resolve(that.list.disabledItems);
                        } else if (that.externalBasket) {
                            that.externalBasket.add(values).then(function(values) {
                                resolve(values);
                            });
                        } else {
                            resolve(values.concat(that.list.disabledItems));
                        }
                    });
                } else if (that.settings.mode === 'selector') {
                    if (that.externalBasket) {
                        that.externalBasket.copyFrom(that.basketManager.getBasket()).then(function(values) {
                            resolve(values);
                        });
                    } else {
                        that.basketManager.load().then(function(values) {
                            resolve(values);
                        });
                    }
                } else {
                    resolve([]);
                }
            });

            // basketPromise returns all selected items
            // including previously selected items
            basketPromise.then(function(selectedItems) {
                // get basket item data
                var requestParams = {
                    args: that.list.getRequestArgs(),
                    methodname: that.list.getWebservice(),
                };
                requestParams.args.filters = {'ids': selectedItems};
                requestParams.args.page = 0;

                ajax.getData(requestParams).then(function(items) {
                    var selectionData = items.results.items;

                    that.reset();

                    // Trigger saved callback
                    if (that.settings.onSaved) {
                        that.settings.onSaved(that, selectedItems, selectionData);
                    }
                });
            });
        });

        // On modal close
        modalRoot.on('modal:hidden', function() {
            // Close basket view
            if (that.basketManager && that.basketManager.widget.classList.contains('tw-selectionBasket__displayed')) {
                that.basketManager.onBasketHide();
                that.basketManager.onBasketHidden();
            }
            if (that.basketManager) {
                that.basketManager.delete();
            }

            that.reset();
            // Trigger closed callback
            if (that.settings.onClosed) {
                that.settings.onClosed();
            }
        });
     };

     /**
     *  Extend base functions
     */
     ModalList.prototype.setExtendingFunctions = function() {
         var that = this,
             events = that.settings.events;

         // If we don't have custom events, skip
         if (!events) {
             return;
         }

         // Loop through event object
         Object.keys(events).forEach(function(groupKey) {
             // If the key matches an existing reference
             if (that[groupKey]) {
                 // Loop through each function in group
                 for (var key in that.settings.events[groupKey]) {
                     if (events[groupKey].hasOwnProperty(key) && typeof events[groupKey][key] == 'function') {
                         that[groupKey][key] = events[groupKey][key].bind(that);
                     }
                 }
             }
         });
     };

     /**
      *  Display the modal
      *
      * @param {Array} ids
     */
     ModalList.prototype.show = function(ids) {
         if (typeof ids === 'undefined') {
             ids = null;
         }

         var that = this;

         // Prepare the baskets
         var prepBasketsPromise = new Promise(function(resolve) {
             if (that.settings.mode === 'adder') {
                 // Make sure we start with a clean basket
                 // and have the given ids shown as disabled
                 that.basketManager.delete().then(function() {
                     if (that.externalBasket && ids === null) {
                         that.externalBasket.load().then(function(values) {
                             that.list.disabledItems = values;
                             resolve([]);
                         });
                     } else {
                         that.list.disabledItems = ids;
                         resolve([]);
                     }
                 });
             } else if (that.settings.mode === 'selector') {
                 // If we've provided an external basket make sure we copy the existing ids into our internal one
                 if (that.externalBasket && ids === null) {
                     that.basketManager.getBasket().copyFrom(that.externalBasket).then(function(values) {
                         resolve(values);
                     });
                 } else {
                     that.basketManager.getBasket().replace(ids).then(function(values) {
                         resolve(values);
                     });
                 }
             } else {
                 resolve();
             }
         });

         prepBasketsPromise.then(function(ids) {
             that.loader.show();

             if (that.settings.mode === 'viewer') {
                 that.list.update().then(function() {
                     that.modal.show();
                 });
             } else {
                 that.basketManager.renderBasket(ids).then(function() {
                     return that.updatePage([that.list.getUpdateRequestArgs()]);
                 }).then(function() {
                    that.modal.show();
                 });
             }
         });
     };

    /**
    * Validate that we have all required params for a modal list
    * @param {Object} options
    * @return {promise}
    */
    var validateParams = function(options) {
        return new Promise(function(resolve, reject) {
            if (options.key === undefined) {
                reject('Required option "key" has not been defined');
            } else if (options.list === undefined) {
                reject('Required option "list" has not been defined');
            } else if (options.list.service === undefined) {
                reject('Required list option "service" has not been defined');
            } else if (options.list.map === undefined || typeof options.list.map != "object") {
                reject('Required list option "map" must be defined & must be of type object');
            } else if (options.title === undefined) {
                reject('Required option "title" has not been defined');
            } else {
                resolve();
            }
        });
    };

    /**
     * Function for constructing initial template data
     * @param {Object} settings
     * @return {promise}
     */
    var constructTemplateData = function(settings) {
        var templateData = {
            has_paging: true,
            modal_display: true,
            has_count: true
        };

        if (settings.mode === 'adder' || settings.mode === 'selector') {
            templateData.crumbs = null;
            templateData.hasToggleSelection = true;
            templateData.selection_basket = true;
        }

        if (settings.expandable) {
            // We need at least a template to render the expanded view
            templateData.expandTemplate = settings.expandable.template;
            // If a service is provided it will use it to load the data for the template
            if (settings.expandable.service) {
                templateData.expandTemplateWebservice = settings.expandable.service;
                if (settings.expandable.args) {
                    templateData.expandTemplateWebserviceArgs = JSON.stringify(settings.expandable.args);
                }
            }

        }

        if (settings.list.map.hasHierarchy) {
            templateData.crumbtrail_template_name = 'totara_competency/crumb_with_title';
            templateData.has_crumbtrail = true;
            templateData.has_level_toggle = settings.levelToggle;
        }

        return new Promise(function(resolve) {
            var promiseList = [];

            if (settings.title) {
                promiseList.push(constructTitleString(settings.title));
            }

            if (settings.primarySearch) {
                promiseList.push(constructSearchData(settings.primarySearch));
            }

            if (settings.primaryDropDown) {
                promiseList.push(constructDropDownData(settings.primaryDropDown));
            }

            // Collected all required data
            Promise.all(promiseList).then(function(data) {
                for (var a = 0; a < data.length; a++) {
                    var feature = data[a];
                    if (typeof feature == 'object') {
                        for (var key in feature) {
                            templateData[key] = feature[key];
                        }
                    }
                }
                resolve(templateData);
            });
        });
    };

    /**
    * Add list template
    * @param {Object} settings
    * @return {promise}
    */
    var addListTemplate = function(settings) {
        return new Promise(function(resolve) {
            constructTemplateData(settings).then(function(data) {
                var modalType = settings.mode === 'viewer' ? ModalFactory.types.DEFAULT : ModalFactory.types.SAVE_CANCEL;

                TemplatesManager.render('totara_competency/basket_list', data).then(function(html) {
                    ModalFactory.create({
                        body: html,
                        large: true,
                        title: data.title,
                        type: modalType
                    }).done(function(modal) {
                        // Add modal list to dom
                        modal.attachToDOM();
                        resolve(modal);
                    });
                });
            });
        });
    };

    /**
    * Function for constructing data for primary drop down
    * @param {Object} tree
    * @return {promise}
    */
    var constructDropDownData = function(tree) {
        return new Promise(function(resolve) {

            var prepStringsPromise = new Promise(function(resolve) {
                if (tree.placeholderString.value) {
                    resolve(tree.placeholderString.value);
                } else {
                    str.get_string(tree.placeholderString[0].key, tree.placeholderString[0].component).then(resolve);
                }
            });

            prepStringsPromise.then(function(defaultString) {
                ajax.getData({args: tree.serviceArgs, methodname: tree.service}).then(function(data) {
                    var labelKey = tree.serviceLabelKey ? tree.serviceLabelKey : 'name';
                    var options = [{
                        'active': true,
                        'default': true,
                        'has_children': false,
                        'name': defaultString,
                        'key': ''
                    }];

                    options = options.concat(data.results.items.map(function(item) {
                        return {
                            'active': false,
                            'default': false,
                            'has_children': false,
                            'key': item.id,
                            'name': item[labelKey]
                        };
                    }));

                    var treeData = {
                        primary_filter_tree: {
                            'active_name': defaultString,
                            'flat_tree': true,
                            'key': tree.filterKey,
                            'options': options,
                            'parents_are_selectable': true,
                            'partial': true,
                            'show_border_box': true,
                            'title_hidden': true
                        }
                    };

                    resolve(treeData);
                });
            });
        });
    };

    /**
    * Function for constructing data for primary search
    * @param {Object} search
    * @return {promise}
    */
    var constructSearchData = function(search) {
        return new Promise(function(resolve) {

            var prepStringsPromise = new Promise(function(resolve) {
                if (search.placeholderString.value) {
                    resolve(search.placeholderString.value);
                } else {
                    str.get_string(search.placeholderString[0].key, search.placeholderString[0].component).then(resolve);
                }
            });

            prepStringsPromise.then(function(defaultString) {
                var searchData = {
                    primary_filter_search: {
                        key: search.filterKey,
                        partial: true,
                        placeholder_show: true,
                        title: defaultString,
                        title_hidden: true
                    }
                };
                resolve(searchData);
            });
        });
    };

    /**
    * Fetch string for title
    * @param {Object} titleData
    * @return {promise}
    */
    var constructTitleString = function(titleData) {
        return new Promise(function(resolve) {
            var prepStringsPromise = new Promise(function(resolve) {
                if (titleData[0].value) {
                        resolve({'title': titleData[0].value});
                } else {
                    str.get_string(titleData[0].key, titleData[0].component).then(function(string) {
                        resolve({'title': string});
                    });
                }
            });
            prepStringsPromise.then(function(defaultString) {
                resolve(defaultString);
            });
        });
    };

    /**
    * initialisation method
    * @param {Object} options
    * @returns {Object} promise
    */
    var create = function(options) {
        return new Promise(function(resolve) {
            addListTemplate(options).then(function(modal) {

                var crumbtrail = options.crumbtrail ? options.crumbtrail : null;

                var data = {
                    crumbtrail: crumbtrail,
                    list: options.list,
                    parent: modal.body[0],
                };

                // Prepare the baskets
                var createModalList = new Promise(function(resolve) {
                    if (options.mode === 'adder' || options.mode === 'selector') {
                        require(['totara_competency/basket_list'], function(ListBase) {
                            resolve(ListBase);
                        });
                    } else {
                        require(['totara_competency/view_list'], function(ListBase) {
                            resolve(ListBase);
                        });
                    }
                });

                // Create the modal
                createModalList.then(function(ListBase) {
                    // copies properties from the ModalList prototype to the Base prototype
                    Object.assign(ListBase.prototype, ModalList.prototype);
                    var modalList = new ListBase();
                    modalList.modal = modal;
                    modalList.settings = options;

                    if (options.externalBasket) {
                        modalList.externalBasket = options.externalBasket;
                    }

                    modalList.init(data).then(function() {
                        resolve(modalList);
                    });
                });
            });
        });
    };

    /**
    * initialisation for adder modal list
    * The adder modal lists purpose is to display selectable items
    * and to return that selection back to the page.
    * The items can be filtered and any selection will be stored within a basket.
    * Closing and re-opening the modal will retain the previous selection.
    * The key difference between an adder & selector is any previously saved selection
    * in an adder cannot be unselected from the list.
    * @param {Object} options
    * @returns {Promise}
    */
    var adder = function(options) {
        return new Promise(function(resolve, reject) {
            options.mode = 'adder';
            validateParams(options).then(function() {
                options.list.map.hasCheckboxes = true;
                create(options).then(function(modal) {
                    resolve(modal);
                });

            }, function(error) {
                reject([options.key, error]);
            });
        });
    };

    /**
    * initialisation for selector modal list
    * The selector modal lists purpose is to display selectable items
    * and to return that selection back to the page.
    * The items can be filtered and any selection will be stored within a basket.
    * Closing and re-opening the modal will retain the previous selection.
    * @param {Object} options
    * @returns {Promise}
    */
    var selector = function(options) {
        return new Promise(function(resolve, reject) {
            options.mode = 'selector';
            validateParams(options).then(function() {
                options.list.map.hasCheckboxes = true;
                create(options).then(function(modal) {
                    resolve(modal);
                });

            }, function(error) {
                reject([options.key, error]);
            });
        });
    };

    /**
    * initialisation for viewer modal list
    * The viewer modal lists purpose is to display content to the use.
    * The content can still be filtered against but there is no basket,
    * hierarchy, crumbtrail or the ability to select items
    * @param {Object} options
    * @returns {Promise}
    */
    var viewer = function(options) {
        return new Promise(function(resolve, reject) {
            options.mode = 'viewer';
            validateParams(options).then(function() {
                options.list.map.hasCheckboxes = false;
                create(options).then(function(modal) {
                    resolve(modal);
                });

            }, function(error) {
                reject([options.key, error]);
            });
        });
    };

    return {
        adder: adder,
        selector: selector,
        viewer: viewer
    };
});
