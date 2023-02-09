<?php
/*
* This file is part of Totara Learn
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
* @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
* @package mod_facetoface
*/

namespace mod_facetoface;

use html_writer;
use coding_exception;
use mod_facetoface\facilitator;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Class facilitator_user combines a facilitator instance with a formatted user fullname if the facilitator is internal.
 *
 * @package mod_facetoface
 *
 * @method boolean exists()
 * @method boolean is_used()
 * @method boolean is_available()
 * @method boolean has_conflicts()
 * @method integer get_id()
 * @method integer get_userid()
 * @method string  get_name()
 * @method string  get_description()
 * @method boolean get_allowconflicts()
 * @method integer get_usercreated()
 * @method integer get_usermodified()
 * @method integer get_timecreated()
 * @method integer get_timemodified()
 * @method boolean get_custom()
 * @method boolean get_hidden()
 * @method boolean can_show()
 */
class facilitator_user {

    /** @var \mod_facetoface\facilitator $facilitator */
    private $facilitator = null;

    /** @var string $fullname */
    private $fullname = null;

    /**
     * Seminar facilitator_user constructor
     * @param facilitator|stdClass|integer $facilitator
     */
    public function __construct($facilitator) {
        if ($facilitator instanceof facilitator) {
            $this->facilitator = $facilitator;
        } else if (is_object($facilitator)) {
            $this->facilitator = new facilitator();
            $this->facilitator->from_record((object)$facilitator);
        } else {
            $this->facilitator = new facilitator($facilitator);
        }
        $this->set_fullname($facilitator);
    }

    /**
     * Mostly for "get_property" methods
     * @param string $method
     * @param array $arguments
     */
    public function __call(string $method, array $arguments = []) {
        if (method_exists($this->facilitator, $method)) {
            return $this->facilitator->{$method}(...$arguments);
        }
        debugging('Invalid class method accessed! ' . $method, DEBUG_DEVELOPER);
        return null;
    }

    /**
     * Load record from $userid, if it is the invalid $userid, that does not exist within the database.
     * @param int $userid
     * @return facilitator_user
     */
    public static function seek_by_userid(int $userid): facilitator_user {
        global $DB;

        $facilitator = new facilitator();
        if ($userid == 0) {
            return new static($facilitator);
        }

        $usernamefields = get_all_user_name_fields(true, 'u');
        $sql = "SELECT ff.*, {$usernamefields}
                  FROM {facetoface_facilitator} ff
             LEFT JOIN {user} u ON u.id = ff.userid
                 WHERE ff.userid = :userid";
        $record = $DB->get_record_sql($sql, ['userid' => $userid]);
        if (!$record) {
            return new static($facilitator);
        }
        return new static($record);
    }

    /**
     * Get user(not facilitator) full name
     * @return string
     */
    public function get_fullname(): string {
        return $this->fullname;
    }

    /**
     * Get user(not facilitator) full name link to user profile or not
     * depends from capabilities
     * @param bool $link
     * @return string
     */
    public function get_fullname_link(bool $link = true): string {
        global $OUTPUT;
        if (empty($this->fullname)) {
            return '';
        }

        $userid = $this->facilitator->get_userid();
        if (static::is_userid_active($userid)) {
            if (!$link) {
                return $this->fullname;
            }
            return user_helper::get_profile($userid, $this->fullname);
        } else {
            $icon = $OUTPUT->flex_icon('warning', [
                'classes' => 'ft-size-100 ft-state-warning',
                'alt' => get_string('facilitatoruserdeleted', 'mod_facetoface')
            ]);
            return $this->fullname . $icon;
        }
    }

    /**
     * Set user(not facilitator) full name
     * @param facilitator|stdClass $facilitator
     */
    private function set_fullname($facilitator): void {
        if (!(bool)$this->facilitator->get_userid()) {
            $this->fullname = '';
            return;
        }
        if ($facilitator instanceof facilitator) {
            $user = \core_user::get_user($facilitator->get_userid());
        } else {
            $user = (object)$facilitator;
        }
        $this->fullname = format_string(fullname($user));
    }

    /**
     * If user deleted, confirmed, suspened or guest.
     * @param int $userid
     * @return bool
     */
    public static function is_userid_active(int $userid): bool {
        if ($userid == 0) {
            return false;
        }
        $user = \core_user::get_user($userid);
        try {
            \core_user::require_active_user($user, true);
        } catch (\moodle_exception $me) {
            return false;
        }
        return true;
    }

    /**
     * Get a facilitator's name for displaying.
     * @return string
     */
    public function get_display_name(): string {
        if (!empty($this->get_fullname())) {
            $a = (object)['name' => $this->get_name(), 'fullname' => $this->get_fullname()];
            return get_string('facilitatordisplayname', 'mod_facetoface', $a);
        }
        return $this->get_name();
    }
}
