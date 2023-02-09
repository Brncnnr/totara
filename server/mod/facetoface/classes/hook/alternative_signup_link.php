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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\hook;

use mod_facetoface\seminar_event;

/**
 * Alternative sign-up link hook.
 *
 * @package mod_facetoface\hook
 */
class alternative_signup_link extends \totara_core\hook\base {

    /**
     * @var seminar_event
     */
    public $seminarevent;

    /**
     * @var string
     */
    public $signuplink;

    /**
     * @var string for terms and conditions
     */
    public $signuptsandcslink;

    /**
     * The constructor.
     *
     * @param seminar_event $seminarevent
     * @param string $signuplink
     * @param string $signuptsandcslink
     */
    public function __construct(seminar_event $seminarevent, string $signuplink = '', string $signuptsandcslink = '') {
        $this->seminarevent = $seminarevent;
        $this->signuplink = $signuplink;
        $this->signuptsandcslink = $signuptsandcslink;
    }
}
