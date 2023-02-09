<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_userstatus
 */

namespace totara_competency\formatter;

use context_system;
use core\orm\formatter\entity_formatter;
use core\webapi\formatter\field\date_field_formatter;
use core\webapi\formatter\field\string_field_formatter;
use core\webapi\formatter\field\text_field_formatter;

/**
 * @property \totara_hierarchy\entity\competency $object
 */
class competency extends entity_formatter {

    protected function get_map(): array {
        return [
            'id' => null,
            'idnumber' => null,
            'shortname' => string_field_formatter::class,
            'fullname' => string_field_formatter::class,
            'display_name' => string_field_formatter::class,
            'description' => function ($value, text_field_formatter $formatter) {
                global $CFG;
                require_once($CFG->dirroot . '/totara/hierarchy/lib.php');

                $component = 'totara_hierarchy';
                $filearea = \hierarchy::get_short_prefix('competency');
                $itemid = $this->object->id;

                // Competencies are always created in the system context,
                // therefore for the files to work we need to use the system context
                $context = context_system::instance();

                return $formatter
                    ->set_pluginfile_url_options($context, $component, $filearea, $itemid)
                    ->format($value);
            },
            'timecreated' => date_field_formatter::class,
            'timemodified' => date_field_formatter::class,
            'frameworkid' => null,
            'framework' => null,
            'path' => null,
            'parent' => null,
            'parentid' => null,
            'visible' => null,
            'children' => null,
            'typeid' => null,
            'type' => null,
            'assign_availability' => null,
            'aggregationmethod' => null,
            'display_custom_fields' => null,
        ];
    }
}