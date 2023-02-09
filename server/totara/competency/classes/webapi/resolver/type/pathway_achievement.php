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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com
 * @package totara_competency
 */

namespace totara_competency\webapi\resolver\type;

use context_system;
use core\orm\entity\buffer;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use GraphQL\Deferred;
use totara_competency\entity\pathway_achievement as pathway_achievement_entity;
use totara_competency\formatter;

/**
 * Competency pathway achievement type resolver.
 *
 * Note: It is the responsibility of the query to ensure the user is permitted to see an organisation.
 */
class pathway_achievement extends type_resolver {

    /**
     * @param string $field
     * @param pathway_achievement_entity $value
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(string $field, $value, array $args, execution_context $ec) {
        if (!$value instanceof pathway_achievement_entity) {
            throw new \coding_exception('Please pass a pathway_achievement entity');
        }

        $format = $args['format'] ?? null;

        if (!self::authorize($field, $format)) {
            return null;
        }

        switch ($field) {
            case 'scale_value':
                return new Deferred(buffer::defer($value, 'scale_value'));
            case 'pathway':
                return new Deferred(buffer::defer($value, 'pathway'));
            case 'user':
                return new Deferred(buffer::defer($value, 'user'));
            case 'achieved':
                // This is an alias for has_scale_value
                $field = 'has_scale_value';
                break;
        }

        $formatter = new formatter\pathway_achievement($value, context_system::instance());
        return $formatter->format($field, $format);
    }

    /**
     * Check if access to certain fields is allowed
     *
     * @param string $field
     * @param string $format
     * @return bool
     */
    public static function authorize(string $field, ?string $format) {
        // Nothing to do here for now
        return true;
    }

}
