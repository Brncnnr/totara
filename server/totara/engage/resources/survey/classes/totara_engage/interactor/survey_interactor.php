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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package engage_survey
 */

namespace engage_survey\totara_engage\interactor;

use engage_survey\totara_engage\resource\survey;
use totara_engage\access\accessible;
use totara_engage\interactor\interactor;

class survey_interactor extends interactor {

    /**
     * @inheritDoc
     */
    public static function create_from_accessible(accessible $resource, ?int $actor_id = null): interactor {
        if (!($resource instanceof survey)) {
            throw new \coding_exception('Invalid accessible resource for survey interactor');
        }

        /** @var survey $survey */
        $survey = $resource;

        return new self($survey->to_array(), $actor_id);
    }

}