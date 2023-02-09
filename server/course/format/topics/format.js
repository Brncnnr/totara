// Javascript functions for Topics course format

M.course = M.course || {};

M.course.format = M.course.format || {};

/**
 * Get sections config for this format
 *
 * The section structure is:
 * <ul class="topics">
 *  <li class="section">...</li>
 *  <li class="section">...</li>
 *   ...
 * </ul>
 *
 * @return {object} section list configuration
 */
M.course.format.get_config = function() {
    return {
        container_node : 'ul',
        container_class : 'topics',
        section_node : 'li',
        section_class : 'section'
    };
}

/**
 * Swap section
 *
 * @param {YUI} Y YUI3 instance
 * @param {string} node1 node to swap to
 * @param {string} node2 node to swap with
 * @return {NodeList} section list
 */
M.course.format.swap_sections = function(Y, node1, node2) {
    var CSS = {
        COURSECONTENT : 'course-content',
        SECTIONADDMENUS : 'section_add_menus'
    };

    var sectionlist = Y.Node.all('.'+CSS.COURSECONTENT+' '+M.course.format.get_section_selector(Y));
    // Swap menus.
    sectionlist.item(node1).one('.'+CSS.SECTIONADDMENUS).swap(sectionlist.item(node2).one('.'+CSS.SECTIONADDMENUS));
}

/**
 * Process sections after ajax response
 *
 * @param {YUI} Y YUI3 instance
 * @param {array} response ajax response
 * @param {string} sectionfrom first affected section
 * @param {string} sectionto last affected section
 * @return void
 */
M.course.format.process_sections = function(Y, sectionlist, response, sectionfrom, sectionto) {
    var CSS = {
        SECTIONNAME : 'sectionname'
    },
    SELECTORS = {
        SECTIONLEFTSIDE : '.left .section-handle',
        SECTIONLEFTSIDEICON : '.icon',
        SECTIONLEFTSIDESR : '.sr-only'
    };

    if (response.action == 'move') {
        // If moving up swap around 'sectionfrom' and 'sectionto' so the that loop operates.
        if (sectionfrom > sectionto) {
            var temp = sectionto;
            sectionto = sectionfrom;
            sectionfrom = temp;
        }

        // Update titles and move icons in all affected sections.
        var ele, str, stridx, newstr;

        for (var i = sectionfrom; i <= sectionto; i++) {
            // Update section title.
            var content = Y.Node.create('<span>' + response.sectiontitles[i] + '</span>');
            sectionlist.item(i).all('.'+CSS.SECTIONNAME).setHTML(content);

            // Update move icon's title & inner access content to reflect updated sectionlist index
            ele = sectionlist.item(i).one(SELECTORS.SECTIONLEFTSIDE);

            // Determine new string value to be used for the icon and its child nodes
            str = ele.getAttribute('title');
            stridx = str.lastIndexOf(' ');
            newstr = str.substr(0, stridx +1) + i;

            // Update all instances where lang string is expected
            ele.setAttribute('title', newstr);
            ele.one(SELECTORS.SECTIONLEFTSIDEICON).setAttribute('title', newstr);
            ele.one(SELECTORS.SECTIONLEFTSIDESR).setContent(newstr);
        }
    }
};

(function() {
    /**
     * Toggle attribute
     *
     * @param {Element} el
     * @param {string} name
     * @param {boolean} value
     */
    function toggleAttr(el, name, value) {
        if (value) {
            el.setAttribute(name, true);
        } else {
            el.removeAttribute(name);
        }
    }

    var toggles = document.querySelector('.tw-formatTopics__all_toggles');

    var sections = Array.prototype.slice.call(document.querySelectorAll('.tw-formatTopics__topic'));

    var openStates = {};
    sections.forEach(function(sectionEl) {
        openStates[sectionEl.getAttribute('data-section-id')] = sectionEl.getAttribute('data-open') !== null ? 1 : 0;
    });

    var sectionsById = {};

    /**
     * Set all open/closed
     *
     * @param {boolean} newVal
     */
    function setAll(newVal) {
        Object.keys(openStates).forEach(function(key) {
            openStates[key] = newVal;
            if (sectionsById[key]) {
                toggleAttr(sectionsById[key], 'data-open', newVal);
                var arrowControl = sectionsById[key].querySelector('.tw-formatTopics__collapse_link');
                if (arrowControl) {
                    arrowControl.setAttribute('aria-expanded', newVal.toString());
                }
            }
        });
        handleStateUpdate();
    }

    /** Handle state update */
    function handleStateUpdate() {
        M.util.set_user_preference(
            'format_topics/topics_open_state.' + Object.values(sectionsById)[0].getAttribute('data-course-id'),
            JSON.stringify(openStates)
        );

        const allExpanded = Object.values(openStates).every(function(x) {
            return Boolean(x);
        });
        const allCollapsed = Object.values(openStates).every(function(x) {
            return !x;
        });

        if (toggles) {
            toggleAttr(toggles, 'data-all-expanded', allExpanded);
            toggleAttr(toggles, 'data-all-collapsed', allCollapsed);
        }
    }

    sections.forEach(function(sectionEl) {
        var sectionId = sectionEl.getAttribute('data-section-id');

        if (!sectionEl.classList.contains('tw-formatTopics__topic--collapsible')) {
            return;
        }

        sectionsById[sectionId] = sectionEl;

        var headerControl = sectionEl.querySelector('.tw-formatTopics__collapse_handle');
        var arrowControl = sectionEl.querySelector('.tw-formatTopics__collapse_link');
        var collapseAllControl = sectionEl.querySelector('.tw-formatTopics__collapse_all');
        var expandAllControl = sectionEl.querySelector('.tw-formatTopics__expand_all');

        /**
         * Handle click on togglable control.
         *
         * @param {Event} e
         */
        function handleToggle(e) {
            if (e.currentTarget == headerControl && arrowControl.contains(e.target)) {
                // avoid double handling the event
                return;
            }

            e.preventDefault();
            var open = sectionEl.getAttribute('data-open') !== null;
            var newVal = !open;

            openStates[sectionId] = newVal ? 1 : 0;
            handleStateUpdate();

            toggleAttr(sectionEl, 'data-open', newVal);
            if (arrowControl) {
                arrowControl.setAttribute('aria-expanded', newVal.toString());
            }
        }

        // add handlers to controls
        if (headerControl) {
            headerControl.addEventListener('click', handleToggle);
        }
        if (arrowControl) {
            arrowControl.addEventListener('click', handleToggle);
        }
        if (collapseAllControl) {
            collapseAllControl.addEventListener('click', function(e) {
                e.preventDefault();
                setAll(false);
            });
        }
        if (expandAllControl) {
            expandAllControl.addEventListener('click', function(e) {
                e.preventDefault();
                setAll(true);
            });
        }
    });
})();
