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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package engage_survey
 */

namespace engage_survey\totara_engage\query\provider;

use core\orm\query\builder;
use engage_survey\totara_engage\resource\survey;
use totara_engage\query\provider\resource_provider;
use totara_engage\entity\question as engage_question;
use engage_survey\entity\survey_question;

final class survey_provider extends resource_provider {

    /**
     * @inheritDoc
     */
    public function get_base_builder(): builder {
        $builder = parent::get_base_builder();
        $builder->join([survey_question::TABLE, 'sq'], 'sq.surveyid', '=', 'er.instanceid');
        $builder->join([engage_question::TABLE, 'eq'], 'eq.id', '=', 'sq.questionid');
        $builder = $builder->where('er.resourcetype', '=', survey::get_resource_type());
        return $builder;
    }

    /**
     * @inheritDoc
     */
    protected function get_resource_type(): string {
        return survey::get_resource_type();
    }

}