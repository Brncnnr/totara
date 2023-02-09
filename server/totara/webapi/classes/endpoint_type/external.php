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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_webapi
 */

namespace totara_webapi\endpoint_type;

use GraphQL\Validator\Rules\QueryDepth;
use totara_oauth2\webapi\middleware\client_rate_limit;
use totara_oauth2\webapi\middleware\global_rate_limit;
use totara_api\global_api_config;
use totara_webapi\disable_introspection;

class external extends base {

    /**
     * @inheritDoc
     */
    public function validate_schema(): bool {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function allow_direct_queries(): bool {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function allow_persistent_queries(): bool {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function require_sesskey(): bool {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function get_middleware(): array {
        return array_merge(
            parent::get_middleware(),
            [
                client_rate_limit::class,
                global_rate_limit::class,
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function get_validation_rules(): array {
        $rules = [];
        $max_depth = global_api_config::get_max_query_depth();
        if (!is_null($max_depth)) {
            $rules[] = new QueryDepth($max_depth);
        }

        if (!global_api_config::get_enable_introspection()) {
            $rules[] = new disable_introspection();
        }

        return $rules;
    }

    /**
     * @inheritDoc
     */
    public function support_query_complexity(): bool {
        return true;
    }
}