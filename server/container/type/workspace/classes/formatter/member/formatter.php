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

use container_workspace\member\member;
use core\webapi\formatter\field\date_field_formatter;
use core\webapi\formatter\formatter as base_formatter;

/**
 * Formatter for member
 */
final class formatter extends base_formatter {
    /**
     * member_formatter constructor.
     * @param member    $member
     * @param \context  $context
     */
    public function __construct(member $member, \context $context) {
        $record = new \stdClass();

        $record->workspace_id = $member->get_workspace_id();
        $record->time_created = $member->get_time_created();
        $record->time_modified = $member->get_time_modified();

        parent::__construct($record, $context);
    }

    /**
     * @param string $field
     * @return bool
     */
    protected function has_field(string $field): bool {
        if ('time_joined' === $field) {
            return true;
        }

        return parent::has_field($field);
    }

    /**
     * @param string $field
     * @return mixed|null
     */
    protected function get_field(string $field) {
        if ('time_joined' === $field) {
            return parent::get_field('time_created');
        }

        return parent::get_field($field);
    }

    /**
     * @return array
     */
    protected function get_map(): array {
        $that = $this;

        return [
            'workspace_id' => null,
            'time_joined' => function (int $value, date_field_formatter $format) use ($that): string {
                $time_modified = $that->object->time_modified;
                if ($value <= $time_modified) {
                    return $format->format($time_modified);
                }

                return $format->format($value);
            }
        ];
    }
}