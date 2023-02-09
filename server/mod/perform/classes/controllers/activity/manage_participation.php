<?php
/*
 * This file is part of Totara Perform
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\controllers\activity;

use context;
use context_coursecat;
use context_system;
use core\output\notification;
use mod_perform\controllers\perform_controller;
use mod_perform\models\activity\activity;
use mod_perform\rb\display\participant_instance_manage_participation_actions;
use mod_perform\rb\display\participant_section_manage_participation_actions;
use mod_perform\rb\display\subject_instance_manage_participation_actions;
use mod_perform\util;
use mod_perform\views\embedded_report_view;
use mod_perform\views\override_nav_breadcrumbs;
use moodle_url;
use totara_mvc\renders_components;
use totara_mvc\view;
use totara_mvc\has_report;
use totara_tui\output\component;

class manage_participation extends perform_controller {
    use manage_participation_tabs;
    use has_report;
    use renders_components;

    /**
     * @var activity $activity
     */
    private $activity;
    /**
     * @var string
     */
    private $current_tab;

    public function setup_context(): context {
        $activity_id = $this->get_optional_param('activity_id', null, PARAM_INT);
        if ($activity_id !== null) {
            $this->activity = activity::load_by_id($activity_id);
            return $this->activity->get_context();
        } else {
            $category_id = util::get_default_category_id();
            return context_coursecat::instance($category_id);
        }
    }

    public function action() {
        return $this->action_subjects();
    }

    public function action_subjects() {
        $this->current_tab = 'subject_instances';
        return $this->render_page('perform_manage_participation_subject_instance');
    }

    public function action_participants() {
        $this->current_tab = 'participant_instances';
        return $this->render_page('perform_manage_participation_participant_instance');
    }

    public function action_sections() {
        $this->current_tab = 'participant_sections';
        return $this->render_page('perform_manage_participation_participant_section');
    }

    public static function get_base_url(): string {
        return '/mod/perform/manage/participation/';
    }

    private function render_page(string $report_name) {
        if ($this->activity === null) {
            return $this->empty_activity_view();
        }

        $url = static::get_base_url() . $this->current_tab . '.php';
        $this->set_url(new moodle_url($url, ['activity_id' => $this->activity->id]));

        $data = $this->get_common_data();
        $debug = $this->get_optional_param('debug', 0, PARAM_INT);

        /** @var \reportbuilder $report */
        $report = $this->load_embedded_report($report_name, [
            'activity_id' => $this->activity->id,
        ]);

        $new_heading = get_string("manage_participation_{$this->current_tab}_number_shown",
            'mod_perform', format_string($this->activity->name)
        );

        /** @var embedded_report_view $report_view */
        $report_view = embedded_report_view::create_from_report($report, $debug, 'mod_perform/participation')
            ->add_override(new override_nav_breadcrumbs())
            ->set_title($new_heading);
        $report_view->set_additional_data($data);
        $report_renderer = $report_view->get_page()->get_renderer('totara_reportbuilder');

        // We want to replace the default report heading but want to keep any reporting amd, etc.
        // Thus making use of the existing report_heading template
        $rendered_heading = $report_renderer->render_from_template(
            'totara_reportbuilder/report_heading',
            [
                'reportid' => $report->get_id(),
                'heading' => $new_heading,
                'fullname' => $report->fullname,
            ]
        );

        $report_view->set_report_heading($rendered_heading);

        return $report_view;
    }

    /**
     * @return array
     */
    private function get_common_data(): array {
        $backurl = has_capability('moodle/site:config', context_system::instance())
            ? new moodle_url('/mod/perform/manage/activity/index.php')
            : false;

        return [
            'backurl' => $backurl,
            'banner' => $this->get_info_card_component(),
            'close_all_instances_button' => $this->get_close_all_instances_button(),
            'tabs' => self::get_participation_tabs($this->activity->id, $this->current_tab),
            'page_heading' => get_string('manage_participation_heading', 'mod_perform', format_string($this->activity->name)),
            'toasts' => $this->get_toasts_component(),
        ];
    }

    /**
     * @return view
     */
    private function empty_activity_view(): view {
        $url = new moodle_url('/mod/perform/manage/activity/index.php');
        $this->set_url(static::get_url());
        return self::create_view('mod_perform/no_report', [
            'content' => view::core_renderer()->notification(
                get_string('report_activity_warning_message', 'mod_perform', (object) ['url' => $url->out(true)]),
                notification::NOTIFY_WARNING
            )
        ]);
    }

    /**
     * Get the rendered subject instance or participant instance info card if appropriate.
     *
     * @return string The rendered info card component
     */
    private function get_info_card_component(): string {
        $card_component = null;

        if ($this->should_show_subject_instance_card()) {
            $card_component = $this->get_subject_instance_card();
        } else if ($this->should_show_participant_instance_card()) {
            $card_component = $this->get_participant_instance_card();
        }

        if ($card_component !== null) {
            return $this->render_component($card_component);
        }

        return '';
    }

    private function get_toasts_component(): string {
        $message = null;

        $participants_created_count = $this->get_optional_param('participant_instance_created_count', false, PARAM_INT);
        $is_opened = $this->get_optional_param('is_open', false, PARAM_BOOL);
        $report_type = $this->get_optional_param('report_type', false, PARAM_TEXT);
        $close_all_instances_success = $this->get_optional_param('close_all_instances_success', false, PARAM_BOOL);
        $is_deleted = $this->get_optional_param('is_deleted', false, PARAM_BOOL);

        if (!$report_type && !$close_all_instances_success && $participants_created_count < 1) {
            return '';
        }

        if ($report_type) {
            $lang_str = null;
            switch ($report_type) {
                case subject_instance_manage_participation_actions::SUBJECT_INSTANCE_REPORT_TYPE:
                    if ($is_deleted) {
                        $lang_str = 'subject_instance_delete_confirmation';
                    } else {
                        $lang_str = $is_opened ? 'subject_instance_reopen_confirmation' : 'subject_instance_closed_confirmation';
                    }
                    break;
                case participant_instance_manage_participation_actions::PARTICIPANT_INSTANCE_REPORT_TYPE:
                    if ($is_deleted) {
                        $lang_str = 'participant_instances_delete_confirmation';
                    } else {
                        $lang_str =
                            $is_opened ? 'participant_instances_reopen_confirmation' : 'participant_instances_close_confirmation';
                    }
                    break;
                case participant_section_manage_participation_actions::PARTICIPANT_SECTION_REPORT_TYPE:
                    $lang_str = $is_opened ? 'participant_section_reopen_confirmation' : 'participant_section_close_confirmation';
                    break;
                default:
                    return '';
            }
            $message = get_string($lang_str, 'mod_perform');
        } else if ($participants_created_count === 1) {
            $message = get_string('participant_instances_manually_added_toast_singular', 'mod_perform');
        } else if ($participants_created_count > 1) {
            $message = get_string('participant_instances_manually_added_toast', 'mod_perform', $participants_created_count);
        } else if ($close_all_instances_success) {
            $message = get_string('toast_success_close_all_open_instances', 'mod_perform');
        }

        return $this->get_rendered_component(
            'mod_perform/components/manage_activity/participation/Toasts',
            ['message' => $message]
        );
    }

    private function should_show_subject_instance_card(): bool {
        $subject_instance_id = $this->get_optional_param('subject_instance_id', null, PARAM_INT);

        return $this->current_tab === 'participant_instances' && $subject_instance_id !== null;
    }

    private function should_show_participant_instance_card(): bool {
        $participant_instance_id = $this->get_optional_param('participant_instance_id', null, PARAM_INT);

        return $this->current_tab === 'participant_sections' && $participant_instance_id !== null;
    }

    private function get_subject_instance_card(): component {
        $subject_instance_id = $this->get_optional_param('subject_instance_id', null, PARAM_INT);

        $show_all_link = $this->url;
        $show_all_link->params(['activity_id' => $this->activity->id]);

        return new component('mod_perform/components/manage_activity/participation/SubjectInstanceInfoCard',
            [
                'subject-instance-id' => $subject_instance_id,
                'show-all-link' => $show_all_link->out(false),
            ]
        );
    }

    private function get_participant_instance_card(): component {
        $participant_instance_id = $this->get_optional_param('participant_instance_id', null, PARAM_INT);

        $show_all_link = $this->url;
        $show_all_link->params(['activity_id' => $this->activity->id]);

        return new component('mod_perform/components/manage_activity/participation/ParticipantInstanceInfoCard',
            [
                'participant-instance-id' => $participant_instance_id,
                'show-all-link' => $show_all_link->out(false),
            ]
        );
    }

    private function get_close_all_instances_button(): string {
        $activity_id = $this->get_optional_param('activity_id', null, PARAM_INT);

        if ($activity_id === null) {
            return '';
        }

        return $this->render_component(
            new component('mod_perform/components/report/manage_participation/CloseAllInstancesButton',
                [
                    'activity-id' => $activity_id,
                ]
            )
        );
    }
}
