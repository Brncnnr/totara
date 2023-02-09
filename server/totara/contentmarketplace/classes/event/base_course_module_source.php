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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_contentmarketplace
 */
namespace totara_contentmarketplace\event;

use context_module;
use core\event\base;
use totara_contentmarketplace\model\course_module_source;

/**
 * Class base_course_module_source
 * @package totara_contentmarketplace\event
 */
abstract class base_course_module_source extends base {
    /**
     * @inheritDoc
     */
    protected function init() {
        $this->data['objecttable'] = 'totara_contentmarketplace_course_module_source';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * @param course_module_source $course_module_source
     * @return static
     */
    public static function from_model(course_module_source $course_module_source): self {
        $data = [
            'objectid' => $course_module_source->id,
            'context' => context_module::instance($course_module_source->cm_id),
            'courseid' => $course_module_source->course_id,
            'other' => [
                'cm_id' => $course_module_source->cm_id,
                'component' => $course_module_source->marketplace_component,
                'learning_object_id' => $course_module_source->learning_object_id,
            ]
        ];

        /** @var base_course_module_source $event */
        $event = static::create($data);
        return $event;
    }
}