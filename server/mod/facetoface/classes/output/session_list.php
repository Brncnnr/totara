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

namespace mod_facetoface\output;

use stdClass;
use context;
use core\output\template;
use mod_facetoface\dashboard\filter_list;
use mod_facetoface\dashboard\render_session_list_config;
use mod_facetoface\dashboard\render_session_option;
use mod_facetoface\internal\session_data;
use mod_facetoface\seminar;
use mod_facetoface\seminar_event;
use mod_facetoface\seminar_event_helper;
use mod_facetoface\seminar_event_list;
use mod_facetoface\signup;
use renderer_base;

defined('MOODLE_INTERNAL') || die();

/**
 * The seminar event list.
 */
final class session_list extends template {

    /** @var array */
    private $reservation;

    /** @var session_list_table */
    private $sessiontable;

    /**
     * Create an instance.
     *
     * @param seminar $seminar
     * @param filter_list $filters
     * @param render_session_option $option
     * @param context $context
     * @param string|null $id
     * @param integer|null $userid 0 or null to use the current user
     * @return self
     */
    public static function create(seminar $seminar, filter_list $filters, render_session_option $option, context $context, string $id = null, int $userid = null): self {
        return new self($seminar, $filters, $option, $context, $id, $userid);
    }

    /**
     * Constructor.
     *
     * @param seminar $seminar
     * @param context $context
     * @param filter_list $filters
     * @param render_session_option $option
     * @param string $id id attribute for the session list table
     * @param integer|null $userid
     * @return array of [reservation, table]
     */
    private function __construct(seminar $seminar, filter_list $filters, render_session_option $option, context $context, ?string $id, ?int $userid) {
        global $USER, $CFG;

        if (empty($userid)) {
            $userid = $USER->id;
        }

        $config = new render_session_list_config($seminar, $context, $option, $userid);
        $query = $filters->to_query_with_option($seminar, $context, $userid, $option);
        $seminarevents = seminar_event_list::from_query($query);

        if ($option->get_displayreservation()) {
            if (!empty($seminar->get_managerreserve())) {
                $this->reservation = get_string('lastreservation', 'mod_facetoface', $seminar->get_properties());
            }
        }

        if ($seminarevents->is_empty()) {
            // No sessions.
            return;
        }

        require_once($CFG->dirroot . '/mod/facetoface/lib.php');

        $time = time();
        /** @var session_data[] $sessionarray */
        $sessionarray = [];
        /** @var seminar_event $seminarevent */
        foreach ($seminarevents as $seminarevent) {
            $signup = signup::create($userid, $seminarevent, MDL_F2F_BOTH, true);
            $sessiondata = seminar_event_helper::get_sessiondata($seminarevent, $signup, $option->get_sessionascendingorder(), $time);
            $sessionarray[] = $sessiondata;
        }

        $this->sessiontable = session_list_table::create($sessionarray, $config, $id);
    }

    /**
     * Export data for template.
     *
     * @return array of [reservation, table => [template, context]]
     *                  - reservation: raw HTML string about reservation information (optional)
     *                  - table: session table data (optional)
     *                  - table.template: template name to render the session table
     *                  - table.context: template data to render the session table
     */
    public function get_template_data() {
        global $OUTPUT;
        $data = [];
        if (!empty($this->sessiontable)) {
            if (!empty($this->reservation)) {
                $data['reservation'] = $this->reservation;
            }
            $data['table'] = [
                'template' => session_list_table::TEMPLATE_NAME,
                'context' => $this->sessiontable->export_for_template($OUTPUT)
            ];
        }
        return $data;
    }
}
