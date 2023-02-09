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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\webapi\resolver\mutation;

use core\entity\user;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\mutation_resolver;
use mod_perform\models\response\participant_section;
use mod_perform\state\participant_section\closed;
use mod_perform\state\participant_section\open;
use mod_perform\util;
use mod_perform\webapi\middleware\require_activity;

class manually_change_participant_section extends mutation_resolver {
    /**
     * {@inheritdoc}
     */
    public static function resolve(array $args, execution_context $ec) {
        $input = $args['input'];

        $participant_section = participant_section::load_by_id($input['participant_section_id']);

        $manager_id = user::logged_in()->id;
        $subject_user_id = $participant_section->participant_instance->subject_instance->subject_user_id;
        if (!util::can_manage_participation($manager_id, $subject_user_id)) {
            throw new \coding_exception('You do not have permission to manage participation of the subject');
        }

        switch ($input['availability']) {
            case open::get_name():
                $participant_section->manually_open();
                break;
            case closed::get_name():
                $participant_section->manually_close();
                break;
        }

        return [
            'participant_section' => $participant_section,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('performance_activities'),
            require_activity::by_participant_section_id('input.participant_section_id', true)
        ];
    }
}