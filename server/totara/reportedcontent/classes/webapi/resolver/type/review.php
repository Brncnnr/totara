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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package totara_reportedcontent
 */

namespace totara_reportedcontent\webapi\resolver\type;

use core\webapi\execution_context;
use core\webapi\type_resolver;
use totara_reportedcontent\formatter\review_formatter;
use totara_reportedcontent\review as model;

/**
 * Type resolver for totara_comment_comment
 */
class review extends type_resolver {
    /**
     * @param string $field
     * @param model $source
     * @param array $args
     * @param execution_context $ec
     *
     * @return mixed
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        if (!($source instanceof model)) {
            $cls = model::class;
            throw new \coding_exception(
                "Expecting parameter \$source to be an instance of '{$cls}'"
            );
        }

        $formatter = new review_formatter($source);

        $format = null;
        if (isset($args['format'])) {
            $format = $args['format'];
        }

        return $formatter->format($field, $format);
    }
}