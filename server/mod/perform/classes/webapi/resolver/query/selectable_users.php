<?php
/**
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\webapi\resolver\query;

use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\query_resolver;
use mod_perform\data_providers\activity\selectable_users as selectable_users_provider;
use mod_perform\models\activity\subject_instance;
use mod_perform\webapi\middleware\require_activity;
use moodle_exception;

/**
 * Get the users that the current user can see and can select.
 *
 * @package mod_perform\webapi\resolver\query
 */
class selectable_users extends query_resolver {

    /**
     * {@inheritdoc}
     */
    public static function resolve(array $args, execution_context $ec) {
        $subject_instance_id = $args['subject_instance_id'];
        $subject_instance = subject_instance::load_by_id($subject_instance_id);

        if ($subject_instance->should_be_hidden()) {
            throw new moodle_exception('invalid_activity', 'mod_perform');
        }

        return (new selectable_users_provider($subject_instance))
            ->add_filters($args['filters'] ?? [])
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('performance_activities'),
            require_activity::by_subject_instance_id('subject_instance_id', true),
        ];
    }

}
