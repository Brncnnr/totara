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
 * @package dev_datageneration
 */

namespace degeneration;

use coding_exception;
use core\entity\enrol;
use core\orm\collection;
use core\orm\query\builder;
use degeneration\items\audience;
use degeneration\items\competency;
use degeneration\items\competency_scale;
use degeneration\items\container_perform\activity as activity_item;
use degeneration\items\container_perform\preset_configurations;
use degeneration\items\container_workspace\discussion;
use degeneration\items\container_workspace\workspace;
use degeneration\items\course;
use degeneration\items\course_completion;
use degeneration\items\course_completion_basic;
use degeneration\items\criteria\child_competency;
use degeneration\items\criteria\course_completion as course_completion_criterion;
use degeneration\items\criteria\linked_courses as linked_courses_criterion;
use degeneration\items\criteria\on_activate;
use degeneration\items\item;
use degeneration\items\ml_recommender\interaction;
use degeneration\items\ml_recommender\item_recommendation;
use degeneration\items\ml_recommender\user_recommendation;
use degeneration\items\organisation;
use degeneration\items\pathways\criteria_group;
use degeneration\items\position;
use degeneration\items\totara_comment\comment;
use degeneration\items\totara_engage\article;
use degeneration\items\totara_engage\reaction;
use degeneration\items\totara_engage\share;
use degeneration\items\totara_engage\survey;
use degeneration\items\totara_playlist\playlist;
use degeneration\items\totara_playlist\rating;
use degeneration\items\user;
use Exception;
use Generator;
use hierarchy_organisation\entity\organisation_framework;
use hierarchy_position\entity\position_framework;
use ml_recommender\entity\interaction as interaction_entity;
use ml_recommender\entity\recommended_item;
use ml_recommender\entity\recommended_user_item;
use mod_perform\expand_task;
use mod_perform\task\service\subject_instance_creation;
use totara_competency\linked_courses;
use totara_engage\access\access;
use totara_engage\entity\rating as rating_entity;
use totara_engage\entity\share_recipient;
use totara_engage\timeview\time_view;
use totara_playlist\entity\playlist_resource;
use totara_reaction\entity\reaction as reaction_entity;
use totara_topic\topic;

class performance_testing extends App {

    /**
     * User ids
     *
     * @var int[]
     */
    protected $users = [];

    protected $users_with_courses = [];

    protected $courses_with_users = [];

    /**
     * @var course[]
     */
    protected $courses = [];

    /**
     * Created audiences
     *
     * @var audience[]
     */
    protected $audiences = [];

    /**
     * @var hierarchy|null
     */
    protected $competency_hierarchy = null;

    /**
     * @var array
     */
    protected $scales = [];

    /**
     * Should it create group assignment or user assignments
     *
     * @var bool
     */
    protected $assign_groups = true;

    /**
     * Check for if track user assignments were expanded.
     *
     * @var bool
     */
    private $user_assignments_expanded = false;

    /**
     * @var array|position[]
     */
    private $positions = [];

    /**
     * @var array|organisation[]
     */
    private $organisations = [];

    /**
     * @var topic[]
     */
    protected $topics = [];

    /**
     * Collection of public resource [ids, context ids, ownerid, timeview].
     * @var array
     */
    protected $public_resources = [];

    /**
     * Collection of public survey [ids, context ids]
     * @var array
     */
    protected $public_surveys = [];

    /**
     * Collection of playlist [ids, access level]
     *
     * @var array
     */
    protected $playlists = [];

    /**
     * Collection of shares
     * @var [share[]]
     */
    protected $shares = [];

    /**
     * @var array|\container_workspace\workspace[]
     */
    protected $public_workspaces = [];

    /**
     * @var array
     */
    protected $badges = [];

    /**
     * Hard-coded password that can be set for all users with "not cached" value
     * @var string[]
     */
    protected $password = ['pa$$word' => '$2y$10$7GBQQ8GFSixJRmCI1LjyCeTqzxm9IBemXhVpdAFKgHlK0ZAn8NFsO'];

    /**
     * Enable / disable function calls here to control which data is generated
     * when you run this script.
     */
    public function generate_from_config(performance_testing_config $config) {
        // If you need caching enable it here.
        // Cached data is not used at the moment so disabled by default for now.
        Cache::disable();

        $this
            ->create_users()
            ->hardcode_passwords()
            ->create_courses()
            ->create_users_csv()
            ->create_organisations()
            ->create_positions()
            ->add_aspirational_positions()
            ->create_audiences()
            ->create_badges()
            ->add_badges()
            ->add_audience_members()
            ->create_job_assignments_for_user()
        ;

        if ($config->include_enrolments) {
            $this->enrol_users()                             // Enrolling users has a huge performance impact only activate if absolutely necessary
            ->create_course_completions()
//                 ->create_course_completions_basic()       // I don't thnk you can have both types of completion going at one time.
            ;
        }

        if ($config->include_perform) {
            $this->perform_act_create_activities()
                 ->perform_act_expand_track_user_assignments()   // Use with care as will have considerable performance impact on generation
                 ->perform_act_generate_instances()              // Use with care as will have considerable performance impact on generation
                 ->create_scales()
                 ->create_competencies()
                 ->perform_comp_create_organisation_assignments()
                 ->perform_comp_create_position_assignments()
                 ->perform_comp_create_audience_assignments()
                 ->perform_comp_create_assignments()

             ;
            if ($config->include_enrolments) {
                $this->add_linked_courses()
                    ->perform_comp_create_criteria()
                ;
            }
        }

        if ($config->include_engage) {
            $this->add_interests()                           // This can take a long time for larger sizes.
                 ->engage_create_topics()
                 ->engage_create_resources()
                 ->engage_create_surveys()
                 ->engage_create_playlists()
                 ->engage_create_playlist_resources()
                 ->engage_create_playlist_ratings()
                 ->engage_create_resource_comments()
                 ->engage_create_reactions()
                 ->engage_create_playlist_comments()
                 ->engage_create_workspaces()
                 ->engage_create_workspace_members()         // Use with care as will have considerable performance impact on generation
                 ->engage_create_workspace_discussions()
                 ->engage_create_workspace_shares()
                 ->engage_create_shares()
                 ->engage_create_interactions()              // Slow, and only useful if you want to run recommenders engine.
                 ->engage_create_item_recommendations()
                 ->engage_create_user_recommendations()
            ;
        }

        return $this;
    }

    /**
     * Tweak numbers here
     *
     * @return array
     */
    public function get_t_size() {
        return [
            'users' => 10,
            'courses' => 10,
            'audiences' => 5,
            'users_per_audience' => 5,
            'activities' => [
                'count' => 5,
                'audiences_per_activity' => 1,
            ],
            'organisations' => [3, 3],
            'positions' => [3, 3],
            'competencies' => [3, 3],
            'competency_assignments' => 20,
            'badges' => 5,
            'job_assignments_for_user' => 2,
            'enrolments' => 50,
            'courses_per_criterion' => 5,
            'variable_courses_per_criterion' => false,
            'completions' => 80, // <-- Note this is a percentage
            'workspaces' => [
                'count' => 5,
                'members_per_workspace' => 5,
                'discussions_per_workspace' => 10,
                'comments_per_discussion' => 5,
                'replies_per_comment' => 5,
                'items_per_workspace_per_type' => 10,
            ],
            'topics' => 10,
            'engage_resources' => [
                'count_public' => 10,
                'count_private' => 10,
                'comments_per_resource' => 5,
                'replies_per_comment' => 5,
                'shared_users_per_resources' => 10,
            ],
            'engage_surveys' => [
                'count_public' => 5,
                'count_private' => 5,
                'shared_users_per_survey' => 5,
                'voting_users_per_survey' => 5,
            ],
            'reactions_per_item' => 10,
            'playlists' => [
                'count_public' => 5,
                'count_private' => 5,
                'resources_per_playlist' => 5,
                'surveys_per_playlist' => 5,
                'ratings_per_playlist' => 5,
                'comments_per_playlist' => 5,
                'replies_per_comment' => 5,
                'shared_users_per_playlist' => 5,
            ],
            'ml_interactions' => [
                'percent_of_users' => 80, // <-- Note this is a percentage
                'min_interactions_per_user' => 2,
                'max_interactions_per_user' => 5,
            ],
            'ml_recommendations' => [
                'playlists_to_playlists' => 3,
                'resources_to_resources' => 3,
                'resources_to_users' => 5,
                'workspaces_to_users' => 5,
                'courses_to_users' => 5,
            ],
        ];
    }

    /**
     * Tweak numbers here
     *
     * @return array
     */
    public function get_xs_size() {
        return [
            'users' => 2000,
            'courses' => 10,
            'audiences' => 50,
            'users_per_audience' => 1000,
            'activities' => [
                'count' => 5,
                'audiences_per_activity' => 1,
            ],
            'organisations' => [3, 3],
            'positions' => [3, 3],
            'competencies' => [3, 3],
            'competency_assignments' => 100,
            'badges' => 5,
            'job_assignments_for_user' => 2,
            'enrolments' => 30,
            'courses_per_criterion' => 5,
            'variable_courses_per_criterion' => false,
            'completions' => 100, // <-- Note this is a percentage
            'workspaces' => [
                'count' => 5,
                'members_per_workspace' => 5,
                'discussions_per_workspace' => 10,
                'comments_per_discussion' => 5,
                'replies_per_comment' => 5,
                'items_per_workspace_per_type' => 10,
            ],
            'topics' => 10,
            'engage_resources' => [
                'count_public' => 50,
                'count_private' => 50,
                'comments_per_resource' => 10,
                'replies_per_comment' => 5,
                'shared_users_per_resources' => 250,
            ],
            'engage_surveys' => [
                'count_public' => 50,
                'count_private' => 50,
                'shared_users_per_survey' => 100,
                'voting_users_per_survey' => 100,
            ],
            'reactions_per_item' => 250,
            'playlists' => [
                'count_public' => 50,
                'count_private' => 50,
                'resources_per_playlist' => 10,
                'surveys_per_playlist' => 5,
                'ratings_per_playlist' => 10,
                'comments_per_playlist' => 10,
                'replies_per_comment' => 10,
                'shared_users_per_playlist' => 5,
            ],
            'ml_interactions' => [
                'percent_of_users' => 80, // <-- Note this is a percentage
                'min_interactions_per_user' => 10,
                'max_interactions_per_user' => 20,
            ],
            'ml_recommendations' => [
                'playlists_to_playlists' => 3,
                'resources_to_resources' => 3,
                'resources_to_users' => 5,
                'workspaces_to_users' => 5,
                'courses_to_users' => 5,
            ],
        ];
    }

    /**
     * Tweak numbers here
     *
     * @return array
     */
    public function get_s_size() {
        return [
            'users' => 7000,
            'courses' => 100,
            'audiences' => 80,
            'users_per_audience' => 2500,
            'activities' => [
                'count' => 25,
                'audiences_per_activity' => 2,
            ],
            'organisations' => [4, 4],
            'positions' => [4, 4],
            'competencies' => [4, 4],
            'competency_assignments' => 100,
            'badges' => 10,
            'job_assignments_for_user' => 1,
            'enrolments' => 60,
            'courses_per_criterion' => 5,
            'variable_courses_per_criterion' => false,
            'completions' => 75, // <-- Note this is a percentage
            'workspaces' => [
                'count' => 50,
                'members_per_workspace' => 10,
                'discussions_per_workspace' => 20,
                'comments_per_discussion' => 10,
                'replies_per_comment' => 5,
                'items_per_workspace_per_type' => 10,
            ],
            'topics' => 10,
            'engage_resources' => [
                'count_public' => 200,
                'count_private' => 50,
                'comments_per_resource' => 50,
                'replies_per_comment' => 5,
                'shared_users_per_resources' => 250,
            ],
            'engage_surveys' => [
                'count_public' => 200,
                'count_private' => 50,
                'shared_users_per_survey' => 100,
                'voting_users_per_survey' => 100,
            ],
            'reactions_per_item' => 250,
            'playlists' => [
                'count_public' => 200,
                'count_private' => 50,
                'resources_per_playlist' => 20,
                'surveys_per_playlist' => 10,
                'ratings_per_playlist' => 50,
                'comments_per_playlist' => 20,
                'replies_per_comment' => 10,
                'shared_users_per_playlist' => 5,
            ],
            'ml_interactions' => [
                'percent_of_users' => 80, // <-- Note this is a percentage
                'min_interactions_per_user' => 30,
                'max_interactions_per_user' => 100,
            ],
            'ml_recommendations' => [
                'playlists_to_playlists' => 3,
                'resources_to_resources' => 3,
                'resources_to_users' => 5,
                'workspaces_to_users' => 5,
                'courses_to_users' => 5,
            ],
        ];
    }

    /**
     * Tweak numbers here
     *
     * @return array
     */
    public function get_m_size() {
        return [
            'users' => 15000,
            'courses' => 100,
            'audiences' => 50,
            'users_per_audience' => 5000,
            'activities' => [
                'count' => 100,
                'audiences_per_activity' => 3,
            ],
            'organisations' => [4, 4],
            'positions' => [4, 4],
            'competencies' => [4, 4],
            'competency_assignments' => 80,
            'badges' => 20,
            'job_assignments_for_user' => 1,
            'enrolments' => 80,
            'courses_per_criterion' => 5,
            'variable_courses_per_criterion' => false,
            'completions' => 75, // <-- Note this is a percentage
            'workspaces' => [
                'count' => 200,
                'members_per_workspace' => 50,
                'discussions_per_workspace' => 50,
                'comments_per_discussion' => 20,
                'replies_per_comment' => 5,
                'items_per_workspace_per_type' => 10,
            ],
            'topics' => 10,
            'engage_resources' => [
                'count_public' => 1000,
                'count_private' => 500,
                'comments_per_resource' => 50,
                'replies_per_comment' => 5,
                'shared_users_per_resources' => 250,
            ],
            'engage_surveys' => [
                'count_public' => 1000,
                'count_private' => 250,
                'shared_users_per_survey' => 100,
                'voting_users_per_survey' => 100,
            ],
            'reactions_per_item' => 250,
            'playlists' => [
                'count_public' => 1000,
                'count_private' => 250,
                'resources_per_playlist' => 20,
                'surveys_per_playlist' => 10,
                'ratings_per_playlist' => 50,
                'comments_per_playlist' => 20,
                'replies_per_comment' => 10,
                'shared_users_per_playlist' => 5,
            ],
            'ml_interactions' => [
                'percent_of_users' => 80, // <-- Note this is a percentage
                'min_interactions_per_user' => 30,
                'max_interactions_per_user' => 100,
            ],
            'ml_recommendations' => [
                'playlists_to_playlists' => 3,
                'resources_to_resources' => 3,
                'resources_to_users' => 5,
                'workspaces_to_users' => 5,
                'courses_to_users' => 5,
            ],
        ];
    }

    /**
     * Tweak numbers here
     *
     * @return array
     */
    public function get_l_size() {
        return [
            'users' => 35000,
            'courses' => 100,
            'audiences' => 80,
            'users_per_audience' => 10000,
            'activities' => [
                'count' => 500,
                'audiences_per_activity' => 5,
            ],
            'organisations' => [4, 4],
            'positions' => [4, 4],
            'competencies' => [4, 4],
            'competency_assignments' => 100,
            'badges' => 50,
            'job_assignments_for_user' => 2,
            'enrolments' => 100,
            'courses_per_criterion' => 5,
            'variable_courses_per_criterion' => false,
            'completions' => 75, // <-- Note this is a percentage
            'workspaces' => [
                'count' => 500,
                'members_per_workspace' => 50,
                'discussions_per_workspace' => 50,
                'comments_per_discussion' => 20,
                'replies_per_comment' => 5,
                'items_per_workspace_per_type' => 10,
            ],
            'topics' => 10,
            'engage_resources' => [
                'count_public' => 2000,
                'count_private' => 500,
                'comments_per_resource' => 50,
                'replies_per_comment' => 5,
                'shared_users_per_resources' => 250,
            ],
            'engage_surveys' => [
                'count_public' => 1500,
                'count_private' => 250,
                'shared_users_per_survey' => 100,
                'voting_users_per_survey' => 100,
            ],
            'reactions_per_item' => 250,
            'playlists' => [
                'count_public' => 1000,
                'count_private' => 250,
                'resources_per_playlist' => 20,
                'surveys_per_playlist' => 10,
                'ratings_per_playlist' => 50,
                'comments_per_playlist' => 20,
                'replies_per_comment' => 10,
                'shared_users_per_playlist' => 5,
            ],
            'ml_interactions' => [
                'percent_of_users' => 50, // <-- Note this is a percentage
                'min_interactions_per_user' => 30,
                'max_interactions_per_user' => 100,
            ],
            'ml_recommendations' => [
                'playlists_to_playlists' => 3,
                'resources_to_resources' => 3,
                'resources_to_users' => 5,
                'workspaces_to_users' => 5,
                'courses_to_users' => 5,
            ],
        ];
    }

    /**
     * Tweak numbers here
     *
     * @return array
     */
    public function get_xl_size() {
        return [
            'users' => 75000,
            'courses' => 1000,
            'audiences' => 100,
            'users_per_audience' => 25000,
            'activities' => [
                'count' => 1200,
                'audiences_per_activity' => 8,
            ],
            'organisations' => [5, 5],
            'positions' => [5, 5],
            'competencies' => [5, 5],
            'competency_assignments' => 70,
            'badges' => 100,
            'job_assignments_for_user' => 2,
            'enrolments' => 70,
            'courses_per_criterion' => 5,
            'variable_courses_per_criterion' => false,
            'completions' => 80, // <-- Note this is a percentage
            'workspaces' => [
                'count' => 500,
                'members_per_workspace' => 50,
                'discussions_per_workspace' => 50,
                'comments_per_discussion' => 20,
                'replies_per_comment' => 5,
                'items_per_workspace_per_type' => 10,
            ],
            'topics' => 10,
            'engage_resources' => [
                'count_public' => 2000,
                'count_private' => 1000,
                'comments_per_resource' => 50,
                'replies_per_comment' => 5,
                'shared_users_per_resources' => 250,
            ],
            'engage_surveys' => [
                'count_public' => 1000,
                'count_private' => 250,
                'shared_users_per_survey' => 100,
                'voting_users_per_survey' => 100,
            ],
            'reactions_per_item' => 250,
            'playlists' => [
                'count_public' => 1000,
                'count_private' => 250,
                'resources_per_playlist' => 20,
                'surveys_per_playlist' => 10,
                'ratings_per_playlist' => 50,
                'comments_per_playlist' => 20,
                'replies_per_comment' => 10,
                'shared_users_per_playlist' => 5,
            ],
            'ml_interactions' => [
                'percent_of_users' => 50, // <-- Note this is a percentage
                'min_interactions_per_user' => 30,
                'max_interactions_per_user' => 100,
            ],
            'ml_recommendations' => [
                'playlists_to_playlists' => 3,
                'resources_to_resources' => 3,
                'resources_to_users' => 5,
                'workspaces_to_users' => 5,
                'courses_to_users' => 5,
            ],
        ];
    }

    /**
     * Tweak numbers here
     *
     * @return array
     */
    public function get_xxl_size() {
        return [
            'users' => 150000,
            'courses' => 1000,
            'audiences' => 120,
            'users_per_audience' => 50000,
            'activities' => [
                'count' => 2500,
                'audiences_per_activity' => 10,
            ],
            'organisations' => [5, 5],
            'positions' => [5, 5],
            'competencies' => [5, 5],
            'competency_assignments' => 70,
            'badges' => 1000,
            'job_assignments_for_user' => 4,
            'enrolments' => 70,
            'courses_per_criterion' => 5,
            'variable_courses_per_criterion' => false,
            'completions' => 80, // <-- Note this is a percentage
            'workspaces' => [
                'count' => 1000,
                'members_per_workspace' => 50,
                'discussions_per_workspace' => 50,
                'comments_per_discussion' => 20,
                'replies_per_comment' => 5,
                'items_per_workspace_per_type' => 10,
            ],
            'topics' => 10,
            'engage_resources' => [
                'count_public' => 5000,
                'count_private' => 2000,
                'comments_per_resource' => 50,
                'replies_per_comment' => 5,
                'shared_users_per_resources' => 250,
            ],
            'engage_surveys' => [
                'count_public' => 2000,
                'count_private' => 500,
                'shared_users_per_survey' => 100,
                'voting_users_per_survey' => 100,
            ],
            'reactions_per_item' => 250,
            'playlists' => [
                'count_public' => 2000,
                'count_private' => 500,
                'resources_per_playlist' => 20,
                'surveys_per_playlist' => 10,
                'ratings_per_playlist' => 50,
                'comments_per_playlist' => 20,
                'replies_per_comment' => 10,
                'shared_users_per_playlist' => 5,
            ],
            'ml_interactions' => [
                'percent_of_users' => 50, // <-- Note this is a percentage
                'min_interactions_per_user' => 30,
                'max_interactions_per_user' => 100,
            ],
            'ml_recommendations' => [
                'playlists_to_playlists' => 3,
                'resources_to_resources' => 3,
                'resources_to_users' => 5,
                'workspaces_to_users' => 5,
                'courses_to_users' => 5,
            ],
        ];
    }

    /**
     * Tweak numbers here
     *
     * @return array
     */
    public function get_goliath_size() {
        return [
            'users' => 300000,
            'courses' => 1000,
            'audiences' => 140,
            'users_per_audience' => 100000,
            'activities' => [
                'count' => 5000,
                'audiences_per_activity' => 15,
            ],
            'organisations' => [5, 5],
            'positions' => [5, 5],
            'competencies' => [6, 5],
            'competency_assignments' => 70,
            'badges' => 10000,
            'job_assignments_for_user' => 5,
            'enrolments' => 70,
            'courses_per_criterion' => 5,
            'variable_courses_per_criterion' => false,
            'completions' => 80, // <-- Note this is a percentage
            'workspaces' => [
                'count' => 1000,
                'members_per_workspace' => 50,
                'discussions_per_workspace' => 50,
                'comments_per_discussion' => 20,
                'replies_per_comment' => 5,
                'items_per_workspace_per_type' => 10,
            ],
            'topics' => 10,
            'engage_resources' => [
                'count_public' => 7500,
                'count_private' => 2500,
                'comments_per_resource' => 50,
                'replies_per_comment' => 5,
                'shared_users_per_resources' => 250,
            ],
            'engage_surveys' => [
                'count_public' => 3000,
                'count_private' => 1000,
                'shared_users_per_survey' => 100,
                'voting_users_per_survey' => 100,
            ],
            'reactions_per_item' => 250,
            'playlists' => [
                'count_public' => 3000,
                'count_private' => 2000,
                'resources_per_playlist' => 20,
                'surveys_per_playlist' => 10,
                'ratings_per_playlist' => 50,
                'comments_per_playlist' => 20,
                'replies_per_comment' => 10,
                'shared_users_per_playlist' => 5,
            ],
            'ml_interactions' => [
                'percent_of_users' => 50, // <-- Note this is a percentage
                'min_interactions_per_user' => 30,
                'max_interactions_per_user' => 100,
            ],
            'ml_recommendations' => [
                'playlists_to_playlists' => 3,
                'resources_to_resources' => 3,
                'resources_to_users' => 5,
                'workspaces_to_users' => 5,
                'courses_to_users' => 5,
            ],
        ];
    }

    public function create_users() {
        $this->output('Creating users...');

        $size = $this->get_item_size('users');

        builder::get_db()->transaction(function () use ($size) {
            $users = [];
            $total = $size / BATCH_INSERT_MAX_ROW_COUNT;
            $done = 0;
            for ($c = 1; $c <= $size; $c++) {
                $users[] = (object)user::new()->create_for_bulk();
                if (count($users) >= BATCH_INSERT_MAX_ROW_COUNT) {
                    builder::get_db()->insert_records('user', $users);
                    $users = [];

                    self::show_progress($done, $total);
                }
            }

            if (!empty($users)) {
                builder::get_db()->insert_records('user', $users);
            }
        });

        // We mostly need only the userid,
        // to save memory we only load those
        $this->users = user::load_existing_ids();

        return $this;
    }

    /**
     * Set hard-coded password for all users without passwords
     * @return performance_testing
     */
    public function hardcode_passwords() {
        $this->output('Adding hard-coded password...');
        $password_hash = reset($this->password);
        App::db()->set_field('user', 'password', $password_hash, ['password' => 'not cached']);
        return $this;
    }
    /**
     * Save all users with hard-coded password (self::$password) into dataroot/users.csv file
     * Fields saved are: id,username,password
     *
     * @return performance_testing
     */
    public function create_users_csv() {
        $this->output('Creating users.csv...');
        $dataroot = App::config()->dataroot;
        $password_hash = reset($this->password);
        $password = key($this->password);

        $file = fopen($dataroot . '/users.csv', 'w');
        fputcsv($file, ['id', 'username', 'password']);

        $userset = App::db()->get_recordset('user', ['password' => $password_hash], '', 'id, username');
        foreach ($userset as $user) {
            fputcsv($file, [$user->id, $user->username, $password]);
        }
        fclose($file);

        return $this;
    }

    public function assign_groups(bool $assign = true) {
        $this->assign_groups = $assign;

        return $this;
    }

    public function perform_comp_create_assignments() {
        if (!$this->assign_groups) {
            $this->perform_comp_create_user_assignments();
        } else {
            $this->perform_comp_create_audience_assignments();
        }

        return $this;
    }

    public function create_organisations() {
        $this->output('Creating organisations...');

        builder::get_db()->transaction(function () {
            [$depth, $count] = $this->get_item_size('organisations');

            if (!$fw = organisation_framework::repository()->order_by('id')->first()) {
                $fw = (new organisation())->create_framework();
            }

            $hierarchy = new hierarchy();
            $hierarchy->set_type(organisation::class)
                ->set_variable_count(false)
                ->set_variable_depth(false)
                ->set_framework($fw)
                ->set_depth($depth)
                ->set_count($count)
                ->create_hierarchy();

            $this->organisations = $hierarchy->get_items();
        });

        return $this;
    }

    public function create_positions() {
        $this->output('Creating positions...');

        builder::get_db()->transaction(function () {
            [$depth, $count] = $this->get_item_size('positions');

            if (!$fw = position_framework::repository()->order_by('id')->first()) {
                $fw = (new position())->create_framework();
            }

            $hierarchy = new hierarchy();
            $hierarchy->set_type(position::class)
                ->set_variable_count(false)
                ->set_variable_depth(false)
                ->set_framework($fw)
                ->set_depth($depth)
                ->set_count($count)
                ->create_hierarchy();

            $this->positions = $hierarchy->get_items();
        });

        return $this;
    }

    public function add_aspirational_positions() {
        global $CFG;
        require_once($CFG->dirroot . '/totara/gap/lib.php');

        $this->output('Creating aspirational positions...');

        if (empty($this->users)) {
            throw new Exception('You must create users first');
        }

        if (empty($this->positions)) {
            throw new Exception('You must create audiences first');
        }

        builder::get_db()->transaction(function () {
            foreach ($this->users as $userid) {
                $pos = $this->positions[array_rand($this->positions)];
                totara_gap_assign_aspirational_position($userid, $pos->get_data()->id);
            }
        });

        return $this;
    }

    public function add_interests() {
        global $CFG;
        require_once($CFG->dirroot . '/tag/classes/tag.php');

        $this->output('Adding user interests...');

        if (empty($this->users)) {
            throw new Exception('You must create users first');
        }

        builder::get_db()->transaction(function () {
            $interests = ['swimming', 'hunting', 'fishing', 'macrame', 'biking', 'trailing', 'riding', 'programming', 'other'];
            $count = count($this->users) / 10;
            $done = 0;
            $doneactual = 0;
            foreach ($this->users as $userid) {
                $doneactual++;
                if ($doneactual % 10 == 0) {
                    self::show_progress($done, $count);
                }
                if (rand(0, 1)) {
                    continue;
                }
                shuffle($interests);
                $userinterest = array_slice($interests, 0, rand(1, 3));
                \core_tag_tag::set_item_tags('core', 'user', $userid, \context_user::instance($userid), $userinterest);
            }
        });

        return $this;
    }

    public function create_audiences() {
        $this->output('Creating audiences...');

        builder::get_db()->transaction(function () {
            $size = $this->get_item_size('audiences');

            $done = 0;
            for ($c = 1; $c <= $size; $c++) {
                $this->audiences[] = audience::new()->save_and_return();

                self::show_progress($done, $size);
            }
        });

        return $this;
    }

    public function add_audience_members() {
        $this->output('Adding audience members...');

        if (empty($this->users)) {
            throw new Exception('You must create users first');
        }

        if (empty($this->audiences)) {
            throw new Exception('You must create audiences first');
        }

        builder::get_db()->transaction(function () {
            $user_per_audience = $this->get_item_size('users_per_audience');

            $users = $this->users;

            $audiences = $this->audiences;

            $members = [];
            $total = (count($audiences) * $user_per_audience) / BATCH_INSERT_MAX_ROW_COUNT;
            $done = 0;
            foreach ($audiences as $audience) {
                shuffle($users);

                $users = array_slice($users, 0, $user_per_audience);
                foreach ($users as $user) {
                    $members[] = $audience->add_member($user);
                    if (count($members) >= BATCH_INSERT_MAX_ROW_COUNT) {
                        builder::get_db()->insert_records('cohort_members', $members);
                        $members = [];

                        self::show_progress($done, $total);
                    }
                }
            }

            if (!empty($members)) {
                builder::get_db()->insert_records('cohort_members', $members);
            }
        });

        return $this;
    }

    public function create_scales() {
        $this->output('Creating competency scales...');

        builder::get_db()->transaction(function () {
            $simple_scale = new competency_scale();

            $simple_scale->add_value(false, 'Incompetent', $simple_incompetent)
                ->add_value(true, 'Competent', $simple_competent)
                ->save();

            $complex_scale = new competency_scale();

            $complex_scale->add_value(false, 'Incompetent', $complex_incompetent)
                ->add_value(false, 'A little less incompetent', $complex_less_competent)
                ->add_value(true, 'Competent ', $complex_competent)
                ->add_value(true, 'A little more competent ', $complex_more_competent)
                ->save();

            $this->scales = [
                $simple_scale->get_data('id') => [
                    'incompetent' => $simple_incompetent,
                    'competent' => $simple_competent,
                ],
                $complex_scale->get_data('id') => [
                    'incompetent' => $complex_incompetent,
                    'less_incompetent' => $complex_less_competent,
                    'competent' => $complex_competent,
                    'more_competent' => $complex_more_competent,
                ]
            ];
        });

        return $this;
    }

    public function create_competencies() {
        $this->output('Creating competencies...');

        builder::get_db()->transaction(function () {
            [$depth, $count] = $this->get_item_size('competencies');

            $this->competency_hierarchy = new hierarchy();

            $scale_ids = array_keys($this->scales);

            foreach ($scale_ids as $scale_id) {
                $this->competency_hierarchy->set_framework((new competency())->create_framework(['scale' => $scale_id]));
            }

            $this->competency_hierarchy->set_type(competency::class)
                ->set_variable_count(false)
                ->set_variable_depth(false)
                ->set_depth($depth)
                ->set_count($count)
                ->create_hierarchy();

            // Let's eager load scales
            collection::new(
                array_map(
                    function (item $item) {
                        return $item->get_data();
                    },
                    $this->competency_hierarchy->get_items()
                )
            )->load('scale');
        });

        return $this;
    }

    public function create_badges() {
        global $CFG;
        require_once($CFG->dirroot . '/lib/badgeslib.php');

        $this->output('Creating badges...');
        /**
         * @var \core_badges_generator $generator
         */
        $generator =  App::generator()->get_plugin_generator('core_badges');

        builder::get_db()->transaction(function () use ($generator) {
            $count = $this->get_item_size('badges');
            $admin = get_admin();
            for ($i = 0; $i < $count; $i++) {
                $id = $generator->create_badge($admin->id, ['name' => 'Badge ' . $i]);
                $generator->add_manual_badge_criteria($id);
                $this->badges[$id] = $id;
            }
        });

        return $this;
    }

    public function add_badges() {
        global $CFG;
        require_once($CFG->dirroot . '/lib/phpunit/classes/message_sink.php');

        $this->output('Awarding badges...');

        if (empty($this->users)) {
            throw new Exception('You must create users first');
        }

        if (empty($this->badges)) {
            throw new Exception('You must create badges first');
        }

        builder::get_db()->transaction(function () {
            $count = count($this->users) / 10;
            $done = 0;
            $doneactual = 0;
            foreach ($this->users as $user) {
                $doneactual++;
                if ($doneactual % 10 == 0) {
                    self::show_progress($done, $count);
                }
                if (rand(0, 1)) {
                    continue;
                }
                // 1 to 3 badges for user
                $badges = $this->badges;
                shuffle($badges);
                $userbadges = array_slice($badges, 0, rand(1, 3));
                foreach ($userbadges as $badgeid) {
                    $badge = new \badge($badgeid);
                    $badge->issue($user, true);
                }
            }
        });

        return $this;
    }

    public function perform_comp_create_organisation_assignments() {

        return $this;
    }

    public function perform_comp_create_position_assignments() {

        return $this;
    }

    public function perform_comp_create_audience_assignments() {
        $this->output('Creating audience assignments...');

        if ($this->competency_hierarchy === null) {
            throw new Exception('You must create competency hierarchy first');
        }

        builder::get_db()->transaction(function () {
            $competencies = $this->get_competencies('competency_assignments');

            foreach ($this->audiences as $audience) {
                foreach ($competencies as $competency) {
                    static::competency_generator()
                        ->assignment_generator()
                        ->create_cohort_assignment($competency->get_data('id'), $audience->get_data()->id);
                }
            }
        });

        return $this;
    }

    public function perform_comp_create_user_assignments() {
        $this->output('Creating user assignments...');

        if ($this->competency_hierarchy === null) {
            throw new Exception('You must create competency hierarchy first');
        }

        builder::get_db()->transaction(function () {
            $competencies = $this->get_competencies('competency_assignments');

            foreach ($this->users as $user) {
                foreach ($competencies as $competency) {
                    static::competency_generator()
                        ->assignment_generator()
                        ->create_user_assignment($competency->get_data('id'), $user);
                }
            }
        });

        return $this;
    }

    public function create_courses() {
        $this->output('Creating courses...');

        $count = $this->get_item_size('courses');
        $done = 0;

        for ($c = 1; $c <= $count; $c++) {
            $this->courses[] = course::new()->save_and_return();
            static::show_progress($done, $count);
        }

        return $this;
    }

    public function add_linked_courses() {
        $this->output('Adding linked courses');

        if (empty($this->courses_with_users)) {
            throw new Exception('You must create courses and enrol users first');
        }

        builder::get_db()->transaction(function () {
            $count = $this->get_item_size('courses_per_criterion');
            $variable = $this->get_item_size('variable_courses_per_criterion');

            foreach ($this->competency_hierarchy->get_items() as $item) {
                $count = $variable ? rand(1, $count) : $count;

                $courses = array_map(
                    function ($id) {
                        return [
                            'id' => $this->courses_with_users[$id]->get_data('id'),
                            'mandatory' => false,
                        ];
                    },
                    array_rand($this->courses_with_users, $count)
                );

                linked_courses::set_linked_courses($item->get_data('id'), $courses);
            }
        });

        return $this;
    }

    public function enrol_users(bool $with_completions = true) {
        $this->output('Enrolling users...');

        if (empty($this->users) || empty($this->courses)) {
            throw new Exception('Users and courses must be created first!');
        }

        $percentage = $this->get_item_size('enrolments');

        $users = array_rand($this->users, $this->get_percentage(count($this->users), $percentage));
        $courses = array_rand($this->courses, $this->get_percentage(count($this->courses), $percentage));

        $this->users_with_courses = array_filter($this->users, function ($key) use ($users) {
            return in_array($key, $users);
        }, ARRAY_FILTER_USE_KEY);

        $this->courses_with_users = array_filter($this->courses, function ($key) use ($courses) {
            return in_array($key, $courses);
        }, ARRAY_FILTER_USE_KEY);

        foreach ($courses as $course) {
            foreach ($users as $user) {
                $this->courses[$course]->enrol($this->users[$user]);
            }
        }

        return $this;
    }

    public function create_course_completions() {
        $this->output('Creating course completions...');

        // We'll be creating completions for courses with users
        $percentage = $this->get_item_size('completions');

        $count = $this->get_percentage(count($this->courses_with_users), $percentage);

        $courses_to_complete = array_rand($this->courses_with_users, $count);

        foreach ($courses_to_complete as $course_to_complete) {
            foreach ($this->courses_with_users[$course_to_complete]->get_enrolled_users() as $user) {
                $cc = new course_completion();
                $cc->for($this->courses_with_users[$course_to_complete])
                    ->by($user)
                    ->save_and_return();
            }
        }

        return $this;
    }

    public function create_course_completions_basic() {
        $this->output('Creating basic course completions...');

        builder::get_db()->transaction(function () {
            // We'll be creating completions for courses with users
            $percentage = $this->get_item_size('completions');

            $count = $this->get_percentage(count($this->courses), $percentage);

            $courses_to_complete = array_rand($this->courses, $count);
            $this->courses_with_users = $this->courses;

            $completions = [];
            $count = 0;
            foreach ($courses_to_complete as $course_to_complete) {
                // Create completions for all users
                foreach ($this->users as $user) {
                    $cc = new course_completion_basic();
                    $completions[] = $cc->for($this->courses[$course_to_complete])
                        ->by($user)
                        ->save_and_return();

                    $count++;
                    if ($count == BATCH_INSERT_MAX_ROW_COUNT) {
                        $this->bulk_insert_completions($completions);
                        $completions = [];
                        $count = 0;
                    }
                }
            }

            if (!empty($completions)) {
                $this->bulk_insert_completions($completions);
            }
            unset($completions);
            unset($courses_to_complete);
        });

        return $this;
    }

    private function bulk_insert_completions(array $completions) {
        builder::get_db()->insert_records(
            'course_completions',
            array_map(
                function (item $item) {
                    return (object)$item->get_data();
                },
                $completions
            )
        );
    }

    public function perform_comp_create_criteria_set(competency $competency) {
        // Create criteria group - on activate
        // Create criteria group - completion and child competencies
        // Create criteria group - completion and linked course
        // Manual rating pathway

        $scale = $this->scales[$competency->get_data()->scale->id];

        if (!$scale) {
            throw new Exception('Something went wrong scale value is not found');
        }

        $on_activate = criteria_group::new()
            ->for($competency)
            ->set_value($scale['incompetent'])
            ->add_criterion(on_activate::new()->for($competency)->save_and_return())
            ->save_and_return();

        $linked_course = criteria_group::new()
            ->for($competency)
            ->set_value($scale['competent'])
            ->add_criterion($this->create_course_completion_criterion())
            ->add_criterion($this->create_child_competency_criterion($competency))
            ->save_and_return();

        if (count($scale) === 4) {
            $another_one = criteria_group::new()
                ->for($competency)
                ->set_value($scale['less_incompetent'])
                ->add_criterion($this->create_course_completion_criterion())
                ->add_criterion($this->create_linked_courses_criterion($competency))
                ->save_and_return();
        }
    }

    public function create_linked_courses_criterion(competency $competency): linked_courses_criterion {
        return linked_courses_criterion::new()
            ->set_aggregation_method(2)
            ->set_required_items(1)
            ->for($competency)
            ->save_and_return();
    }

    public function perform_comp_create_criteria() {
        $this->output('Creating criteria...');

        if (!$this->competency_hierarchy) {
            throw new Exception('You must generate your competency hierarchy first');
        }

        builder::get_db()->transaction(function () {
            foreach ($this->competency_hierarchy->get_items() as $item) {
                $this->perform_comp_create_criteria_set($item);
            }
        });

        return $this;
    }

    protected function create_pathways() {
        return $this;
    }

    protected function get_competencies(string $key = null) {
        $competencies = $this->competency_hierarchy->get_items();
        $ids = array_rand($competencies, $this->get_percentage(count($competencies), $key ? $this->get_item_size($key) : 100));

        return array_filter(
            $competencies,
            function ($key) use ($ids) {
                return in_array($key, $ids);
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    protected function create_child_competency_criterion(competency $competency): ?child_competency {
        // No criterion if there is no child competencies
        if ($competency->get_data()->children()->count() === 0) {
            return null;
        }

        return child_competency::new()
            ->for($competency)
            ->set_aggregation_method(2)
            ->set_required_items(2)
            ->save_and_return();
    }

    protected function create_course_completion_criterion(): course_completion_criterion {
        $criterion = course_completion_criterion::new();

        $count = $this->get_item_size('courses_per_criterion');
        $variable = $this->get_item_size('variable_courses_per_criterion');

        $count = $variable ? rand(1, $count) : $count;

        // Now let's pick courses
        $courses = array_rand($this->courses_with_users, $count);

        foreach ($courses as $course) {
            $criterion->add_course($this->courses_with_users[$course]);
        }

        return $criterion->save_and_return();
    }

    protected function create_job_assignments_for_user() {
        $size = $this->get_item_size('job_assignments_for_user');
        if (!$size) {
            return $this;
        }
        $this->output('Creating job assignments...');

        if (empty($this->users)) {
            throw new coding_exception("Cannot create workspace member when users are empty");
        }

        $job_assignment_creator = new job_assignments_creator($this->users, $this->positions, $this->organisations);
        $job_assignment_creator->generate($size);

        return $this;
    }

    protected function perform_act_create_activities() {
        $this->output('Creating activities ...');
        $activity_size = $this->get_item_size('activities');
        $configuration_manager = new preset_configurations();

        builder::get_db()->transaction(
            function () use ($activity_size, $configuration_manager) {
                $done = 0;
                for ($x = 0; $x <= $activity_size['count']; $x++) {
                    $config = $configuration_manager->get_a_configuration();
                    $config['track']['audiences'] = $this->get_random_audiences($activity_size['audiences_per_activity']);
                    (activity_item::new())->create_activity($config);

                    self::show_progress($done, $activity_size['count']);
                }
            }
        );
        $this->output('All activities created');

        return $this;
    }

    private function perform_act_expand_track_user_assignments() {
        $this->output('Expanding user assignments...');
        expand_task::create()->expand_all();

        $this->user_assignments_expanded = true;
        return $this;
    }

    private function perform_act_generate_instances() {
        if (!$this->user_assignments_expanded) {
            $this->perform_act_expand_track_user_assignments();
        }

        $this->output('Generating subject & participant instance data for activities...');
        (new subject_instance_creation())->generate_instances();

        return $this;
    }

    private function get_random_audiences($count) {
        if (empty($this->audiences)) {
            throw new coding_exception("You'll need to create audiences & audience users first.");
        }
        $audience_keys = array_rand($this->audiences, $count);
        $audience_ids = [];

        if ($audience_keys === null) {
            throw new coding_exception('Trying to pick more audiences than created');
        }

        // Cater for the case when there's only one
        if (!is_array($audience_keys)) {
            $audience_keys = [$audience_keys];
        }

        foreach ($audience_keys as $audience_key) {
            $audience_ids[] = $this->audiences[$audience_key]->get_data()->id;
        }

        return $audience_ids;
    }

    protected function perform_act_create_random_responses() {
        $this->output('Creating random responses for activities...');
        // todo: create_random_responses.
    }

    public function engage_create_workspaces() {
        $this->output('Creating workspaces ...');
        $sizes = $this->get_item_size('workspaces');
        builder::get_db()->transaction(function () use ($sizes) {
            $done = 0;
            $user_list = $this->sequential_users_list();
            for ($i = 0; $i < $sizes['count']; $i++) {
                $user_list->next();
                $this->public_workspaces[] = (workspace::new())->create_workspace($user_list->current());
                self::show_progress($done, $sizes['count']);
            }
        });
        return $this;
    }

    public function engage_create_workspace_members() {
        if (empty($this->public_workspaces)) {
            return $this;
        }
        $this->output('Creating workspace members ...');
        $sizes = $this->get_item_size('workspaces');

        if (empty($this->users)) {
            throw new coding_exception("Cannot create workspace member when users are empty");
        }
        builder::get_db()->transaction(function () use ($sizes) {
            $generator = App::generator();
            $total = count($this->public_workspaces) * $sizes['members_per_workspace'];
            $done = 0;

            $workspace_generator = \container_workspace\testing\generator::instance();

            // We want to randomise the users who are assigned to workspaces, but we don't want to randomly
            // pick every time (that's slow). So we'll just start at the start of the list and work towards the end
            $users_list = $this->sequential_users_list();
            foreach ($this->public_workspaces as $workspace) {
                for ($i = 0; $i < $sizes['members_per_workspace']; $i++) {
                    $users_list->next();
                    $user_id = $users_list->current();

                    if ($user_id == $workspace->get_user_id()) {
                        $users_list->next();
                        $user_id = $users_list->current();
                    }

                    $workspace_generator->create_self_join_member($workspace, $user_id);
                    self::show_progress($done, $total);
                }
            }
            unset($users_list);
        });
        return $this;
    }

    public function engage_create_workspace_discussions() {
        if (empty($this->public_workspaces)) {
            return $this;
        }

        $this->output('Creating workspace discussions, comments & replies ...');
        $sizes = $this->get_item_size('workspaces');
        $discussion_count = $sizes['discussions_per_workspace'];
        $comment_count = $sizes['comments_per_discussion'];
        $reply_count = $sizes['replies_per_comment'];

        $users = $this->users;
        reset($users);

        builder::get_db()->transaction(function () use ($discussion_count, $comment_count, $reply_count, $users) {
            /**
             * Helper function to get a user id
             *
             * @return int
             */
            $next_user_id = function () use (&$users) {
                $user_id = next($users);
                if (false === $user_id) {
                    reset($users);
                    $user_id = current($users);
                }
                return $user_id;
            };

            $done = 0;
            $total_discussions = count($this->public_workspaces) * $discussion_count;
            $total_comments = $total_discussions * $comment_count;
            $total_replies = $total_comments * $reply_count;
            $total = $total_discussions + $total_comments + $total_replies;

            foreach ($this->public_workspaces as $workspace) {
                $workspace_id = $workspace->get_id();
                // Create the discussions
                for ($i = 0; $i < $discussion_count; $i++) {
                    $discussion = (new discussion($workspace_id, $next_user_id()))
                        ->save_and_return();
                    self::show_progress($done, $total);
                    // We want to now create a comment & replies on this discussion
                    for ($j = 0; $j < $comment_count; $j++) {
                        $comment = (new comment(
                            $discussion->get_data('id'),
                            $next_user_id(),
                            'container_workspace',
                            \container_workspace\discussion\discussion::AREA
                        ))->save_and_return();
                        self::show_progress($done, $total);
                        $comment->add_replies($reply_count, $users, $done, $total);
                    }
                }
            }
        });
        return $this;
    }

    public function engage_create_topics() {
        $this->output('Creating engage topics...');
        $size = (int) $this->get_item_size('topics');

        builder::get_db()->transaction(function () use ($size) {
            $faker = App::faker();
            $generator = \totara_topic\testing\generator::instance();
            $done = 0;
            while ($done < $size) {
                try {
                    $topic = $generator->create_topic($faker->word);
                    // We also need to save the topic raw name
                    $this->topics[$topic->get_id()] = $topic->get_raw_name();
                    self::show_progress($done, $size);
                } catch (Exception $ex) {
                    // We don't want to crash if we used the same topic twice
                }
            }
        });

        return $this;
    }

    public function engage_create_resources() {
        if (empty($this->topics)) {
            throw new coding_exception('Must generate topics before creating engage resources');
        }

        $this->output('Creating engage resources...');
        builder::get_db()->transaction(function () {
            $valid_times_to_view = [time_view::LESS_THAN_FIVE, time_view::FIVE_TO_TEN, time_view::MORE_THAN_TEN];

            $sizes = $this->get_item_size('engage_resources');
            $max_topics = min(count($this->topics), 10);
            $total = $sizes['count_public'] + $sizes['count_private'];
            $created = 0;
            $done = 0;

            $recommended_sizes = $this->get_item_size('ml_recommendations');
            $required_short_time_to_view = $recommended_sizes['resources_to_users'];
            $this->public_resources = [];
            $user_list = $this->sequential_users_list();

            for ($i = 0; $i < $total; $i++) {
                $user_list->next();
                $owner_id = $user_list->current();
                $time_to_view = null;
                $topics = [];

                $access = $created >= $sizes['count_public'] ? access::PRIVATE : access::PUBLIC;
                $is_public = $access === access::PUBLIC;

                if ($is_public) {
                    if ($required_short_time_to_view > 0) {
                        $time_to_view = time_view::LESS_THAN_FIVE;
                    } else {
                        $time_to_view = $valid_times_to_view[array_rand($valid_times_to_view, 1)];
                    }
                    // Note, as the keys for topics are the IDs, $topics will be the topic ids
                    $topics = $this->random_items(array_keys($this->topics), rand(1, $max_topics));
                }

                $article = new article($owner_id, $access, $time_to_view, $topics);
                $article->save();
                $created++;

                if ($is_public) {
                    $this->public_resources[$created] = $article->get_article_info();

                    // Used for recommendations later on
                    if ($time_to_view === time_view::LESS_THAN_FIVE) {
                        $required_short_time_to_view--;
                    }
                }
                unset($article);
                self::show_progress($done, $total);
            }
        });

        return $this;
    }

    public function engage_create_resource_comments() {
        $this->output('Creating comments on public engage resources...');
        builder::get_db()->transaction(function () {
            $sizes = $this->get_item_size('engage_resources');
            $comments = $sizes['comments_per_resource'];
            $replies = $sizes['replies_per_comment'];
            $done = 0;
            $total = count($this->public_resources) * $comments * ($replies + 1);
            $user_list = $this->sequential_users_list();
            foreach ($this->public_resources as $resource_key) {
                for ($j = 0; $j < $comments; $j++) {
                    $user_list->next();
                    $comment = (new items\totara_comment\comment(
                        $resource_key[0],
                        $user_list->current(),
                        'engage_article',
                        'comment'
                    ))->save_and_return();
                    self::show_progress($done, $total);
                    $comment->add_replies($replies, [], $done, $total);
                }
            }
        });

        return $this;
    }

    public function engage_create_surveys() {
        if (empty($this->topics)) {
            throw new coding_exception('Must generate topics before creating engage resources');
        }
        $this->output('Creating engage surveys...');
        $sizes = $this->get_item_size('engage_surveys');
        $max_topics = min(count($this->topics), 5);
        builder::get_db()->transaction(function () use ($sizes, $max_topics) {
            $votes = $sizes['voting_users_per_survey'];
            $total = $sizes['count_public'] + $sizes['count_private'];
            $done = 0;
            $created = 0;
            $user_list = $this->sequential_users_list();
            for ($i = 0; $i < $total; $i++) {
                $user_list->next();
                $owner_id = $user_list->current();
                $access = $created >= $sizes['count_public'] ? access::PRIVATE : access::PUBLIC;
                $is_public = $access === access::PUBLIC;

                $topics = [];
                if ($is_public) {
                    $random_topics = array_rand($this->topics, rand(1, $max_topics));
                    if (!is_array($random_topics)) {
                        $random_topics = [$random_topics];
                    }
                    foreach ($random_topics as $random_topic) {
                        $topics[] = $this->topics[$random_topic];
                    }
                }

                $survey = new survey($owner_id, $access, $topics);
                $survey->save();
                $created++;

                if ($is_public) {
                    $this->public_surveys[] = $survey->get_survey_info();
                    // Add votes for this survey
                    $users = $this->random_items($this->users, $votes);
                    $total_votes = 0;
                    foreach ($users as $user_id) {
                        if ($user_id == $owner_id) {
                            continue;
                        }

                        $total_votes += $survey->create_votes($user_id);
                    }
                    unset($users);
                    $survey->save_resource_extra($total_votes, $votes);
                }
                unset($survey);
                self::show_progress($done, $total);
            }
        });

        return $this;
    }

    public function engage_create_reactions() {
        $this->output('Creating likes on public resources/surveys...');
        builder::get_db()->transaction(function () {
            $size = (int) $this->get_item_size('reactions_per_item');
            $users = $this->random_items($this->users, $size);
            $lists = [
                'engage_resource' => $this->public_resources,
                'engage_survey' => $this->public_surveys
            ];
            $total = (count($this->public_resources) + count($this->public_surveys))
                     * count($users)
                     / BATCH_INSERT_MAX_ROW_COUNT;
            $bulk = [];
            $done = 0;
            foreach ($lists as $component => $list) {
                foreach ($list as $item) {
                    foreach ($users as $user) {
                        $bulk[] = (new reaction(
                            $component,
                            $item[0],
                            $item[1],
                            $user
                        ))->create_for_bulk();
                        if (count($bulk) >= BATCH_INSERT_MAX_ROW_COUNT) {
                            builder::get_db()->insert_records(reaction_entity::TABLE, $bulk);
                            $bulk = [];
                            self::show_progress($done, $total);
                        }
                    }
                }
            }

            if (!empty($bulk)) {
                builder::get_db()->insert_records(reaction_entity::TABLE, $bulk);
                self::show_progress($done, $total);
            }
        });

        return $this;
    }

    public function engage_create_playlists() {
        if (empty($this->topics)) {
            throw new coding_exception('Must generate topics before creating playlists');
        }

        $this->output('Creating playlists...');
        $sizes = $this->get_item_size('playlists');
        $max_topics = min(count($this->topics), 10);
        builder::get_db()->transaction(function () use ($sizes, $max_topics) {
            $total = $sizes['count_public'] + $sizes['count_private'];
            $created = 0;
            $done = 0;
            $user_list = $this->sequential_users_list();
            for ($i = 0; $i < $total; $i++) {
                $user_list->next();
                $owner_id = $user_list->current();
                $access = $created >= $sizes['count_public'] ? access::PRIVATE : access::PUBLIC;
                $is_public = $access === access::PUBLIC;

                $topics = $is_public ? $this->random_items(array_keys($this->topics), rand(1, $max_topics)) : [];
                $playlist = new playlist($owner_id, $access, $topics);
                $playlist->save();
                $created++;
                $this->playlists[] = $playlist->get_playlist_info();
                self::show_progress($done, $total);
            }
        });
        // We no longer need topics
        $this->topics = [];
        return $this;
    }

    public function engage_create_playlist_ratings() {
        $this->output('Creating playlist ratings...');

        $sizes = $this->get_item_size('playlists');
        $max_ratings = (int) $sizes['ratings_per_playlist'];
        builder::get_db()->transaction(function () use ($max_ratings) {
            $public_playlists = array_filter($this->playlists, function (array $playlist) {
                return $playlist[1] === access::PUBLIC;
            });

            $total = (count($public_playlists) * $max_ratings) / BATCH_INSERT_MAX_ROW_COUNT;
            $done = 0;
            $bulk = [];
            $user_list = $this->sequential_users_list();
            foreach ($this->playlists as $playlist) {
                for ($i = 0; $i < $max_ratings; $i++) {
                    $user_id = $user_list->current();
                    $bulk[] = (new rating($playlist[0], $user_id))
                        ->create_for_bulk();

                    if (count($bulk) >= BATCH_INSERT_MAX_ROW_COUNT) {
                        builder::get_db()->insert_records(rating_entity::TABLE, $bulk);
                        $bulk = [];
                        static::show_progress($done, $total);
                    }

                    $user_list->next();
                }
            }
            if (!empty($bulk)) {
                builder::get_db()->insert_records(rating_entity::TABLE, $bulk);
                self::show_progress($done, $total);
            }
        });

        return $this;
    }

    public function engage_create_playlist_comments() {
        $this->output('Creating comments on public playlists...');
        builder::get_db()->transaction(function () {
            $sizes = $this->get_item_size('playlists');
            $comments = $sizes['comments_per_playlist'];
            $replies = $sizes['replies_per_comment'];
            $done = 0;

            $total_comments = count($this->playlists) * $comments;
            $total_replies = $total_comments * ($replies / BATCH_INSERT_MAX_ROW_COUNT);
            $total = $total_comments + $total_replies;

            $public_playlists = array_filter($this->playlists, function ($playlist): bool {
                return $playlist[1] == access::PUBLIC;
            });
            $user_list = $this->sequential_users_list();
            foreach ($public_playlists as $playlist) {
                for ($j = 0; $j < $comments; $j++) {
                    $user_list->next();
                    $comment = (new items\totara_comment\comment(
                        $playlist[0],
                        $user_list->current(),
                        'totara_playlist',
                        'comment'
                    ))->save_and_return();
                    self::show_progress($done, $total);
                    $comment->add_replies($replies, $this->users, $done, $total);
                }
            }
            unset($public_playlists);
        });
        return $this;
    }

    public function engage_create_playlist_resources() {
        $this->output('Sharing resources and surveys to playlists...');
        if (empty($this->public_resources) && empty($this->public_surveys)) {
            throw new coding_exception("Cannot create playlist resources without any public resources or surveys");
        }

        $sizes = $this->get_item_size('playlists');
        $resource_size = $sizes['resources_per_playlist'];
        $survey_size = $sizes['surveys_per_playlist'];

        $count = count($this->public_resources);
        if ($resource_size > $count) {
            $resource_size = $count;
        }
        $count = count($this->public_surveys);
        if ($survey_size > $count) {
            $survey_size = $count;
        }

        builder::get_db()->transaction(function () use ($resource_size, $survey_size) {
            // For each playlist, we need to add some playlist resources & surveys
            $resource_counts = [];
            $playlist_count = count($this->playlists);

            $to_process = [
                'public_resources' => $resource_size,
                'public_surveys' => $survey_size,
            ];

            $total = (($playlist_count * ($resource_size + $survey_size)) + $resource_size + $survey_size) / BATCH_INSERT_MAX_ROW_COUNT;
            $done = 0;
            $bulk = [];
            foreach ($this->playlists as $playlist) {
                foreach ($to_process as $data_source => $data_size) {
                    $resources = $this->random_items($this->$data_source, $data_size);
                    $order = 0;
                    foreach ($resources as $resource) {
                        $resource_id = $resource[0];
                        $bulk[] = playlist::add_resource($playlist[0], $resource_id, $playlist[3], $order);
                        if (!isset($resource_counts[$resource_id])) {
                            $resource_counts[$resource_id] = 0;
                        }
                        $resource_counts[$resource_id]++;
                        $order++;

                        if (count($bulk) >= BATCH_INSERT_MAX_ROW_COUNT) {
                            builder::get_db()->insert_records(playlist_resource::TABLE, $bulk);
                            $bulk = [];
                            self::show_progress($done, $total);
                        }
                    }
                }
                unset($resources);
            }
            if (!empty($bulk)) {
                builder::get_db()->insert_records(playlist_resource::TABLE, $bulk);
                self::show_progress($done, $total);
            }

            $this->output('Syncing playlist resource counts ...');
            $done = 0;
            $total = count($resource_counts);

            // Update the resource counts
            foreach ($resource_counts as $resource_id => $count) {
                playlist::update_playlist_resource_count($resource_id, $count);
                self::show_progress($done, $total);
            }
            unset($resource_counts);
        });

        return $this;
    }

    /**
     * @param int $owner_id
     * @param int $resource_id
     * @param string $component
     * @param int $context_id
     * @return share
     */
    private function share_or_new(int $owner_id, int $resource_id, string $component, int $context_id) {
        if (!isset($this->shares[$component])) {
            $this->shares[$component] = [];
        }

        $key = join(':', [$owner_id, $resource_id, $context_id]);
        if (!isset($this->shares[$component][$key])) {
            $share = (new share($owner_id, $resource_id, $component, $context_id))
                ->save_and_return();
            $this->shares[$component][$key] = $share;
        }

        return $this->shares[$component][$key];
    }

    public function engage_create_workspace_shares() {
        // If workspaces were disabled, skip this generation
        if (empty($this->public_workspaces)) {
            return $this;
        }

        $this->output('Sharing engage resources, surveys and playlists to workspaces...');
        $sizes = $this->get_item_size('workspaces');
        $workspaces = $sizes['items_per_workspace_per_type'];

        builder::get_db()->transaction(function () use ($workspaces) {
            $total = (count($this->public_resources) * count($this->public_surveys) + count($this->playlists))
                    * $workspaces
                    / BATCH_INSERT_MAX_ROW_COUNT;
            $bulk = [];
            $do_bulk = function (array $bulk, int &$done) use ($total): array {
                if (count($bulk) >= BATCH_INSERT_MAX_ROW_COUNT) {
                    builder::get_db()->insert_records(share_recipient::TABLE, $bulk);
                    $bulk = [];
                    performance_testing::show_progress($done, $total);
                }

                return $bulk;
            };
            $done = 0;
            foreach ($this->public_workspaces as $workspace) {
                $workspace_id = $workspace->get_id();

                // Grab some random resources
                if (!empty($this->public_resources)) {
                    $random_resources = $this->random_items($this->public_resources, $workspaces);
                    foreach ($random_resources as $random_resource) {
                        [$resource_id, $context_id, $owner_id] = $random_resource;
                        $share = $this->share_or_new($owner_id, $resource_id, 'engage_article', $context_id);
                        $bulk[] = $share->add_recipient($workspace_id, 'container_workspace', 'library', $owner_id);
                        $bulk = $do_bulk($bulk, $done);
                    }
                }
                // Random surveys
                if (!empty($this->public_surveys)) {
                    $random_surveys = $this->random_items($this->public_surveys, $workspaces);
                    foreach ($random_surveys as $random_survey) {
                        [$survey_id, $context_id, $owner_id] = $random_survey;
                        $share = $this->share_or_new($owner_id, $survey_id, 'engage_survey', $context_id);
                        $bulk[] = $share->add_recipient($workspace_id, 'container_workspace', 'library', $owner_id);
                        $bulk = $do_bulk($bulk, $done);
                    }
                }
                // Random public playlists
                if (!empty($this->playlists)) {
                    $public_playlists = array_filter($this->playlists, function ($playlist): bool {
                        return $playlist[1] === access::PUBLIC;
                    });
                    $random_playlists = $this->random_items($public_playlists, $workspaces);
                    foreach ($random_playlists as $random_playlist) {
                        [$playlist_id, $access, $context_id, $owner_id] = $random_playlist;
                        $share = $this->share_or_new($owner_id, $playlist_id, 'totara_playlist', $context_id);
                        $bulk[] = $share->add_recipient($workspace_id, 'container_workspace', 'library', $owner_id);
                        $bulk = $do_bulk($bulk, $done);
                    }
                }
            }

            if (!empty($bulk)) {
                builder::get_db()->insert_records(share_recipient::TABLE, $bulk);
                self::show_progress($done, $total);
            }
        });
        return $this;
    }

    public function engage_create_shares() {
        $this->output('Sharing engage resources, surveys and playlists with users...');
        builder::get_db()->transaction(function () {
            $resource_sizes = $this->get_item_size('engage_resources');
            $users_per_resource = $resource_sizes['shared_users_per_resources'];
            $survey_sizes = $this->get_item_size('engage_surveys');
            $users_per_survey = $survey_sizes['shared_users_per_survey'];
            $playlist_sizes = $this->get_item_size('playlists');
            $users_per_playlist = $playlist_sizes['shared_users_per_playlist'];
            unset($resource_sizes, $survey_sizes, $playlist_sizes);

            $done = 0;
            $total = ((count($this->public_resources) * $users_per_resource)
                + (count($this->public_surveys) * $users_per_survey)
                + (count($this->playlists) * $users_per_playlist))
                / BATCH_INSERT_MAX_ROW_COUNT;

            $bulk = [];
            $do_bulk = function (array $bulk, int &$done) use ($total): array {
                if (count($bulk) >= BATCH_INSERT_MAX_ROW_COUNT) {
                    builder::get_db()->insert_records(share_recipient::TABLE, $bulk);
                    $bulk = [];
                    performance_testing::show_progress($done, $total);
                }

                return $bulk;
            };

            if (!empty($this->public_resources)) {
                $random_users = $this->random_items($this->users, $users_per_resource);
                foreach ($this->public_resources as $resource) {
                    [$resource_id, $resource_context_id, $owner_id] = $resource;
                    $share = $this->share_or_new($owner_id, $resource_id, 'engage_article', $resource_context_id);
                    foreach ($random_users as $user_id) {
                        $bulk[] = $share->add_recipient($user_id, 'core_user', 'user', $owner_id);
                        $bulk = $do_bulk($bulk, $done);
                    }
                }
            }

            if (!empty($this->public_surveys)) {
                $random_users = $this->random_items($this->users, $users_per_survey);
                foreach ($this->public_surveys as $survey) {
                    [$survey_id, $resource_context_id, $owner_id] = $survey;
                    $share = $this->share_or_new($owner_id, $survey_id, 'engage_survey', $resource_context_id);
                    foreach ($random_users as $user_id) {
                        $bulk[] = $share->add_recipient($user_id, 'core_user', 'user', $owner_id);
                        $bulk = $do_bulk($bulk, $done);
                    }
                }
            }

            if (!empty($this->playlists)) {
                $random_users = $this->random_items($this->users, $users_per_playlist);
                foreach ($this->playlists as $playlist) {
                    $share = $this->share_or_new($playlist[3], $playlist[0], 'totara_playlist', $playlist[2]);
                    foreach ($random_users as $user_id) {
                        $bulk[] = $share->add_recipient($user_id, 'core_user', 'user', $playlist[3]);
                        $bulk = $do_bulk($bulk, $done);
                    }
                }
            }

            if (!empty($bulk)) {
                builder::get_db()->insert_records(share_recipient::TABLE, $bulk);
                self::show_progress($done, $total);
            }
            unset($random_users);
        });

        // No longer needed
        $this->shares = [];

        return $this;
    }

    /**
     * Create the recommender interactions
     *
     * @param bool $resources
     * @param bool $playlists
     * @param bool $workspaces
     * @param bool $courses
     * @return $this
     */
    public function engage_create_interactions(
        bool $resources = true,
        bool $playlists = true,
        bool $workspaces = true,
        bool $courses = true
    ) {
        if ($resources) {
            $this->engage_create_resource_interactions();
        }
        if ($playlists) {
            $this->engage_create_playlist_interactions();
        }
        if ($workspaces) {
            $this->engage_create_workspaces_interactions();
        }
        if ($courses) {
            $this->engage_create_courses_interactions();
        }

        return $this;
    }

    private function engage_create_resource_interactions() {
        // If there are no resources, we do not recommend
        if (empty($this->public_resources)) {
            return $this;
        }

        $this->output('Creating engage resource recommenders interactions...');

        builder::get_db()->transaction(function () {
            $sizes = $this->get_item_size('ml_interactions');

            // Only going to run with XX% of users
            $user_count = floor(count($this->users) * ($sizes['percent_of_users'] / 100));
            $resource_count = count($this->public_resources);

            $range_from = $sizes['min_interactions_per_user'];
            $range_to = $sizes['max_interactions_per_user'];

            // We don't actually know how many it'll pick, so we go for the max
            $total = ($user_count * $range_to) / BATCH_INSERT_MAX_ROW_COUNT;
            $done = 0;
            $i = 0;
            $bulk = [];
            foreach ($this->users as $user_id) {
                // Pick a random number of resources to interact with
                $count = ceil(rand($range_from, $range_to) / 4);
                $random_resources = (int) min($count, $resource_count);
                $resources = $this->random_items($this->public_resources, $random_resources);
                foreach ($resources as $resource) {
                    $resource_id = $resource[0];
                    $bulk[] = (new interaction(
                        $user_id,
                        $resource_id,
                        'engage_article',
                        'view',
                        1
                    ))->create_for_bulk();

                    if (count($bulk) >= BATCH_INSERT_MAX_ROW_COUNT) {
                        builder::get_db()->insert_records(interaction_entity::TABLE, $bulk);
                        $bulk = [];
                        self::show_progress($done, $total);
                    }

                    $bulk[] = (new interaction(
                        $user_id,
                        $resource_id,
                        'engage_article',
                        'like',
                        1
                    ))->create_for_bulk();

                    if (count($bulk) >= BATCH_INSERT_MAX_ROW_COUNT) {
                        builder::get_db()->insert_records(interaction_entity::TABLE, $bulk);
                        $bulk = [];
                        self::show_progress($done, $total);
                    }
                }
                unset($resources);
                $i++;
                if ($i >= $user_count) {
                    break;
                }
            }

            if (!empty($bulk)) {
                builder::get_db()->insert_records(interaction_entity::TABLE, $bulk);
                self::show_progress($done, $total);
            }
        });
        return $this;
    }

    private function engage_create_playlist_interactions() {
        $this->output('Creating playlist recommenders interactions...');

        builder::get_db()->transaction(function () {
            $sizes = $this->get_item_size('ml_interactions');

            $range_from = $sizes['min_interactions_per_user'];
            $range_to = $sizes['max_interactions_per_user'];

            // Only going to run with XX% of users
            $user_count = floor(count($this->users) * ($sizes['percent_of_users'] / 100));
            $playlist_count = count($this->playlists);

            $i = 0;
            $done = 0;
            $total = ($user_count * $playlist_count) / BATCH_INSERT_MAX_ROW_COUNT;
            $bulk = [];
            foreach ($this->users as $user_id) {
                // Pick a random number of playlists to interact with
                $count = ceil(rand($range_from, $range_to) / 4);
                $random_playlists = min($count, $playlist_count);
                $playlists = $this->random_items($this->playlists, $random_playlists);
                foreach ($playlists as $playlist) {
                    $playlist_id = $playlist[0];
                    $bulk[] = (new interaction(
                        $user_id,
                        $playlist_id,
                        'totara_playlist',
                        'view',
                        1
                    ))->create_for_bulk();

                    if (count($bulk) >= BATCH_INSERT_MAX_ROW_COUNT) {
                        builder::get_db()->insert_records(interaction_entity::TABLE, $bulk);
                        $bulk = [];
                        self::show_progress($done, $total);
                    }

                    $bulk[] = (new interaction(
                        $user_id,
                        $playlist_id,
                        'totara_playlist',
                        'rate',
                        rand(1, 5)
                    ))->create_for_bulk();

                    if (count($bulk) >= BATCH_INSERT_MAX_ROW_COUNT) {
                        builder::get_db()->insert_records(interaction_entity::TABLE, $bulk);
                        $bulk = [];
                        self::show_progress($done, $total);
                    }
                }
                unset($playlists);
                $i++;
                if ($i >= $user_count) {
                    break;
                }
            }

            if (!empty($bulk)) {
                builder::get_db()->insert_records(interaction_entity::TABLE, $bulk);
                self::show_progress($done, $total);
            }
        });

        return $this;
    }

    private function engage_create_workspaces_interactions() {
        if (empty($this->public_workspaces)) {
            return $this;
        }
        $this->output('Creating workspace recommenders interactions...');

        builder::get_db()->transaction(function () {
            $sizes = $this->get_item_size('ml_interactions');

            $range_from = $sizes['min_interactions_per_user'];
            $range_to = $sizes['max_interactions_per_user'];

            // Only going to run with XX% of users
            $user_count = floor(count($this->users) * ($sizes['percent_of_users'] / 100));
            $workspace_count = count($this->public_workspaces);

            $i = 0;
            $done = 0;
            $total = ($user_count * $workspace_count) / BATCH_INSERT_MAX_ROW_COUNT;
            $bulk = [];
            foreach ($this->users as $user_id) {
                $count = ceil(rand($range_from, $range_to) / 4);
                $random_workspaces = min($count, $workspace_count);
                $workspaces = $this->random_items($this->public_workspaces, $random_workspaces);
                foreach ($workspaces as $workspace) {
                    $bulk[] = (new interaction(
                        $user_id,
                        $workspace->get_id(),
                        'container_workspace',
                        'view',
                        1
                    ))->create_for_bulk();

                    if (count($bulk) >= (BATCH_INSERT_MAX_ROW_COUNT)) {
                        builder::get_db()->insert_records(interaction_entity::TABLE, $bulk);
                        $bulk = [];
                        self::show_progress($done, $total);
                    }

                    $bulk[] = (new interaction(
                        $user_id,
                        $workspace->get_id(),
                        'container_workspace',
                        'comment',
                        1
                    ))->create_for_bulk();

                    if (count($bulk) >= (BATCH_INSERT_MAX_ROW_COUNT)) {
                        builder::get_db()->insert_records(interaction_entity::TABLE, $bulk);
                        $bulk = [];
                        self::show_progress($done, $total);
                    }
                }
                unset($workspaces);
                $i++;
                if ($i >= $user_count) {
                    break;
                }
            }
            if (!empty($bulk)) {
                builder::get_db()->insert_records(interaction_entity::TABLE, $bulk);
                self::show_progress($done, $total);
            }
        });

        return $this;
    }

    private function engage_create_courses_interactions() {
        if (empty($this->courses)) {
            return $this;
        }
        $this->output('Creating course recommenders interactions...');

        builder::get_db()->transaction(function () {
            $sizes = $this->get_item_size('ml_interactions');

            $range_from = $sizes['min_interactions_per_user'];
            $range_to = $sizes['max_interactions_per_user'];

            // Only going to run with XX% of users
            $user_count = floor(count($this->users) * ($sizes['percent_of_users'] / 100));
            $courses_count = count($this->courses);
            $i = 0;

            $done = 0;
            $total = ($user_count * $courses_count) / BATCH_INSERT_MAX_ROW_COUNT;
            $bulk = [];
            foreach ($this->users as $user_id) {
                $count = ceil(rand($range_from, $range_to) / 4);
                $random_courses = min($count, $courses_count);
                $courses = $this->random_items($this->courses, $random_courses);
                foreach ($courses as $course) {
                    $course_id = $course->get_data('id');
                    $bulk[] = (new interaction(
                        $user_id,
                        $course_id,
                        'container_course',
                        'view',
                        1
                    ))->create_for_bulk();

                    if (count($bulk) >= BATCH_INSERT_MAX_ROW_COUNT) {
                        builder::get_db()->insert_records(interaction_entity::TABLE, $bulk);
                        $bulk = [];
                        self::show_progress($done, $total);
                    }
                }
                $i++;
                if ($i >= $user_count) {
                    break;
                }
            }

            if (!empty($bulk)) {
                builder::get_db()->insert_records(interaction_entity::TABLE, $bulk);
                self::show_progress($done, $total);
            }
        });

        return $this;
    }

    /**
     * Recommendations for item-to-item
     *
     * @return $this
     */
    public function engage_create_item_recommendations() {
        if (!empty($this->public_resources)) {
            $this->engage_create_resource_to_resource_recommendations();
        }

        if (!empty($this->playlists)) {
            $this->engage_create_playlist_to_playlist_recommendations();
        }

        return $this;
    }

    /**
     * Create recommendations from one article to another
     *
     * @return $this
     */
    private function engage_create_resource_to_resource_recommendations() {
        if (empty($this->public_resources)) {
            return $this;
        }

        $this->output('Creating engage resource to resource recommendations...');

        builder::get_db()->transaction(function () {
            $sizes = $this->get_item_size('ml_recommendations');
            $count = $sizes['resources_to_resources'];

            $done = 0;
            $total = (count($this->public_resources) * $count) / BATCH_INSERT_MAX_ROW_COUNT;
            $recommended = [];

            // Each resource is going to relate to a random $count number of resources
            // For speed, we're only going to pick random resources 5% of the time, otherwise this can
            // take a very long time.
            $regenerate_threshold = ceil($total * 0.05);

            $bulk = [];
            foreach ($this->public_resources as $resource) {
                if ($done % $regenerate_threshold === 0) {
                    $recommended = $this->random_items($this->public_resources, $count);
                }
                foreach ($recommended as $item) {
                    $bulk[] = (new item_recommendation(
                        $resource[0],
                        'engage_article',
                        null,
                        $item[0],
                        'engage_article',
                        null
                    ))->create_for_bulk();

                    if (count($bulk) >= BATCH_INSERT_MAX_ROW_COUNT) {
                        builder::get_db()->insert_records(recommended_item::TABLE, $bulk);
                        $bulk = [];
                        self::show_progress($done, $total);
                    }
                }
            }

            if (!empty($bulk)) {
                builder::get_db()->insert_records(recommended_item::TABLE, $bulk);
                self::show_progress($done, $total);
            }
            unset($recommended, $bulk);
        });

        return $this;
    }

    /**
     * Create recommendations from one article to another
     *
     * @return $this
     */
    private function engage_create_playlist_to_playlist_recommendations() {
        if (empty($this->playlists)) {
            return $this;
        }

        $this->output('Creating engage playlist to playlist recommendations...');

        builder::get_db()->transaction(function () {
            $sizes = $this->get_item_size('ml_recommendations');
            $count = $sizes['playlists_to_playlists'];

            $public_playlists = array_filter($this->playlists, function ($playlist): bool {
                return $playlist[1] == access::PUBLIC;
            });

            $done = 0;
            $total = (count($public_playlists) * $count) / BATCH_INSERT_MAX_ROW_COUNT;
            $recommended = [];

            // Each playlist is going to relate to a random $count number of public playlists
            // For speed, we're only going to pick random playlists 5% of the time, otherwise this can
            // take a very long time.
            $regenerate_threshold = ceil($total * 0.05);

            $bulk = [];
            foreach ($public_playlists as $playlist) {
                if ($done % $regenerate_threshold === 0) {
                    $recommended = $this->random_items($public_playlists, $count);
                }
                foreach ($recommended as $item) {
                    $bulk[] = (new item_recommendation(
                        $playlist[0],
                        'totara_playlist',
                        null,
                        $item[0],
                        'totara_playlist',
                        null
                    ))->create_for_bulk();

                    if (count($bulk) >= BATCH_INSERT_MAX_ROW_COUNT) {
                        builder::get_db()->insert_records(recommended_item::TABLE, $bulk);
                        $bulk = [];
                        self::show_progress($done, $total);
                    }
                }
            }
            if (!empty($bulk)) {
                builder::get_db()->insert_records(recommended_item::TABLE, $bulk);
                self::show_progress($done, $total);
            }
            unset($recommended, $bulk);
        });

        return $this;
    }

    /**
     * Recommendations for items-to-users
     *
     * @return $this
     */
    public function engage_create_user_recommendations() {
        if (!empty($this->public_resources)) {
            $this->engage_create_resource_to_user_recommendations();
        }

        if (!empty($this->public_workspaces)) {
            $this->engage_create_workspaces_to_user_recommendations();
        }

        if (!empty($this->courses)) {
            $this->engage_create_courses_to_user_recommendations();
        }

        return $this;
    }

    /**
     * Create recommendations for users to articles
     *
     * @return $this
     */
    private function engage_create_resource_to_user_recommendations() {
        if (empty($this->public_resources)) {
            return $this;
        }

        $this->output('Creating engage resource to user recommendations...');

        builder::get_db()->transaction(function () {
            $sizes = $this->get_item_size('ml_recommendations');
            $count = $sizes['resources_to_users'];

            $short_resources = array_filter($this->public_resources, function ($resource): bool {
                return $resource[3] == time_view::LESS_THAN_FIVE;
            });

            $done = 0;
            $total = (count($this->users) * $count) / BATCH_INSERT_MAX_ROW_COUNT;
            $recommended_resources = [];

            // Each user will be recommended $count public resources with time to view < 5 (only ones we show up)
            // For speed, we're only going to pick random resources 5% of the time, otherwise this can
            // take a very long time.
            $regenerate_threshold = ceil($total * 0.05);
            $bulk = [];
            foreach ($this->users as $user_id) {
                if ($done % $regenerate_threshold === 0) {
                    $recommended_resources = $this->random_items($short_resources, $count);
                }
                foreach ($recommended_resources as $resource) {
                    $bulk[] = (new user_recommendation(
                        $user_id,
                        $resource[0],
                        'engage_article',
                        null
                    ))
                        ->create_for_bulk();

                    if (count($bulk) >= BATCH_INSERT_MAX_ROW_COUNT) {
                        builder::get_db()->insert_records(recommended_user_item::TABLE, $bulk);
                        $bulk = [];
                        self::show_progress($done, $total);
                    }
                }
            }
            if (!empty($bulk)) {
                builder::get_db()->insert_records(recommended_user_item::TABLE, $bulk);
                self::show_progress($done, $total);
            }
            unset($recommended_resources, $bulk);
        });

        return $this;
    }

    /**
     * Create recommendations for users to workspaces
     *
     * @return $this
     */
    private function engage_create_workspaces_to_user_recommendations() {
        if (empty($this->public_workspaces)) {
            return $this;
        }

        $this->output('Creating workspace to user recommendations...');

        builder::get_db()->transaction(function () {
            $sizes = $this->get_item_size('ml_recommendations');
            $count = $sizes['workspaces_to_users'];

            $done = 0;
            $total = (count($this->users) * $count) / BATCH_INSERT_MAX_ROW_COUNT;
            $recommended_workspaces = [];

            // Each user will be recommended $count public workspaces
            // For speed, we're only going to pick random workspaces 5% of the time
            $regenerate_threshold = ceil($total * 0.05);

            $bulk = [];
            foreach ($this->users as $user_id) {
                if ($done % $regenerate_threshold === 0) {
                    $recommended_workspaces = $this->random_items($this->public_workspaces, $count);
                }
                /** @var \container_workspace\workspace $workspace */
                foreach ($recommended_workspaces as $workspace) {
                    $bulk[] = (new user_recommendation(
                        $user_id,
                        $workspace->get_id(),
                        'container_workspace',
                        null
                    ))
                        ->create_for_bulk();

                    if (count($bulk) >= BATCH_INSERT_MAX_ROW_COUNT) {
                        builder::get_db()->insert_records(recommended_user_item::TABLE, $bulk);
                        $bulk = [];
                        self::show_progress($done, $total);
                    }
                }
            }
            if (!empty($bulk)) {
                builder::get_db()->insert_records(recommended_user_item::TABLE, $bulk);
                self::show_progress($done, $total);
            }
            unset($recommended_workspaces, $bulk);
            $this->public_workspaces = [];
        });

        return $this;
    }

    /**
     * Create recommendations for users to workspaces
     *
     * @return $this
     */
    private function engage_create_courses_to_user_recommendations() {
        if (empty($this->courses)) {
            return $this;
        }

        $this->output('Creating course to user recommendations...');

        builder::get_db()->transaction(function () {
            $sizes = $this->get_item_size('ml_recommendations');
            $count = $sizes['courses_to_users'];

            // As recommending courses requires the self-enroll plugin to be enabled, we're going to
            // make this faster by picking 20 courses, forcing self-enrollment on, and then
            // using that as our random recommendations list.

            /** @var course[] $random_courses */
            $random_courses = $this->random_items($this->courses, 20);
            $random_course_ids = [];

            foreach ($random_courses as $random_course) {
                $course_id = $random_course->get_data('id');

                $enrol_record = enrol::repository()
                    ->where('courseid', $course_id)
                    ->where('enrol', 'self')
                    ->order_by('courseid')
                    ->first();
                if ($enrol_record) {
                    $enrol_record->set_attribute('status', 0);
                    $enrol_record->save();
                    unset($enrol_record);
                }
                $random_course_ids[] = $course_id;
            }
            unset($random_courses);

            $done = 0;
            $total = (count($this->users) * $count) / BATCH_INSERT_MAX_ROW_COUNT;
            $regenerate_threshold = ceil($total * 0.5);
            $recommended_course_ids = [];

            $bulk = [];
            foreach ($this->users as $user_id) {
                if ($done % $regenerate_threshold === 0) {
                    $recommended_course_ids = $this->random_items($random_course_ids, $count);
                }
                foreach ($recommended_course_ids as $course_id) {
                    $bulk[] = (new user_recommendation(
                        $user_id,
                        $course_id,
                        'container_course',
                        null
                    ))
                        ->create_for_bulk();

                    if (count($bulk) >= BATCH_INSERT_MAX_ROW_COUNT) {
                        builder::get_db()->insert_records(recommended_user_item::TABLE, $bulk);
                        $bulk = [];
                        self::show_progress($done, $total);
                    }
                }
            }

            if (!empty($bulk)) {
                builder::get_db()->insert_records(recommended_user_item::TABLE, $bulk);
                self::show_progress($done, $total);
            }
            unset($recommended_course_ids, $random_course_ids, $bulk);
        });

        return $this;
    }

    /**
     * Return a random number of items from the array.
     * Will return the actual random items.
     *
     * @param array $items
     * @param int $count
     * @return array
     */
    private function random_items(array $items, int $count) {
        $items_count = count($items);
        if ($count > $items_count) {
            $count = $items_count;
        }

        if ($count < 1) {
            return [];
        }

        // We have to work with pure keys
        $clean_items = \SplFixedArray::fromArray($items, false);
        $results = [];
        $picked = 0;
        while ($picked < $count) {
            $random_key = mt_rand(0, $items_count - 1);
            if (!isset($results[$random_key])) {
                $results[$random_key] = $clean_items[$random_key];
                $picked++;
            }
        }
        unset($clean_items);

        return $results;
    }

    /**
     * Iterates through the list of users
     *
     * @return Generator
     */
    private function sequential_users_list(): Generator {
        $users = $this->users;
        shuffle($users);
        while (true) {
            reset($users);
            foreach ($users as $user) {
                if (isguestuser($user) || is_siteadmin($user)) {
                    continue;
                }
                yield $user;
            }
        }
    }

    /**
     * Show dot and overall progress
     *
     * @param int $done
     * @param int $total
     */
    public static function show_progress(int &$done, int $total) {
        $done++;
        echo '.';

        if ($done % 50 === 0) {
            $percentage_done = round($done * 100 / $total, 0);
            echo "$percentage_done%" . PHP_EOL;
        }
    }
}
