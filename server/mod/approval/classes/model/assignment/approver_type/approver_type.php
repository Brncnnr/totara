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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

namespace mod_approval\model\assignment\approver_type;

use core\orm\entity\entity;
use core\orm\entity\model;

/**
 * This interface is a representation of an approver type.
 */
interface approver_type {
    /**
     * Approver type's entity.
     *
     * @param int $identifier
     *
     * @return entity|model
     */
    public function entity(int $identifier);

    /**
     * Name of the approver.
     *
     * @param int $identifier
     *
     * @return string
     */
    public function entity_name(int $identifier): string;

    /**
     * Checks if the identifier is valid.
     *
     * @param int $identifier
     * @return bool
     */
    public function is_valid(int $identifier): bool;

    /**
     * Label associated with the approver type.
     *
     * @return string
     */
    public function label(): string;

    /**
     * Possible selectable options that may be used by the front end component.
     *
     * @return array|null array of elements consisting of at least identifier and name, or null if none applicable
     */
    public function options(): ?array;

    /**
     * Get the approval type code.
     *
     * @return integer
     */
    public static function get_code(): int;

    /**
     * An enum representation of the approval type.
     *
     * @return string
     */
    public static function get_enum(): string;

    /**
     * Get the name of a type resolver class.
     *
     * @return string
     */
    public static function resolver_class(): string;
}