<?php
/**
 *This file is part of Totara Learn
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package message_popup
 */

namespace message_popup\webapi\formatter;

use core\webapi\formatter\formatter;
use core\webapi\formatter\field\date_field_formatter;
use core\webapi\formatter\field\string_field_formatter;
use core\webapi\formatter\field\text_field_formatter;

class message_formatter extends formatter {

    protected function get_map(): array {
        return [
            'id' => null,
            'subject' => string_field_formatter::class,
            'fullmessage' => string_field_formatter::class,
            'fullmessagehtml' => function ($value, text_field_formatter $formatter) {
                $component = 'message';
                $filearea = 'fullmessage';
                $itemid = $this->object->id;

                return $formatter
                    ->set_pluginfile_url_options($this->context, $component, $filearea, $itemid)
                    ->format($value);
            },
            'fullmessageformat' => null,
            'contexturl' => string_field_formatter::class,
            'timecreated' => date_field_formatter::class,
            'isread' => null
        ];
    }
}
