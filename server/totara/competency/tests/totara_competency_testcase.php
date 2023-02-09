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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package totara_competency
 */

use core\orm\query\builder;
use core\collection as core_collection;
use core\orm\collection;
use core_phpunit\testcase;
use totara_competency\expand_task;
use totara_competency\models\assignment as assignment_model;
use core\entity\user;
use pathway_learning_plan\learning_plan;
use pathway_criteria_group\criteria_group;
use pathway_manual\manual;
use pathway_perform_rating\perform_rating;
use pathway_perform_rating\testing\generator as perform_rating_generator;
use totara_competency\achievement_configuration;
use totara_competency\user_groups;
use totara_competency\entity\assignment;
use totara_competency\entity\competency_achievement;
use totara_competency\testing\generator;
use totara_criteria\testing\generator as criterion_generator;
use totara_hierarchy\entity\competency;
use totara_hierarchy\entity\competency_framework;
use totara_hierarchy\entity\scale;
use totara_hierarchy\entity\scale_value;
use totara_job\job_assignment;

abstract class totara_competency_testcase extends testcase {

    /**
     * @var int
     */
    protected $time;

    /**
     * Setup before the test
     */
    protected function setUp(): void {
        parent::setUp();

        $this->warpTime();
    }

    /**
     * Clean up after each test
     */
    protected function tearDown(): void {
        unset($this->time);

        parent::tearDown();
    }

    /**
     * Get time based on time space continuum fluctuations
     *
     * @param bool $warp Warp time to the next milestone flag
     * @return int
     */
    protected function getTime(bool $warp = true) {
        $time = $this->time;

        if ($warp) {
            $this->warpTime($time + 86400); // 86400 is one star-day
        }

        return $time;
    }

    /**
     * Warp time to a given value
     *
     * @param int|null $time
     * @return $this
     */
    protected function warpTime(int $time = null) {
        if (is_null($time)) {
            $time = time();
        }

        $this->time = $time;

        return $this;
    }

    /**
     * Creates data for testing filtering and sorting, it's all crammed to a single method as it's really
     * more an integration test and creating data comes with an overhead for creating records with timestamps
     * in a particular order
     *
     * Returns an array of collections with the created data:
     *
     * [
     *  'users' => Collection of created users
     *  'competencies' => Collection of created users
     *  'assignments' => Collection of created assignments
     *  'achievements' => Collection of created achievements
     * ]
     *
     * It also creates related user groups (audience, position, organisation) however these aren't returned.
     *
     * @param bool $ignore_order Do not sleep during data creation
     * @return array
     */
    protected function create_sorting_testing_data(bool $ignore_order = false) {
        $expander = new expand_task(builder::get_db());
        $assignment_generator = generator::instance()->assignment_generator();

        // Create 2 users
        $users = $this->create_n_users(3);

        // Create competencies
        $competencies = $this->create_some_competencies();

        // We need to get scale values for achievements. Our competencies I believe will all come with the default scale
        $scale = scale::repository()
            ->with(['min_proficient_value', 'values'])
            ->one();

        // The competent value will be min-proficient value and non-proficient value will be the first value
        // from values array.
        $competent = $scale->min_proficient_value;
        $not_competent = $scale->values->first();

        // Define assignments collection
        $assignments = new collection();

        // Define achievements collection
        $achievements = new collection();

        $users_to_add = [$users->item(0), $users->item(1), $users->item(2)];
        $other_users_to_add = [$users->item(1), $users->item(2)];

        // Create position
        $position = $assignment_generator->create_position_and_add_members($users_to_add);
        $another_pos = $assignment_generator->create_position_and_add_members($other_users_to_add);

        // Create organisation
        $organisation = $assignment_generator->create_organisation_and_add_members($users_to_add);
        $another_org = $assignment_generator->create_organisation_and_add_members($other_users_to_add);

        // Create audience
        $audience = $assignment_generator->create_cohort_and_add_members($users_to_add);
        $another_aud = $assignment_generator->create_cohort_and_add_members($other_users_to_add);

        // First assignments set

        // Create position competency assignment
        $assignments->set(
            $aa = $assignment_generator->create_position_assignment($competencies->first()->id, $position->id),
            "p_{$position->id}_{$competencies->first()->id}"
        );

        // Let's add a not proficient scale value
        $achievements->append($this->create_achievement_record(new assignment($aa), $users->item(0), $not_competent));

        // Create another position assignment that we'll archive
        $assignments->set(
            $ass = $assignment_generator->create_position_assignment($competencies->first()->id, $another_pos->id),
            "p_{$another_pos->id}_{$competencies->first()->id}"
        );

        // Let's add a not proficient scale value
        $achievements->append($this->create_achievement_record(new assignment($ass), $users->item(2), $not_competent));

        $expander->expand_all();

        // Before we sleep, we'd need to archive and re-expand
        (assignment_model::load_by_entity(new assignment($ass)))->archive(false);

        $expander->expand_all();

        !$ignore_order && $this->waitForSecond();

        // ------------------
        // Second assignments set

        // Create organisation competency assignment
        $assignments->set(
            $aa = $assignment_generator->create_organisation_assignment($competencies->item(1)->id, $organisation->id),
            "o_{$organisation->id}_{$competencies->item(1)->id}"
        );

        // Let's add a not proficient scale value
        $achievements->append($this->create_achievement_record(new assignment($aa), $users->item(0), $not_competent));

        // Create another position assignment that we'll archive
        $assignments->set(
            $ass = $assignment_generator->create_organisation_assignment($competencies->item(1)->id, $another_org->id),
            "o_{$another_org->id}_{$competencies->first()->id}"
        );

        // Let's add a not proficient scale value
        $achievements->append($this->create_achievement_record(new assignment($ass), $users->item(0), $not_competent));

        $expander->expand_all();

        // Before we sleep, we'd need to archive and re-expand
        (assignment_model::load_by_entity(new assignment($ass)))->archive(false);

        $expander->expand_all();

        !$ignore_order && $this->waitForSecond();

        // ------------------
        // Third assignments set

        // Create audience competency assignment
        $assignments->set(
            $assignment_generator->create_cohort_assignment($competencies->item(2)->id, $audience->id),
            "a_{$audience->id}_{$competencies->item(2)->id}"
        );

        // Create another position assignment that we'll archive
        $assignments->set(
            $ass = $assignment_generator->create_cohort_assignment($competencies->item(2)->id, $another_aud->id),
            "o_{$another_aud->id}_{$competencies->first()->id}"
        );

        $expander->expand_all();

        // Before we sleep, we'd need to archive and re-expand
        (assignment_model::load_by_entity(new assignment($ass)))->archive(false);

        $expander->expand_all();

        !$ignore_order && $this->waitForSecond();

        // ------------------

        // Then we are going to create an individual assignment for the first competency again.
        $assignments->set(
            $aa = $assignment_generator->create_user_assignment($competencies->first()->id, $users->first()->id),
            "i_{$users->first()->id}_{$competencies->first()->id}"
        );

        // Let's add a not proficient scale value
        $achievements->append($this->create_achievement_record(new assignment($aa), $users->item(0), $competent));

        // And the one to archive, this time from a mid competency
        $assignments->set(
            $ass = $assignment_generator->create_user_assignment($competencies->item(1)->id, $users->item(1)->id),
            "i_{$users->item(1)->id}_{$competencies->item(1)->id}"
        );

        // Let's add a not proficient scale value
        $achievements->append($this->create_achievement_record(new assignment($ass), $users->item(1), $competent));

        $expander->expand_all();

        // Before we return, we'd need to archive and re-expand
        (assignment_model::load_by_entity(new assignment($ass)))->archive(false);

        $expander->expand_all();

        // To avoid sleeps in our tests we need clearly sequential timestamps for assignment users.
        // Since this part is not exposed we'd warp the time after the assignments have been created.
        // This function is intended to be used standalone (with no other data created prior to this function execution).
        // We should be ok to just take all assignment user records,
        // order by id and then replace timestamps with incremental values.

        // We've created achievements record, including competent and not competent at the same time for the first competency
        // This is in particular to test proficient filter which should match a competency that has at least one proficient
        // value regardless of which assignment it came from.

        return [
            'users' => $users,
            'competencies' => $competencies,
            'assignments' => $assignments,
            'achievements' => $achievements,
        ];
    }

    /**
     * Create competency achievement record
     *
     * @param assignment $assignment Assignment entity
     * @param user $user User entity
     * @param scale_value $value Scale value entity
     * @param array $attributes Allows to override:
     *                          status, time_created, time_status, time_proficient, time_scale_value and time_aggregated
     *                          whatever those things are...
     * @return competency_achievement
     */
    protected function create_achievement_record(assignment $assignment, user $user, scale_value $value, array $attributes = []) {
        if (!$assignment->exists() || !$user->exists() || !$value->exists()) {
            throw new Exception("Assignment, user and value must all exist.");
        }

        $achievement = new competency_achievement([
            'competency_id' => $assignment->competency_id,
            'user_id' => $user->id,
            'assignment_id' => $assignment->id,
            'scale_value_id' => $value->id,
            'proficient' => $value->proficient,
            'status' => $attributes['status'] ?? 0,
            'time_created' => $attributes['time_created'] ?? time(),
            'time_status' => $attributes['time_status'] ?? time(),
            'time_proficient' => $attributes['time_proficient'] ?? time(),
            'time_scale_value' => $attributes['time_scale_value'] ?? time(),
            'last_aggregated' => $attributes['last_aggregated'] ?? time(),
        ]);

        return $achievement->save();
    }

    /**
     * Create data for the competency to test
     */
    public function create_a_lot_of_data() {
        $assignment_generator = generator::instance()->assignment_generator();

        // Define assignments
        $assignments = new collection([
            'pos' => new collection(),
            'org' => new collection(),
            'audience' => new collection(),
            'user' => new collection(),
        ]);

        // Create competency (5)
        $competencies = $this->create_some_competencies();

        // Create user(s)
        $users = $this->create_n_users();

        // Create user group(s)

        $pos_users = array_merge(
            [$users->item(19)],
            array_slice($users->all(),0, 5)
        );

        $org_users = array_merge(
            [$users->item(19)],
            array_slice($users->all(),5, 5)
        );

        $audience_users = array_merge(
            [$users->item(19)],
            array_slice($users->all(),10, 5)
        );

        // Create position
        $position = $assignment_generator->create_position_and_add_members($pos_users);

        // Create organisation
        $organisation = $assignment_generator->create_organisation_and_add_members($org_users);

        // Create audience
        $audience = $assignment_generator->create_cohort_and_add_members($audience_users);

        // Create assignments for a position and organisation
        for ($i = 1; $i <= 3; $i++) {
            $assignments->item('pos')->append(
                $assignment_generator->create_position_assignment(
                    $competencies->item($i)->id,
                    $position->id,
                    $this->attributes_with_ts()
                )
            );

            $assignments->item('org')->append(
                $assignment_generator->create_organisation_assignment(
                    $competencies->item($i)->id,
                    $organisation->id,
                    $this->attributes_with_ts()
                )
            );

            $assignments->item('audience')->append(
                $assignment_generator->create_cohort_assignment(
                    $competencies->item($i)->id,
                    $audience->id,
                    $this->attributes_with_ts()
                )
            );

            $assignments->item('user')->append(
                $assignment_generator->create_user_assignment(
                    $competencies->item($i)->id,
                    $users->item(18)->id,
                    $this->attributes_with_ts()
                )
            );

            $assignments->item('user')->append(
                $assignment_generator->create_user_assignment(
                    $competencies->item($i)->id,
                    $users->item(19)->id,
                    $this->attributes_with_ts()
                )
            );
        }

        // Let's create self assignments as well
        $assignments->item('user')->append(
            $assignment_generator->create_self_assignment($competencies->item(4)->id, $users->item(19)->id)
        );

        $task = new expand_task($GLOBALS['DB']);
        $task->expand_all();

        // Then we'd have to remove users from org/pos using job assignments

        $users_to_remove = $users->all();
        $users_to_remove = [
            $users_to_remove[4]->id,
            $users_to_remove[8]->id,
            $users_to_remove[9]->id,
        ];

        foreach ($users_to_remove as $user) {
            $ass = job_assignment::get_first($user);
            job_assignment::delete($ass);
        }

        // Let's remove users from a cohort
        cohort_remove_member($audience->id, $users->all()[14]->id);

        // Re-evaluating
        $task->expand_all();

        // Now let's archive a few assignments and re-evaluate :)
        $assignments->item('user')->filter(function ($assignment) use ($users, $assignment_generator) {
            $assignment = new assignment($assignment);

            if ($assignment->user_group_type === user_groups::USER &&
                $assignment->user_group_id === $users->item(19)->id) {
                $assignment_generator->archive_assignment($assignment, false);
            }
        });

        // Re-evaluating
        $task->expand_all();

        return [
            'assignments' => $assignments,
            'users' => $users,
            'pos' => $position,
            'org' => $organisation,
            'comps' => $competencies,
            'audience' => $audience,
        ];
    }

    /**
     * Create 5 predictable competencies in 2 frameworks with children.
     *
     * @return core_collection
     */
    protected function create_some_competencies(): ?core_collection {
        $competencies = new core_collection();

        $scale = scale::repository()->one();

        $generator = generator::instance();
        $fw = $generator->create_framework($scale);

        $competencies->append($generator->create_competency(null, $fw));

        $competencies->append(
            $generator
                ->create_competency(null, $competencies->first()->frameworkid,
                    ['fullname' => 'Something. This is a predefined key phrase for searching a competency. Another thing']
                )
        );

        $competencies->append(
            $generator
                ->create_competency(null, $competencies->first()->frameworkid,
                    ['description' => 'The description. This is a predefined key phrase for searching a competency']
                )
        );

        $competencies->append($comp = $generator->create_competency(null, $generator->create_framework($scale)));
        $competencies->append($generator->create_competency(null, $comp->frameworkid));

        return $competencies;
    }

    /**
     * Append attributes array with time-stamps (created_at, updated_at)
     *
     * @param array $attributes
     * @return array
     */
    protected function attributes_with_ts(array $attributes = []) {
        $time = $this->getTime();

        return array_merge($attributes, [
            'created_at' => $time,
            'updated_at' => $time,
        ]);
    }

    /**
     * Create n users
     *
     * @param int $n How many users to create
     * @param array $attributes Attributes to be shared among all users
     * @return collection
     */
    protected function create_n_users(int $n = 20, array $attributes = []) {
        $users = new collection();

        for ($i = 0; $i < $n; $i++) {
            $users->append($this->create_user($attributes));
        }

        return $users;
    }

    /**
     * Create a user
     *
     * @param array $attributes
     * @return user
     */
    protected function create_user(array $attributes = []) {
        return new user($this->getDataGenerator()->create_user($attributes));
    }

    /**
     * Get competency data generator
     *
     * @return \totara_competency\testing\generator
     */
    public function generator() {
        return $this->getDataGenerator()->get_plugin_generator('totara_competency');
    }

    /**
     * Get competency data generator
     *
     * @return \totara_hierarchy\testing\generator
     */
    public function hierarchy_generator() {
        return $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
    }

    /**
     * Get user without globals
     *
     * @return user
     */
    protected function get_user() {
        return new user($GLOBALS['USER']->id);
    }

    /**
     * Creates a collection of items from an item or array/collection of items.
     *
     * @param mixed|core_collection<mixed>|mixed[] $items items to convert.
     *
     * @return core_collection<mixed> the collection.
     */
    protected function as_collection($items): core_collection {
        if ($items instanceof core_collection) {
            return $items;
        } else if (is_array($items)) {
            return core_collection::new($items);
        } else {
            return core_collection::new([$items]);
        }
    }

    /**
     * Tests that the specified competencies have the expected aggregation type.
     *
     * @param competency|core_collection<competency>|competency[] $competencies
     *        competencies.
     * @param string $expected expected aggregation type.
     */
    protected function assert_aggregation_type(
        $competencies,
        string $expected
    ): void {
        $this->as_collection($competencies)->map(
            function (competency $competency) use ($expected): void {
                $aggregation = (new achievement_configuration($competency))
                    ->get_aggregation_type();

                $this->assertEquals(
                    $expected, $aggregation, 'wrong aggregation type'
                );
            }
        );
    }

    /**
     * Convenience function to add an aggregation method and a set of pathway
     * to the given competencies.
     *
     * @param competency|core_collection<competency>|competency[] $competencies
     *        target competencies.
     * @param stdClass[]|core_collection<stdClass> $active_pathways metadata for
     *        active pathways to be created. Each stdClass follows the format as
     *        stated in the self::add_competency_pathways() method documentation.
     *        If null, creates a canned set of pathways.
     * @param stdClass[]|core_collection<stdClass> $archived_pathways metadata for
     *        archived pathways to be created. Each stdClass follows the format as
     *        stated in the self::add_competency_pathways() method documentation.
     *        If null, creates a canned set of pathways.
     * @param string $aggregation aggregation type.
     *
     * @return core_collection<competency> the updated competencies.
     */
    protected function add_pathways_to_competencies(
        $competencies,
        ?array $active_pathways = null,
        ?array $archived_pathways = null,
        string $aggregation = achievement_configuration::DEFAULT_AGGREGATION
    ): core_collection {
        $canned = [
            (object) ['class' => learning_plan::class],
            (object) ['class' => perform_rating::class]
        ];

        $active = is_null($active_pathways) ? $canned : $active_pathways;
        $archived = is_null($archived_pathways) ? $canned : $archived_pathways;

        return $this->as_collection($competencies)->map(
            function (
                competency $competency
            ) use ($aggregation, $active, $archived): competency {
                generator::instance()->create_achievement_configuration(
                    $competency, $aggregation
                );

                $with_active = $active
                    ? $this->add_competency_pathways($competency, $active)
                    : $competency;

                return $archived
                    ? $this->add_competency_pathways(
                        $with_active, $archived, true
                    )
                    : $with_active;
            }
        );
    }

    /**
     * Adds the specified pathways to the specified competency.
     *
     * @param competency $competency target competency.
     * @param stdClass[]|core_collection<stdClass> $pathways metadata for pathways
     *        to be created. Each metadata element has these values:
     *        - [string] class: the class of the pathway to create eg manual::class
     *        - [string[]] [ONLY FOR MANUAL PATHWAYS] roles: roles to add to the
     *          pathway. If unspecified or blank, adds all known roles.
     *        - [criterion[]] [ONLY FOR CRITERIA GROUP PATHWAYS] criteria: criteria
     *          to add to the pathway. If unspecified or blank, just adds a single
     *          onactivate criteria to the pathway.
     * @param bool $archived if true, sets the pathways as archived, otherwise
     *        leave then as active.
     *
     * @return competency the updated competency.
     */
    protected function add_competency_pathways(
        competency $competency,
        array $pathways,
        bool $archived = false
    ): competency {
        $generator = generator::instance();
        $perform_generator = perform_rating_generator::instance();
        $criterion_generator = criterion_generator::instance();

        $this->as_collection($pathways)->map(
            function (
                stdClass $metadata
            ) use ($competency, $archived, $generator, $perform_generator, $criterion_generator): void {
                $type = $metadata->class;
                $pathway = null;

                switch ($type) {
                    case learning_plan::class:
                        $pathway = $generator->create_learning_plan_pathway($competency);
                        break;

                    case manual::class:
                        $pathway = $generator->create_manual(
                            $competency, $metadata->roles ?? []
                        );
                        break;

                    case perform_rating::class:
                        $pathway = $perform_generator->create_perform_rating_pathway($competency);
                        break;

                    case criteria_group::class:
                        $pathway = $generator->create_criteria_group(
                            $competency,
                            $metadata->criteria
                                ?? $criterion_generator->create_onactivate(
                                    ['competency' => $competency->id]
                                )
                        );
                        break;

                    default:
                        throw new coding_exception("unknown pathway type: $type");
                }

                if ($archived) {
                    $pathway->delete();
                }
            }
        );

        $competency->pathways(); // Forces a reload of the pathways.
        return $competency;
    }

    /**
     * Extracts the competencies belonging to the specified frameworks.
     *
     * @param competency|core_collection<competency>|competency[] $competencies
     *        competencies to filter.
     * @param competency_framework|core_collection<competency_framework>|competency_framework[]
     *        $fw frameworks to filter by.
     *
     * @return core_collection<competency> the competencies belonging to the
     *         given frameworks.
     */
    protected function filter_competencies_by_frameworks(
        $competencies,
        $fw
    ): core_collection {
        // Unfortunately, core_collection::pluck('xyz') assumes each element in
        // the collection has an 'xyz' property and _silently_ returns null if
        // this is not the case. Hence the code below to stop errors from being
        // shown in a place far removed from the actual error site.
        $fw_ids = $this->as_collection($fw)
            ->map(
                function (competency_framework $fw): int {
                    return $fw->id;
                }
            )
            ->all();

        return $this->as_collection($competencies)->filter(
            function (competency $competency) use ($fw_ids): bool {
                return in_array((int)$competency->frameworkid, $fw_ids);
            }
        );
    }
}
