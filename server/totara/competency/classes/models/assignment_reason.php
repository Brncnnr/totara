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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\models;

use core\orm\collection;

/**
 * A unique reason as to why a competency was assigned.
 *
 * @package totara_competency\models
 *
 * @property-read string $reason
 * @property-read assignment[] $assignments
 * @property-read string $key
 */
class assignment_reason {

    /**
     * @var string
     */
    private $reason;

    /**
     * @var assignment[]|collection
     */
    private $assignment_models;

    /**
     * @param string $reason
     * @param assignment[]|null $assignments
     */
    private function __construct(string $reason, array $assignments = []) {
        $this->reason = $reason;
        $this->assignment_models = new collection($assignments);
    }

    /**
     * Organise multiple assignments into groups by the reason they were assigned to the user.
     *
     * @param collection $assignments
     * @return static[]
     */
    public static function build_from_assignments(collection $assignments): array {
        /** @var static[] $reasons_assigned */
        $reasons_assigned = [];

        foreach ($assignments as $assignment_entity) {
            $assignment = assignment::load_by_entity($assignment_entity);

            $reason = $assignment->get_reason_assigned();

            if (!isset($reasons_assigned[$reason])) {
                $reasons_assigned[$reason] = new static($reason);
            }

            $reasons_assigned[$reason]->assignment_models->append($assignment);
        }

        $reasons_assigned = array_values($reasons_assigned);

        usort($reasons_assigned, function (assignment_reason $a, assignment_reason $b) {
            return $a->reason <=> $b->reason;
        });

        return $reasons_assigned;
    }

    /**
     * Get the assignment reason descriptive string.
     *
     * @return string
     */
    public function get_reason(): string {
        return $this->reason;
    }

    /**
     * Get the assignments associated with this assignment reason.
     *
     * @return assignment[]
     */
    public function get_assignments(): array {
        return $this->assignment_models->sort('id')->all();
    }

    /**
     * Get a unique key that refers to this unique assignment reason.
     *
     * @return string
     */
    public function get_key(): string {
        return md5($this->reason . '/' . implode('/', $this->assignment_models->sort('id')->pluck('id')));
    }

    /**
     * @param $attribute
     * @return mixed
     */
    public function __get($attribute) {
        return $this->{'get_' . $attribute}();
    }

    /**
     * @param $attribute
     * @return mixed
     */
    public function __isset($attribute) {
        return method_exists($this, 'get_' . $attribute);
    }

}
