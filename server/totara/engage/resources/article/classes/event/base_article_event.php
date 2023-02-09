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
 * @package engage_article
 */
namespace engage_article\event;

use core\event\base;
use engage_article\entity\article as article_entity;
use engage_article\totara_engage\resource\article;
use core_ml\event\interaction_event;
use core_ml\event\public_access_aware_event;

abstract class base_article_event extends base implements interaction_event, public_access_aware_event {
    /**
     * @return void
     */
    protected function init(): void {
        $this->data['objecttable'] = article_entity::TABLE;
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * @param article   $resource
     * @param int|null  $actorid
     *
     * @return base_article_event
     */
    public static function from_article(article $resource, int $actorid = null): base_article_event {
        global $USER;

        if (!$resource->is_exists(true)) {
            throw new \coding_exception("Unable to create an event for the not-existing article");
        }

        if (null == $actorid) {
            $actorid = $USER->id;
        }

        $ownerid = $resource->get_userid();
        $context = \context_user::instance($ownerid);

        $data = [
            'objectid' => $resource->get_instanceid(),
            'context' => $context,
            'userid' => $actorid,
            'relateduserid' => $ownerid,
            'other' => [
                'name' => $resource->get_name(false),
                'resourceid' => $resource->get_id(),
                'owner_id' => $ownerid,
                'is_public' => $resource->is_public()
            ]
        ];

        /** @var base_article_event $event */
        $event = static::create($data);
        return $event;
    }

    /**
     * @return string
     */
    public function get_component(): string {
        return article::get_resource_type();
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
        return $this->other['resourceid'];
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