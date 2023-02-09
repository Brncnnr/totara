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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\data_providers\activity;

use core\collection;
use core\orm\entity\repository;
use core\orm\query\field;
use mod_perform\entity\activity\activity as activity_entity;
use mod_perform\models\activity\activity;
use mod_perform\rb\util as rb_util;
use mod_perform\state\activity\draft;

/**
 * Class element_identifier
 * @package mod_perform
 */
class reportable_activities {

    /**
     * @var collection
     */
    private $items = null;

    /**
     *
     * @return collection of activities
     */
    public function get(): collection {
        if (is_null($this->items)) {
            $this->fetch();
        }
        return $this->items;
    }

    /**
     *
     * @return reportable_activities this object.
     */
    public function fetch(): reportable_activities {
        //get reportable activities
        $this->items = $this->get_reportable_activities();

        return $this;
    }

    /**
     * Get Reportable activities
     *
     * @return collection
     */
    private function get_reportable_activities() :collection {
        global $USER;

        return activity_entity::repository()
            ->when(true, function (repository $repository) use ($USER) {
                [$activities_sql, $activities_params] = rb_util::get_report_on_subjects_activities_sql(
                    $USER->id,
                    (new field('id', $repository->get_builder()))->sql()
                );

                $repository->where_raw($activities_sql, $activities_params);
            })
            ->filter_by_visible()
            ->where('status', '<>', draft::get_code())
            ->order_by('name')
            ->get()
            ->map_to(activity::class);
    }
}