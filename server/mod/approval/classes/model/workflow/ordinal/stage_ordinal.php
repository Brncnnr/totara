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

namespace mod_approval\model\workflow\ordinal;

use core\orm\entity\model;
use mod_approval\entity\workflow\workflow_stage as workflow_stage_entity;
use mod_approval\model\workflow\workflow_stage as workflow_stage_model;

/**
 * Manage the ordinal number of workflow stages.
 */
final class stage_ordinal implements ordinal {
    /**
     * @inheritDoc
     */
    public function table_name(): string {
        return workflow_stage_entity::TABLE;
    }

    /**
     * @inheritDoc
     */
    public function foreign_key(): string {
        return 'workflow_version_id';
    }

    /**
     * @inheritDoc
     */
    public function ordinal_field(): string {
        return 'sortorder';
    }

    /**
     * @inheritDoc
     */
    public function timestamp_field(): ?string {
        return 'updated';
    }

    /**
     * @param workflow_stage_model $item
     * @return integer
     */
    public function map_ordinal_number(model $item): int {
        return $item->ordinal_number;
    }
}
