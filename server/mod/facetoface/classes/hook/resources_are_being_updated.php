<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\hook;

use mod_facetoface\hook\service\seminar_session_resource;
use mod_facetoface\seminar_event;
use stdClass;

/**
 * A hook called when assets, rooms or facilitators are about to be attached to/detached from a session.
 *
 * @package mod_facetoface\hook
 */
class resources_are_being_updated extends \totara_core\hook\base {
    /**
     * A seminar event associated to the hook. **Do not modify the instance!!**
     *
     * @var seminar_event
     */
    public $seminarevent;

    /**
     * Seminar sessions associated to the hook. **Do not modify the instance!!**
     * @var seminar_session_resource[]
     */
    public $sessions;

    /**
     * The constructor.
     *
     * @param seminar_event $seminarevent
     * @param stdClass[] $sessiondates a facetoface_sessions_dates record optionally containing assetids, roomids and facilitatorids
     */
    public function __construct(seminar_event $seminarevent, array $sessiondates) {
        // Make a shallow copy of the seminar event instance.
        $this->seminarevent = new seminar_event();
        $this->seminarevent->from_record($seminarevent->to_record());
        $this->sessions = array_map(function ($sessiondate) {
            return seminar_session_resource::from_record($sessiondate);
        }, $sessiondates);
    }
}
