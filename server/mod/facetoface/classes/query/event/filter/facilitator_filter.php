<?php
/*
 * This file is part of Totara LMS
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\query\event\filter;

use core\orm\query\builder;
use mod_facetoface\query\event\filter_factory;

defined('MOODLE_INTERNAL') || die();

/**
 * Filter by facilitator.
 */
final class facilitator_filter extends filter {
    /**
     * @var int
     */
    private $facilitatorid;

    /**
     * facilitator_filter constructor.
     *
     * @param int $facilitatorid
     */
    public function __construct(int $facilitatorid) {
        parent::__construct('facilitator');
        $this->facilitatorid = $facilitatorid;
    }

    /**
     * We do allow the facilitator id for filtering to be changed here anyway.
     *
     * @param int $facilitatorid
     *
     * @return facilitator_filter
     */
    public function set_facilitatorid(int $facilitatorid): facilitator_filter {
        $this->facilitatorid = $facilitatorid;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function get_where_and_params(int $time): array {
        debugging('The method ' . __METHOD__ . '() has been deprecated and no longer effective. Please use the apply() counterpart instead.', DEBUG_DEVELOPER);
        return ["(1=1)", []];
    }

    public function apply(builder $builder, int $time): void {
        if (empty($this->facilitatorid)) {
            return;
        }
        $builder->where_exists(function (builder $inner) {
            $inner->select('sessionid')
                ->from('facetoface_sessions_dates', 'sd')
                ->join(['facetoface_facilitator_dates', 'ffd'], 'id', 'sessionsdateid')
                ->where('ffd.facilitatorid', $this->facilitatorid)
                ->where_field('sd.sessionid', 's.id');
        });
    }
}
