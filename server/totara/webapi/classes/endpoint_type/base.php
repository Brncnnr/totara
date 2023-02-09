<?php
/**
 * This file is part of Totara TXP
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package totara_webapi
 */

namespace totara_webapi\endpoint_type;

use GraphQL\Validator\Rules\ValidationRule;

abstract class base {

    /**
     * Return the name of the type class. This shortname is used as a unique identifier when we know we are talking
     * about an API type.
     *
     * @return string
     */
    public static function get_name(): string {
        return (new \ReflectionClass(static::class))->getShortName();
    }

    /**
     * Determines whether to validate the schema during the build step. This will be slow so typically
     * only used for in development.
     *
     * @return bool
     */
    public function validate_schema(): bool {
        return false;
    }

    /**
     * Whether this webapi type allows direct queries.
     *
     * @return bool
     */
    public function allow_direct_queries(): bool {
        return false;
    }

    /**
     * Whether this webapi type allows persistent queries.
     *
     * @return bool
     */
    public function allow_persistent_queries(): bool {
        return true;
    }

    /**
     * Whether this webapi type should check for sesskey or not.
     *
     * @return bool
     */
    public function require_sesskey(): bool {
        return false;
    }

    /**
     * Whether this webapi type should load schemas from all types, or just core and its own.
     *
     * Designed for the 'dev' type so a single comprehensive schema is available.
     *
     * @return bool
     */
    public function use_all_schemas(): bool {
        return false;
    }

    /**
     * The middleware returned by this method is applied to all requests for this endpoint type
     *
     * @return array
     */
    public function get_middleware(): array {
        return [];
    }

    /**
     * Array of GraphQL ValidationRules to apply to all requests for this endpoint type
     *
     * @return array|ValidationRule[]
     */
    public function get_validation_rules(): array {
        return [];
    }

    /**
     * Whether this webapi type should support query complexity or not.
     *
     * @return bool
     */
    public function support_query_complexity(): bool {
        return false;
    }
}