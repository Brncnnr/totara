<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @package container_workspace
 */
namespace container_workspace\formatter\file;

use container_workspace\file\file;
use core\webapi\formatter\field\string_field_formatter;
use core\webapi\formatter\formatter;

final class extension_option_formatter extends formatter {
    /**
     * source_option_formatter constructor.
     * @param string $value
     * @param \context|null $context
     */
    public function __construct(string $value, ?\context $context = null) {
        if (null === $context) {
            $context = \context_system ::instance();
        }

        $record = new \stdClass();

        $record->value = $value;
        $record->label = strtoupper($value);

        parent::__construct($record, $context);
    }

    /**
     * @return array
     */
    protected function get_map(): array {
        return [
            'value' => null,
            'label' => string_field_formatter::class
        ];
    }
}