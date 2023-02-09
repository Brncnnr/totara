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
 * @author Marco Song <marco.song@totaralearning.com>
 * @package totara_evidence
 */

namespace totara_evidence\webapi\resolver\type;

use coding_exception;

use context_system;
use core\format;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use totara_evidence\models\evidence_type as evidence_type_model;
use totara_evidence\formatter\evidence_type as evidence_type_formatter;

class evidence_type extends type_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve(string $field, $evidence_type, array $args, execution_context $ec) {
        if (!$evidence_type instanceof evidence_type_model) {
            throw new coding_exception('Expected evidence type model');
        }

        $format = $args['format'] ?? format::FORMAT_HTML;

        $formatter = new evidence_type_formatter($evidence_type, context_system::instance());

        return $formatter->format($field, $format);
    }
}
