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

namespace mod_approval\entity\application;

use core\orm\entity\repository;

/**
 * Repository for application_submisson.
 */
final class application_submission_repository extends repository {
    /**
     * Return submissions in the specific application, including the superseded ones.
     *
     * @param integer $appid
     * @return self
     */
    public function filter_by_application_id(int $appid): self {
        return $this->where("{$this->get_alias()}.application_id", $appid);
    }

    /**
     * Return submissions by the specific user.
     *
     * @param integer $userid
     * @return self
     */
    public function filter_by_user_id(int $userid = 0): self {
        global $USER;
        if (!$userid) {
            $userid = $USER->id;
        }
        return $this->where("{$this->get_alias()}.user_id", $userid);
    }

    /**
     * Return a submission by the specific user in the specific application at the specific workflow stage
     * where the submission is the latest/current non-superseded submission.
     *
     * @param integer $appid
     * @param integer $stageid
     * @param integer $userid
     * @return self
     */
    public function filter_by_current(int $appid, int $stageid, int $userid = 0): self {
        $alias = $this->get_alias();
        return $this->filter_by_application_id($appid)
            ->filter_by_user_id($userid)
            ->where("{$alias}.workflow_stage_id", $stageid)
            ->where("{$alias}.superseded", 0);
    }

    /**
     * Return a submission by the specific user in the specific application at the specific workflow stage
     * where the submission can be updated. This can only occur if
     * - the submission is not superseded (can only update the latest/current non-superseded submission) and
     * - the submission has not been submitted (we don't want to overwrite published submissions).
     *
     * @param integer $appid
     * @param integer $stageid
     * @param integer $userid
     * @return self
     */
    public function filter_by_updateable(int $appid, int $stageid, int $userid = 0): self {
        $alias = $this->get_alias();
        return $this->filter_by_current($appid, $stageid, $userid)
            ->where_null("{$alias}.submitted");
    }
}
