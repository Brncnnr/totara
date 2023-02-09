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

use coding_exception;
use context;
use html_writer;
use mod_facetoface\dashboard\filter_list;
use mod_facetoface\dashboard\render_session_option;
use mod_facetoface\event_time;
use mod_facetoface\query\query_helper;
use mod_facetoface\seminar;
use renderer_base;
use core\output\template;

defined('MOODLE_INTERNAL') || die();

/**
 * The session area of the seminar dashboard.
 */
final class seminarevent_dashboard_sessions extends template {
    /** @var string */
    private $type;

    /** @var session_list */
    private $sessionlist;

    /** @var show_previous_events|null */
    private $previouseventslink;

    /** @var string[] */
    private $debuginfo;

    /**
     * Private constructor to enforce the factory pattern.
     *
     * @param string $type 'upcoming' or 'past'
     * @param session_list $sessionlist
     * @param show_previous_events|null $previouseventslink
     * @param string[] $debuginfo
     */
    private function __construct(string $type, session_list $sessionlist, ?show_previous_events $previouseventslink, array $debuginfo) {
        $this->type = $type;
        $this->sessionlist = $sessionlist;
        $this->previouseventslink = $previouseventslink;
        $this->debuginfo = $debuginfo;
    }

    /**
     * Create an instance.
     *
     * @param seminar $seminar
     * @param filter_list $filters
     * @param context $context
     * @param string $type 'upcoming' or 'past'
     * @param boolean $debug set true to display debugging information
     * @return self
     */
    public static function create(seminar $seminar, filter_list $filters, context $context, string $type, bool $debug): self {
        $previouseventslink = null;
        $debuginfo = [];

        // Only admins can see debug information.
        $debug = $debug && is_siteadmin();

        if ($type == 'upcoming') {
            // Upcoming events.
            $option = (new render_session_option())
                ->set_displayreservation(true)
                ->set_eventascendingorder(true)
                ->set_sessionascendingorder(true)
                ->set_eventtimes([event_time::FUTURE, event_time::INPROGRESS, event_time::WAITLISTED]);
            $tableid = 'mod_facetoface_upcoming_events_table';
        } else if ($type == 'past') {
            // Past events.
            $option = (new render_session_option())
                ->set_displayreservation(false)
                ->set_displaysignupperiod(false)
                ->set_eventascendingorder(false)
                ->set_sessionascendingorder(true)
                ->set_eventtimes([event_time::PAST, event_time::CANCELLED]);
            $tableid = 'mod_facetoface_past_events_table';
            if ($seminar->has_events()) {
                $previouseventslink = show_previous_events::create($seminar, $filters, 'previoussessionheading');
            }
        } else {
            throw new coding_exception('Unknown $type specified: '.$type);
        }

        $sessionlist = session_list::create($seminar, $filters, $option, $context, $tableid);

        if ($debug) {
            $stmt = $filters->to_query_with_option($seminar, $context, null, $option)->get_statement();
            $debuginfo = [
                query_helper::highlight($stmt->get_sql(), $stmt->get_parameters()),
                html_writer::tag('pre', html_writer::tag('code', s(print_r($option->to_object(), true))), ['style' => 'font-size:11px']),
                html_writer::tag('pre', html_writer::tag('code', s(print_r($filters->to_object(), true))), ['style' => 'font-size:11px']),
            ];
        }

        return new self($type, $sessionlist, $previouseventslink, $debuginfo);
    }

    /**
     * Export data for template.
     *
     * @return array of [type, debug, pastlink, reservation, table]
     *                  - type: upcoming or past
     *                  - debug: array of raw HTML strings about debug information (optional)
     *                  - pastlink: show previous link data
     *                  - pastlink.template: template name to render previous link
     *                  - pastlink.context: template data to render previous link
     *                  - reservation: see output\session_list::get_template_data (optional)
     *                  - table: see output\session_list::get_template_data (optional)
     */
    public function get_template_data() {
        $data = (array)$this->sessionlist->get_template_data();
        $data['type'] = $this->type;
        $data['debug'] = $this->debuginfo;

        if ($this->previouseventslink !== null) {
            $data['pastlink'] = [
                'template' => $this->previouseventslink->get_template_name(),
                'context' => $this->previouseventslink->get_template_data()
            ];
        }

        // Behat steps are not supposed to look at deprecated CSS class names.
        if (!defined('BEHAT_SITE_RUNNING')) {
            if ($this->type === 'upcoming') {
                $data['legacystateclass'] = 'upcomingsessionlist';
            } else if ($this->type === 'past') {
                $data['legacystateclass'] = 'previoussessionlist';
            }
        }

        return $data;
    }
}
