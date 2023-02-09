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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\webapi\resolver\query;

use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\query_resolver;
use mod_perform\models\activity\notification as notification_model;
use mod_perform\webapi\middleware\require_activity;
use mod_perform\webapi\middleware\require_manage_capability;

/**
 * Class notifications
 *
 * @deprecated Since Totara 17.0
 */
class notifications extends query_resolver {
    /**
     * {@inheritdoc}
     * @deprecated Since Totara 17.0
     */
    public static function resolve(array $args, execution_context $ec) {
        debugging('mod_perform\webapi\resolver\query\notifications::resolve has been deprecated', DEBUG_DEVELOPER);
        $activity = $args['activity'];

        return notification_model::load_all_by_activity($activity);
    }

    /**
     * {@inheritdoc}
     * @deprecated Since Totara 17.0
     */
    public static function get_middleware(): array {
        debugging('mod_perform\webapi\resolver\query\notifications::get_middleware has been deprecated', DEBUG_DEVELOPER);
        return [
            new require_advanced_feature('performance_activities'),
            require_activity::by_activity_id('activity_id', true),
            require_manage_capability::class
        ];
    }
}
