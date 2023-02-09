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

namespace totara_competency\helpers\copy_pathway;

use totara_competency\helpers\error;

/**
 * Holds details of an error that happened during a copy pathways related process.
 */
class errors {
    // Error codes.
    public const CODE_COPY_PATHWAY_SOURCE_HAS_NO_PATHWAYS = 'CODE_COPY_PATHWAY_SOURCE_HAS_NO_PATHWAYS';
    public const CODE_COPY_PATHWAY_MISSING_SOURCE = 'CODE_COPY_PATHWAY_MISSING_SOURCE';

    // Mapping of error codes to lang keys.
    private const LANG_KEYS = [
        self::CODE_COPY_PATHWAY_SOURCE_HAS_NO_PATHWAYS => 'error_copy_pathway_source_unknown',
        self::CODE_COPY_PATHWAY_MISSING_SOURCE => 'error_copy_pathway_source_has_no_pathways'
    ];

    /**
     * Creates an instance of this object.
     *
     * @return error the object.
     */
    public static function missing_source(): error {
        $code = self::CODE_COPY_PATHWAY_MISSING_SOURCE;
        return error::create($code, self::LANG_KEYS[$code]);
    }

    /**
     * Creates an instance of this object.
     *
     * @return error the object.
     */
    public static function source_has_no_pathways(): error {
        $code = self::CODE_COPY_PATHWAY_SOURCE_HAS_NO_PATHWAYS;
        return error::create($code, self::LANG_KEYS[$code]);
    }
}