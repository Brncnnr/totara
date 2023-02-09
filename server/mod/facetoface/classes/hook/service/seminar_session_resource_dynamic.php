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

namespace mod_facetoface\hook\service;

use coding_exception;
use mod_facetoface\asset_list;
use mod_facetoface\facilitator_list;
use mod_facetoface\factory;
use mod_facetoface\room_list;
use mod_facetoface\seminar_session;
use stdClass;

/**
 * A class that holds the snapshot of a seminar session, assets, rooms and facilitators.
 */
class seminar_session_resource_dynamic extends seminar_session_resource {
    /** @var seminar_session */
    private $session;

    /**
     * Protected constructor.
     *
     * @param seminar_session $session
     */
    protected function __construct(seminar_session $session) {
        $this->session = clone $session;
    }

    /**
     * Create a class instance from a session instance.
     *
     * @param seminar_session $session
     * @return self
     */
    public static function from_session(seminar_session $session): self {
        return new self($session);
    }

    /**
     * @inheritDoc
     */
    public static function from_record(stdClass $sessionrecord, bool $strict = true): seminar_session_resource {
        throw new coding_exception('do not call from_record');
    }

    /**
     * @inheritDoc
     */
    public function get_session_id(): int {
        return $this->session->get_id();
    }

    /**
     * @inheritDoc
     */
    public function has_assets(): bool {
        return $this->has_resource('asset');
    }

    /**
     * @inheritDoc
     */
    public function has_rooms(): bool {
        return $this->has_resource('room');
    }

    /**
     * @inheritDoc
     */
    public function has_facilitators(): bool {
        return $this->has_resource('facilitator');
    }

    /**
     * @inheritDoc
     */
    public function get_session(): seminar_session {
        return $this->session;
    }

    /**
     * @inheritDoc
     */
    public function get_asset_list(): asset_list {
        return asset_list::from_session($this->get_session_id());
    }

    /**
     * @inheritDoc
     */
    public function get_room_list(): room_list {
        return room_list::from_session($this->get_session_id());
    }

    /**
     * @inheritDoc
     */
    public function get_facilitator_list(bool $internal_only): facilitator_list {
        return facilitator_list::from_session($this->get_session_id(), $internal_only);
    }

    /**
     * See if any specific type of resource is attached to the session.
     *
     * @param string $type asset, room or facilitator
     * @return boolean
     */
    private function has_resource(string $type): bool {
        return factory::create_resource_builder($type, 'r')
            ->join(["facetoface_{$type}_dates", 'rd'], 'r.id', '=', "rd.{$type}id")
            ->join(['facetoface_sessions_dates', 'sd'], 'sd.id', '=', 'rd.sessionsdateid')
            ->where('sd.id', $this->get_session_id())
            ->exists();
    }
}
