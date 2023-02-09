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
 * @package totara_engage
 */
namespace totara_engage\rating;

use totara_engage\entity\rating;
use totara_engage\repository\rating_repository;

/**
 * Handle generic ratings
 */
class rating_manager {
    /**
     * Rating component
     * @var string
     */
    private $component;

    /**
     * Rating area (optional)
     * @var string
     */
    private $area;

    /**
     * Rated instance
     * @var int
     */
    private $instanceid;

    /**
     * rating_manager constructor.
     * @param int $instanceid
     * @param string $component
     * @param string|null $area
     */
    private function __construct(int $instanceid, string $component, ?string $area = null) {
        $this->instanceid = $instanceid;
        $this->component = $component;
        $this->area = $area;
    }

    /**
     * Prepare rating manager instance
     *
     * @param int $instanceid
     * @param string $component
     * @param string|null $area
     * @return rating_manager
     */
    public static function instance(int $instanceid, string $component, ?string $area = null): rating_manager {
        return new self($instanceid, $component, $area);
    }

    /**
     * Add new rating
     *
     * @param int $rating
     * @param int|null $userid
     * @return rating
     */
    public function add(int $rating, ?int $userid = null): rating {
        global $USER;
        if (null == $userid) {
            $userid = $USER->id;
        }
        $record = new rating();
        $record->component = $this->component;
        $record->area = $this->area;
        $record->instanceid = $this->instanceid;
        $record->userid = $userid;
        $record->rating = $rating;
        $record->save();

        return $record;
    }

    /**
     * Delete all ratings for the instance
     *
     * @return rating_manager
     */
    public function delete(): rating_manager {
        /** @var rating_repository */
        rating::repository()->delete_for_instance($this->instanceid, $this->component, $this->area);
        return $this;
    }

    /**
     * Get all current ratings of the instance.
     * @return array
     */
    public function get(): array {
        /** @var rating_repository $repo */
        $repo = rating::repository();
        return $repo->get_ratings($this->instanceid, $this->component, $this->area);
    }

    /**
     * Check if user can rate instance
     *
     * @param int|null $userid
     * @param int|null $owner
     * @return bool
     */
    public function can_rate(?int $userid = null, ?int $owner = null): bool {
        global $USER;

        if (null == $userid) {
            $userid = $USER->id;
        }

        // Owners can not rate their own items.
        if (!empty($owner) && $owner == $userid) {
            return false;
        }

        /** @var rating_repository $repo */
        $repo = rating::repository();
        return !$repo->has_rated($userid, $this->instanceid, $this->component, $this->area);
    }

    /**
     * Get average rating
     *
     * @return float
     */
    public function avg(): float {
        /** @var rating_repository $repo */
        $repo = rating::repository();
        $rating = $repo->get_rating_average($this->instanceid, $this->component, $this->area);

        if (!empty($rating)) {
            return (float)ceil($rating * 2) / 2;
        }

        return 0.0;
    }

    /**
     * Get number of ratings for instance
     * @return int
     */
    public function count(): int {
        /** @var rating_repository $repo */
        $repo = rating::repository();
        return $repo->get_rating_count($this->instanceid, $this->component, $this->area);
    }

    /**
     * Get summary of rating for instance
     *
     * @param int|null $userid
     * @return \stdClass
     */
    public function summary(?int $userid = null): \stdClass {
        global $USER;

        if (null == $userid) {
            $userid = $USER->id;
        }

        /** @var rating_repository $repo */
        $repo = rating::repository();

        $obj = new \stdClass();
        $obj->itemid = $this->instanceid;
        $obj->count = $repo->get_rating_count($this->instanceid, $this->component, $this->area);
        $obj->rating = $this->avg();
        $obj->rated = $repo->has_rated($userid, $this->instanceid, $this->component, $this->area);
        return $obj;
    }
}