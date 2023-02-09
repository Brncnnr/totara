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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package mod_contentmarketplace
 */
namespace mod_contentmarketplace\webapi\resolver\type;

use coding_exception;
use core\format;
use mod_contentmarketplace\formatter\learning_object as learning_object_formatter;
use core\webapi\type_resolver;
use core\webapi\execution_context;
use totara_contentmarketplace\learning_object\abstraction\metadata\model;

class learning_object extends type_resolver {
    /**
     * @param string $field
     * @param model $learning_object
     * @param array $args
     * @param execution_context $ec
     * @return mixed|void
     */
    public static function resolve(string $field, $learning_object, array $args, execution_context $ec) {
        if (!($learning_object instanceof model)) {
            throw new coding_exception('Expected learning object model');
        }

        $formatter = new learning_object_formatter($learning_object, $ec->get_relevant_context());

        return $formatter->format($field, $args['format'] ?? format::FORMAT_PLAIN);
    }
}