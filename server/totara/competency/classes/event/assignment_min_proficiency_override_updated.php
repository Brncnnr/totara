<?php
/*
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Triggered when the minimum proficiency override value for an assignment got updated
 */
class assignment_min_proficiency_override_updated extends assignment {
    /**
     * @@inheritDoc
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'totara_competency_assignments';
    }

    /**
     * @@inheritDoc
     */
    public static function get_name() {
        return get_string('event_assignment_min_proficiency_override_updated', 'totara_competency');
    }

    /**
     * @@inheritDoc
     */
    public function get_description() {
        return 'Minimum proficiency override of assignment was updated';
    }
}
