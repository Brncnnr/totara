<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_engage
 */
namespace totara_engage\webapi\resolver\type;

use coding_exception;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use core_user;
use totara_engage\formatter\resource_formatter;
use totara_engage\resource\resource_item;

/**
 * Type resolver for totara_engage_resource
 */
class resource extends type_resolver {
    /**
     * @param string            $field
     * @param resource_item     $source
     * @param array             $args
     * @param execution_context $ec
     *
     * @return mixed
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        if (!($source instanceof resource_item)) {
            throw new coding_exception(
                "Invalid parameter \$source, was expecting the type of " . resource_item::class
            );
        }

        if ('user' === $field) {
            $userid = $source->get_userid();
            return core_user::get_user($userid);
        }

        $format = null;
        if (isset($args['format'])) {
            $format = $args['format'];
        }

        $formatter = new resource_formatter($source);
        return $formatter->format($field, $format);
    }
}