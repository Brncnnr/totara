<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_approval
 */
namespace mod_approval\totara_notification\resolver;

use core\orm\query\builder;
use lang_string;
use mod_approval\entity\application\application_activity as application_activity_entity;
use mod_approval\model\application\activity\stage_all_approved as stage_all_approved_activity;
use mod_approval\totara_notification\placeholder\workflow_stage as workflow_stage_placeholder_group;
use moodle_recordset;
use totara_notification\placeholder\placeholder_option;
use totara_notification\resolver\abstraction\scheduled_event_resolver;

class stage_all_approved extends stage_base implements scheduled_event_resolver {

    /**
     * @inheritDoc
     */
    public static function get_notification_available_placeholder_options(): array {
        $options = parent::get_notification_available_placeholder_options();
        $options[] = placeholder_option::create(
            'workflow_stage',
            workflow_stage_placeholder_group::class,
            new lang_string('notification:placeholder_group_stage_all_approved', 'mod_approval'),
            function (array $event_data): workflow_stage_placeholder_group {
                return workflow_stage_placeholder_group::from_id($event_data['workflow_stage_id']);
            }
        );
        return $options;
    }

    /**
     * @inheritDoc
     */
    public static function get_notification_title(): string {
        return get_string('notification:stage_all_approved_resolver_title', 'mod_approval');
    }

    /**
     * @return int
     */
    public function get_fixed_event_time(): int {
        return $this->event_data['time_all_approved'];
    }

    /**
     * @inheritDoc
     */
    public static function get_scheduled_events(int $min_time, int $max_time): moodle_recordset {
        return builder::table(application_activity_entity::TABLE)
            ->select(['application_id', 'workflow_stage_id', 'timestamp AS time_all_approved'])
            ->where('activity_type', stage_all_approved_activity::get_type())
            ->where('timestamp', '>=', $min_time)
            ->where('timestamp', '<', $max_time)
            ->get_lazy();
    }
}