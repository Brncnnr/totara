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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\entity\activity\filters;

use coding_exception;
use core\orm\entity\filter\filter;
use mod_perform\entity\activity\participant_instance;
use mod_perform\models\activity\participant_source;

class subject_instances_about_role extends filter {
    public function __construct(int $participant_id) {
        parent::__construct([$participant_id]);
    }

    /**
     * @inheritdoc
     */
    public function apply(): void {
        $repository = participant_instance::repository()
            ->as('target_role')
            ->where_raw('target_role.subject_instance_id = si.id')
            ->where('participant_source', participant_source::INTERNAL)
            ->where('participant_id', $this->params[0])
            ->where('target_role.core_relationship_id', (int)$this->value);

        $this->builder->where_exists($repository->get_builder());
    }
}
