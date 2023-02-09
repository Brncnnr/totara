<?php
/**
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_playlist
 */
namespace totara_playlist\event;

use core\event\base;
use core_ml\event\interaction_event;
use totara_playlist\entity\playlist as entity;
use totara_playlist\playlist;
use core_ml\event\public_access_aware_event;

abstract class base_playlist extends base implements interaction_event, public_access_aware_event {
    /**
     * @return void
     */
    protected function init(): void {
        $this->data['objecttable'] = entity::TABLE;
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * @param playlist $playlist
     * @param int|null $userid      The actor user who is responsible for triggering this event.
     *
     * @return base_playlist
     */
    public static function from_playlist(playlist $playlist, ?int $userid = null): base_playlist {
        global $USER;
        if (null == $userid) {
            $userid = $USER->id;
        }

        if (!$playlist->exists()) {
            throw new \coding_exception("Cannot create an event from a playlist that is not existing in the system");
        }

        $context = $playlist->get_context();
        $data = [
            'objectid' => $playlist->get_id(),
            'userid' => $userid,
            'context' => $context,
            'relateduserid' => $playlist->get_userid(),
            'other' => ['is_public' => $playlist->is_public()]
        ];

        if (CONTEXT_COURSE == $context->contextlevel) {
            $data['courseid'] = $context->instanceid;
        }

        /** @var base_playlist $event */
        $event = static::create($data);
        return $event;
    }

    /**
     * @return string
     */
    public function get_component(): string {
        return playlist::get_resource_type();
    }

    /**
     * @return int
     */
    public function get_rating(): int {
        return 1;
    }

    /**
     * @return int
     */
    public function get_user_id(): int {
        return $this->userid;
    }

    /**
     * @return int
     */
    public function get_item_id(): int {
        return $this->objectid;
    }

    /**
     * @return string|null
     */
    public function get_area(): ?string {
        return null;
    }

    /**
     * @return bool
     */
    public function is_public(): bool {
        return $this->other['is_public'];
    }
}