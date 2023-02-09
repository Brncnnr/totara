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

namespace mod_approval\model\workflow\stage_feature;

use coding_exception;
use core\collection;
use core\orm\query\builder;
use mod_approval\entity\workflow\workflow_stage_approval_level as workflow_stage_approval_level_entity;
use mod_approval\event\workflow_stage_approval_level_created;
use mod_approval\event\workflow_stage_approval_level_deleted;
use mod_approval\event\workflow_stage_approval_levels_reordered;
use mod_approval\exception\model_exception;
use mod_approval\model\status;
use mod_approval\model\workflow\ordinal\allocate;
use mod_approval\model\workflow\ordinal\level_ordinal;
use mod_approval\model\workflow\ordinal\move;
use mod_approval\model\workflow\ordinal\reorder;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_stage_approval_level;

/**
 * Approval levels feature:
 * This class handles adding, reordering, deleting approval levels.
 * This class also handles getting the approval level after a specified approval level.
 */
class approval_levels extends base {

    /**
     * @inheritDoc
     */
    public static function get_label(): string {
        return get_string('approvals_feature', 'mod_approval');
    }

    /**
     * @inheritDoc
     */
    public static function get_enum(): string {
        return 'APPROVAL_LEVELS';
    }

    /**
     * @inheritDoc
     */
    public static function get_sort_order(): int {
        return 20;
    }

    /**
     * Add approval level to workflow stage.
     *
     * @param string $name
     * @return workflow_stage_approval_level
     */
    public function add(string $name): workflow_stage_approval_level {
        if ($this->stage->workflow_version->status !== status::DRAFT) {
            throw new model_exception("Can only add approval level to a draft workflow version");
        }
        if (!$this->stage->active) {
            throw new model_exception("Can not add approval level to inactive workflow stage");
        }

        $new_approval_level = builder::get_db()->transaction(function () use ($name) {
            $entity = new workflow_stage_approval_level_entity();
            $entity->workflow_stage_id = $this->stage->id;
            $entity->name = $name;
            $entity->active = true;
            (new allocate(new level_ordinal()))->execute($this->stage, $entity);
            $entity->save();

            return workflow_stage_approval_level::load_by_entity($entity);
        });

        // Refresh so stage has the new approval level.
        $this->stage->refresh(true);

        // Trigger event
        workflow_stage_approval_level_created::execute($new_approval_level);

        return $new_approval_level;
    }

    /**
     * Delete approval level from workflow stage.
     *
     * @param workflow_stage_approval_level $level An approval level
     * @return workflow_stage
     */
    public function delete(workflow_stage_approval_level $level): workflow_stage {
        if ($level->workflow_stage_id != $this->stage->id) {
            throw new coding_exception('Cannot delete a foreign approval level');
        }
        if ($this->stage->workflow_version->status !== status::DRAFT) {
            throw new model_exception("Can only delete approval level from a draft workflow version");
        }
        if (!$this->stage->active) {
            throw new model_exception("Can not delete approval level from inactive workflow stage");
        }

        builder::get_db()->transaction(function () use ($level) {
            // Trigger event
            workflow_stage_approval_level_deleted::execute($level);

            $level->delete();
            (new move(new level_ordinal()))->execute($this->stage, $level);
        });

        return $this->stage->refresh(true);
    }

    /**
     * Edit approval level to workflow stage.
     *
     * @param int $level_id
     * @param string $name
     * @return workflow_stage_approval_level
     */
    public function edit(int $level_id, string $name): workflow_stage_approval_level {
        if ($this->stage->workflow_version->status !== status::DRAFT) {
            throw new model_exception("Can only edit approval level attached to a draft workflow version");
        }
        if (!$this->stage->active) {
            throw new model_exception("Can not edit approval level attached to inactive workflow stage");
        }

        $approval_level = builder::get_db()->transaction(function () use ($level_id, $name) {
            $entity = new workflow_stage_approval_level_entity($level_id);
            $entity->name = $name;
            $entity->save();

            return workflow_stage_approval_level::load_by_entity($entity);
        });

        // Refresh so stage has the new approval level name.
        $this->stage->refresh(true);
        return $approval_level;
    }

    /**
     * Change the order of approval levels.
     *
     * @param workflow_stage_approval_level[] $new_levels all approval levels in this workflow stage.
     * @return workflow_stage
     */
    public function reorder(array $new_levels): workflow_stage {
        if ($this->stage->workflow_version->status !== status::DRAFT) {
            throw new model_exception("Can only reorder approval levels of a draft workflow version");
        }
        if (!$this->stage->active) {
            throw new model_exception("Can not reorder approval levels of inactive workflow stage");
        }

        $all_levels = $this->stage->approval_levels->all(false);

        if ((new reorder(new level_ordinal()))->execute($this->stage, $all_levels, $new_levels)) {
            $this->stage->refresh();
        }

        // Trigger event
        workflow_stage_approval_levels_reordered::execute($this->stage);

        return $this->stage;
    }

    public function get_first(): ?workflow_stage_approval_level {
        return $this->stage->approval_levels->first();
    }

    /**
     * Get the approval level after the specified approval level.
     *
     * If the specified approval level provided is null, then the first approval level will be returned.
     * If the specified approval level provided is the last approval level, then null will be returned.
     *
     * @param int $approval_level_id
     * @return workflow_stage_approval_level|null
     */
    public function get_next(int $approval_level_id): ?workflow_stage_approval_level {
        $approval_levels = $this->stage->approval_levels;

        $level_found = false;
        foreach ($approval_levels as $approval_level) {
            if ($approval_level->id === $approval_level_id) {
                $level_found = true;
                continue;
            }

            if ($level_found) {
                return $approval_level;
            }
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function add_default(): void {
        $this->add(get_string('level_1', 'mod_approval'));
    }
}
