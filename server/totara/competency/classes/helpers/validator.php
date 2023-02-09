<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTDvs
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\helpers;

use core\collection;
use totara_hierarchy\entity\competency;
use totara_hierarchy\entity\competency_framework;

/**
 * Validates conditions for general competency operations.
 */
class validator {
    /**
     * Validates the specified competency frameworks are parents to the given
     * competencies.
     *
     * @param collection<competency_framework> $frameworks parent frameworks.
     * @param collection<competency> $competencies competencies to check.
     *
     * @return ?error if the validation failed.
     */
    public static function competencies_are_in_frameworks(
        collection $frameworks,
        collection $competencies
    ): ?error {
        $fw_ids = array_unique($frameworks->pluck('id'));

        $extra_count = $competencies
            ->filter(
                function (competency $competency) use ($fw_ids): bool {
                    return !in_array($competency->frameworkid, $fw_ids);
                }
            )
            ->count();

        return $extra_count === 0
            ? null
            : error::competencies_not_in_frameworks($extra_count);
    }

    /**
     * Validates the specified competencies exist.
     *
     * @param collection<competency> $competencies competencies to check.
     *
     * @return ?error if the validation failed.
     */
    public static function competencies_exist(
        collection $competencies
    ): ?error {
        $ids = array_unique($competencies->pluck('id'));
        $expected_id_count = count($ids);

        // Since this is done in bulk, it is possible for this SQL statement to
        // exceed the database's allowed packet size. Hence the retrieving of
        // count in batches.
        $actual_count = collection::new(array_chunk($ids, 200))->reduce(
            function (int $actual_count, array $ids): int {
                $count = competency::repository()
                    ->where('id', 'in', $ids)
                    ->count();

                return $actual_count + $count;
            },
            0
        );

        $missing_count = $expected_id_count - $actual_count;
        return $missing_count === 0
            ? null
            : error::missing_competencies($missing_count);
    }

    /**
     * Validates the specified competency list is not empty.
     *
     * @param collection<competency> $competencies competencies to check.
     *
     * @return ?error if the validation failed.
     */
    public static function competencies_not_empty(
        collection $competencies
    ): ?error {
        return $competencies->count() > 0
            ? null
            : error::no_selected_competencies();
    }

    /**
     * Validates the specified competency frameworks exist.
     *
     * @param collection<competency_framework> $frameworks frameworks to check.
     *
     * @return ?error if the validation failed.
     */
    public static function frameworks_exist(collection $frameworks): ?error {
        $ids = array_unique($frameworks->pluck('id'));
        $expected_id_count = count($ids);

        $actual_count = competency_framework::repository()
            ->where('id', 'in', $ids)
            ->count();

        $missing_count = $expected_id_count - $actual_count;
        return $missing_count === 0
            ? null
            : error::missing_frameworks($missing_count);
    }
}