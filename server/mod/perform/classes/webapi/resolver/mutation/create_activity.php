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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\webapi\resolver\mutation;

use container_perform\create_exception;
use container_perform\perform as perform_container;
use core\webapi\execution_context;
use core\webapi\mutation_resolver;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\activity_type;
use mod_perform\models\activity\section;
use mod_perform\models\activity\track;

class create_activity extends mutation_resolver {
    /**
     * {@inheritdoc}
     */
    public static function resolve(array $args, execution_context $ec) {
        global $DB;

        if (!activity::can_create()) {
            throw new create_exception(get_string('error_create_permission_missing', 'mod_perform'));
        }

        if (empty($args['name'])) {
            throw new create_exception(get_string('error_activity_name_missing', 'mod_perform'));
        }

        $type = $args['type'] ?? null;
        if (!$type) {
            throw new create_exception(get_string('error_activity_type_missing', 'mod_perform'));
        }
        $type_model = activity_type::load_by_id($type);
        if (!$type_model) {
            throw new create_exception(get_string('error_activity_type_unknown', 'mod_perform'));
        }

        $courseinfo = new \stdClass();
        $courseinfo->fullname = $args['name'];
        $courseinfo->description = $args['description'] ?? null;
        $courseinfo->category = perform_container::get_default_category_id();

        /** @var activity $activity */
        $activity =  $DB->transaction(function () use ($courseinfo, $args, $type_model) {
            $container = perform_container::create($courseinfo);

            // Create a performance activity inside the new performance container.
            $name = $args['name'];
            $description = $args['description'] ?? null;

            /** @var perform_container $container */
            $activity = activity::create($container, $name, $type_model, $description);

            // Create the first track for the entity.
            track::create($activity);

            // Create the first section for the entity.
            section::create($activity);

            return $activity->refresh();
        });

        $ec->set_relevant_context($activity->get_context());

        return ['activity' => $activity];
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
}