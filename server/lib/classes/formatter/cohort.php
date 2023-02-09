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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package core_cohort
 */

namespace core\formatter;

use cohort as core_cohort;
use core\entity\cohort as cohort_entity;
use core\webapi\formatter\formatter;
use core\webapi\formatter\field\string_field_formatter;
use core\webapi\formatter\field\text_field_formatter;

defined('MOODLE_INTERNAL') || die();


// Needed for the cohort type enums when accessing through graphql
global $CFG;
require_once($CFG->dirroot.'/totara/cohort/lib.php');

/**
 * Maps a cohort_entity into the GraphQL core_cohort type.
 *
 * @property cohort_entity $object
 */
class cohort extends formatter {
    private const COHORT_TYPES = [
        core_cohort::TYPE_STATIC => 'STATIC',
        core_cohort::TYPE_DYNAMIC => 'DYNAMIC'
    ];

    /**
     * {@inheritdoc}
     */
    protected function get_map(): array {
        return [
            'active' => null,
            'description' => function ($value, text_field_formatter $formatter) {
                $component = 'cohort';
                $filearea = 'description';
                $itemid = $this->object->id;

                return $formatter
                    ->set_pluginfile_url_options($this->context, $component, $filearea, $itemid)
                    ->format($value);
            },
            'id' => null,
            'idnumber' => string_field_formatter::class,
            'name' =>  string_field_formatter::class,
            'type' => null,
            'members_count' => null,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function get_field(string $field) {
        switch ($field) {
            case 'id':
            case 'name':
            case 'idnumber':
            case 'description':
            case 'members_count':
            case 'active':
                return $this->object->$field;

            case 'type':
                return self::COHORT_TYPES[$this->object->cohorttype];
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function has_field(string $field): bool {
        return array_key_exists($field, $this->get_map());
    }
}
