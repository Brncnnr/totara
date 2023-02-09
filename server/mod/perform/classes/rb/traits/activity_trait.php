<?php
/**
 * This file is part of Totara Perform
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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\rb\traits;

use coding_exception;
use mod_perform\entity\activity\activity_type as activity_type_entity;
use mod_perform\models\activity\activity_type as activity_type_model;
use rb_base_source;
use rb_column_option;
use rb_filter_option;
use rb_join;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("{$CFG->dirroot}/totara/reportbuilder/lib.php");

/**
 * Trait activity_trait
 */
trait activity_trait {
    /** @var string $activity_join */
    protected $activity_join = null;

    /**
     * Add activity info where activity is the base table.
     *
     * @throws coding_exception
     */
    protected function add_activity_to_base() {
        /** @var activity_trait|rb_base_source $this */
        if (isset($this->activity_join)) {
            throw new coding_exception('Activity info can be added only once!');
        }

        $this->activity_join = 'base';

        // Add component for lookup of display functions and other stuff.
        if (!in_array('mod_perform', $this->usedcomponents, true)) {
            $this->usedcomponents[] = 'mod_perform';
        }

        $this->add_activity_joins();
        $this->add_activity_columns();
        $this->add_activity_filters();
    }

    /**
     * Add activity info.
     * If a new join isn't specified then the existing join will be used.
     *
     * @param rb_join $join
     * @throws coding_exception
     */
    protected function add_activity(rb_join $join = null): void {
        $join = $join ?? $this->get_join('perform');

        /** @var activity_trait|rb_base_source $this */
        if (isset($this->activity_join)) {
            throw new coding_exception('Activity info can be added only once!');
        }

        if (!in_array($join, $this->joinlist, true)) {
            $this->joinlist[] = $join;
        }
        $this->activity_join = $join->name;

        // Add component for lookup of display functions and other stuff.
        if (!in_array('mod_perform', $this->usedcomponents, true)) {
            $this->usedcomponents[] = 'mod_perform';
        }

        $this->add_activity_joins();
        $this->add_activity_columns();
        $this->add_activity_filters();
    }

    /**
     * Add joins required for activity column and filter options to report.
     */
    protected function add_activity_joins() {
        /** @var activity_trait|rb_base_source $this */
        $join = $this->activity_join;

        // Add in perform_type table so we can add type columns/filters too.
        $this->joinlist[] = new rb_join(
            'perform_type',
            'INNER',
            '{perform_type}',
            "{$join}.type_id = perform_type.id",
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            $join
        );
    }

    /**
     * Add columnoptions for activity to report.
     */
    protected function add_activity_columns() {
        /** @var activity_trait|rb_base_source $this */
        $join = $this->activity_join;

        $this->columnoptions[] = new rb_column_option(
            'activity',
            'name',
            get_string('activity_name', 'mod_perform'),
            "{$join}.name",
            [
                'joins' => [$join],
                'dbdatatype' => 'text',
                'outputformat' => 'text',
                'displayfunc' => 'format_string'
            ]
        );
        $this->columnoptions[] = new rb_column_option(
            'activity',
            'type',
            get_string('activity_type', 'mod_perform'),
            "perform_type.id",
            [
                'joins' => [$join, 'perform_type'],
                'dbdatatype' => 'text',
                'outputformat' => 'text',
                'displayfunc' => 'activity_type_name',
                'extrafields' => [
                    'name' => 'perform_type.name',
                    'is_system' => 'perform_type.is_system',
                ],
            ]
        );
    }

    /**
     * Add filteroptions for activities to report.
     */
    protected function add_activity_filters() {
        $this->filteroptions[] = new rb_filter_option(
            'activity',
            'name',
            get_string('activity_name', 'mod_perform'),
            'text'
        );
        $this->filteroptions[] = new rb_filter_option(
            'activity',
            'type',
            get_string('activity_type', 'mod_perform'),
            'select',
            ['selectchoices' => $this->get_activity_type_options()]
        );
    }

    /**
     * Get an array of activity type options to use for filtering.
     *
     * @return string[] of [ID => Display Name]
     */
    protected function get_activity_type_options(): array {
        return activity_type_entity::repository()
            ->select(['id', 'name', 'is_system'])
            ->get()
            ->map_to(activity_type_model::class)
            ->map(static function (activity_type_model $activity_type) {
                return $activity_type->display_name;
            })
            ->sort(static function (string $a, string $b) {
                return $a <=> $b;
            })
            ->all(true);
    }
}
