<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTDvs
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\webapi\resolver\type;

use context_system;
use coding_exception;
use core\format;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use totara_competency\helpers\error as error_model;
use totara_competency\formatter\error as error_formatter;

/**
 * Maps the error class into a GraphQL totara_competency_error type.
 */
class error extends type_resolver {
    /**
     * Default formats.
     */
    private const DEF_FORMATS = [
        'message' => format::FORMAT_PLAIN
    ];

    /**
     * {@inheritdoc}
     */
    public static function resolve(
        string $field,
        $source,
        array $args,
        execution_context $ec
    ) {
        if (!$source instanceof error_model) {
            throw new coding_exception(
                __METHOD__ . ' requires an input ' . error_model::class
            );
        }

        $format = $args['format'] ?? self::DEF_FORMATS[$field] ?? null;
        $context = context_system::instance();

        return (new error_formatter($source, $context))
            ->format($field, $format);
    }
}