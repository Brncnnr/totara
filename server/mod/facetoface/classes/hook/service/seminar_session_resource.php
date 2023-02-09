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

use core\orm\query\builder;
use mod_facetoface\asset_list;
use mod_facetoface\facilitator_list;
use mod_facetoface\factory;
use mod_facetoface\room_list;
use mod_facetoface\seminar_event;
use mod_facetoface\seminar_session;
use stdClass;

/**
 * A class that holds the snapshot of a seminar session, assets, rooms and facilitators.
 */
class seminar_session_resource {
    /** @var stdClass */
    private $sessionrecord;

    /** @var boolean */
    private $strict = true;

    /** @var integer[] */
    private $assetids;

    /** @var integer[] */
    private $roomids;

    /** @var integer[] */
    private $facilitatorids;

    /**
     * Protected constructor.
     */
    protected function __construct() {
        $this->sessionrecord = new stdClass();
    }

    /**
     * Create a class instance from a record.
     *
     * @param stdClass $sessionrecord
     * @param boolean $strict Set false to ignore bogus properties
     * @return self
     */
    public static function from_record(stdClass $sessionrecord, bool $strict = true): self {
        $sessionrecord = clone $sessionrecord;
        $self = new self();
        $self->assetids = $sessionrecord->assetids ?? [];
        $self->roomids = $sessionrecord->roomids ?? [];
        $self->facilitatorids = $sessionrecord->facilitatorids ?? [];
        unset($sessionrecord->assetids);
        unset($sessionrecord->roomids);
        unset($sessionrecord->facilitatorids);
        $self->sessionrecord = $sessionrecord;
        $self->strict = $strict;
        return $self;
    }

    /**
     * @return integer
     */
    public function get_session_id(): int {
        return $this->sessionrecord->id;
    }

    /**
     * @return boolean
     */
    public function has_assets(): bool {
        return !empty($this->assetids);
    }

    /**
     * @return boolean
     */
    public function has_rooms(): bool {
        return !empty($this->roomids);
    }

    /**
     * @return boolean
     */
    public function has_facilitators(): bool {
        return !empty($this->facilitatorids);
    }

    /**
     * Return the seminar session instance.
     *
     * @return seminar_session
     */
    public function get_session(): seminar_session {
        $session = new seminar_session();
        $session->from_record($this->sessionrecord, $this->strict);
        return $session;
    }

    /**
     * Return the seminar event instance.
     *
     * @return seminar_event
     */
    public function get_event(): seminar_event {
        $record = builder::table(seminar_session::DBTABLE, 'sd')
            ->join([seminar_event::DBTABLE, 's'], 's.id', '=', 'sd.sessionid')
            ->where('sd.id', $this->get_session_id())
            ->select('s.*')
            ->one(true);
        $event = new seminar_event();
        $event->from_record($record);
        return $event;
    }

    /**
     * Return the asset list.
     *
     * @return asset_list
     */
    public function get_asset_list(): asset_list {
        return self::load_resource_list($this->assetids, 'asset');
    }

    /**
     * Return the room list.
     *
     * @return room_list
     */
    public function get_room_list(): room_list {
        return self::load_resource_list($this->roomids, 'room');
    }

    /**
     * Return the facilitators.
     *
     * @param boolean $internal_only set true to return only internal facilitators
     * @return facilitator_list
     */
    public function get_facilitator_list(bool $internal_only): facilitator_list {
        return self::load_resource_list($this->facilitatorids, 'facilitator', function (builder $builder) use ($internal_only) {
            if ($internal_only) {
                $builder->join(['user', 'u'], 'u.id', '=', 'r.userid', 'inner');
            }
        });
    }

    /**
     * Create a resource list.
     *
     * @param array $ids
     * @param string $type
     * @param callable|null $filter
     * @return asset_list|facilitator_list|room_list the instance of $listclass
     */
    private static function load_resource_list(array $ids, string $type, callable $filter = null) {
        $builder = factory::create_resource_builder($type, 'r');
        if ($filter) {
            $filter($builder);
        }
        $records = $builder->where_in('id', $ids)->order_by('id')->select('r.*')->get()->all();
        $list = factory::create_resource_list($type);
        foreach ($records as $record) {
            $item = factory::create_resource_list_item_from_record($type, $record);
            $list->add($item);
        }
        return $list;
    }
}
