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

namespace totara_evidence\models\helpers;

use coding_exception;
use context;
use context_user;
use core\entity\user;
use totara_evidence\models\evidence_item;

/**
 * This class abstracts much of the logic behind checking capabilities for evidence
 *
 * @package totara_evidence\models\helpers
 */
class evidence_item_capability_helper {

    /**
     * @var context
     */
    protected $context;

    /**
     * @var user
     */
    protected $user_id;

    /**
     * @var evidence_item
     */
    protected $evidence_item;

    protected function __construct(int $user_id) {
        $this->user_id = $user_id;
        $this->context = context_user::instance($user_id, IGNORE_MISSING);
    }

    /**
     * For if we want to check the evidence capabilities in general
     *
     * @param int $user_id
     *
     * @return evidence_item_capability_helper
     */
    public static function for_user(int $user_id): self {
        return new static($user_id);
    }

    /**
     * For if we want to check capabilities for a specific evidence item
     *
     * @param evidence_item $evidence_item
     *
     * @return evidence_item_capability_helper
     */
    public static function for_item(evidence_item $evidence_item): self {
        $helper = new static($evidence_item->user_id);
        $helper->evidence_item = $evidence_item;
        return $helper;
    }

    /**
     * Check that the user has the specified capability in the specified context
     *
     * @param string $capability
     * @param bool   $require    If true throws an exception, otherwise returns false if not allowed
     * @return bool
     */
    final protected function check_capability(string $capability, bool $require): bool {
        if (!$this->context) {
            return false;
        }

        if ($require) {
            require_capability($capability, $this->context);
        }

        return has_capability($capability, $this->context);
    }

    /**
     * Check if the user can manage evidence that they created for a user
     *
     * @param bool $require If true throws an exception, otherwise returns false if not allowed
     * @return bool
     */
    protected function can_manage_own(bool $require = false): bool {
        if ($this->is_logged_in()) {
            return $this->check_capability('totara/evidence:manageownevidenceonself', $require);
        } else {
            return $this->check_capability('totara/evidence:manageownevidenceonothers', $require);
        }
    }

    /**
     * Check if the user can manage any evidence of a user, regardless of who created it
     *
     * @param bool $require If true throws an exception, otherwise returns false if not allowed
     * @return bool
     */
    protected function can_manage_any(bool $require = false): bool {
        if ($this->is_logged_in()) {
            return $this->check_capability('totara/evidence:manageanyevidenceonself', $require);
        } else {
            return $this->check_capability('totara/evidence:manageanyevidenceonothers', $require);
        }
    }

    /**
     * Check if the user can view any evidence of a user
     *
     * @param bool $require If true throws an exception, otherwise returns false if not allowed
     * @return bool
     */
    protected function can_view(bool $require = false): bool {
        if ($this->is_logged_in()) {
            return $this->check_capability('totara/evidence:viewanyevidenceonself', $require);
        } else {
            return $this->check_capability('totara/evidence:viewanyevidenceonothers', $require);
        }
    }

    /**
     * Check if the user can create new evidence for a user
     *
     * @param bool $require If true throws an exception, otherwise returns false if not allowed
     * @return bool
     */
    public function can_create(bool $require = false): bool {
        return $this->can_manage_any() || $this->can_manage_own($require);
    }

    /**
     * Check if the user can modify the specific evidence item
     *
     * @param bool $require If true throws an exception, otherwise returns false if not allowed
     * @return bool
     */
    public function can_modify(bool $require = false): bool {
        if (is_null($this->evidence_item)) {
            throw new coding_exception('Please use evidence_capability_helper::for_item() instead of for_user()');
        }

        if ($this->evidence_item->imported) {
            // Must have the admin level capability to edit imported evidence.
            return $this->check_capability('totara/evidence:manageanyevidenceonothers', $require);
        }

        if ($this->evidence_item->is_creator()) {
            return $this->can_manage_any() || $this->can_manage_own($require);
        }

        return $this->can_manage_any($require);
    }

    /**
     * Check if the user can view a list of the evidence a user has
     *
     * @param bool $require If true throws an exception, otherwise returns false if not allowed
     * @return bool
     */
    public function can_view_list(bool $require = false): bool {
        return $this->can_create() || $this->can_view($require);
    }

    /**
     * Check if the user can view a specific evidence item
     *
     * @param bool $require If true throws an exception, otherwise returns false if not allowed
     * @return bool
     */
    public function can_view_item(bool $require = false): bool {
        if (is_null($this->evidence_item)) {
            throw new coding_exception('Please use evidence_capability_helper::for_item() instead of for_user()');
        }

        if ($this->is_logged_in()) {
            return $this->can_manage_any() || $this->can_manage_own() || $this->can_view($require);
        } else {
            return $this->can_modify() || $this->can_view($require);
        }
    }

    /**
     * Check if the user is allowed to view evidence of another user, but only if they created it.
     * If the user is allowed to view all evidence, then this returns false.
     * This is primarily useful for restricting what rows are displayed in reports.
     *
     * @return bool
     */
    public function can_view_own_items_only(): bool {
        return $this->can_manage_own() && !$this->is_logged_in() && !$this->can_view() && !$this->can_manage_any();
    }

    /**
     * Is the subject user logged in?
     * @return bool
     */
    private function is_logged_in(): bool {
        return $this->user_id == user::logged_in()->id;
    }
}
