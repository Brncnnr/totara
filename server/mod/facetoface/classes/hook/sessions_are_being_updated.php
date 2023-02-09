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

use mod_facetoface\seminar_event;
use mod_facetoface\seminar_session_list;
use mod_facetoface\hook\service\seminar_session_resource;
use mod_facetoface\hook\service\seminar_session_resource_dynamic;

/**
 * A hook called when seminar session(s) are about to be updated.
 *
 * @package mod_facetoface\hook
 */
class sessions_are_being_updated extends \totara_core\hook\base {
    /**
     * A seminar event associated to the hook. **Do not modify the instance!!**
     * @var seminar_event
     */
    public $seminarevent;

    /**
     * Sessions being inserted. **Do not modify the array!!**
     * @var seminar_session_resource[]
     */
    public $sessionstobeinserted;

    /**
     * Sessions whose time being updated. **Do not modify the array!!**
     * @var seminar_session_resource[]
     */
    public $sessionstobeupdated;

    /**
     * Sessions being deleted. **Do not modify the array!!**
     * @var seminar_session_resource[]
     */
    public $sessionstobedeleted;

    /**
     * The constructor.
     *
     * @param seminar_event $seminarevent
     * @param array $sessionstobeinserted
     * @param array $sessionstobeupdated
     * @param seminar_session_list $sessionstobedeleted
     */
    public function __construct(seminar_event $seminarevent, array $sessionstobeinserted, array $sessionstobeupdated, seminar_session_list $sessionstobedeleted) {
        $this->seminarevent = clone $seminarevent;
        $this->sessionstobeinserted = array_map(function ($sessionrecord) {
            return seminar_session_resource::from_record($sessionrecord);
        }, array_values($sessionstobeinserted));
        $this->sessionstobeupdated = array_map(function ($sessionrecord) {
            return seminar_session_resource::from_record($sessionrecord);
        }, array_values($sessionstobeupdated));
        $this->sessionstobedeleted = array_map(function ($sessiontobedeleted) {
            return seminar_session_resource_dynamic::from_session($sessiontobedeleted);
        }, iterator_to_array($sessionstobedeleted, false));
    }
}
