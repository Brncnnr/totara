<?php
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
 * @package totara_facetoface
 */

namespace enrol_totara_facetoface\watcher;

use enrol_totara_facetoface_plugin;
use mod_facetoface\hook\alternative_signup_link;

/**
 * Hook watcher for a seminar.
 */
class seminar_watcher {

    // Let's have a static instance of the enrol_plugin object so we can take advantage of its cache.
    private static $enrol_plugin;

    /**
     * Reset the enrol_plugin static instance
     */
    public static function reset_enrol_plugin(): void {
        self::$enrol_plugin = null;
    }

    /**
     * Rewrite a sign-up link if necessary.
     *
     * @param alternative_signup_link $hook
     * @return void
     */
    public static function alter_signup_link(alternative_signup_link $hook) {
        global $CFG;
        require_once($CFG->dirroot . '/enrol/totara_facetoface/lib.php');

        if (!self::$enrol_plugin) {
            self::$enrol_plugin = new enrol_totara_facetoface_plugin();
        }

        $seminar = $hook->seminarevent->get_seminar();
        // see if the current user can enrol on the course
        if (array_key_exists($hook->seminarevent->get_id(), self::$enrol_plugin->get_enrolable_sessions($seminar->get_course()))) {
            $showsignuplink = true;
            if (!enrol_is_enabled('totara_facetoface') || $CFG->enableavailability) {
                $cm = get_coursemodule_from_instance('facetoface', $hook->seminarevent->get_facetoface());
                $modinfo = get_fast_modinfo($cm->course);
                $cm = $modinfo->get_cm($cm->id);

                // If Seminar enrolment plugin is not enabled check visibility of the activity.
                if (!enrol_is_enabled('totara_facetoface')) {
                    // Check visibility of activity (includes visible flag, conditional availability, etc) before adding Sign up link.
                    $showsignuplink = $cm->uservisible;
                }

                if ($CFG->enableavailability) {
                    // Check whether this activity is available for the user. However if it's available, but not visible
                    // for some reason we're still not displaying a link.
                    $showsignuplink &= $cm->available;
                }
            }
            if (!$showsignuplink) {
                $hook->signuplink = '';
                $hook->signuptsandcslink = '';
                return;
            }

            // redirect to totara_facetoface *unless* the user has already enrolled on the course
            $cm = $seminar->get_coursemodule();
            $context = \context_module::instance($cm->id);
            if (!is_enrolled($context)) {
                $hook->signuplink = '/enrol/totara_facetoface/signup.php';
                $hook->signuptsandcslink = '/enrol/totara_facetoface/ajax/signup_tsandcs.php';
            }
        }
    }
}
