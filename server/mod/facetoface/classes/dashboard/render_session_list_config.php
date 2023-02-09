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

use stdClass;
use coding_exception;
use context;
use mod_facetoface\seminar;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderer options used by session_list_table
 * @property    context $context                module context
 * @property    bool    $viewattendees          mod/facetoface:viewattendees capability
 * @property    bool    $editevents             mod/facetoface:editevents capability
 * @property    bool    $displaytimezones       facetoface_displaysessiontimezones
 * @property    int     $sessionattendance      {facetoface}.sessionattendance - seminar::SESSION_ATTENDANCE_xxx
 * @property    int     $eventattendance        {facetoface}.attendancetime - seminar::EVENT_ATTENDANCE_xxx
 * @property    bool    $dropemptycolumns       {facetoface}.decluttersessiontable
 * @property    bool    $minimal                display only fundamentals for course page
 * @property    bool    $viewsignupperiod       display sign-up period
 * @property    bool    $viewsessionattendance  display session attendance tracking
 * @property    bool    $viewactions            display actions
 * @property    string  $currenturl             $PAGE->url
 * @property    int     $currenttime            current timestamp
 * @property    array   $reserveinfo            NOT USED
 * @property    bool    $returntoallsessions    backtoallsessions parameter
 * @property    int     $userid                 NOT USED
 */
final class render_session_list_config {
    /** @var stdClass */
    private $object;

    /**
     * Constructor.
     *
     * @param seminar $seminar
     * @param context $context
     * @param render_session_option $option
     */
    public function __construct(seminar $seminar, context $context, render_session_option $option) {
        global $PAGE;

        $this->object = new stdClass();
        $this->object->context = $context;
        $this->object->viewattendees = has_capability('mod/facetoface:viewattendees', $context);
        $this->object->editevents = has_capability('mod/facetoface:editevents', $context);
        $this->object->displaytimezones = $option->get_displaytimezones();
        $this->object->sessionattendance = $seminar->get_sessionattendance();
        $this->object->eventattendance = $seminar->get_attendancetime();
        $this->object->dropemptycolumns = (bool)$seminar->get_decluttersessiontable();
        $this->object->minimal = false;
        $this->object->viewsignupperiod = $option->get_displaysignupperiod();
        $this->object->viewsessionattendance = $this->object->sessionattendance != seminar::SESSION_ATTENDANCE_DISABLED && has_capability('mod/facetoface:takeattendance', $context);
        $this->object->viewactions = ($this->object->viewattendees || $this->object->editevents || $option->is_upcoming()) && $option->get_displayactions();
        $this->object->currenturl = $PAGE->url;
        $this->object->currenttime = time();
        $this->object->reserveinfo = array();
        $this->object->returntoallsessions = true;
        $this->object->userid = 0;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get(string $name) {
        if (property_exists($this->object, $name)) {
            return $this->object->{$name};
        }
        throw new coding_exception("Property {$name} is not defined");
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set(string $name, $value): void {
        if (property_exists($this->object, $name)) {
            $this->object->{$name} = $value;
            return;
        }
        throw new coding_exception("Property {$name} is not defined");
    }

    /**
     * @param string $name
     * @return boolean
     */
    public function __isset(string $name): bool {
        return isset($this->object->{$name});
    }

    /**
     * @param string $name
     * @return void
     */
    public function __unset(string $name): void {
        if (isset($this->object->{$name})) {
            throw new coding_exception("Cannot unset property {$name}");
        }
    }
}
