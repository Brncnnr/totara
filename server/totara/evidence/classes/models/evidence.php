<?php
/**
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
 * @package totara_evidence
 */

namespace totara_evidence\models;

use core\entity\user;
use core\orm\entity\model;
use totara_evidence\entity;

/**
 * Class evidence
 *
 * @package totara_evidence\models
 *
 * @property-read int $id ID
 * @property-read string $name Evidence item name (raw)
 * @property-read string $display_name Evidence item name (formatted)
 * @property-read int $created_by User ID for who created the evidence
 * @property-read user $created_by_user User for who created the evidence
 * @property-read int $created_at Created timestamp
 * @property-read string $display_created_at Date created (formatted)
 * @property-read int $modified_by User ID for who last modified the evidence
 * @property-read user $modified_by_user User for who last modified the evidence
 * @property-read int $modified_at Last modified timestamp
 * @property-read string $display_modified_at Date last modified (formatted)
 */
abstract class evidence extends model {

    /**
     * @var entity\evidence_type|entity\evidence_item
     */
    protected $entity;

    /**
     * Return the name of this evidence model for display
     *
     * @return string
     */
    public function get_display_name(): string {
        return format_string($this->name);
    }

    /**
     * Get the formatted date of when this was created
     *
     * @return string
     */
    public function get_display_created_at(): string {
        return userdate($this->created_at, get_string('strftimedatetime', 'core_langconfig'));
    }

    /**
     * Get the formatted date of when this was last modified
     *
     * @return string
     */
    public function get_display_modified_at(): string {
        return userdate($this->modified_at, get_string('strftimedatetime', 'core_langconfig'));
    }

    /**
     * Has this evidence been modified since it was created?
     *
     * @return bool
     */
    public function is_modified(): bool {
        return $this->created_at !== $this->modified_at;
    }

    /**
     * Did the logged in user create this evidence item?
     *
     * @return bool
     */
    public function is_creator(): bool {
        return $this->created_by == user::logged_in()->id;
    }

    /**
     * Is this evidence currently in use?
     *
     * @return bool
     */
    abstract public function in_use(): bool;

    /**
     * Can this evidence be modified by the current user?
     *
     * @return bool
     */
    abstract public function can_modify(): bool;

    /**
     * Get all data associated with this evidence
     *
     * @return array
     */
    public function get_data(): array {
        return array_merge($this->entity->to_array(), [
            'in_use' => $this->in_use(),
            'is_modified' => $this->is_modified(),
            'is_creator' => $this->is_creator(),
            'can_modify' => $this->can_modify(),
            'display_name' => $this->get_display_name()
        ]);
    }

}
