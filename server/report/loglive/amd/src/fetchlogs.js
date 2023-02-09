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
 * @author Alvin Smith <alvin.smith@totaralearning.com>
 * @package report_loglive
 */

define(['core/config', 'core/str', 'core/notification'], function(cfg, str, notificationLib) {

    const CSS = {
        NEWROW: 'newrow',
        SPINNER: 'spinner',
    };
    const SELECTORS = {
        NEWROW: '.' + CSS.NEWROW,
        TBODY: '.flexible tbody',
        PAUSEBUTTON: '#livelogs-pause-button',
        SPINNER: '.' + CSS.SPINNER
    };

    var props = {since: null};
    var intervalId, pauseButton, spinner;

    /** function to hide the loading icon */
    function hideLoadingIcon() {
        spinner.style.display = 'none';
    }

    /** function to show the loading icon */
    function showLoadingIcon() {
        spinner.style.display = '';
    }

    /**
     * Method to fetch recent logs.
     *
     * @method fetchRecentLogs
     */
    function fetchRecentLogs() {
        showLoadingIcon();
        const data = {
            logreader: props.logreader,
            since: props.since,
            page: props.page,
            id: props.courseid,
        };
        const query = Object.keys(data)
            .map(function(k) {
                return k + '=' + data[k];
            })
            .join('&');

        const url = cfg.wwwroot + '/report/loglive/loglive_ajax.php?' + query;

        const uniqueId = 'report_loglive_fetchlogs';
        M.util.js_pending(uniqueId);
        fetch(url, {
            credentials: 'same-origin',
            method: 'get',
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(json) {
                updateLogTable(json);
                M.util.js_complete(uniqueId);
            });
    }

    /**
     * Method to update the log table, populate the table with new entries and remove old entries if needed.
     * @param {json} res response from loglive_ajax.php
     * @method updateLogTable
     */
    function updateLogTable(res) {
        // Hide loading icon, give sometime to people to actually see it. We should do it, event in case of an error.
        setTimeout(hideLoadingIcon, 600);

        if (!res) {
            notificationLib.exception(res);
        }

        props.since = res.until;
        const logs = res.logs;

        const tbody = document.querySelector(SELECTORS.TBODY);

        if (tbody && logs) {
            tbody.insertAdjacentHTML('afterbegin', logs);

            // Let us chop off some data from end of table to prevent really long pages.
            const perpage = props.perpage;
            while (tbody.childNodes.length > perpage) {
                tbody.removeChild(tbody.lastElementChild);
            }

            // Remove highlighting from new rows.
            setTimeout(removeHighlight, 5000);
        }
    }

    /**
     * Remove background highlight from the newly added rows.
     *
     * @method removeHighlight
     */
    function removeHighlight() {
        var elements = document.querySelectorAll('.time' + props.since);
        for (var i = 0; i < elements.length; i++) {
            elements[i].classList.remove(CSS.NEWROW);
        }
    }

    /** Wrap the get_string function
     * @param {string} key string key
     * @param {string} component component name
     */
    function languageSupport(key) {
        str.get_string(key, 'report_loglive').done(function(string) {
            pauseButton.innerText = string;
        });
    }

    /** Toggle the pause/resume button update */
    function toggleUpdate() {
        if (intervalId) {
            languageSupport('resume');
            clearInterval(intervalId);
            intervalId = null;
        } else {
            languageSupport('pause');
            fetchRecentLogs();
            intervalId = setInterval(fetchRecentLogs, props.interval * 1000);
        }
    }

    return {
        /**
         * module initialisation method called by php js_call_amd()
         * @param {number} since the unix timestamp
         * @param {number} courseid the courseid for the log
         * @param {number} page the current page
         * @param {string} logreader the type of log
         * @param {number} interval the time seconds between api calls
         * @param {number} perpage the max number in the page
         */
        init: function(since, courseid, page, logreader, interval, perpage) {
            Object.assign(props, {
                since: since,
                courseid: courseid || 0,
                page: page || 0,
                logreader: logreader || 'logstore_standard',
                interval: interval || 60,
                perpage: perpage || 100,
            });

            pauseButton = document.querySelector(SELECTORS.PAUSEBUTTON);
            spinner = document.querySelector(SELECTORS.SPINNER);

            hideLoadingIcon();
            pauseButton.addEventListener('click', toggleUpdate);

            if (props.page === 0) {
                fetchRecentLogs();
                intervalId = setInterval(fetchRecentLogs, props.interval * 1000);
            }

        }
    };
});