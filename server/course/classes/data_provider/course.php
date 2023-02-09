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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package core_course
 */

namespace core_course\data_provider;

use core\entity\course as course_entity;
use core\orm\query\raw_field;
use totara_core\data_provider\provider;
use totara_core\data_provider\provider_interface;
use core\orm\entity\filter\filter_factory;

class course extends provider implements provider_interface {

    // Mapping of sort field display names to physical entity _columns_.
    public const SORT_FIELDS = [
        'course_id' => 'id',
        'course_name' => 'fullname'
    ];

    /**
     * @inheritDoc
     */
    public static function create(?filter_factory $filter_factory = null): provider {
        return new static(
            course_entity::repository(),
            self::SORT_FIELDS,
            $filter_factory
        );
    }

    /**
     * @inheritDoc
     */
    public static function get_type(): string {
        return 'course';
    }

    /**
     * @inheritDoc
     */
    public static function get_summary_format_select() {
        return 'summaryformat';
    }

}