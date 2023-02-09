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

namespace mod_facetoface\output\builder;

defined('MOODLE_INTERNAL') || die();

use mod_facetoface\output\seminarevent_detail_session_list;

/**
 * A builder class for seminarevent_detail_session_list.
 */
final class seminarevent_detail_session_list_builder {
    /**
     * @var string
     */
    private $class = '';

    /**
     * @var string
     */
    private $idnum = 0;

    /**
     * @var array
     */
    private $states = [];

    /**
     * @var array
     */
    private $sessions = [];

    /**
     * @param string $idnum
     * @param string $class
     */
    public function __construct(string $idnum, string $class = '') {
        $this->class = $class;
        $this->idnum = $idnum;
    }

    /**
     * Add seminar session information.
     *
     * @param string $status string returned by seminar_session_helper::get_status()
     * @param string $sessiontime HTML string returned by session_time::to_html()
     * @param string[] $states one or more of [started, over, joinnow]
     * @param string[] $actions action links
     * @param string[] $assets asset links
     * @param string[] $rooms room links
     * @param string[] $facilitators facilitator links
     * @return self
     */
    public function add_session(string $status, string $sessiontime, array $states, array $actions, array $assets, array $rooms, array $facilitators) : self {
        // Strip invalid states.
        $states = array_values(array_intersect($states, ['started', 'over', 'joinnow']));
        $this->sessions[] = [
            'status' => $status,
            'sessiontime' => $sessiontime,
            'states' => $states,
            'assets' => $assets,
            'rooms' => $rooms,
            'facilitators' => $facilitators,
            'actions' => $actions,
        ];
        return $this;
    }

    /**
     * Set the states of the seminar event.
     *
     * @param string[] $states one or more of [waitlisted, start, over, cancelled, userbooked, fullybooked, closed]
     * @return self
     */
    public function set_states(array $states) : self {
        // Strip invalid states.
        $states = array_values(array_intersect(['waitlisted', 'started', 'over', 'cancelled', 'userbooked', 'fullybooked', 'closed'], $states));
        $this->states = $states;
        return $this;
    }

    /**
     * Create a seminarevent_detail_session_list object.
     *
     * @return seminarevent_detail_session_list
     */
    public function build(): seminarevent_detail_session_list {
        $data = [
            'class' => $this->class,
            'id' => 'mod_facetoface__detailsection__sessions__'.$this->idnum,
            'label' => get_string('sessionsdatelist', 'mod_facetoface'),
            'states' => $this->states,
            'sessioncount' => count($this->sessions),
            'sessions' => $this->sessions,
        ];
        foreach (['status', 'sessiontime', 'assets', 'rooms', 'facilitators', 'actions'] as $thing) {
            $has = false;
            foreach ($this->sessions as $session) {
                if (!empty($session[$thing])) {
                    $has = true;
                    break;
                }
            }
            $data['has'.$thing] = $has;
        }
        return new seminarevent_detail_session_list($data);
    }
}
