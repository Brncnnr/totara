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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\model\application;

use coding_exception;
use mod_approval\model\application\action\action;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_stage_approval_level;

/**
 * Class for storing application states. Provides functions for determining properties of the state, such
 * as if it is a draft state or if it is before submission.
 */
class application_state {

    /** @var int */
    protected $stage_id;

    /** @var bool */
    protected $is_draft;

    /** @var int */
    protected $approval_level_id;

    /**
     * Instantiate a new application state object
     *
     * The class does not check that the given stage properties are valid for any given application workflow,
     * such as being draft in only the first stage, or that an approval level id is only provided on an approval
     * stage, you must validate this against your workflow externally.
     *
     * @param int $stage_id
     * @param bool $is_draft
     * @param int|null $approval_level_id
     */
    public function __construct(
        int $stage_id,
        bool $is_draft = false,
        ?int $approval_level_id = null
    ) {
        $this->stage_id = $stage_id;
        $this->is_draft = $is_draft;
        $this->approval_level_id = $approval_level_id;
    }

    /**
     * The ID of the stage.
     *
     * @return int
     */
    public function get_stage_id(): int {
        return $this->stage_id;
    }

    /**
     * The stage model.
     *
     * @return workflow_stage
     */
    public function get_stage(): workflow_stage {
        return workflow_stage::load_by_id($this->stage_id);
    }

    /**
     * Returns true if the type of the stage matches the given type.
     *
     * @param int $type_code
     * @return bool
     */
    public function is_stage_type(int $type_code): bool {
        return $this->get_stage()->get_type()::get_code() == $type_code;
    }

    /**
     * Whether the state is draft
     *
     * @return bool
     */
    public function is_draft(): bool {
        return $this->is_draft;
    }

    /**
     * The approval level of the state.
     *
     * @return int|null
     */
    public function get_approval_level_id(): ?int {
        return $this->approval_level_id;
    }

    /**
     * The approval level model.
     *
     * @return workflow_stage_approval_level|null
     */
    public function get_approval_level(): ?workflow_stage_approval_level {
        if (is_null($this->approval_level_id)) {
            return null;
        }
        return workflow_stage_approval_level::load_by_id($this->approval_level_id);
    }

    /**
     * Used to compare two application states.
     *
     * @param application_state $other_state
     * @return bool
     */
    public function is_same_as(self $other_state): bool {
        return $this->stage_id == $other_state->get_stage_id() &&
            $this->is_draft == $other_state->is_draft() &&
            $this->approval_level_id == $other_state->get_approval_level_id();
    }
}
