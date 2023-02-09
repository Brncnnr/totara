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
 * @package engage_survey
 */
namespace engage_survey\repository;

use core\orm\entity\repository;
use core\orm\query\builder;
use engage_survey\entity\survey_question;

final class survey_question_repository extends repository {
    /**
     * @param int $surveyid
     * @return survey_question[]
     */
    public function get_all_for_survey(int $surveyid): array {
        $builder = builder::table(static::get_table());
        $builder->map_to(survey_question::class);

        $builder->where('surveyid', $surveyid);
        return $builder->fetch();
    }
}