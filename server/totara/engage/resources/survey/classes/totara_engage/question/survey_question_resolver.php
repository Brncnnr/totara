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
 * @package engage_survey
 */
namespace engage_survey\totara_engage\question;

use core\orm\query\builder;
use engage_survey\entity\survey_question;
use totara_engage\entity\engage_resource;
use totara_engage\question\question_resolver;
use engage_survey\totara_engage\resource\survey;

/**
 * A callback resolver for totara_engage.
 */
final class survey_question_resolver extends question_resolver {
    /**
     * @param int $userid
     * @return bool
     */
    public function can_create(int $userid): bool {
        // Anyone who can create a survey, can actually create the question.
        return survey::can_create($userid);
    }

    /**
     * @param int $userid
     * @param int $questionid
     *
     * @return bool
     */
    public function can_delete(int $userid, int $questionid): bool {
        $builder = builder::table(survey_question::TABLE, 'esq');

        $builder->select('er.id');
        $builder->join([engage_resource::TABLE, 'er'], 'esq.surveyid', 'er.instanceid');

        $builder->where('er.resourcetype', survey::get_resource_type());
        $builder->where('esq.questionid', $questionid);

        $record = $builder->one(true);
        $survey = survey::from_resource_id($record->id);

        // Anyone who has the permission to delete the survey can delete the question.
        return $survey->can_delete($userid);
    }
}