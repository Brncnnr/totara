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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core
 */
namespace core\webapi\resolver\type;

use core\webapi\execution_context;
use core\webapi\type_resolver;
use core\formatter\user_card_display_field_formatter;
use core_user\profile\card_display_field;
use core\format;

/**
 * Type resolver for 'core_user_display_field'
 */
class user_card_display_field extends type_resolver {
    /**
     * @param string                $field
     * @param card_display_field    $source
     * @param array                 $args
     * @param execution_context     $ec
     *
     * @return mixed|null
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        if (!($source instanceof card_display_field)) {
            throw new \coding_exception(
                "Invalid parameter of source, expecting an instance of class " . card_display_field::class
            );
        }

        // Fallback to context system if the execution_context does not have any relevant context.
        // Note that we cannot go with \context_user because user can be deleted and this code still
        // runs which it will result an empty value as expected.
        $context = \context_system::instance();
        if ($ec->has_relevant_context()) {
            $context = $ec->get_relevant_context();
        }

        $formatter = new user_card_display_field_formatter($source, $context);
        return $formatter->format($field, $args['format'] ?? format::FORMAT_PLAIN);
    }
}