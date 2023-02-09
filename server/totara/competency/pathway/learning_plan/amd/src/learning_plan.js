
define([], function() {

    /**
     * Class constructor for PwLearningPlan.
     *
     * @class
     * @constructor
     */
    function PwLearningPlan() {
        if (!(this instanceof PwLearningPlan)) {
            return new PwLearningPlan();
        }

        this.widget = '';

        /**
         * Pathway data.
         * This object should only contain the data to be sent on the save api endpoint.
         *
         * @type {Object}
         */
        this.pathway = {
            id: 0,
            sortorder: 0,
            type: 'learning_plan',
            singleuse: 1
        };

        // Key to use in achievementPath events
        this.pwKey = '';

        this.endpoints = {
            create: 'pathway_learning_plan_create',
            update: 'pathway_learning_plan_update',
        };

        this.filename = 'modal.js';
    }

    PwLearningPlan.prototype = {

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
                pwKey = 0,
                pwId = 0,
                idWgt = this.widget.closest('[data-tw-editAchievementPaths-pathway-id]'),
                target = this.widget;

            return new Promise(function(resolve) {
                if (pwWgt) {
                    pwKey = pwWgt.getAttribute('data-tw-editAchievementPaths-pathway-key') ? pwWgt.getAttribute('data-tw-editAchievementPaths-pathway-key') : 0;
                }

                if (idWgt) {
                    pwId = idWgt.getAttribute('data-tw-editAchievementPaths-pathway-id') ? idWgt.getAttribute('data-tw-editAchievementPaths-pathway-id') : 0;
                }

                that.pwKey = pwKey;

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
                    target.setAttribute('data-tw-editAchievementPaths-save-endPoint', that.endpoints.create);

                } else {
                    that.pathway.id = pwId;
                    target.setAttribute('data-tw-editAchievementPaths-save-endPoint', that.endpoints.update);
                }

                that.triggerEvent('update', {pathway: that.pathway});
                resolve();
            });
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
    };

    /**
     * Initialisation method
     *
     * @param {node} parent
     * @returns {Object} promise
     */
    var init = function(parent) {
        return new Promise(function(resolve) {
            var wgt = new PwLearningPlan();
            wgt.setParent(parent);
            resolve(wgt);

            M.util.js_pending('pathwayLearningPlan');
            wgt.initData().then(function() {
                M.util.js_complete('pathwayLearningPlan');
            }).catch(function() {
                // Failed
            });
        });
    };

    return {
        init: init
    };
});
