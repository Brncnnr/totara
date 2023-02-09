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
 * @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\formatter\application;

use core\format;
use core\orm\formatter\entity_model_formatter;
use core\webapi\formatter\field\date_field_formatter;
use core\webapi\formatter\field\string_field_formatter;
use mod_approval\model\application\action\action;
use mod_approval\model\application\application_action;

/**
 * Format application_action.
 */
final class application_action_formatter extends entity_model_formatter {
    protected function get_map(): array {
        return [
            'id' => null,
            'label' => 'format_action_label',
            'user' => null,
            'created' => date_field_formatter::class,
        ];
    }

    protected function has_field(string $field): bool {
        if ($field == 'label') {
            return true;
        }

        return parent::has_field($field);
    }

    protected function get_field(string $field) {
        if ($field == 'label') {
            /** @var application_action $action */
            $action = $this->object;
            return $action->code;
        }

        return parent::get_field($field);
    }

    /**
     * @param int $code
     * @param string|null $format
     * @return string
     */
    protected function format_action_label(int $code, ?string $format): string {
        $format = $format ?? format::FORMAT_RAW;
        $formatter = new string_field_formatter($format, $this->context);
        $action = action::from_code($code);
        $label = $action::get_label()->out();
        return $formatter->format($label);
    }
}
