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

/**
 * A hook called when a seminar event is about to be cancelled or deleted.
 *
 * @package mod_facetoface\hook
 */
class event_is_being_cancelled extends \totara_core\hook\base {
    /**
     * A seminar event associated to the hook. **Do not modify the instance!!**
     * @var seminar_event
     */
    public $seminarevent;

    /**
     * Has the seminar event been cancelled during deletion? **Do not modify the value!!**
     *
     * @var boolean
     */
    public $deleted;

    /**
     * The constructor.
     *
     * @param seminar_event $seminarevent
     * @param boolean $deleted
     */
    public function __construct(seminar_event $seminarevent, bool $deleted) {
        $this->seminarevent = clone $seminarevent;
        $this->deleted = $deleted;
    }
}
