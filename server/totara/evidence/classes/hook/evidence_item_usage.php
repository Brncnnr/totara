<?php
/**
 * This file is part of Totara LMS
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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_evidence
 */

namespace totara_evidence\hook;

use totara_core\hook\base;

defined('MOODLE_INTERNAL') || die();

/**
 * Hook to indicate that a specific instance of a component is in use somewhere.
 */
class evidence_item_usage extends base {

    /** @var string */
    private $component;

    /** @var int */
    private $instance_id;

    /** @var int */
    private $in_use_count = 0;

    /** @var string[] */
    private $used_by = [];

    /**
     * in_use constructor.
     *
     * @param string $component
     * @param int $instance_id
     */
    public function __construct(string $component, int $instance_id) {
        $this->component = $component;
        $this->instance_id = $instance_id;
    }

    /**
     * @return string[]
     */
    public function get_used_by(): array {
        return $this->used_by;
    }

    /**
     * @return string
     */
    public function get_component(): string {
        return $this->component;
    }

    /**
     * @return int
     */
    public function get_instance_id(): int {
        return $this->instance_id;
    }

    /**
     * @param string $user
     */
    public function add_used_by(string $user):void {
        ++$this->in_use_count;
        $this->used_by[] = $user;
    }

    /**
     * @return bool
     */
    public function count(): bool {
        return $this->in_use_count;
    }

}