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
 * @package totara_competency
 */

namespace totara_competency\event;

defined('MOODLE_INTERNAL') || die();

use core\event\base;
use totara_competency\entity\competency_assignment_user;

abstract class assignment_user extends base {

    protected $is_bulk = false;

    /**
     * Return related assignment id
     *
     * @return int|null
     */
    public function get_assignment_id(): ?int {
        return $this->data['other']['assignment_id'] ?? null;
    }

    /**
     * Return related competency id
     *
     * @return int|null
     */
    public function get_competency_id(): ?int {
        return $this->data['other']['competency_id'] ?? null;
    }

    /**
     * Return related assignment type
     *
     * @return string|null
     */
    public function get_assignment_type(): ?string {
        return $this->data['other']['type'] ?? null;
    }

    /**
     * Return related assignment id
     *
     * @return int|null
     */
    public function get_user_id(): ?int {
        return $this->data['relateduserid'] ?? null;
    }

    public function is_bulk(): bool {
        return $this->is_bulk;
    }

    /**
     * Create instance of event.
     *
     * @param array $assignment_user
     * @param string $assignment_type
     * @return self|base
     */
    public static function create_from_array(array $assignment_user, ?string $assignment_type = null) {
        $assignment_user = (object) $assignment_user;

        $data = [
            'objectid' => $assignment_user->assignment_id,
            'relateduserid' => $assignment_user->user_id,
            'other' => [
                'assignment_id' => $assignment_user->assignment_id,
                'competency_id' => $assignment_user->competency_id,
                'type' => $assignment_type ?? null,
            ],
            'context' => \context_system::instance()
        ];
        return static::create($data);
    }

    /**
     * Create instance of event.
     *
     * @param competency_assignment_user $assignment_user
     * @param string $assignment_type
     * @return self
     */
    public static function create_from_assignment_user(competency_assignment_user $assignment_user, ?string $assignment_type = null) {
        return self::create_from_array($assignment_user->get_attributes_raw(), $assignment_type);
    }

}
