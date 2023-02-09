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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package core
 */

namespace core\formatter;


use context;
use core\format;
use core\webapi\formatter\formatter;
use core_user\profile\card_display_field;

class user_card_display_field_formatter extends formatter {
    /**
     * user_card_display_field_formatter constructor.
     * @param card_display_field $source
     * @param context $context
     */
    public function __construct(card_display_field $source, context $context) {
        $record = new \stdClass();

        $record->value = $source->get_field_value();
        $record->label = $source->get_field_label();
        $record->associate_url = $source->get_field_url();
        $record->is_custom = $source->is_custom_field();

        parent::__construct($record, $context);
    }

    /**
     * @return array
     */
    protected function get_map(): array {
        return [
            'value' => function (?string $value, string $format): ?string {
                if (empty($value)) {
                    return null;
                }

                if ($format === format::FORMAT_RAW) {
                    // Format raw - we return as it is.
                    return $value;
                }

                $value = clean_string($value);
                if ($format === format::FORMAT_PLAIN) {
                    // Format plain will run it thru html_to_text
                    return html_to_text($value, 0, false);
                }

                return $value;
            },
            'label' => null,
            'associate_url' => null,
            'is_custom' => null
        ];
    }
}