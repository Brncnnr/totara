<?php
/**
 * This file is part of Totara Learn
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package weka_notification_placeholder
 */
namespace weka_notification_placeholder\webapi\resolver\type;

use coding_exception;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use totara_notification\placeholder\option;

class placeholder extends type_resolver {
    /**
     * @param string            $field
     * @param option            $source
     * @param array             $args
     * @param execution_context $ec
     * @return mixed|null
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        if (!($source instanceof option)) {
            throw new coding_exception("Invalid second argument as expecting the instance of " . option::class);
        }

        switch ($field) {
            case 'key':
                return $source->get_key();

            case 'label':
                return $source->get_label();

            default:
                throw new coding_exception("Field '{$field}' not yet support");
        }
    }
}