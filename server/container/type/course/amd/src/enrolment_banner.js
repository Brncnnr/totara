/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_course
 */
define(["core/config", 'core/ajax'], function(config, ajax) {
    /**
     * @param {HTMLDivElement} widget
     * @returns {Promise}
     */
    function init(widget) {
        return new Promise(
            function(resolve) {
                var el = widget.querySelector('.tw-containerCourse-enrolmentBanner__enrolButton');
                if (el) {
                    var courseId = el.getAttribute('data-course-id');
                    // Adding sesskey to the URL.
                    el.addEventListener(
                        'click',
                        function(event) {
                            event.preventDefault();
                            // Logged user can not do non interactive enrol.
                            if (el.href.includes('enrol/index.php')) {
                                window.location.href = el.href;
                                return;
                            }
                            ajax.call([{
                                    methodname: 'container_course_process_non_interactive_enrol',
                                    args: {course_id: courseId}
                                }], true, true)[0].then(function (response) {
                                if (response) {
                                    window.location.href = el.href;
                                }
                            });
                        }
                    );
                }

                resolve();
            }
        );
    }

    return {
        init: init
    };
});