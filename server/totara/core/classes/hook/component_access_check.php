<?php
/**
 *
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
 * @package totara_core
 */

namespace totara_core\hook;

/**
 * Hook to check a viewing user's access to a target user's component
 */
class component_access_check extends base {

    /**
     * @var string $component_name Component where access is checked.
     */
    private $component_name;

    /**
     * @var int $viewing_user_id The user trying to access component items.
     */
    private $viewing_user_id;

    /**
     * @var int $target_user_id The user whose component items are accessed.
     */
    private $target_user_id;

    /**
     * @var array $extra_data  Extra component data
     */
    private $extra_data;

    /**
     * @var bool $has_permission Whether the viewing user has permission to view the target user's component
     */
    private $has_permission = false;

    /**
     * @param string $component_name Component where access is checked
     * @param int $viewing_user_id The user trying to access component items
     * @param int $target_user_id The user whose component items are accessed
     * @param array $extra_data Optional extra component data
     */
    public function __construct(
        string $component_name,
        int $viewing_user_id,
        int $target_user_id,
        $extra_data = []
    ) {
        $this->component_name = $component_name;
        $this->viewing_user_id = $viewing_user_id;
        $this->target_user_id = $target_user_id;
        $this->extra_data = $extra_data;
    }

    public function get_component_name(): string {
        return $this->component_name;
    }

    public function get_viewing_user_id(): int {
        return $this->viewing_user_id;
    }

    public function get_target_user_id(): int {
        return $this->target_user_id;
    }

    public function get_extra_data(): array {
        return $this->extra_data;
    }

    public function give_permission() {
        $this->has_permission = true;
    }

    public function has_permission(): bool {
        return $this->has_permission;
    }
}