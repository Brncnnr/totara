<?php
/*
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\entity\activity\filters;

use core\orm\entity\filter\filter;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\models\activity\participant_source;

class subject_instances_participant_progress extends filter {

    /**
     * @var string
     */
    protected $participant_instance_alias;

    /**
     * @var bool Whether to exclude the given progress values from the result.
     */
    protected $exclude_progress_values = false;

    /**
     * @var int|null
     */
    protected $relationship_id;

    public function __construct(
        int $participant_id,
        string $participant_instance_alias = 'pi',
        ?int $relationship_id = null
    ) {
        parent::__construct([$participant_id]);
        $this->participant_instance_alias = $participant_instance_alias;
        $this->relationship_id = $relationship_id;
    }

    public function apply(): void {
        $repository = participant_instance_entity::repository()
            ->as('target_participant_progress')
            ->where_raw('target_participant_progress.subject_instance_id = si.id')
            ->where('participant_id', $this->get_participant_id())
            ->where('participant_source', participant_source::INTERNAL);

        if (!empty($this->relationship_id)) {
            $repository->where('core_relationship_id', $this->relationship_id);
        }

        if ($this->exclude_progress_values) {
            $repository->where_not_in('progress', $this->value);
        } else {
            $repository->where('progress', $this->value);
        }

        $this->builder->where_exists($repository->get_builder());
    }

    public function exclude_progress_values(): self {
        $this->exclude_progress_values = true;
        return $this;
    }

    protected function get_participant_id() {
        return $this->params[0] ?? null;
    }
}