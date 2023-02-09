<?php
/*
 * This file is part of Totara Learn
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\controllers\activity;

use context;
use context_coursecat;
use core\entity\user;
use mod_perform\controllers\perform_controller;
use mod_perform\controllers\reporting\performance\activity_response_data;
use mod_perform\data_providers\activity\activity_type;
use mod_perform\data_providers\activity\subject_instance_for_participant;
use mod_perform\models\activity\participant_instance;
use mod_perform\models\activity\helpers\manual_participant_helper;
use mod_perform\state\participant_instance\participant_instance_progress;
use mod_perform\state\state_helper;
use mod_perform\state\subject_instance\subject_instance_progress;
use mod_perform\util;
use moodle_url;
use totara_core\relationship\relationship;
use totara_mvc\tui_view;

/*
 * This page lists perform activities the logged in user are a participant in.
 */
class user_activities extends perform_controller {

    /**
     * @inheritDoc
     */
    protected function setup_context(): context {
        $category_id = util::get_default_category_id();
        return context_coursecat::instance($category_id);
    }

    /**
     * @return tui_view
     */
    public function action(): tui_view {
        $this->set_url(self::get_url());

        $current_user_id = user::logged_in()->id;
        [$subject_role_id, $tabs] = $this->get_activity_role_tabs($current_user_id);
        $pending_subject_instances_count = manual_participant_helper::for_user($current_user_id)->get_pending_subject_instances_count();
        $has_pending_selections = ($pending_subject_instances_count > 0);

        $props = [
            'current-user-id' => $current_user_id,
            'activity-role-tabs' => ['tabs' => $tabs],
            'view-activity-url' => (string) view_user_activity::get_url(),
            'print-activity-url' => (string) print_user_activity::get_url(),
            'priority-url' => (string) user_activities_priority::get_url(),
            'view-response-data-url' => (string) (new moodle_url(activity_response_data::URL)),
            'completion-save-success' => (bool) $this->get_optional_param('completion_save_success', false, PARAM_BOOL),
            'closed-on-completion' => (bool) $this->get_optional_param('closed_on_completion', false, PARAM_BOOL),
            'require-manual-participants-count' => $pending_subject_instances_count,
            'require-manual-participants-notification' => $has_pending_selections,
            'can-potentially-manage-participants' => util::can_potentially_manage_participants($current_user_id),
            'can-potentially-report-on-subjects' => util::can_potentially_report_on_subjects($current_user_id),
            'initially-open-tab' => (int)$this->get_optional_param('about_role', $subject_role_id, PARAM_INT),
            'initial-filters' => (object) [
                'sort_by_filter' => $this->get_optional_param('sort_by_filter', null, PARAM_NOTAGS),
                'about_role' => $this->get_optional_param('about_role', null, PARAM_NOTAGS),
                'activity_type' => $this->get_optional_param('activity_type', null, PARAM_NOTAGS),
                'exclude_complete' => $this->get_optional_param('exclude_complete', null, PARAM_NOTAGS),
                'overdue' => $this->get_optional_param('overdue', null, PARAM_NOTAGS),
                'participant_progress' => $this->get_optional_param('participant_progress', null, PARAM_NOTAGS),
                'search_term' => $this->get_optional_param('search_term', null, PARAM_NOTAGS),
            ],
            'is-historic-activities-enabled' => util::is_historic_activities_enabled(),
            'filter-options' => $this->get_filter_options(),
            'sort-options' => $this->get_sort_options(),
        ];

        return self::create_tui_view('mod_perform/pages/UserActivities', $props)
            ->set_title(get_string('user_activities_page_title', 'mod_perform'));
    }

    /**
     * @return string
     */
    public static function get_base_url(): string {
        return '/mod/perform/activity/index.php';
    }

    /**
     * @return array
     */
    private function get_filter_options(): array {
        $activity_types = [];
        foreach ((new activity_type())->get() as $type) {
            $activity_types[$type->id] = $type->display_name;
        }

        /** @var array $progress_options */
        // Order by code
        $progress_names = state_helper::get_all_names('participant_instance', participant_instance_progress::get_type());
        ksort($progress_names);

        $progress_display_names = state_helper::get_all_display_names('participant_instance', subject_instance_progress::get_type());
        ksort($progress_display_names);
        $progress_options = array_combine($progress_names, $progress_display_names);

        return [
            'activityTypes' => $activity_types,
            'progressOptions' => $progress_options,
        ];
    }

    /**
     * @return array
     */
    private function get_activity_role_tabs(int $user_id): array {
        $subject_idnumber = 'subject';
        $subject_role_id = relationship::load_by_idnumber($subject_idnumber)->id;

        $initial = [
            // The FE requires the subject role to be returned even if this
            // participant does not have a subject role.
            [
                'about_others' => false,
                'id' => $subject_role_id,
                'name' => get_string('user_activities_your_activities_title', 'mod_perform')
            ]
        ];

        $exclude_suspended = get_config(null, 'perform_hide_suspended_users');
        $tabs = participant_instance::get_activity_roles_for($user_id, $exclude_suspended)
            ->reduce(
                function (array $roles, relationship $relationship) use ($subject_idnumber): array {
                    if ($relationship->idnumber !== $subject_idnumber) {
                        $roles[] = [
                            'about_others' => true,
                            'id' => $relationship->id,
                            'name' => get_string('user_activities_as_a_role', 'mod_perform', $relationship->name)
                        ];
                    }

                    return $roles;
                },
                $initial
            );

        return [$subject_role_id, $tabs];
    }

    /**
     * @return array
     */
    private function get_sort_options(): array {
        $options = array_map(static function (string $sort_option) {
            return [
                'id' => $sort_option,
                'label' => get_string('user_activities_sort_option_' . $sort_option, 'mod_perform')
            ];
        }, subject_instance_for_participant::$sort_options);

        return ['options' => $options];
    }
}