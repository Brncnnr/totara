<?php
/*
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\data_providers;

use core\orm\entity\entity;
use core\orm\entity\repository;
use core\orm\query\builder;
use core\orm\query\field;
use totara_competency\entity\assignment;
use totara_competency\entity\competency_achievement;
use totara_competency\entity\competency_assignment_user;
use totara_competency\entity\competency_assignment_user_log;
use totara_competency\entity\configuration_change;
use totara_competency\models;
use totara_competency\models\activity_log_factory;

/**
 * Class activity_log_loader
 *
 * Loads an array of activity_log_data instances for a given user and competency. Optionally narrowed down by assignment.
 */
class activity_log {

    /** @var int */
    private $user_id;

    /** @var int */
    private $competency_id;

    /** @var int|null */
    private $assignment_id;

    public static function create(int $user_id, int $competency_id): activity_log {
        $provider = new activity_log();
        $provider->user_id = $user_id;
        $provider->competency_id = $competency_id;

        return $provider;
    }

    /**
     * @param array $filters
     * @return activity_log
     */
    public function set_filters(array $filters): activity_log {

        if (isset($filters['assignment_id'])) {
            $this->assignment_id = $filters['assignment_id'];
        }

        return $this;
    }

    /**
     * Loads data from the database, processes and sets the data property.
     *
     * @return models\activity_log[]
     */
    public function fetch() {
        if (is_null($this->user_id) || is_null($this->competency_id)) {
            throw new \coding_exception('Missing values', 'Both user and competency must be defined before loading');
        }

        $entities = $this->fetch_entities();

        $data = [];

        foreach ($entities as $entity) {
            $data[] = activity_log_factory::create($entity);
        }

        return $this->arrange_log_data($data);
    }

    /**
     * Given an array of activity_log_data, this will sort them in reverse chronological order.
     * Any additional records will be added as necessary for the given type of activity_log_data instance.
     *
     * @param models\activity_log[] $data
     * @return models\activity_log[]
     */
    private function arrange_log_data(array $data): array {
        usort($data, function (models\activity_log $a, models\activity_log $b) {
            // Sort from latest date.
            return $b->get_date() - $a->get_date();
        });

        $returned_data = [];
        $has_rating = false;

        foreach (array_reverse($data) as $entry) {
            if ($entry instanceof models\activity_log\competency_achievement) {
                if (!$has_rating && !$entry->has_scale_value()) {
                    // We don't want to display an empty rating unless there has been a rating before it.
                    continue;
                }

                // We don't want to display "Criteria met" for legacy assignments
                if ($entry->get_assignment() && $entry->get_assignment()->get_type() !== assignment::TYPE_LEGACY) {
                    array_unshift($returned_data, $entry->get_achieved_via());
                }

                $has_rating = true;
            }
            array_unshift($returned_data, $entry);
        }

        return $returned_data;
    }

    /**
     * Get the entities related to the activity log for this user, competency and optionally assignment.
     *
     * @return entity[]
     */
    private function fetch_entities(): array {
        $assignment_log = competency_assignment_user_log::repository()
            ->join(assignment::TABLE, 'assignment_id', '=', 'id')
            ->where('user_id', $this->user_id)
            ->where(assignment::TABLE . '.competency_id', $this->competency_id)
            ->order_by('created_at', 'desc')
            ->order_by('id', 'desc');
        if (!is_null($this->assignment_id)) {
            $assignment_log->where('assignment_id', $this->assignment_id);
        }
        $assignment_log = $assignment_log->get();

        $achievements = competency_achievement::repository()
            ->where('competency_id', $this->competency_id)
            ->where('user_id', $this->user_id)
            ->with([
                'assignment' => function (repository $repository) {
                    $repository->with('assigner');
                }
            ])
            ->with('value')
            ->with([
                'achieved_via' => function (repository $repository) {
                    $repository->with('pathway');
                }
            ])
            ->order_by('time_created', 'desc')
            ->order_by('id', 'desc');

        if (!is_null($this->assignment_id)) {
            $achievements->where('assignment_id', $this->assignment_id);
        }
        $achievements = $achievements->get();

        if ($assignment_log->count() === 0) {
            return $achievements->all();
        }

        $config_assignment_builder = builder::table(competency_assignment_user::TABLE, 'cau')
            ->where_field('cau.assignment_id', 'cc.assignment_id')
            ->where('cau.user_id', $this->user_id);
        if (!is_null($this->assignment_id)) {
            $config_assignment_builder->where('assignment_id', $this->assignment_id);
        }

        $config_changes = configuration_change::repository()
            ->as('cc')
            ->where('competency_id', $this->competency_id)
            ->where('time_changed', '>', $assignment_log->last()->created_at)
            ->where(function (builder $builder) use ($config_assignment_builder) {
                $builder->or_where_null('assignment_id')
                    ->or_where_exists($config_assignment_builder);
            });


        $config_changes = $config_changes
            ->order_by('time_changed', 'desc')
            ->order_by('id', 'desc')
            ->get();

        return array_merge($achievements->all(), $assignment_log->all(), $config_changes->all());
    }
}
