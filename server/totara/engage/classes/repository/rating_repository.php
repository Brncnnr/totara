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
namespace totara_engage\repository;

use core\orm\entity\repository;
use core\orm\query\builder;
use totara_engage\entity\rating;

final class rating_repository extends repository {
    /**
     * Get all rating entities
     *
     * @param int $instanceid
     * @param string $component
     * @param string $area
     * @return array
     */
    public function get_ratings(int $instanceid, string $component, ?string $area = null): array {
        return $this->build_base_query($instanceid, $component, $area)->fetch();
    }

    /**
     * Check if user has rated the instance
     *
     * @param int $userid
     * @param int $instanceid
     * @param string $component
     * @param string $area
     * @return bool
     */
    public function has_rated(int $userid, int $instanceid, string $component, ?string $area = null): bool {
        return $this->build_base_query($instanceid, $component, $area)
            ->where('userid', $userid)
            ->exists();
    }

    /**
     * Get total rating of one resource
     *
     * @param int $instanceid
     * @param string $component
     * @param string $area
     * @return int
     */
    public function get_rating_count(int $instanceid, string $component, ?string $area = null): int {
        return $this->build_base_query($instanceid, $component, $area)->count();
    }

    /**
     * Get average rating of one resource.
     *
     * @param int $itemid
     * @param string $component
     * @param string $ratingarea
     * @return float
     */
    public function get_rating_average(int $instanceid, string $component, ?string $area = null): float {
        $record = $this->build_base_query($instanceid, $component, $area)
            ->select_raw('AVG(r.rating) as rating')
            ->one();
        return (float)$record->rating;
    }

    /**
     * Delete all ratings for the instance
     * @param int $instanceid
     * @param string $component
     * @param string|null $area
     * @return void
     */
    public function delete_for_instance(int $instanceid, string $component, ?string $area = null): void  {
        $this->build_base_query($instanceid, $component, $area)->delete();
    }

    /**
     * Get commonly used part of the rating query
     * @param int $itemid
     * @param string $component
     * @param string $ratingarea
     * @return builder
     */
    private function build_base_query(int $instanceid, string $component, ?string $area = null): builder {
        $builder = builder::table(rating::TABLE, 'r');
        $builder->map_to(rating::class);
        $builder->where('instanceid', $instanceid)
            ->where('component', $component);
        if (isset($area)) {
            $builder->where('area', $area);
        }
        return $builder;
    }
}