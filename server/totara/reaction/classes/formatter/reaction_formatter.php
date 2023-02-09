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
 * @package totara_reaction
 */
namespace totara_reaction\formatter;

use totara_core\formatter\field\date_field_formatter;
use core\webapi\formatter\formatter;
use totara_reaction\reaction;

/**
 * Formatter for reaction
 */
final class reaction_formatter extends formatter {
    /**
     * reaction_formatter constructor.
     * @param reaction $reaction
     */
    public function __construct(reaction $reaction) {
        $record = new \stdClass();
        $record->component = $reaction->get_component();
        $record->area = $reaction->get_area();
        $record->instanceid = $reaction->get_instanceid();
        $record->timecreated = $reaction->get_timecreated();

        $context = \context::instance_by_id($reaction->get_contextid());
        parent::__construct($record, $context);
    }

    /**
     * @return array
     */
    protected function get_map(): array {
        return [
            'component' => null,
            'area' => null,
            'instanceid' => null,
            'timecreated' => date_field_formatter::class
        ];
    }
}