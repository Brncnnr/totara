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

namespace mod_approval\model\workflow\stage_feature;

use coding_exception;
use core\orm\query\builder;
use mod_approval\entity\workflow\workflow_stage_interaction as workflow_stage_interaction_entity;
use mod_approval\exception\model_exception;
use mod_approval\model\application\action\action;
use mod_approval\model\status;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_stage_interaction;
use mod_approval\model\workflow\workflow_stage_interaction_transition;

/**
 * Interactions feature:
 * This class handles adding and deleting interactions.
 */
class interactions extends base {

    /**
     * @inheritDoc
     */
    public static function get_label(): string {
        return get_string('interactions_feature', 'mod_approval');
    }

    /**
     * @inheritDoc
     */
    public static function get_enum(): string {
        return 'INTERACTIONS';
    }

    /**
     * @inheritDoc
     */
    public static function get_sort_order(): int {
        return 30;
    }

    /**
     * Add interaction to workflow stage.
     *
     * @param action $action
     * @return workflow_stage_interaction
     */
    public function add(action $action): workflow_stage_interaction {
        if ($this->stage->workflow_version->status !== status::DRAFT) {
            throw new model_exception("Can only add interaction to a draft workflow version");
        }
        if (!$this->stage->active) {
            throw new model_exception("Can not add interaction to inactive workflow stage");
        }

        // Just use the model class's create method, no ordering to worry about.
        $new_interaction = workflow_stage_interaction::create($this->stage, $action);

        // Also add default transition
        $transition = $action::get_default_transition();
        $workflow_stage_interaction_transition = workflow_stage_interaction_transition::create(
            $new_interaction,
            null,
            $transition,
            1
        );

        // todo TL-33542: Events for creating an interaction

        return $new_interaction;
    }

    /**
     * Delete interaction from workflow stage.
     *
     * @param workflow_stage_interaction $interaction
     * @return workflow_stage
     */
    public function delete(workflow_stage_interaction $interaction): workflow_stage {
        if ($interaction->workflow_stage_id != $this->stage->id) {
            throw new coding_exception('Cannot delete a foreign interaction');
        }
        if ($this->stage->workflow_version->status !== status::DRAFT) {
            throw new model_exception("Can only delete interaction from a draft workflow version");
        }
        if (!$this->stage->active) {
            throw new model_exception("Can not delete interaction from inactive workflow stage");
        }

        $interaction->delete();

        // todo TL-33542: Events for deleting an interaction

        return $this->stage->refresh(true);
    }

    /**
     * @inheritdoc
     */
    public function add_default(): void {
        $actions = $this->stage->type::get_available_actions();
        foreach ($actions as $action) {
            $this->add(new $action());
        }
    }
}
