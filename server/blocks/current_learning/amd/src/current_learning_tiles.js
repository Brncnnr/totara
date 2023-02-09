/**
 * This file is part of Totara LMS
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @package block_current_learning
 */
define(['core/templates', 'core/str', 'core/yui'], function(templates, mdlstr, YUI) {

    /** @type {HTMLDivElement} */
    var blockNode;

    /** @type {HTMLDivElement} */
    var tilesNode;

    /** @type {HTMLDivElement} */
    var pagesNode;

    /** @type {object} */
    var pagination;

    /** @type {Array} */
    var learningItems;

    /** @type {number} */
    var totalPages;

    /** @type {boolean} */
    var resizePending = false;

    /** @type {number} */
    var blockinstanceid;

    /**
     * Initialises functionality inside the current learning block
     *
     * @param {Object} context an object containing all the data for the current learning block
     */
    function init(context) {
        // No JS functionality is required if there are no current learning items
        if (context.learningitems.length === 0) {
            return;
        }
        reloadNodes(document.getElementById('inst' + context.instanceid));
        pagination = context.pagination;
        pagination.pagination = true;
        totalPages = Math.ceil(pagination.totalitems / pagination.itemsperpage);
        learningItems = context.learningitems;
        blockinstanceid = context.instanceid;

        addEvents();

        checkResize();
    }

    /**
     * Resets the nodes as moving too and from the Dock causes event listeners to be lost
     *
     * @param {DOMNode} newBlockNode The new root node of the block
     */
    function reloadNodes(newBlockNode) {
        blockNode = newBlockNode;
        pagesNode = blockNode.querySelector('.panel-footer');
        tilesNode = blockNode.querySelector('.block_current_learning-tiles ul');
    }

    /**
     * Adds events to the current learning block
     */
    function addEvents() {
        blockNode.addEventListener('click', changePage);
        window.addEventListener('resize', checkResize);

        // Dock may not be present at the time this is called
        addDockListeners();

        // trigger the resize when moving a block around
        if (document.body.classList.contains('editing')) {
            var checkMove = function(mutationList) {
                mutationList.filter(function(mutation) {
                    return mutation.type === 'childList' && mutation.target.hasAttribute('data-blockregion');
                }).forEach(function (mutation) {
                    mutation.addedNodes.forEach(function(node) {
                        // Make sure that only moving this block triggers a resize check
                        if (node.id == 'inst' + blockinstanceid) {
                            checkResize();
                        }
                    });
                });
            };

            var obs = new MutationObserver(checkMove);
            obs.observe(document.body, {attributes: false, childList: true, subtree: true});
        }
    }

    /**
     * Add Listeners associated with the dock
     */
    function addDockListeners() {
        YUI.use('moodle-core-dock', function() {
            var dock = M.core.dock.get();

            // The name of this event isn't exactly what you expect
            // It fires when the block is displayed in the dock
            dock.on('dock:resizepanelcomplete', function () {
                var dockNode = document.querySelector('#dock .block_current_learning');
                if (dockNode) {
                    reloadNodes(dockNode);
                    checkResize();
                    blockNode.addEventListener('click', changePage);
                }
            });

            dock.on('dock:itemremoved', function() {
                var block = document.getElementById('inst' + blockinstanceid);
                if (block) {
                    reloadNodes(block);
                    checkResize();
                }
            });
        });
    }

    /**
     * Prevents resizing on every size change
     */
    function checkResize() {
        if (resizePending) {
            return;
        }
        resizePending = true;
        setTimeout(doResize, 50);
    }

    /**
     * Does the actual resize
     */
    function doResize() {
        resizePending = false;
        var width = parseInt(getComputedStyle(tilesNode.parentElement).width, 10);
        var tilesPerRow = Math.floor(width / 214) || 1;
        var rows;

        if (tilesPerRow < 2) {
            rows = 3;
        } else if (tilesPerRow < 5) {
            rows = 2;
        } else {
            rows = 1;
        }

        if (tilesPerRow == tilesNode.getAttribute('data-items-per-row') && tilesNode.parentElement.getAttribute('data-loading') == "false") {
            updateMargin();
            return;
        }
        tilesNode.parentElement.setAttribute('data-loading', true);

        pagination.itemsperpage = tilesPerRow * rows;
        render(tilesPerRow).then(updateMargin);
    }

    /**
     * Updates the margin so the tile is nicely centered when it's only one tile wide
     */
    function updateMargin() {
        if (tilesNode.getAttribute('data-items-per-row') == 1) {
            var tile = tilesNode.querySelector('.block_current_learning-tile');
            var style = getComputedStyle(tile);
            if (style.maxWidth === style.width) {
                var parentWidth = getComputedStyle(tilesNode).width;
                var margin = (parseInt(parentWidth, 10) - parseInt(style.width, 10)) / 2;

                tilesNode.querySelectorAll('.block_current_learning-tile').forEach(function(node) {
                    node.style.marginLeft = margin + 'px';
                    node.style.marginRight = margin + 'px';
                });
            } else {
                tilesNode.querySelectorAll('.block_current_learning-tile').forEach(function(node) {
                    node.style.marginLeft = '';
                    node.style.marginRight = '';
                });
            }
        }
    }

    /**
     * Changes the page that the current user is looking at
     *
     * @param {Event} e The event that triggers this call
     */
    function changePage(e) {
        /** @type {HTMLAnchorElement} */
        var target = e.target.closest('a');

        if (!target || !target.hasAttribute('data-page')) {
            return;
        }
        e.preventDefault();

        if (target.parentElement.classList.contains('disabled')) {
            return;
        }

        var page = target.getAttribute('data-page');
        if (page === 'next') {
            pagination.currentpage += 1;
        } else if (page === 'prev') {
            pagination.currentpage -= 1;
        } else {
            pagination.currentpage = parseInt(page);
        }

        render().then(updateMargin);
    }

    /**
     * Re-renders the block
     *
     * @param {Number|null} tilesPerRow number of tiles per row
     */
    function render(tilesPerRow) {
        if (!tilesPerRow) {
            tilesPerRow = tilesNode.getAttribute('data-items-per-row');
        }

        totalPages = Math.ceil(pagination.totalitems / pagination.itemsperpage);
        pagination.onepage = totalPages == 1;
        if (pagination.currentpage > totalPages) {
            pagination.currentpage = totalPages;
        }

        pagination.previousclass = pagination.currentpage === 1 ? 'disabled' : '';
        pagination.nextclass = pagination.currentpage === totalPages ? 'disabled' : '';

        var start = (pagination.currentpage - 1) * pagination.itemsperpage;
        var end = Math.min((pagination.currentpage * pagination.itemsperpage), pagination.totalitems);
        var tileData = learningItems.slice(start, end);
        var strData = {
            end: end,
            start: start + 1,
            total: learningItems.length
        };

        pagination.pages = [];

        for (var page = 0; page < totalPages; page++) {
            pagination.pages.push({
                page: page + 1,
                active: page + 1 == pagination.currentpage ? 'active' : ''
            });
        }

        M.util.js_pending('block_current_learning-updated');
        /** @type {Promise} */
        var pagingComplete = mdlstr.get_string('displayingxofx', 'block_current_learning', strData).then(function(string) {
            pagination.text = string;
            return templates.render('block_current_learning/paging', pagination);
        }).then(function(html, js) {
            return {
                html: html,
                js: js
            };
        });

        var tiles = tileData.map(function (tile) {
            return templates.render('block_current_learning/tile', tile).then(function(html, js) {
                return Promise.resolve({html: html, js: js});
            });
        });

        var tilesUpdated = Promise.all(tiles).then(function (tiles) {
            var html = "";
            var js = "";

            tiles.forEach(function (tile) {
                html += tile.html;
                js += tile.js + ';';
            });
            return Promise.resolve({html: html, js: js});
        });

        return Promise.all([pagingComplete, tilesUpdated]).then(function(pagingHTML) {
            blockNode.querySelector('.panel-footer').outerHTML = pagingHTML[0].html;
            tilesNode.innerHTML = pagingHTML[1].html;
            tilesNode.querySelectorAll('script').forEach(function (script) {
                // Yes this isn't ideal, but progrtess bars include a script tag
                eval(script.innerHTML); // eslint-disable-line no-eval
            });
            tilesPerRow = tilesNode.setAttribute('data-items-per-row', tilesPerRow);
            tilesNode.parentElement.setAttribute('data-loading', false);
            return templates.runTemplateJS(pagingHTML[0].js + ";"  + pagingHTML[1].js);
        }).then(function() {
            M.util.js_complete('block_current_learning-updated');
        });
    }

    return {
        init: init
    };
});