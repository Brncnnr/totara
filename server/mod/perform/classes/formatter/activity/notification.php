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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\formatter\activity;

use core\orm\formatter\entity_model_formatter;
use core\webapi\formatter\field\string_field_formatter;
use mod_perform\notification\trigger;

defined('MOODLE_INTERNAL') || die();

/**
 * Maps the notification model class into a GraphQL mod_perform_notification.
 */
class notification extends entity_model_formatter {
    private const TRIGGER_TYPES = [
        trigger::TYPE_BEFORE => 'BEFORE',
        trigger::TYPE_AFTER => 'AFTER'
    ];

    /**
     * {@inheritdoc}
     */
    protected function get_map(): array {
        return [
            'id' => null,
            'name' => string_field_formatter::class,
            'active' => null,
            'class_key' => null,
            'trigger_type' => 'format_trigger_type',
            'trigger_label' => string_field_formatter::class,
            'recipients' => null,
            'triggers' => null,
        ];
    }

    /**
     * Return trigger_type.
     *
     * @return string|null the trigger type, or null if event triggers are not supported
     */
    protected function format_trigger_type(): ?string {
        return self::TRIGGER_TYPES[$this->object->trigger_type] ?? null;
    }
}