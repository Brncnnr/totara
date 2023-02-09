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

namespace mod_facetoface\dashboard;

use mod_facetoface\event_time;

defined('MOODLE_INTERNAL') || die();

/**
 * A set of render options that is passed to mod_facetoface_renderer::render_session_list()
 */
final class render_session_option {
    /** @var boolean|null */
    private $displaytimezones = null;

    /** @var boolean */
    private $displayreservation = false;

    /** @var boolean */
    private $displaysignupperiod = true;

    /** @var boolean */
    private $displayactions = true;

    /** @var integer[] */
    private $eventtimes = [];

    /** @var boolean */
    private $eventascendingorder = false;

    /** @var boolean */
    private $sessionascendingorder = false;

    /**
     * @return boolean  true to display upcoming events
     */
    public function is_upcoming(): bool {
        return empty($this->eventtimes) || !empty(array_intersect([event_time::ALL, event_time::UPCOMING, event_time::INPROGRESS], $this->eventtimes));
    }

    /**
     * @return boolean  true to display timezones
     */
    public function get_displaytimezones(): bool {
        return $this->displaytimezones ?? (bool)get_config(null, 'facetoface_displaysessiontimezones');
    }

    /**
     * @param boolean|null $value Set true to display timezones, null to use facetoface_displaysessiontimezones config
     * @return render_session_option
     */
    public function set_displaytimezones(?bool $value): render_session_option {
        $this->displaytimezones = $value;
        return $this;
    }

    /**
     * @return boolean  true to display reservation information on upcoming events
     *                  Note that the function returns false if the option is configured not to display upcoming events
     */
    public function get_displayreservation(): bool {
        return $this->displayreservation && $this->is_upcoming();
    }

    /**
     * @param boolean $value Set true to display reservation info above the session list table
     * @return render_session_option
     */
    public function set_displayreservation(bool $value): render_session_option {
        $this->displayreservation = $value;
        return $this;
    }

    /**
     * @return boolean  true to display the "Sign-up period" column
     */
    public function get_displaysignupperiod(): bool {
        return $this->displaysignupperiod;
    }

    /**
     * @param boolean $value Set true to display reservation info above the session list table
     * @return render_session_option
     */
    public function set_displaysignupperiod(bool $value): render_session_option {
        $this->displaysignupperiod = $value;
        return $this;
    }

    /**
     * @return boolean  true to display the "Action" column
     */
    public function get_displayactions(): bool {
        return $this->displayactions;
    }

    /**
     * @param boolean $value Set true to display reservation info above the session list table
     * @return render_session_option
     */
    public function set_displayactions(bool $value): render_session_option {
        $this->displayactions = $value;
        return $this;
    }

    /**
     * @return integer[] containing event_time constants
     */
    public function get_eventtimes(): array {
        return $this->eventtimes;
    }

    /**
     * @param integer[] $values One or more event_time constants
     * @return render_session_option
     */
    public function set_eventtimes(array $values): render_session_option {
        $this->eventtimes = array_unique($values);
        return $this;
    }

    /**
     * @return boolean  true to sort events by past first
     */
    public function get_eventascendingorder(): bool {
        return $this->eventascendingorder;
    }

    /**
     * @param boolean $value Set true to sort events by past first
     * @return render_session_option
     */
    public function set_eventascendingorder(bool $value): render_session_option {
        $this->eventascendingorder = $value;
        return $this;
    }

    /**
     * @return boolean  true to sort sessions by past first
     */
    public function get_sessionascendingorder(): bool {
        return $this->sessionascendingorder;
    }

    /**
     * @param boolean $value    Set true to sort sessions by past first
     * @return render_session_option
     */
    public function set_sessionascendingorder(bool $value): render_session_option {
        $this->sessionascendingorder = $value;
        return $this;
    }

    /**
     * Convert to an object for debugging purposes.
     *
     * @return \stdClass
     */
    public function to_object(): \stdClass {
        return (object)get_object_vars($this);
    }
}
