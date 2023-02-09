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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package pathway_manual
 */

namespace pathway_manual\models;

use core\entity\user;
use core\orm\query\builder;
use pathway_manual\entity\rating as rating_entity;
use pathway_manual\models\roles\role;
use pathway_manual\models\roles\role_factory;
use totara_competency\aggregation_users_table;
use totara_hierarchy\entity\competency;
use totara_job\job_assignment;

defined('MOODLE_INTERNAL') || die();

/**
 * Class rating
 *
 * @package pathway_manual\models
 */
class rating {

    /**
     * @var user
     */
    protected $subject_user;

    /**
     * @var role
     */
    protected $role;

    /**
     * @param int $subject_user_id
     * @param string $role
     */
    public function __construct(int $subject_user_id, string $role) {
        $this->subject_user = new user($subject_user_id);
        $this->role = role_factory::create($role);
    }

    /**
     * @param int $subject_user_id
     * @param string $role
     * @return rating
     */
    public static function for_user_and_role(int $subject_user_id, string $role): self {
        return new static($subject_user_id, $role);
    }

    /**
     * Create a new manual rating made by the logged in user.
     *
     * @param int $competency_id
     * @param int|null $scale_value_id
     * @param string|null $comment
     */
    public function create(
        int $competency_id,
        ?int $scale_value_id,
        string $comment = null
    ) {
        $this->create_multiple([
            ['competency_id' => $competency_id, 'scale_value_id' => $scale_value_id, 'comment' => $comment]
        ]);
    }

    /**
     * Validate and create multiple ratings made by the logged in user.
     *
     * @param array $ratings
     */
    public function create_multiple(array $ratings) {
        $this->require_can_rate_user();
        self::validate_scale_values_for_competencies(
            array_combine(array_column($ratings, 'competency_id'), array_column($ratings, 'scale_value_id'))
        );
        $this->validate_role_for_competencies(array_column($ratings, 'competency_id'));

        $rating_record = [
            'user_id' => $this->subject_user->id,
            'date_assigned' => time(),
            'assigned_by' => user::logged_in()->id,
            'assigned_by_role' => $this->role::get_name(),
        ];

        builder::get_db()->transaction(function () use ($ratings, $rating_record) {
            $queue_data = [];
            foreach ($ratings as $rating) {
                // Prevent passing null as input parameter in PHP 8.1
                $rating['comment'] = trim($rating['comment'] ?? '');
                if (strlen($rating['comment']) === 0) {
                    $rating['comment'] = null;
                }

                $rating_entity = new rating_entity(array_merge($rating_record, $rating));
                $rating_entity->save();

                $queue_data[] = [
                    'user_id' => $rating_record['user_id'],
                    'competency_id' => $rating['competency_id']
                ];
            }
            (new aggregation_users_table())->queue_multiple_for_aggregation($queue_data);
        });
    }

    /**
     * Validate that the logged in user can rate for the given user and role.
     */
    private function require_can_rate_user() {
        $this->role::require_capability($this->subject_user->id);
        $this->role::require_for_user($this->subject_user->id);
    }

    /**
     * Validate that the role is enabled for the given competencies.
     *
     * @param array $competency_ids
     * @return bool
     */
    public function validate_role_for_competencies(array $competency_ids): bool {
        $competencies_with_role = roles::get_competencies_with_role($this->role);

        $invalid_competencies = array_diff($competency_ids, $competencies_with_role->pluck('id'));

        if ($invalid_competencies) {
            throw new \coding_exception(
                'The following competencies: ' .
                implode(', ', $invalid_competencies) .
                " do not have the {$this->role::get_name()} role enabled."
            );
        }

        return true;
    }

    /**
     * Validate for a list of competency / scale value pairs that each scale value is valid for the respective competency.
     *
     * @param array $comp_scale_map
     * @return bool
     */
    public static function validate_scale_values_for_competencies(array $comp_scale_map): bool {
        /** @var competency[] $competencies */
        $competencies = competency::repository()
            ->with('scale.values')
            ->where_in('id', array_keys($comp_scale_map))
            ->get()
            ->all(true);
        foreach ($comp_scale_map as $competency_id => $scale_value_id) {
            if (empty($competencies[$competency_id]) || !$competencies[$competency_id]->visible) {
                throw new \coding_exception('Non-existent or invisible competency id given.');
            }
            // Null value is valid.
            if (is_null($scale_value_id)) {
                continue;
            }
            if (!$competencies[$competency_id]->scale->values->find('id', $scale_value_id)) {
                throw new \coding_exception("Invalid scale value {$scale_value_id} for competency {$competency_id}");
            }
        }
        return true;
    }

    /**
     * Do the two specified users share a relation via a manual rating?
     *
     * Will return true if the viewing user share a manual rating with the target user,
     * or if the the target user is the subject of a manual rating that the viewing user has made.
     *
     * @param int $viewing_user_id The user requesting to view the target user
     * @param int $target_user_id The target user
     * @return bool
     */
    public static function users_share_rating(int $viewing_user_id, int $target_user_id): bool {
        return rating_entity::repository()
            ->where(static function (builder $builder) use ($viewing_user_id, $target_user_id) {
                return $builder
                    ->where('user_id', $viewing_user_id)
                    ->where('assigned_by', $target_user_id);
            })
            ->or_where(static function (builder $builder) use ($viewing_user_id, $target_user_id) {
                return $builder
                    ->where('user_id', $target_user_id)
                    ->where('assigned_by', $viewing_user_id);
            })
            ->exists();
    }

    /**
     * Make sure a logged user can see the rater of users whom he manages.
     *
     * @param int $viewing_user_id
     * @param int $target_user_id
     * @return bool
     */
    public static function can_see_raters_of_staff_members(int $viewing_user_id, int $target_user_id): bool {
        $staff = job_assignment::get_staff_userids($viewing_user_id);

        return rating_entity::repository()
            ->where_in('user_id', $staff)
            ->where('assigned_by', $target_user_id)
            ->exists();
    }
}
