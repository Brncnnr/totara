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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package core_cohort
 */
namespace core\entity;

defined('MOODLE_INTERNAL') || die();

use cohort;
use coding_exception;
use core\orm\entity\filter\equal;
use core\orm\entity\filter\filter;
use core\orm\entity\filter\in;
use core\orm\entity\filter\like;

/**
 * Convenience filters to use with the cohort entity.
 */
final class cohort_filters {
    /**
     * Returns the appropriate filter given the query key.
     *
     * @param string $key query key.
     * @param mixed $value search value(s) .
     *
     * @return filter the filter if it was found or null if it wasn't.
     */
    public static function for(string $key, $value): ?filter {
        switch ($key) {
            case 'active':
                return self::create_active_filter($value);

            case 'context_ids':
                $values = is_array($value) ? $value : [$value];
                return self::create_context_id_filter($values);

            case 'ids':
                $values = is_array($value) ? $value : [$value];
                return self::create_id_filter($values);

            case 'name':
                return self::create_name_filter($value);

            case 'type':
                return self::create_type_filter($value);
        }

        return null;
    }

    /**
     * Returns an instance of an active cohort filter.
     *
     * @param bool $value true if searching for active cohorts, false to find
     *        inactive ones.
     *
     * @return filter the filter instance.
     */
    public static function create_active_filter(bool $value): filter {
        return (new equal('active'))
            ->set_value($value)
            ->set_entity_class(cohort::class);
    }

    /**
     * Returns an instance of a context id filter.
     *
     * @param int[] $values the matching values. Note this may be an empty array
     *        in which this filter will return nothing.
     *
     * @return filter the filter instance.
     */
    public static function create_context_id_filter(array $values): filter {
        return (new in('contextid'))
            ->set_value($values)
            ->set_entity_class(cohort::class);
    }

    /**
     * Returns an instance of a cohort id filter.
     *
     * @param int[] $values the matching values. Note this may be an empty array
     *        in which this filter will return nothing.
     *
     * @return filter the filter instance.
     */
    public static function create_id_filter(array $values): filter {
        return (new in('id'))
            ->set_value($values)
            ->set_entity_class(cohort::class);
    }

    /**
     * Returns an instance of a cohort name filter.
     *
     * Note this does like '%name%" matches.
     *
     * @param string $value the matching value(s).
     *
     * @return filter the filter instance.
     */
    public static function create_name_filter(string $value): filter {
        return (new like('name'))
            ->set_value($value)
            ->set_entity_class(cohort::class);
    }

    /**
     * Returns an instance of a cohort type filter.
     *
     * @param int $value the matching value(s). Either cohort::TYPE_STATIC or
     *        cohort::TYPE_DYNAMIC.
     *
     * @return filter the filter instance.
     */
    public static function create_type_filter(int $value): filter {
        global $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');

        $cohort_types = [cohort::TYPE_STATIC, cohort::TYPE_DYNAMIC];
        if (!in_array($value, $cohort_types, true)) {
            $allowed = implode(', ', $cohort_types);
            throw new coding_exception("cohort type filter only accepts $allowed");
        }

        return (new equal('cohorttype'))
            ->set_value($value)
            ->set_entity_class(cohort::class);
    }
}
