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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_plan
 */

namespace totara_plan\event;

use core\event\base;

defined('MOODLE_INTERNAL') || die();

class competency_value_set extends base {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['objecttable'] = 'dp_plan_competency_value';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * @param \stdClass $record Record from dp_plan_competency_value
     * @param \development_plan $plan
     * @return competency_value_set|base
     */
    public static function create_from_record($record, \development_plan $plan): self {
        /** @var competency_value_set $event */
        $event = static::create([
            'objectid' => $record->id,
            'relateduserid' => $record->user_id,
            'other' => [
                'competency_id' => $record->competency_id,
                'scale_value_id' => $record->scale_value_id,
                'plan_id' => $plan->id,
                'plan_name' => $plan->name
            ],
            'context' => \context_system::instance()
        ]);

        $event->add_record_snapshot('dp_plan_competency_value', $record);

        return $event;
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_competency_value_set', 'totara_plan');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $scale_value_id = $this->other['scale_value_id'] ?? '';
        $plan_id = $this->other['plan_id'] ?? '';
        return "A scale value with id {$scale_value_id} for user {$this->relateduserid} was set by user {$this->userid} in learning plan with id {$plan_id}";
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        $plan_id = $this->other['plan_id'] ?? '';
        return new \moodle_url('/totara/plan/component.php', ['id' => $plan_id, 'c' => 'competency']);
    }

}
