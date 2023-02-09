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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\webapi\resolver\query;

use core\entity\user;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use mod_perform\data_providers\response\view_only_section_with_responses;
use mod_perform\entity\activity\section;
use mod_perform\entity\activity\subject_instance;
use mod_perform\models\activity\activity as activity_model;
use mod_perform\util;

class view_only_section_responses extends query_resolver {
    /**
     * {@inheritdoc}
     */
    public static function resolve(array $args, execution_context $ec) {
        $section_id = $args['section_id'] ?? null; // Optional, if not supplied we default to the first section.
        $subject_instance_id = $args['subject_instance_id'];

        $viewing_user = user::logged_in();

        $section_entity = self::find_section_entity($subject_instance_id, $section_id);

        /** @var subject_instance $subject_instance_entity */
        $subject_instance_entity = subject_instance::repository()->find($subject_instance_id);

        if ($section_entity === null || $subject_instance_entity === null) {
            return null;
        }

        if (!util::can_report_on_user($subject_instance_entity->subject_user_id, $viewing_user->id)) {
            return null;
        }

        $activity = new activity_model($section_entity->activity);
        $ec->set_relevant_context($activity->get_context());

        $data_provider = new view_only_section_with_responses($section_entity, $subject_instance_entity);

        return $data_provider->fetch()->get();
    }

    /**
     * {@inheritdoc}
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('performance_activities'),
            new require_login()
        ];
    }

    private static function find_section_entity(int $subject_instance_id, ?int $section_id): ?section {
        if ($section_id === null) {
            return section::repository()->find_first_for_subject_instance($subject_instance_id);
        }

        return section::repository()->find($section_id);
    }
}