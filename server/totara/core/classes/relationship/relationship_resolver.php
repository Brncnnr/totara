<?php
/*
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\relationship;

use coding_exception;
use context;

/**
 * Abstract class relationship_resolver.
 * Specifies a reusable relationship resolver class which takes inputs and returns a list of users.
 *
 * @package totara_core
 */
abstract class relationship_resolver {

    public const SOURCE = 'internal';

    /**
     * @var relationship
     */
    protected $parent_relationship;

    public function __construct(relationship $parent_relationship) {
        $this->parent_relationship = $parent_relationship;
    }

    /**
     * Get a list of all fields that must be provided to {@see get_users}.
     *
     * Here we define an array of what input combinations are allowed.
     * Examples:
     * [ ['user_id'] ] = 'user_id' MUST be provided.
     * [ ['job_assignment_id'] ] = 'job_assignment_id' MUST be provided.
     * [ ['user_id', 'job_assignment_id'] ] = 'user_id' AND 'job_assignment_id' MUST be provided.
     * [ ['user_id'], ['job_assignment_id'] ] = 'user_id' OR 'job_assignment_id' MUST be provided.
     * [ ['user_id', 'job_assignment_id'], ['seminar_id'] ] = ('user_id' AND 'job_assignment_id') OR 'seminar_id' MUST be provided.
     *
     * @return string[][]
     */
    abstract public static function get_accepted_fields(): array;

    /**
     * Are the fields given to this resolver valid?
     *
     * @param string[] $fields fields to check e.g. ['job_assignment_id'] or ['user_id', 'course_id']
     * @return bool
     */
    public static function is_acceptable_input(array $fields): bool {
        if (empty($fields)) {
            return false;
        }

        foreach (static::get_accepted_fields() as $accepted_fields) {
            if (empty(array_diff($accepted_fields, $fields))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Throw exception if the fields passed to this resolver are invalid.
     *
     * @param array $fields fields to check e.g. ['user_id'] or ['job_assignment_id']
     * @throws coding_exception
     */
    final protected static function validate_input(array $fields): void {
        if (!static::is_acceptable_input($fields)) {
            throw new coding_exception('The fields inputted into the ' . static::class .' relationship resolver are invalid');
        }
    }

    /**
     * Check if resolvers have the same accepted inputs.
     *
     * @param relationship_resolver|string ...$resolvers Resolver class names
     * @return bool True if accepted inputs are identical for all specified resolvers.
     */
    final public static function are_resolvers_compatible(...$resolvers): bool {
        /** @var relationship_resolver $first_resolver */
        $first_resolver = array_shift($resolvers);

        foreach ($first_resolver::get_accepted_fields() as $first_resolver_fields) {
            $resolvers_are_compatible = true;
            foreach ($resolvers as $resolver) {
                $resolvers_are_compatible &= $resolver::is_acceptable_input($first_resolver_fields);
            }
            if ($resolvers_are_compatible) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the list of users.
     *
     * @param array $data containing the fields specified by {@see get_accepted_fields}
     * @param context $context
     * @return relationship_resolver_dto[]
     */
    abstract protected function get_data(array $data, context $context): array;

    /**
     * Validate the input and get the list of users.
     *
     * @param array $data containing the fields specified by {@see get_accepted_fields}
     * @param context $context
     * @return relationship_resolver_dto[]
     */
    final public function get_users(array $data, context $context): array {
        global $CFG;
        if ($CFG->debugdeveloper) {
            // Don't validate the input on production sites for better performance.
            static::validate_input(array_keys($data));
        }

        $relationship_resolver_dtos = $this->get_data($data, $context);

        if ($CFG->debugdeveloper) {
            //validate get_data() returns an array consisting only of dtos
            foreach ($relationship_resolver_dtos as $dto) {
                if (!$dto instanceof relationship_resolver_dto) {
                    throw new coding_exception("get_data must return relationship_resolver_dto");
                }
            }
        }

        return $relationship_resolver_dtos;
    }

}
