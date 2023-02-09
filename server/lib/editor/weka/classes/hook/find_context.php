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
 * @package editor_weka
 */
namespace editor_weka\hook;

use totara_core\hook\base;

/**
 * A hook to help the constructing weka editor easily.
 */
final class find_context extends base {
    /**
     * @var string
     */
    private $component;

    /**
     * @var string
     */
    private $area;

    /**
     * @var int|null
     */
    private $instance_id;

    /**
     * @var \context|null
     */
    private $context;

    /**
     * find_context constructor.
     * @param string    $component
     * @param string    $area
     * @param int|null  $instance_id
     */
    public function __construct(string $component, string $area, ?int $instance_id = null) {
        $this->component = $component;
        $this->area = $area;
        $this->instance_id = $instance_id;

        $this->context = null;
    }

    /**
     * @param \context $context
     * @return void
     */
    public function set_context(\context $context): void {
        if (null !== $this->context) {
            $identifier = "{$this->component}-{$this->area}-{$this->instance_id}";
            debugging("Context has already been set for identifier '{$identifier}'", DEBUG_DEVELOPER);

            return;
        }

        $this->context = $context;
    }

    /**
     * @return string
     */
    public function get_component(): string {
        return $this->component;
    }

    /**
     * @return string
     */
    public function get_area(): string {
        return $this->area;
    }

    /**
     * @return int|null
     */
    public function get_instance_id(): ?int {
        return $this->instance_id;
    }

    /**
     * @return \context|null
     */
    public function get_context(): ?\context {
        return $this->context;
    }
}