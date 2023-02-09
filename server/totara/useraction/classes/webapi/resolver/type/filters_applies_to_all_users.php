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
use totara_useraction\filter\applies_to;
use totara_useraction\model\scheduled_rule as model;
use totara_useraction\webapi\formatter\filters_formatter;

/**
 * Type resolver for scheduled_rule filters.
 */
class filters_applies_to_all_users extends type_resolver {
    /**
     * @param string $field
     * @param $source
     * @param array $args
     * @param execution_context $ec
     * @return mixed|void
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        if (!($source instanceof applies_to)) {
            throw new coding_exception('Expected a applies_to filter.');
        }

        if ($field === 'label') {
            return get_string('filter_applies_to_all_users', 'totara_useraction');
        }

        throw new \coding_exception('Unexpected applies_to filter field.');
    }
}