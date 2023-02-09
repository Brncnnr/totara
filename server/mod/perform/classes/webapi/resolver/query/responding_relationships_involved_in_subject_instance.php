<?php
/*
 *
 * This file is part of Totara LMS
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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\webapi\resolver\query;

use core\collection;
use core\entity\user;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\query_resolver;
use mod_perform\entity\activity\subject_instance;
use mod_perform\util;
use mod_perform\webapi\middleware\require_activity;
use moodle_exception;
use totara_core\relationship\relationship;
use mod_perform\entity\activity\activity;

/**
 * Query to return relationships involved in a subject instance.
 */
class responding_relationships_involved_in_subject_instance extends query_resolver {

    /**
     * @param array $args
     * @param execution_context $ec
     * @return collection|relationship[]
     */
    public static function resolve(array $args, execution_context $ec) {
        $subject_instance_id = $args['subject_instance_id'];

        /** @var subject_instance $subject_instance_entity */
        $subject_instance_entity = subject_instance::repository()->find_or_fail($subject_instance_id);

        if (!util::can_report_on_user($subject_instance_entity->subject_user_id, user::logged_in()->id)) {
            throw new moodle_exception('can_not_view_relationships', 'mod_perform');
        }

        return activity::repository()->get_responding_relationships($args['activity']->id)
            ->map_to(relationship::class);
    }

    /**
     * {@inheritdoc}
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('performance_activities'),
            require_activity::by_subject_instance_ids('subject_instance_id', true),
        ];
    }

}
