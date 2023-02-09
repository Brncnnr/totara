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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\model\application\activity;

use mod_approval\model\application\application_activity;
use totara_notification\recipient\recipient;
use totara_notification\resolver\notifiable_event_resolver;

/**
 * Type 5: notification_sent.
 */
final class notification_sent extends activity {
    /**
     * @param application_activity $activity
     */
    protected function __construct(application_activity $activity) {
        $info = $activity->activity_info_parsed;

        /** @var notifiable_event_resolver $resolver */
        $resolver = $info['resolver_class_name'];

        /** @var recipient $recipient */
        $recipient = $info['recipient_class_name'];

        $this->by_system(
            'model_application_activity_type_notification_sent_desc',
            [
                'resolver_title' => $resolver::get_notification_title(),
                'recipient_name' => $recipient::get_name(),
            ]
        );
    }

    public static function get_type(): int {
        return 5;
    }

    protected static function get_label_key(): string {
        return 'model_application_activity_type_notification_sent';
    }

    public static function is_valid_activity_info(array $info): bool {
        if (empty($info)) {
            return false;
        }

        if (empty($info['resolver_class_name']) ||
            !is_subclass_of($info['resolver_class_name'], notifiable_event_resolver::class)) {
            return false;
        }

        if (empty($info['recipient_class_name']) ||
            !is_subclass_of($info['recipient_class_name'], recipient::class)) {
            return false;
        }

        return true;
    }
}
