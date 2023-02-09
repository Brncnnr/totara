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

use lang_string;
use mod_approval\totara_notification\placeholder\workflow_stage as workflow_stage_placeholder_group;
use totara_comment\totara_notification\placeholder\comment as comment_placeholder_group;
use totara_notification\placeholder\placeholder_option;
use totara_notification\schedule\schedule_on_event;

/**
 * Event data required:
 * - application_id
 * - workflow_stage_id
 * - comment_id
 * - event_time
 */
class stage_comment_created extends stage_base {

    /**
     * @inheritDoc
     */
    public static function get_notification_available_placeholder_options(): array {
        $options = parent::get_notification_available_placeholder_options();
        $options[] = placeholder_option::create(
            'workflow_stage',
            workflow_stage_placeholder_group::class,
            new lang_string('notification:placeholder_group_stage_related', 'mod_approval'),
            function (array $event_data): workflow_stage_placeholder_group {
                return workflow_stage_placeholder_group::from_id($event_data['workflow_stage_id']);
            }
        );
        $options[] = placeholder_option::create(
            'comment',
            comment_placeholder_group::class,
            new lang_string('notification:placeholder_group_comment', 'mod_approval'),
            function (array $event_data): comment_placeholder_group {
                return comment_placeholder_group::from_id($event_data['comment_id']);
            }
        );
        return $options;
    }

    /**
     * @inheritDoc
     */
    public static function get_notification_title(): string {
        return get_string('notification:stage_comment_created_resolver_title', 'mod_approval');
    }

    /**
     * Override stage_base - we only support on-event
     *
     * @inheritDoc
     */
    public static function get_notification_available_schedules(): array {
        return [
            schedule_on_event::class,
        ];
    }

    /**
     * @return int
     */
    public function get_fixed_event_time(): int {
        return $this->event_data['event_time'];
    }
}