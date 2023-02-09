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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\entity\assignment;

use core\orm\entity\repository;
use core\orm\query\builder;
use mod_approval\entity\workflow\workflow;
use mod_approval\entity\workflow\workflow_stage;
use mod_approval\entity\workflow\workflow_stage_approval_level;
use mod_approval\entity\workflow\workflow_version;

/**
 * Repository for assignment_approver.
 */
class assignment_approver_repository extends repository {
    /**
     * Filter by active approvers.
     *
     * @return self
     */
    public function filter_by_active(): self {
        $this->where("{$this->get_alias()}.active", 1);
        return $this;
    }

    /**
     * Filter by approval level.
     *
     * @param integer $level_id
     * @return self
     */
    public function filter_by_approval_level(int $level_id): self {
        $this->join([workflow_stage_approval_level::TABLE, 'wal'], function (builder $joining) use ($level_id) {
            $joining->where_field('wal.id', "{$this->get_alias()}.workflow_stage_approval_level_id");
            $joining->where("{$this->get_alias()}.workflow_stage_approval_level_id", $level_id);
        });
        return $this;
    }

    /**
     * Filter by assignment.
     *
     * @param integer $approval_id
     * @return self
     */
    public function filter_by_assignment(int $approval_id): self {
        $this->where("{$this->get_alias()}.approval_id", $approval_id);
        return $this;
    }

    /**
     * Filter by approval level and default assignment.
     *
     * @param integer $level_id
     * @return self
     */
    public function filter_by_default_assignment_of_approval_level(int $level_id): self {
        $this->filter_by_approval_level($level_id);
        if (!$this->has_join(workflow_stage::TABLE, 'ws')) {
            $this->join([workflow_stage::TABLE, 'ws'], 'ws.id', 'wal.workflow_stage_id');
        }
        if (!$this->has_join(workflow_version::TABLE, 'wv')) {
            $this->join([workflow_version::TABLE, 'wv'], 'wv.id', 'ws.workflow_version_id');
        }
        if (!$this->has_join(workflow::TABLE, 'w')) {
            $this->join([workflow::TABLE, 'w'], 'w.id', 'wv.workflow_id');
        }
        $this->join([assignment::TABLE, 'das'], function (builder $joining) {
            $joining->where_field('das.course', 'w.course_id');
            $joining->where('das.is_default', 1);
        });
        $this->where_field("{$this->get_alias()}.approval_id", 'das.id');
        return $this;
    }
}