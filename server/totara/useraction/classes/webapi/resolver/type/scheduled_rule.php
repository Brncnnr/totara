<?php
/**
 * This file is part of Totara Talent Experience Platform
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
 * @author Cody Finegan <cody.finegan@totara.com>
 * @package totara_useraction
 */

namespace totara_useraction\webapi\resolver\type;

use coding_exception;
use core\format;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use totara_useraction\webapi\formatter\scheduled_rule_formatter;
use totara_useraction\model\scheduled_rule as model;

/**
 * Type resolver for scheduled_rule model
 */
class scheduled_rule extends type_resolver {
    /**
     * @param string $field
     * @param $source
     * @param array $args
     * @param execution_context $ec
     * @return mixed|void
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        if (!($source instanceof model)) {
            throw new coding_exception('Expected a scheduled_rule model');
        }

        // Filters are a virtual field, so bypass the regular model formatter
        if ($field === 'filters') {
            return $source;
        }

        $context = $source->get_context();
        $formatter = new scheduled_rule_formatter($source, $context);

        return $formatter->format($field, $args['format'] ?? format::FORMAT_PLAIN);
    }
}