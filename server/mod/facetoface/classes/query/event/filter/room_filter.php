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
 * Filter by room.
 */
final class room_filter extends filter {
    /**
     * @var int
     */
    private $roomid;

    /**
     * room_filter constructor.
     *
     * @param int $roomid
     */
    public function __construct(int $roomid) {
        parent::__construct('room');
        $this->roomid = $roomid;
    }

    /**
     * We do allow the room id for filtering to be changed here anyway.
     *
     * @param int $roomid
     *
     * @return room_filter
     */
    public function set_roomid(int $roomid): room_filter {
        $this->roomid = $roomid;
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
        if (empty($this->roomid)) {
            return;
        }
        $builder->where_exists(function (builder $inner) {
            $inner->select('sessionid')
                ->from('facetoface_sessions_dates', 'sd')
                ->join(['facetoface_room_dates', 'frd'], 'id', 'sessionsdateid')
                ->where('frd.roomid', $this->roomid)
                ->where_field('sd.sessionid', 's.id');
        });
    }
}
