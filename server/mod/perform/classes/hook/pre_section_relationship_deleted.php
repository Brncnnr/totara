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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\hook;

use mod_perform\models\activity\section_relationship;
use totara_core\hook\base;

/**
 * Hook for section relationship deletion
 *
 * @package mod_perform\hook
 */
class pre_section_relationship_deleted extends base {

    use pre_delete_helper;

    /** @var section_relationship $section_relationship */
    private $section_relationship;

    /** @var bool $ignore_conflicts_on_same_section */
    private $ignore_conflicts_on_same_section;

    public function __construct(section_relationship $section_relationship, bool $ignore_conflicts_on_same_section = false) {
        $this->section_relationship = $section_relationship;
        $this->ignore_conflicts_on_same_section = $ignore_conflicts_on_same_section;
    }

    /**
     * Get current section id
     *
     * @return section_relationship
     */
    public function get_section_relationship(): section_relationship {
        return $this->section_relationship;
    }

    /**
     * Get information about ignoring conflicts.
     * If we check relationships which belongs to the same section no need to check restrictions.
     *
     * @return bool
     */
    public function get_ignore_conflicts_on_same_section(): bool {
        return $this->ignore_conflicts_on_same_section;
    }
}
