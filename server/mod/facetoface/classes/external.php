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
 * @package mod_facetoface
 */

namespace mod_facetoface;

use coding_exception;
use context_course;
use core\entity\user;
use core\orm\query\exceptions\record_not_found_exception;
use external_api;
use external_description;
use external_function_parameters;
use external_single_structure;
use external_value;
use mod_facetoface\dashboard\filter_list;
use mod_facetoface\dashboard\filters\filter;
use mod_facetoface\output\seminarevent_dashboard_sessions;
use moodle_exception;
use totara_core\virtualmeeting\virtual_meeting_auth;

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

/**
 * This is the external API for mod_facetoface.
 */
class external extends external_api {
    /**
     * Parameter definitions of render_session_list.
     *
     * @return external_function_parameters
     */
    public static function render_session_list_parameters(): external_function_parameters {
        $filterparams = [];
        (new filter_list())->add_default_filters()->walk(function (string $name, string $type, filter $filter) use (&$filterparams) {
            $filterparams[$name] = new external_value($type, $name, VALUE_OPTIONAL, $filter->get_default_value());
        });

        return new external_function_parameters([
            'id' => new external_value(PARAM_INT, 'Course module id', VALUE_DEFAULT, 0),
            'f' => new external_value(PARAM_INT, 'Facetoface id', VALUE_DEFAULT, 0),
            'type' => new external_value(PARAM_ALPHA, 'List type'),
            'cookie' => new external_value(PARAM_INT, 'Unique value to distinguish requests'),
            'filterparams' => new external_single_structure($filterparams, 'Filter params'),
            'debug' => new external_value(PARAM_BOOL, 'Debug', VALUE_DEFAULT, false)
        ]);
    }

    /**
     * Render the session list table.
     *
     * @param integer|null $id course module id
     * @param integer|null $f facetoface (seminar) id
     * @param string $type 'upcoming' or 'past'
     * @param integer $cookie
     * @param array $filterparams filters as [name => value, ...]
     * @param boolean|null $debug
     * @return array
     * @throws moodle_exception $id or $f is wrong
     * @throws coding_exception $type is wrong
     */
    public static function render_session_list(?int $id, ?int $f, string $type, int $cookie, array $filterparams, ?bool $debug): array {
        global $PAGE;

        // Only admins can see debug information.
        $debug = ($debug ?? false) && is_siteadmin();

        if ($id) {
            if (!$cm = get_coursemodule_from_id('facetoface', $id)) {
                throw new moodle_exception('error:incorrectcoursemoduleid', 'facetoface');
            }
            $seminar = new seminar($cm->instance);
        } else if ($f) {
            $seminar = new seminar($f);
            $cm = $seminar->get_coursemodule();
        } else {
            throw new moodle_exception('error:mustspecifycoursemodulefacetoface', 'facetoface');
        }

        $courseid = $seminar->get_course();
        $context = context_course::instance($courseid);
        /** @var context_course $context */

        $filters = new filter_list(function (string $parname, $default, string $type) use (&$filterparams) {
            return $filterparams[$parname] ?? $default;
        });
        $filters->add_default_filters();

        $PAGE->set_context($context);
        // This line assumes that the AJAX is requested from only the event dashboard page.
        $PAGE->set_url($filters->to_url($seminar, '/mod/facetoface/view.php'));

        $template = seminarevent_dashboard_sessions::create($seminar, $filters, $context, $type, $debug);
        $data = $template->get_template_data();

        $aredefault = $filters->are_default();
        $title = $seminar->get_name();
        return [
            'cookie' => $cookie,
            'title' => $title,
            'resetfilter' => $aredefault,
            'data' => $data
        ];
    }

    /**
     * Returns an object that describes the structure of the return from render_session_list.
     *
     * @return external_description|null
     */
    public static function render_session_list_returns(): ?external_description {
        // It's not possible to define variable structures in this function.
        return null;
    }

    /**
     * Parameter definitions of user_profile.
     *
     * @return external_function_parameters
     */
    public static function user_profile_parameters(): external_function_parameters {
        return new external_function_parameters([
            'plugin' => new external_value(PARAM_PLUGIN, 'Plugin name'),
            'update' => new external_value(PARAM_BOOL, 'Tell plugin to update auth token', VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Get the user's external profile provided by the given virtual meeting plugin.
     *
     * @param string $pluginname
     * @param boolean|null $update
     * @return array
     */
    public static function user_profile(string $pluginname, ?bool $update): array {
        /** @var user */
        $user = user::logged_in();
        try {
            $auth = virtual_meeting_auth::load_by_plugin_user($pluginname, $user);
            return $auth->get_user_profile($update ?? false);
        } catch (record_not_found_exception $ex) {
            return [];
        }
    }

    /**
     * Returns an object that describes the structure of the return from user_auth_status.
     *
     * @return external_description|null
     */
    public static function user_profile_returns(): ?external_description {
        return new external_single_structure(
            [
                'name' => new external_value(PARAM_RAW, 'username', VALUE_OPTIONAL),
                'email' => new external_value(PARAM_EMAIL, 'email address', VALUE_OPTIONAL),
                'friendly_name' => new external_value(PARAM_RAW, "user's name", VALUE_OPTIONAL)
            ],
            'response data'
        );
    }
}
