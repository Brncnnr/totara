<?php
/**
 * This file is part of Totara Core
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @package totara_oauth2
 */

namespace totara_oauth2\webapi\resolver\mutation;

use core\webapi\execution_context;
use core\webapi\middleware\require_login;
use core\webapi\middleware\require_system_capability;
use core\webapi\mutation_resolver;
use core_text;
use totara_oauth2\config;
use totara_oauth2\exception\create_provider_exception;
use totara_oauth2\model\client_provider;

/**
 * Class create_provider
 * @package totara_oauth2\webapi\resolver\mutation
 */
class create_provider extends mutation_resolver {
    /**
     * @var int
     */
    private const NAME_LENGTH = 75;

    /**
     * @var int
     */
    private const DESCRIPTION_LENGTH = 1024;

    /**
     *
     * @param array             $args
     * @param execution_context $ec
     * @return client_provider
     */
    public static function resolve(array $args, execution_context $ec): client_provider{
        $input = $args['input'];
        $name = $input['name'];
        $description = $input['description'] ?? '';
        $scope_type = $input['scope_type'];
        $format = $input['format'] ?? FORMAT_PLAIN;

        if (!isset($name)) {
            throw new create_provider_exception(get_string('error_provider_name_missing', 'totara_oauth2'));
        }

        if (core_text::strlen($name) > self::NAME_LENGTH) {
            throw new create_provider_exception(get_string('error_provider_name_length', 'totara_oauth2'));
        }

        if (core_text::strlen($description) > self::DESCRIPTION_LENGTH) {
            throw new create_provider_exception(get_string('error_provider_description_length', 'totara_oauth2'));
        }

        if ($format != FORMAT_PLAIN) {
            throw new create_provider_exception(get_string('error_invalid_format', 'totara_oauth2'));
        }

        if (!isset($scope_type)) {
            throw new create_provider_exception(get_string('error_scope_type_missing', 'totara_oauth2'));
        }

        $value = config::get_scope_type_value($scope_type);
        if (is_null($value)) {
            throw new create_provider_exception(get_string('error_scope_type', 'totara_oauth2'));
        }

        return client_provider::create($name, $value, $format, $description);
    }

    /**
     * @return array
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_system_capability('totara/oauth2:manageproviders')
        ];
    }
}