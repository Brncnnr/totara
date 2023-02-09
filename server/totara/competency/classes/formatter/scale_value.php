<?php
/*
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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com
 * @package totara_competency
 */

namespace totara_competency\formatter;

use context_system;
use core\orm\formatter\entity_formatter;
use core\webapi\formatter\field\date_field_formatter;
use core\webapi\formatter\field\string_field_formatter;
use core\webapi\formatter\field\text_field_formatter;

/**
 * @property scale $object
 */
class scale_value extends entity_formatter {

    protected function get_map(): array {
        return [
            'id' => null,
            'name' => string_field_formatter::class,
            'idnumber' => null,
            'description' => function ($value, text_field_formatter $formatter) {
                $component = 'totara_hierarchy';
                $filearea = \totara_hierarchy\entity\scale_value::TABLE;
                $itemid = $this->object->id;

                // Competencies are always created in the system context,
                // therefore for the files to work we need to use the system context
                $context = context_system::instance();

                return $formatter
                    ->set_pluginfile_url_options($context, $component, $filearea, $itemid)
                    ->format($value);
            },
            'scaleid' => null,
            'numericscore' => null,
            'sortorder' => null,
            'timemodified' => date_field_formatter::class,
            'usermodified' => null,
            'proficient' => null
        ];
    }

}