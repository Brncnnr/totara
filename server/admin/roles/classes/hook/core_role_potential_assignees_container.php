<?php
/**
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package core
 */

namespace core_role\hook;

use totara_core\hook\base;
use core_role_assign_user_selector_base;

/**
 * Currently \core_role_get_potential_user_selector() has some logic to determine whether a context is 'above' a course or 'below' it,
 * and chooses a user selector for assigning roles based on that.
 * But plugins should be free to use a more appropriate role assign user selector if they need to.
 */
class core_role_potential_assignees_container extends base {

    /** @var null $potential_user_selector */
    private $potential_user_selector = null;

    /** @var \context $context */
    private $context = null;

    /** @var string $name */
    private $name = 'addselect';

    /**
     * @var array $options
     */
    private $options = [];

    /**
     * core_role_potential_assignees_container constructor.
     *
     * @param context $context a context.
     * @param string $name control name
     * @param array $options should have two elements with keys groupid and courseid.
     */
    public function __construct(\context $context, string $name, array $options) {
        $this->context = $context;
        $this->name = $name;
        $this->options = $options;
    }

    /**
     * Returns core_role_assign_user_selector_base|null instance depends from a watcher call.
     *
     * @return core_role_assign_user_selector_base|null
     */
    public function get_potential_user_selector(): ?core_role_assign_user_selector_base {
        return $this->potential_user_selector;
    }

    /**
     * Set core_role_assign_user_selector_base|null instance depends from a watcher call.
     *
     * @param core_role_assign_user_selector_base|null $potential_user_selector
     */
    public function set_potential_user_selector(?core_role_assign_user_selector_base $potential_user_selector): void {
        $this->potential_user_selector = $potential_user_selector;
    }

    /**
     * Return context_course|course_module
     *
     * @return \context
     */
    public function get_context(): \context {
        return $this->context;
    }

    /**
     * Return control name
     *
     * @return string
     */
    public function get_control_name(): string {
        return $this->name;
    }

    /**
     * Return two elements with keys groupid and courseid
     *
     * @return array
     */
    public function get_options(): array {
        return $this->options;
    }
}