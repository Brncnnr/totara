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
 * @author Marco Song <marco.song@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\hook;

use totara_core\hook\base;

/**
 * Hook for section deletion
 *
 * @package mod_perform\hook
 */
class pre_section_deleted extends base {

    use pre_delete_helper;

    /** @var int $section_id */
    private $section_id;

    public function __construct(int $section_id) {
        $this->section_id = $section_id;
    }

    /**
     * Get current section id
     *
     * @return int
     */
    public function get_section_id(): int {
        return $this->section_id;
    }
}
