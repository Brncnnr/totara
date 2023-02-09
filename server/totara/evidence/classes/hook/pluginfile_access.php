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

use context;
use totara_core\hook\base;

defined('MOODLE_INTERNAL') || die();

/**
 * Allow other areas to control who can view evidence items.
 */
class pluginfile_access extends base {

    /** @var string */
    private $component;

    /** @var int */
    private $instance_id;

    /** @var string */
    private $file_area;

    /** @var context */
    private $context;

    /** @var array */
    private $args;

    /** @var bool */
    private $can_view = false;

    /**
     * pluginfile_access constructor.
     *
     * @param string $component
     * @param int $instance_id
     * @param string $file_area
     * @param context $context
     * @param array $args
     */
    public function __construct(
        string $component,
        int $instance_id,
        string $file_area,
        context $context,
        array $args
    ) {
        $this->component = $component;
        $this->instance_id = $instance_id;
        $this->file_area = $file_area;
        $this->context = $context;
        $this->args = $args;
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
     * @return string
     */
    public function get_file_area(): string {
        return $this->file_area;
    }

    /**
     * @return context
     */
    public function get_context(): context {
        return $this->context;
    }

    /**
     * @return array
     */
    public function get_args(): array {
        return $this->args;
    }

    /**
     * @return void
     */
    public function allow_view(): void {
        $this->can_view = true;
    }

    /**
     * @return void
     */
    public function block_view(): void {
        $this->can_view = false;
    }

    /**
     * @return bool
     */
    public function can_view(): bool {
        return $this->can_view;
    }

}