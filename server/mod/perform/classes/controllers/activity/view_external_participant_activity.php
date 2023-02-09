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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\controllers\activity;

use coding_exception;
use context;
use Exception;
use invalid_parameter_exception;
use mod_perform\controllers\perform_controller;
use mod_perform\models\activity\helpers\external_participant_token_validator;
use moodle_exception;
use totara_core\advanced_feature;
use totara_mvc\tui_view;

/*
 * This page shows a activity form to an external user if the token supplied is valid
 */
class view_external_participant_activity extends perform_controller {

    protected $require_login = false;

    protected $layout = 'external';

    /**
     * @inheritDoc
     */
    protected function setup_context(): context {
        return \context_system::instance();
    }

    protected function authorize(): void {
        // No extra authorization on this one
    }

    /**
     * @return tui_view
     * @throws invalid_parameter_exception
     */
    public function action(): tui_view {
        advanced_feature::require('performance_activities');

        try {
            $success = $this->get_optional_param('success', null, PARAM_BOOL);
            $token = $this->get_optional_param('token', null, PARAM_ALPHANUM);
            if (empty($token)) {
                throw new coding_exception('Invalid token');
            }

            $validator = new external_participant_token_validator($token);
            if (!$validator->is_valid()) {
                throw new coding_exception('Invalid token!');
            }

            // Show success message
            if ($success) {
                return $this->action_success($token, $validator->is_subject_instance_closed());
            }

            if ($validator->is_subject_instance_closed()) {
                throw new coding_exception('Subject instance is closed.');
            }

            $participant_instance = $validator->get_participant_instance();

            if ($participant_instance->subject_instance->should_be_hidden()) {
                throw new moodle_exception('invalid_activity', 'mod_perform');
            }

            $participant_section_id = $this->get_optional_param('participant_section_id', null, PARAM_INT);
            if ($participant_section_id > 0 && !$validator->is_valid_for_section($participant_section_id)) {
                throw new coding_exception('Invalid token');
            }
        } catch (Exception $e) {
            return $this->action_invalid_token();
        }

        $props = [
            'token' => $token,
            'subject-instance-id' => (int) $participant_instance->subject_instance_id,
            'participant-instance-id' => (int) $participant_instance->id,
            'participant-section-id' => (int) $participant_section_id,
        ];
        $url_args = ['token' => $token];
        if ($participant_section_id) {
            $url_args['participant_section_id'] = $participant_section_id;
        }

        $url = self::get_url($url_args);
        $this->set_url($url);

        return self::create_tui_view('mod_perform/pages/UserActivity', $props)
            ->set_title(get_string('user_activities_page_title', 'mod_perform'));
    }

    /**
     * Show error message that token is not valid
     * @return tui_view
     */
    private function action_invalid_token(): tui_view {
        $this->set_url(self::get_url([]));

        return self::create_tui_view('mod_perform/pages/ExternalUserActivityInvalid', [])
            ->set_title(get_string('user_activities_page_title', 'mod_perform'));
    }

    /**
     * Show a success message
     *
     * @param string $token
     * @param bool $is_closed
     * @return tui_view
     */
    private function action_success(string $token, bool $is_closed): tui_view {
        $this->set_url(self::get_url(['success' => 1, 'token' => $token]));

        $props = [
            'is-closed' => $is_closed,
            'review-url' => self::get_url(['token' => $token])->out(false)
        ];

        return self::create_tui_view('mod_perform/pages/ExternalUserActivitySuccess', $props)
            ->set_title(get_string('user_activities_page_title', 'mod_perform'));
    }

    /**
     * @return string
     */
    public static function get_base_url(): string {
        return '/mod/perform/activity/external.php';
    }

}