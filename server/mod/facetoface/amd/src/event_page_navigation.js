/*
 * This file is part of Totara LMS
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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_facetoface
 */


define([], function() {
    return {
        /**
         * Initialisation method
         *
         * @param {HTMLElement} root
         * @returns {Promise}
         */
        init: function(root) {
            /**
             * Simulate jQuery('html,body').animate({scrollTop: el.offset().y}, duration)
             *
             * @param {number} scrollY
             * @param {number} duration
             */
            function animateScrollTop(scrollY) {
                // 400 is the default of jQuery.animate()
                var duration = arguments.length > 1 ? Math.floor(arguments[1]) : 400;
                var startY = window.pageYOffset;
                var endY = scrollY;
                var startTime = performance.now();
                M.util.js_pending('mod_facetoface_event_page_navigation:scrollTo');
                // Deal with only the last request.
                return (function() {
                    /**
                     * @param {DOMHighResTimeStamp} now
                     */
                    function scroller(now) {
                        var elapsedTime = now - startTime;
                        if (elapsedTime >= duration) {
                            window.scrollTo(0, endY);
                            M.util.js_complete('mod_facetoface_event_page_navigation:scrollTo');
                            return;
                        }
                        var d = elapsedTime / duration;
                        var v = 6 * Math.pow(d, 5) - 15 * Math.pow(d, 4) + 10 * Math.pow(d, 3);
                        var y = startY + (endY - startY) * v;
                        window.scrollTo(0, y);
                        requestAnimationFrame(scroller);
                    }
                    requestAnimationFrame(scroller);
                })();
            }

            /**
             * Simulate jQuery(el).offset().top
             *
             * @param {HTMLElement} el
             * @returns {number}
             */
            function getOffsetTop(el) {
                var offset = 0;
                while (el !== null) {
                    offset += el.offsetTop;
                    el = el.offsetParent;
                }
                return offset;
            }

            /**
             * Simulate jQuery(el).outerHeight()
             *
             * @param {HTMLElement} el
             * @returns {number}
             */
            function getOuterHeight(el) {
                var height = 0;
                var style = getComputedStyle(el);
                ['height', 'marginTop', 'marginBottom', 'paddingTop', 'paddingBottom'].forEach(function(prop) {
                    height += parseFloat(style[prop]);
                });
                return height;
            }

            return new Promise(function(resolve) {
                root.addEventListener('click', function(event) {
                    var element = event.target.closest('a[href^="#"]');
                    if (element !== null) {
                        var owner = element.closest('.mod_facetoface__navigation');
                        var id = element.getAttribute('href');
                        var scrollY = 0;
                        if (/^#\w/.test(id)) {
                            scrollY = getOffsetTop(document.querySelector(id));
                        }
                        if (/sticky$/.test(getComputedStyle(owner).position)) {
                            scrollY -= getOuterHeight(owner);
                        }
                        if (scrollY < 0) {
                            scrollY = 0;
                        }
                        animateScrollTop(scrollY);
                        event.preventDefault();
                    }
                });
                resolve(true); // Nothing interesting to return.
            });
        }
    };
});
