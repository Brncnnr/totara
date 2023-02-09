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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 */

namespace degeneration\items\totara_engage;

use context_user;
use core_tag_tag;
use degeneration\App;
use degeneration\items\item;
use degeneration\items\totara_engage\survey\answer_choice;
use engage_survey\entity\survey as survey_entity;
use engage_survey\entity\survey_question;
use totara_engage\answer\answer_type;
use totara_engage\entity\answer_option;
use totara_engage\entity\engage_resource;
use totara_engage\entity\question as question_entity;

final class survey extends item {
    /**
     * @var int
     */
    protected $owner_id;

    /**
     * @var int
     */
    protected $access;

    /**
     * @var array
     */
    protected $topics;

    /**
     * @var int
     */
    private $survey_id;

    /**
     * ID of the saved question
     *
     * @var int
     */
    private $question_id;

    /**
     * @var array
     */
    private $option_ids;

    /**
     * @var int
     */
    private $context_id;

    /**
     * @var int
     */
    private $option_type;

    /**
     * @var int
     */
    private $options_count;

    /**
     * @var engage_resource
     */
    private $resource_record;

    /**
     * @var array
     */
    private $resource_extra;

    /**
     * @var array
     */
    private $cache_question;

    /**
     * survey constructor.
     *
     * @param int $owner_id
     * @param int $access
     * @param array $topics
     */
    public function __construct(int $owner_id, int $access, array $topics) {
        $this->owner_id = $owner_id;
        $this->access = $access;
        $this->topics = $topics;
        $this->survey_id = null;
        $this->question_id = null;
        $this->option_ids = [];
    }

    /**
     * @return array
     */
    public function get_properties(): array {
        $faker = App::faker();

        $options = [];
        $count = rand(2, survey_entity::MAX_QUESTION_OPTIONS);
        for ($i = 0; $i < $count; $i++) {
            $options[] = $faker->sentence(4);
        }

        return [
            'question' => $faker->sentence(4),
            'userid' => $this->owner_id,
            'access' => $this->access,
            'topics' => $this->topics,
            'options' => $options,
            'answer_type' => rand(0, 1) === 0 ? answer_type::MULTI_CHOICE : answer_type::SINGLE_CHOICE,
            'time_expired' => null,
        ];
    }

    /**
     * @return bool
     */
    public function save(): bool {
        // The generator/create function stresses out when creating large collections of surveys.
        // To avoid crashing the generator we will create the base survey model parts here. As with
        // all parts of the generator, there is a risk the two fall out of sync but this is not
        // unique to surveys.
        $properties = $this->get_properties();

        $record = new engage_resource();
        $record->resourcetype = 'engage_survey';
        $record->name = $properties['question'];
        $record->userid = $properties['userid'];

        $context = context_user::instance($properties['userid']);
        $record->contextid = $context->id;
        $record->access = $properties['access'];
        $this->context_id = $context->id;

        $survey_entity = new survey_entity();
        $survey_entity->timeexpired = $properties['time_expired'];
        $survey_entity->save();

        $this->survey_id = $survey_entity->id;
        unset($survey_entity);

        $record->instanceid = $this->survey_id;

        // Create the question
        $question = new question_entity();
        $question->value = $properties['question'];
        $question->answertype = $properties['answer_type'];
        $question->component = 'engage_survey';
        $question->userid = $properties['userid'];
        $question->save();
        $this->question_id = $question->id;

        $survey_question = new survey_question();
        $survey_question->surveyid = $this->survey_id;
        $survey_question->questionid = $this->question_id;
        $survey_question->save();
        unset($survey_question);

        // Create the options
        $cache_options = [];
        foreach ($properties['options'] as $option_value) {
            $option = new answer_option();
            $option->value = $option_value;
            $option->questionid = $this->question_id;
            $option->save();
            $this->option_ids[] = $option->id;
            $cache_options[$option->id] = [
                'id' => $option->id,
                'questionid' => $this->question_id,
                'value' => $option_value,
                'votes' => 0,
            ];
            unset($option);
        }

        $this->option_type = $properties['answer_type'];
        $this->options_count = count($this->option_ids);

        // Build our cache question
        $this->cache_question = [
            'id' => $question->id,
            'value' => $question->value,
            'votes' => 0,
            'updateable' => true,
            'answertype' => $properties['answer_type'],
            'options' => $cache_options,
            'participants' => 0
        ];
        $this->resource_extra = [
            'timeexpired' => $properties['time_expired'],
            'questions' => [
                $this->cache_question,
            ],
            'editable' => true
        ];

        $record->extra = json_encode($this->resource_extra);
        $record->save();
        $this->resource_record = $record;

        // Set the topics. Again, for speed we bypass the topic helper as the resolver lookups
        // get heavy on larger runs
        foreach ($properties['topics'] as $topic_raw_name) {
            core_tag_tag::add_item_tag(
                'engage_survey',
                'engage_resource',
                $this->resource_record->id,
                $context,
                $topic_raw_name,
                $properties['userid']
            );
        }

        return true;
    }

    /**
     * @param int $total_votes
     * @param int $total_participants
     */
    public function save_resource_extra(int $total_votes, int $total_participants): void {
        $this->cache_question['options'] = array_values($this->cache_question['options']);
        $this->resource_extra['questions'] = [
            $this->cache_question
        ];
        $this->resource_extra['votes'] = $total_votes;
        $this->resource_extra['participants'] = $total_participants;
        $this->resource_record->extra = json_encode($this->resource_extra);
        $this->resource_record->save();
    }

    /**
     * Used
     *
     * @return array
     */
    public function get_survey_info(): array {
        return [$this->resource_record->id, $this->context_id, $this->owner_id];
    }

    /**
     * Create some random votes for the provided user.
     *
     * @param int $user_id
     * @return int
     */
    public function create_votes(int $user_id): int {
        $count = 1;
        if ($this->option_type === answer_type::MULTI_CHOICE) {
            $count = rand(1, $this->options_count);
        }

        $options = array_rand($this->option_ids, $count);
        if ($count === 1) {
            $options = [$options];
        }

        foreach ($options as $option_key) {
            $option_id = $this->option_ids[$option_key];
            $this->cache_question['options'][$option_id]['votes']++;
            (new answer_choice($this->question_id, $option_id, $user_id))
                ->save();
        }

        return $count;
    }
}