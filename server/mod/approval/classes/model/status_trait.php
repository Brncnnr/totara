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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\model;

use mod_approval\exception\model_exception;

/**
 * Class to provide consistent support for status-having model instances: draft, active, archived.
 *
 * @package mod_approval\model
 */
trait status_trait {

    /**
     * Is this a draft instance?
     *
     * @return bool
     */
    public function is_draft(): bool {
        return $this->status === status::DRAFT;
    }

    /**
     * Is this instance status == active?
     *
     * @return bool
     */
    public function is_active(): bool {
        return $this->status === status::ACTIVE;
    }

    /**
     * Is this instance archived?
     *
     * @return bool
     */
    public function is_archived(): bool {
        return $this->status === status::ARCHIVED;
    }

    /**
     * Set status to active if possible.
     *
     * @return static - declarable in php 8 only, see https://wiki.php.net/rfc/static_return_type
     */
    public function activate() {
        if ($this->is_archived()) {
            throw new model_exception("Activating archived objects is not implemented");
        }
        if ($this->is_draft()) {
            if (!$this->can_be_activated()) {
                throw new model_exception("Cannot activate this object");
            }
            $this->entity->status = status::ACTIVE;
            $this->entity->save();
        }
        return $this;
    }

    /**
     * Set status to archived if possible.
     *
     * @return static
     */
    public function archive() {
        if (!$this->is_archived()) {
            if (!$this->can_be_archived()) {
                throw new model_exception("Cannot archive this object");
            }
            $this->entity->status = status::ARCHIVED;
            $this->entity->save();
        }
        return $this;
    }

    /**
     * Checks to see if user can activate (or publish) this object.
     * Override with appropriate capability checks and state verification.
     *
     * @return bool
     */
    public function can_be_activated(): bool {
        return true;
    }

    /**
     * Checks to see if user can archive this object.
     * Override with appropriate capability checks and state verification.
     *
     * @return bool
     */
    public function can_be_archived(): bool {
        return true;
    }

    /**
     * Delete the record, but only if draft.
     *
     * @param boolean $force Force delete (do not use it)
     * @return static
     */
    public function delete(bool $force = false) {
        if (!$force && !$this->is_draft()) {
            throw new model_exception("Only draft objects can be deleted");
        }
        $this->entity->delete();
        return $this;
    }
}