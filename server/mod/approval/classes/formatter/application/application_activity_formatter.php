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

namespace mod_approval\formatter\application;

use core\entity\user;
use core\orm\formatter\entity_model_formatter;
use core\webapi\formatter\field\date_field_formatter;
use core\webapi\formatter\field\string_field_formatter;
use mod_approval\model\application\activity\activity;
use mod_approval\model\application\application_activity;

/**
 * Format application_activity.
 *
 * @property application_activity object
 */
final class application_activity_formatter extends entity_model_formatter {
    protected function get_map(): array {
        return [
            'id' => null,
            'user' => 'format_user',
            'timestamp' => date_field_formatter::class,
            'activity_type_name' => null,
            'activity_type' => null,
            'description' => null,
            'stage' => null,
            'approval_level' => null,
        ];
    }

    protected function has_field(string $field): bool {
        return array_key_exists($field, $this->get_map());
    }

    public function format(string $field, ?string $format = null) {
        if ($field === 'activity_type_name') {
            return $this->format_activity_type_name($this->object->activity_type, $format);
        }
        if ($field === 'activity_type') {
            return $this->object->activity_type;
        }
        if ($field === 'description') {
            $activity = activity::from_activity($this->object);
            $formatter = new string_field_formatter($format, $this->context);
            return $formatter->format($activity->get_description());
        }
        return parent::format($field, $format);
    }

    /**
     * @param user|null $user
     * @param string|null $format
     * @return user|null
     */
    protected function format_user(?user $user, ?string $format): ?user {
        $activity = activity::from_activity($this->object);
        if ($activity->from_system() && $activity->for_system()) {
            return null;
        }
        return $user;
    }

    /**
     * @param integer $activity_type
     * @param string|null $format
     * @return string
     */
    protected function format_activity_type_name(int $activity_type, ?string $format): string {
        $formatter = new string_field_formatter($format, $this->context);
        $label = activity::label($activity_type);
        return $formatter->format($label);
    }
}
