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
use html_writer;
use moodle_url;
use templatable;
use mod_facetoface\dashboard\filter_list;
use mod_facetoface\seminar;
use mod_facetoface_renderer;
use renderer_base;

defined('MOODLE_INTERNAL') || die();

/**
 * The seminar dashboard.
 */
final class seminarevent_dashboard implements templatable {
    /** The name of the mustache template */
    public const TEMPLATE_NAME = 'mod_facetoface/seminarevent_dashboard';

    private const TABLE_UPCOMING = 0;
    private const TABLE_PAST = 1;

    /** @var seminar */
    private $seminar;

    /** @var context */
    private $context;

    /** @var stdClass */
    private $cm;

    /** @var stdClass */
    private $course;

    /** @var filter_list */
    private $filters;

    /** @var seminarevent_dashboard_sessions[] */
    private $tables;

    /**
     * Private constructor to enfoce the factory pattern.
     *
     * @param seminar $seminar
     * @param context $context
     * @param stdClass $cm
     * @param stdClass $course
     * @param filter_list $filters
     * @param boolean $debug set true to display debugging information
     */
    private function __construct(seminar $seminar, context $context, stdClass $cm, stdClass $course, filter_list $filters, bool $debug) {
        $this->seminar = $seminar;
        $this->context = $context;
        $this->cm = $cm;
        $this->course = $course;
        $this->filters = $filters;
        $this->tables = [
            self::TABLE_UPCOMING => seminarevent_dashboard_sessions::create($seminar, $filters, $context, 'upcoming', $debug),
            self::TABLE_PAST => seminarevent_dashboard_sessions::create($seminar, $filters, $context, 'past', $debug)
        ];
    }

    /**
     * Create an instance.
     *
     * @param seminar $seminar
     * @param context $context
     * @param stdClass $cm
     * @param stdClass $course
     * @param filter_list $filters
     * @param boolean $debug set true to display debugging information
     * @return self
     */
    public static function create(seminar $seminar, context $context, stdClass $cm, stdClass $course, filter_list $filters, bool $debug = false): self {
        return new self($seminar, $context, $cm, $course, $filters, $debug);
    }

    /**
     * Export data for template.
     *
     * @param renderer_base $output a renderer instance that is compatible with mod_facetoface_renderer
     * @return array of [title, intro, filters, actions, tables, selfcompletionform, selfapprovalnotice, attendeesexportform, declareinterest]
     *                  - title: the page title
     *                  - intro: raw HTML string of the seminar description
     *                  - actions: template data for seminarevent_actionbar
     *                  - filters: template data for seminarevent_filterbar
     *                  - tables: an array of template data for seminarevent_dashboard_sessions
     *                  - selfcompletionform: raw HTML string returned by self_completion_form()
     *                  - selfapprovalnotice: raw HTML string echoed by mod_facetoface_renderer::selfapproval_notice()
     *                  - attendeesexportform: raw HTML string echoed by mod_facetoface_renderer::attendees_export_form()
     *                  - declareinterest: raw HTML string echoed by mod_facetoface_renderer::declare_interest()
     */
    public function export_for_template(renderer_base $output) {
        $data = array();

        $data['title'] = format_string($this->seminar->get_name());
        $data['actions'] = self::get_action_bar_data($this->seminar, $this->context);
        $data['selfcompletionform']  = self_completion_form($this->cm, $this->course);

        if ($this->seminar->get_intro() !== '') {
            $data['intro'] = format_module_intro('facetoface', $this->seminar->get_properties(), $this->cm->id);
        }

        // Display a warning about previously mismatched self approval sessions.
        if ($output instanceof mod_facetoface_renderer) {
            ob_start();
            $output->selfapproval_notice($this->seminar->get_id());
            $data['selfapprovalnotice'] = ob_get_contents();
            ob_end_clean();
        }

        $hassessions = $this->seminar->has_events();

        // only print the filter bar if this seminar has events
        if ($hassessions) {
            $data['filters'] = self::get_filter_bar_data($this->seminar, $this->context, $this->filters);
        }

        // only print the export form if this seminar has events
        if ($hassessions && $output instanceof mod_facetoface_renderer) {
            ob_start();
            $output->attendees_export_form($this->seminar);
            $data['attendeesexportform'] = ob_get_contents();
            ob_end_clean();
        }

        if ($output instanceof mod_facetoface_renderer) {
            $data['declareinterest'] = $output->declare_interest($this->seminar, true);
        }

        $data['tables'] = array_map(function (seminarevent_dashboard_sessions $template) {
            return $template->get_template_data();
        }, $this->tables);

        $data['tables'][self::TABLE_UPCOMING]['heading'] = get_string('upcomingsessions', 'mod_facetoface');
        $data['tables'][self::TABLE_PAST]['heading'] = get_string('previoussessions', 'mod_facetoface');

        return $data;
    }

    /**
     * Get the template data of the action bar.
     *
     * @param seminar $seminar
     * @param context $context
     * @return array
     */
    private static function get_action_bar_data(seminar $seminar, context $context): array {
        $editevents = has_capability('mod/facetoface:editevents', $context);

        if ($editevents) {
            $actionbar = seminarevent_actionbar::builder()
                ->set_align('far')
                ->set_class('dashboard')
                ->add_commandlink(
                    'addevent',
                    new moodle_url(
                        'events/add.php',
                        array('f' => $seminar->get_id(), 'backtoallsessions' => 1)
                    ),
                    get_string('addsession', 'mod_facetoface')
                );

            return $actionbar->build()->get_template_data();
        }

        return array();
    }

    /**
     * Get the template data of the filter bar.
     *
     * @param seminar $seminar
     * @param context $context
     * @param filter_list $filters
     * @param integer|null $user
     * @return array
     */
    private static function get_filter_bar_data(seminar $seminar, context $context, filter_list $filters, int $user = null): array {
        $id = html_writer::random_id('id-');
        $filterbar = $filters->to_filterbar_builder($seminar, $id, $context, $user)
            ->set_icon(new \core\output\flex_icon('mod_facetoface|filters'))
            ->set_toggle_button(get_string('filtertoggle:closed', 'mod_facetoface'), get_string('filtertoggle:open', 'mod_facetoface'));

        // Always add the "Reset" link
        $filterbar->add_link('Reset', '?f=' . $seminar->get_id());
        return $filterbar->build()->get_template_data();
    }
}
