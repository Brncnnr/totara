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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_notification
 */
namespace totara_notification\event;

use coding_exception;
use core\event\base;
use moodle_url;
use totara_notification\entity\notification_preference as entity;

/**
 * Class base_notification_preference
 * @package totara_notification\event
 */
abstract class base_notification_preference_event extends base {
    /**
     * @return void
     */
    protected function init(): void {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = entity::TABLE;
    }

    /**
     * @return void
     */
    protected function validate_data(): void {
        $other_data = $this->other;
        if (!array_key_exists('extended_context', $other_data) || !is_array($other_data['extended_context'])) {
            throw new coding_exception("The event's data is missing extended context data");
        }

        $extended_context = $other_data['extended_context'];
        $required_fields = ['component', 'area', 'item_id'];

        foreach ($required_fields as $field) {
            if (!array_key_exists($field, $extended_context)) {
                throw new coding_exception("The event's extended context data is missing field '{$field}'");
            }
        }

        parent::validate_data();
    }

    /**
     * @return moodle_url
     */
    public function get_url(): moodle_url {
        if (CONTEXT_SYSTEM === $this->contextlevel) {
            return new moodle_url('/totara/notification/notifications.php');
        }

        $extended_context = $this->other['extended_context'];
        return new moodle_url(
            '/totara/notification/context_notifications.php',
            [
                'context_id' => $this->contextid,
                'item_id' => $extended_context['item_id'],
                'component' => $extended_context['component'],
                'area' => $extended_context['area']
            ]
        );
    }
}