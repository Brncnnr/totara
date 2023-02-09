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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package engage_survey
 */
namespace engage_survey\event;

use core\event\base;
use engage_survey\totara_engage\resource\survey;
use engage_survey\entity\survey as survey_entity;
use core_ml\event\interaction_event;
use core_ml\event\public_access_aware_event;

abstract class base_survey_event extends base implements interaction_event, public_access_aware_event {
    /**
     * @return void
     */
    protected function init(): void {
        $this->data['objecttable'] = survey_entity::TABLE;
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * @param survey $resource
     * @param int|null $actorid
     *
     * @return base_survey_event
     */
    public static function from_survey(survey $resource, int $actorid = null): base_survey_event {
        global $USER;

        if (!$resource->is_exists(true)) {
            throw new \coding_exception("Unable to create an event for the not-existing survey");
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
            'other' => [
                'name' => $resource->get_name(false),
                'resourceid' => $resource->get_id(),
                'owner_id' => $resource->get_userid(),
                'is_public' => $resource->is_public()
            ]
        ];

        /** @var base_survey_event $event */
        $event = static::create($data);
        return $event;
    }

    /**
     * @return string
     */
    public function get_component(): string {
        return survey::get_resource_type();
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