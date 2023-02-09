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
use totara_hierarchy\entity\competency;

/**
 * Validates conditions for copying pathway operations.
 */
class validator {
    /**
     * Validates the specified source competency exists.
     *
     * @param competency $competency competency to check.
     *
     * @return ?error if the validation failed.
     */
    public static function source_exists(competency $competency): ?error {
        $exists = competency::repository()
            ->where('id', $competency->id)
            ->exists();

        return $exists ? null : errors::missing_source();
    }

    /**
     * Validates the source has active pathways to copy.
     *
     * @return ?error if the validation failed.
     */
    public static function source_has_active_pathways(
        competency $source
    ): ?error {
        return $source->active_pathways()->get()->count() > 0
            ? null
            : errors::source_has_no_pathways();
    }
}