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

namespace totara_core\relationship\resolvers;

use context;
use totara_core\relationship\relationship_resolver;
use totara_core\relationship\relationship_resolver_dto;

/**
 * Resolves the same user as what was inputted.
 * This is simply to support situations where we want the current user to perform an action on themselves.
 *
 * @package totara_core\relationship
 */
class subject extends relationship_resolver {

    /**
     * Get a list of fields that can be provided to {@see get_users}
     *
     * @return string[][]
     */
    public static function get_accepted_fields(): array {
        return [
            ['user_id'],
        ];
    }

    /**
     * @inheritDoc
     */
    protected function get_data(array $data, context $context): array {
        $dto = new relationship_resolver_dto($data['user_id']);

        return [$dto];
    }

}
