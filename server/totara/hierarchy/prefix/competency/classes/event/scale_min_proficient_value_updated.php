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
 * @package hierarchy_competency
 */

namespace hierarchy_competency\event;

use core\event\base;

defined('MOODLE_INTERNAL') || die();

/**
 * Triggered when the minimum proficient value for a scale got updated
 */
class scale_min_proficient_value_updated extends base {

    /**
     * Create instance of event.
     *
     * @param \stdClass $scale A hierarchy scale record.
     * @return scale_min_proficient_value_updated
     */
    public static function create_from_instance(\stdClass $scale) {
        $data = [
            'objectid' => $scale->id,
            'context' => \context_system::instance(),
            'other' => ['minproficiencyid' => $scale->minproficiencyid],
        ];

        $event = self::create($data);
        $event->add_record_snapshot($event->objecttable, $scale);

        return $event;
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['objecttable'] = 'comp_scale';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    public static function get_name() {
        return get_string('eventupdatedscaleminprofid', 'hierarchy_competency');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The minimum proficient value for scale {$this->objectid} was changed";
    }

    public function get_url() {
        return new \moodle_url(
            "/totara/hierarchy/prefix/competency/scale/view.php?id=3&prefix=competency",
            ['id' => $this->objectid, 'prefix' => 'competency']
        );
    }

}
