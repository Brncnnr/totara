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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_comment
 */
namespace totara_comment\webapi\resolver\type;

use core\webapi\execution_context;
use core\webapi\type_resolver;
use totara_comment\comment;
use totara_comment\formatter\reply_formatter;
use totara_comment\interactor\reply_interactor;

/**
 * Resolver for reply type
 */
class reply extends type_resolver {
    /**
     * @param string            $field
     * @param comment           $source
     * @param array             $args
     * @param execution_context $ec
     *
     * @return mixed
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        global $USER;

        if (!($source instanceof comment)) {
            throw new \coding_exception("Invalid type of source");
        } else if (!$source->is_reply()) {
            throw new \coding_exception("The comment is not a reply");
        }

        if ('interactor' === $field) {
            $user_id = $USER->id;
            if (isset($args['user_id'])) {
                $user_id = $args['user_id'];
            }

            return new reply_interactor($source, $user_id);
        }

        $format = null;
        if (isset($args['format'])) {
            $format = $args['format'];
        }

        $formatter = new reply_formatter($source);
        return $formatter->format($field, $format);
    }
}