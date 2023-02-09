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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_workspace
 */
namespace container_workspace\formatter\member;

use container_workspace\query\member\sort;
use core\webapi\formatter\formatter;
use core\webapi\formatter\field\string_field_formatter;

/**
 * Formatter for member sort option.
 */
final class sort_option_formatter extends formatter {
    /**
     * member_sort_option_formatter constructor.
     * @param int $member_sort
     * @param \context|null $context
     */
    public function __construct(int $member_sort, ?\context $context = null) {
        if (null === $context) {
            $context = \context_system::instance();
        }

        $record = new \stdClass();
        $record->value = sort::get_code($member_sort);
        $record->label = sort::get_string($member_sort);

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
