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

namespace mod_approval\entity\workflow;

use core\orm\entity\repository;

/**
 * Repository for workflow_stage_formview.
 */
final class workflow_stage_formview_repository extends repository {
    /**
     * Return formviews belonging to the specific workflow stage.
     *
     * @param integer $stageid
     * @return self
     */
    public function filter_by_workflow_stage_id(int $stageid): self {
        return $this->where("{$this->get_alias()}.workflow_stage_id", $stageid);
    }

    /**
     * Return required formviews.
     *
     * @return self
     */
    public function filter_by_required(): self {
        return $this->where("{$this->get_alias()}.required", '!=', 0);
    }

    /**
     * Return enabled formviews.
     *
     * @return self
     */
    public function filter_by_enabled(): self {
        return $this->where("{$this->get_alias()}.disabled", '=', 0);
    }
}
