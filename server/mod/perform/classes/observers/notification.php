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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\observers;

use core\event\base;
use mod_perform\entity\activity\subject_instance as subject_instance_entity;
use mod_perform\event\activity_created;
use mod_perform\models\activity\notification as notification_model;
use mod_perform\models\activity\subject_instance as subject_instance_model;
use totara_notification\external_helper;
use mod_perform\totara_notification\resolver\participant_completion_resolver;

class notification {

    /**
     * Create the default set of notifications for an activity.
     *
     * @param activity_created|base $event
     */
    public static function create_notifications(base $event): void {
        notification_model::create_all_for_activity($event->objectid);
    }

    /**
     * @param base $event
     * @return void
     */
    public static function send_completion_notification(base $event) {
        $entity = new subject_instance_entity($event->objectid);
        $inst = subject_instance_model::load_by_entity($entity);

        // Trigger the centralised notification messages.
        if ($inst->is_complete()) {
            $data = [
                'activity_id' => $inst->get_activity()->get_id(),
                'subject_instance_id' => $inst->get_id(),
                'subject_user_id' => $inst->get_subject_user()->id,
            ];

            external_helper::create_notifiable_event_queue(new participant_completion_resolver($data));
        }
    }
}
