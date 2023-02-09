<?php
/**
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
 * @package virtualmeeting_poc_app
 */

namespace virtualmeeting_poc_app;

use totara_core\virtualmeeting\dto\meeting_dto;
use totara_core\virtualmeeting\dto\meeting_edit_dto;
use totara_core\virtualmeeting\exception\meeting_exception;
use totara_core\virtualmeeting\plugin\provider\provider;
use core_user;
use moodle_url;
use totara_core\virtualmeeting\exception\unsupported_exception;
use totara_core\virtualmeeting\plugin\feature;

/**
 * PoC meeting service provider
 * A meeting with name containing 'fail' is permanently banned.
 */
class poc_service_provider implements provider {
    /** @var string */
    private $name;

    /** @var boolean */
    private $peruser;

    /**
     * Constructor.
     *
     * @param string $name app or user
     * @param boolean $peruser whether user auth is available or not
     */
    public function __construct(string $name, bool $peruser) {
        $this->name = $name;
        $this->peruser = $peruser;
    }

    /**
     * Return a meeting URL.
     *
     * @param meeting_edit_dto $meeting
     * @param string  $username
     * @param integer $age
     * @param boolean $host
     * @return string
     */
    private function make_url(meeting_edit_dto $meeting, string $username, int $age, bool $host): string {
        $url = new moodle_url(
            "/integrations/virtualmeeting/poc_{$this->name}/meet.php",
            [
                'name' => $meeting->name,
                'timestart' => $meeting->timestart->getTimestamp(),
                'timefinish' => $meeting->timefinish->getTimestamp(),
                'username' => $username,
                'age' => $age,
                'host' => (int)$host
            ]
        );
        return $url->out(false);
    }

    /**
     * Get the username from the meeting_dto object.
     *
     * @param meeting_dto $meeting
     * @return string
     */
    private function extract_username(meeting_dto $meeting): string {
        if ($this->peruser) {
            return $meeting->user->get_token();
        } else {
            return core_user::get_user($meeting->userid, '*', MUST_EXIST)->username;
        }
    }

    /**
     * Create or update a virtual meeting URL.
     *
     * @param meeting_edit_dto $meeting
     * @param boolean $update
     * @return void
     */
    private function create_or_update(meeting_edit_dto $meeting, bool $update): void {
        if (strpos($meeting->name, 'fail') !== false) {
            throw new meeting_exception('you are failed');
        }
        $username = $this->extract_username($meeting);
        $age = $meeting->get_storage()->get('age') ?? 0;
        if (get_config("virtualmeeting_poc_{$this->name}", 'feature__' . feature::LOSSY_UPDATE)) {
            $age++; // new age
        }
        $meeting->get_storage()
            ->delete_all()
            ->set('id', $update ? 'updated' : 'created')
            ->set('age', $age)
            ->set('join_url', $this->make_url($meeting, $username, $age, false));
        if (strpos($meeting->name, 'nohost') === false) {
            $meeting->get_storage()->set('host_url', $this->make_url($meeting, $username, $age, true));
        }
    }

    /**
     * @inheritDoc
     */
    public function create_meeting(meeting_edit_dto $meeting): void {
        $this->create_or_update($meeting, false);
    }

    /**
     * @inheritDoc
     */
    public function update_meeting(meeting_edit_dto $meeting): void {
        $id = $meeting->get_storage()->get('id');
        if ($id !== 'created' && $id !== 'updated') {
            throw new meeting_exception('meeting not created');
        }
        $this->create_or_update($meeting, true);
    }

    /**
     * @inheritDoc
     */
    public function delete_meeting(meeting_dto $meeting): void {
        $id = $meeting->get_storage()->get('id');
        if ($id !== 'created' && $id !== 'updated') {
            return;
        }
        $meeting->get_storage()->delete_all();
    }

    /**
     * @inheritDoc
     */
    public function get_join_url(meeting_dto $meeting): string {
        return $meeting->get_storage()->get('join_url', true);
    }

    /**
     * @inheritDoc
     */
    public function get_info(meeting_dto $meeting, string $what): string {
        if (!get_config("virtualmeeting_poc_{$this->name}", "info__{$what}")) {
            throw unsupported_exception::info("poc_{$this->name}");
        }
        if ($what === provider::INFO_HOST_URL) {
            return $meeting->get_storage()->get('host_url', true);
        } else if ($what === provider::INFO_INVITATION) {
            $username = $this->extract_username($meeting);
            return '<p>invitation from '.s($username).'</p>';
        } else if ($what === provider::INFO_PREVIEW) {
            $username = $this->extract_username($meeting);
            return '<p>info from '.s($username).'</p>';
        }
        throw unsupported_exception::info("poc_{$this->name}");
    }
}
