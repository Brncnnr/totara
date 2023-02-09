<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author  Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\signup\condition;

use cache;
use cache_loader;
use mod_facetoface\seminar_session_list;
use mod_facetoface\signup\state\requested;
use mod_facetoface\signup\state\requestedadmin;

defined('MOODLE_INTERNAL') || die();

/**
 * Class user_has_no_conflicts
 */
class user_has_no_conflicts extends condition {

    /**
     * @return cache_loader
     */
    public function get_cache(): cache_loader {
        return cache::make('mod_facetoface', 'user_has_no_conflicts_passed');
    }

    /**
     * @return string
     */
    private function get_cache_key(): string {
        if (empty($this->signup->get_userid())) {
            return '';
        }

        $userid = $this->signup->get_userid();
        $seminarevent = $this->signup->get_seminar_event();
        $cache_key = $this->signup->get_cahce_key();
        return $userid . '_' . $seminarevent->get_id() . '_' . $cache_key;
    }

    /**
     * This can be quite an expensive condition and is tested in multiple state transitions
     * To improve performance, we "cache" the knowledge that the condition passed for a minute.
     *
     * @param array $data
     * @return bool
     */
    private function checked_moments_ago(array $data): bool {
        if (isset($data['queried']) && time() - $data['queried'] < MINSECS) {
            return true;
        }

        return false;
    }

    /**
     * Cache the outcome
     *
     * @param bool $passed
     */
    private function to_cache(bool $passed): void {
        $data = [
            'queried' => time(),
            'passed' => $passed,
        ];

        $cache_key = $this->get_cache_key();
        if (empty($cache_key)) {
            return;
        }

        $cache = $this->get_cache();
        $cache->set($cache_key, $data);
    }

    /**
     * Return the cached outcome
     *
     * @return array
     */
    private function from_cache(): array {
        $cache_key = $this->get_cache_key();
        if (empty($cache_key)) {
            return [];
        }

        $cache = $this->get_cache();
        if (!$cache->has($cache_key)) {
            return [];
        }

        $data = $cache->get($cache_key);
        if ($data === false) {
            // Shouldn't happen, but just in case something happened between ->has and ->get
            return [];
        }

        return $data;
    }

    /**
     * Is the restriction met.
     * @return bool
     */
    public function pass() : bool {
        // Don't bother checking if we are ignoring conflicts.
        if ($this->signup->get_ignoreconflicts()) {
            return true;
        }
        if (empty($this->signup->get_userid())) {
            return false;
        }

        $data = $this->from_cache();
        if ($this->checked_moments_ago($data)) {
            // This can only be true if there were cached data
            return $data['passed'];
        }

        $seminarevent = $this->signup->get_seminar_event();
        $userid = $this->signup->get_userid();

        // If the list of conflict sessions is not empty, then this condition should be failed.
        $conflictsessions = seminar_session_list::from_user_conflicts_with_sessions(
            $userid,
            $seminarevent->get_sessions(),
            null,
            [requested::class, requestedadmin::class]
        );

        $passed = $conflictsessions->is_empty();
        $this->to_cache($passed);

        return $passed;
    }

    /**
     * Get description of condition
     * @return string
     */
    public static function get_description() : string {
        return get_string('state_userhasnoconflicts_desc', 'mod_facetoface');
    }

    /**
     * Return explanation why condition has not passed
     * @return array of strings
     */
    public function get_failure() : array {
        return ['user_has_no_conflicts' => get_string('state_userhasnoconflicts_fail', 'mod_facetoface')];
    }
}
