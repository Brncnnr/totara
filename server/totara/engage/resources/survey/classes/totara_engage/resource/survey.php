<?php
/**
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package engage_survey
 */
namespace engage_survey\totara_engage\resource;

use core\orm\query\builder;
use engage_survey\entity\survey as survey_entity;
use engage_survey\event\survey_deleted;
use engage_survey\event\survey_reshared;
use engage_survey\event\survey_shared;
use engage_survey\repository\survey_question_repository;
use engage_survey\result\vote_result;
use engage_survey\totara_engage\resource\input\answer_length_validator;
use engage_survey\totara_engage\resource\input\question_length_validator;
use totara_engage\access\access_manager;
use totara_engage\answer\answer_factory;
use engage_survey\totara_engage\resource\input\question_validator;
use totara_engage\link\builder as link_builder;
use totara_engage\question\question;
use totara_engage\entity\engage_resource;
use totara_engage\resource\input\definition;
use totara_engage\resource\resource_item;
use engage_survey\entity\survey_question;
use totara_engage\share\manager as share_manager;
use totara_engage\share\share as share_model;
use engage_survey\result\question as question_stat;

/**
 * Engage resource for survey
 */
final class survey extends resource_item {
    /**
     * @var string
     */
    public const REACTION_AREA = 'media';

    /**
     * @var survey_entity
     */
    private $survey;

    /**
     * @var survey_question[]
     */
    private $surveyquestions;

    /**
     * @return int|null
     */
    public function get_timeexpired(): ?int {
        return $this->survey->timeexpired;
    }

    /**
     * @param bool $reload Whether to reload the survey questions instance or not if the survey questions is not empty
     * @return void
     */
    public function load_survey_questions(bool $reload = false): void {
        if (!empty($this->surveyquestions) && !$reload) {
            return;
        }

        $this->surveyquestions = [];

        if (0 == $this->survey->id || !$this->survey->exists()) {
            debugging(
                "Cannot load the survey questions when the survey is not existing in the system",
                DEBUG_DEVELOPER
            );
        }

        /** @var survey_question_repository $repo */
        $repo = survey_question::repository();
        $questions = $repo->get_all_for_survey($this->survey->id);

        if (empty($questions)) {
            return;
        }

        foreach ($questions as $question) {
            $questionid = $question->questionid;
            $this->surveyquestions[$questionid] = $question;
        }
    }

    /**
     * @param bool $reload          Whether to reload from DB or not.
     * @return survey_question[]
     */
    public function get_survey_questions(bool $reload = false): array {
        $this->load_survey_questions($reload);
        return $this->surveyquestions;
    }

    /**
     * @param int $resourceid
     * @return resource_item|survey
     */
    public static function from_resource_id(int $resourceid): resource_item {
        /** @var survey $resource */
        $resource = parent::from_resource_id($resourceid);

        if ($resource->get_resourcetype() !== static::get_resource_type()) {
            throw new \coding_exception('Resource type is not meant for the survey');
        }

        $instanceid = $resource->get_instanceid();
        $resource->survey = new survey_entity($instanceid);

        // Only load the question's map when required.
        $resource->surveyquestions = [];

        return $resource;
    }

    /**
     * @param survey_entity $survey
     * @param engage_resource $entity
     * @return survey
     */
    public static function from_entity(survey_entity $survey, engage_resource $entity): survey {
        $resourcetype = static::get_resource_type();
        if ($resourcetype != $entity->resourcetype) {
            throw new \coding_exception("Invalid resource record that is used for different component");
        }

        if (!$entity->exists() || !$survey->exists()) {
            throw new \coding_exception("Either resource record or the survey record is not being populated");
        } else if ($entity->instanceid != $survey->id) {
            throw new \coding_exception("Resource record is not meant for the survey");
        }

        $resource = new static($entity);
        $resource->survey = $survey;

        return $resource;
    }

    /**
     * @param array           $data
     * @param engage_resource $entity
     * @param int             $userid
     *
     * @return int
     */
    protected static function do_create(array $data, engage_resource $entity, int $userid): int {
        // Steps are simple:
        // 1. Create the survey record
        // 2. Create the questions
        // 3. Create the options of the question
        // 4. Create the map of questions and survey

        $survey = new survey_entity();
        $survey->timeexpired = $data['timeexpired'];

        $survey->save();

        // For now, survey is only allow one question so far.
        $questions = $data['questions'];

        if (1 != count($data['questions'])) {
            debugging("There seems to have more than one questions to be added for a survey", DEBUG_DEVELOPER);
            $questions = [reset($data['questions'])];
        }

        foreach ($questions as $item) {
            // As this is one question only we can update the resource name to be the question
            // so that we can be able to search over it.
            $entity->name = $item['value'];

            $question = question::create(
                $item['value'],
                $item['answertype'],
                static::get_resource_type(),
                $userid
            );

            if (!empty($item['options'])) {
                $total = survey_entity::MAX_QUESTION_OPTIONS;

                if ($total < count($item['options'])) {
                    throw new \coding_exception("There are more than '{$total}' options for a question");
                }

                $question->add_answer_options($item['options']);
            }

            $surveyquestion = new survey_question();
            $surveyquestion->surveyid = $survey->id;
            $surveyquestion->questionid = $question->get_id();
            $surveyquestion->save();
        }

        return $survey->id;
    }

    /**
     * @inheritDoc
     * @return array
     */
    protected static function get_data_definitions(): array {
        $definitions = parent::get_data_definitions();

        return array_merge(
            $definitions,
            [
                definition::from_parameters('timeexpired', ['default' => null]),
                definition::from_parameters(
                    'questions',
                    [
                        'required-on-add' => true,
                        'required-on-update' => false,
                        'validators' => [
                            new question_validator(),
                            new question_length_validator(75),
                            new answer_length_validator(80),
                        ],
                    ]
                )
            ]
        );
    }

    /**
     * @param int $userid
     * @return bool
     */
    protected function do_delete(int $userid): bool {
        $event = builder::get_db()->transaction(function () use ($userid) {
            share_manager::delete($this->get_id(), static::get_resource_type());

            $this->load_survey_questions();

            foreach ($this->surveyquestions as $survey_question) {
                $question = question::from_id($survey_question->questionid);
                $question->delete($userid);

                $survey_question->delete();
            }

            $event = survey_deleted::from_survey($this, $userid);

            $this->remove_topics_by_ids();

            $this->survey->delete();
            $this->resource->delete();

            return $event;
        });

        $event->trigger();

        return true;
    }

    /**
     * @param int $userid
     * @param array $data
     * @return bool
     */
    protected function do_update(array $data, int $userid): bool {
        if (!$this->can_be_updated()) {
            // No debugging, as the survey needs to be updated with access and topics.
            return true;
        }

        // We might have updated something other than questions and answers.
        if (empty($data['questions'])) {
            return true;
        }
        $question_data = $data['questions'];

        if (1 !== count($question_data)) {
            debugging("There seems to have more than one questions to be updated for survey", DEBUG_DEVELOPER);
            $question_data = [reset($question_data)];
        }

        foreach ($question_data as $question_datum) {
            if (!isset($question_datum['id'])) {
                // Updating will just allow us to change the question value, change the answer type
                // and update the options only. There will be no adding new questions.
                throw new \coding_exception("Cannot find the id of the question when updating");
            }

            $question = question::from_id($question_datum['id']);
            $question->set_value($question_datum['value']);
            $question->set_answertype($question_datum['answertype']);

            $question->update($userid);
            $question->update_answer_options($question_datum['options'], $userid);

            // Also set the resource name to new question.
            $this->resource->name = $question_datum['value'];
            $this->resource->save();
        }

        return true;
    }

    /**
     * @return question[]
     */
    public function get_questions(): array {
        $questions = [];
        $this->load_survey_questions();

        foreach ($this->surveyquestions as $surveyquestion) {
            $questions[] = question::from_id($surveyquestion->questionid);
        }

        return $questions;
    }

    /**
     * @param int|null $userid
     * @return bool
     */
    public function has_voted(?int $userid = null): bool {
        global $USER;

        if (null == $userid) {
            $userid = $USER->id;
        }

        $questions = $this->get_questions();
        $question = reset($questions);

        return $question->has_answer_of_user($userid);
    }

    /**
     * @param int $userid
     * @return bool
     */
    public function can_delete(int $userid): bool {
        $owner = $this->get_userid();
        if ($owner == $userid) {
            return true;
        }

        if (access_manager::can_manage_engage($this->get_context(), $userid)) {
            return true;
        }

        $context = \context_user::instance($owner);
        return has_capability('engage/survey:delete', $context, $userid);
    }

    /**
     * @param int $userid
     * @return bool
     */
    public static function can_create(int $userid): bool {
        $context = \context_user::instance($userid);
        return has_capability('engage/survey:create', $context, $userid);
    }

    /**
     * @return bool
     */
    public function can_be_updated(): bool {
        $questions = $this->get_questions();
        foreach ($questions as $question) {
            if (!$question->can_be_updated()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param int $userid
     * @return bool
     */
    public function can_update(int $userid): bool {
        $owner = $this->get_userid();
        $context = $this->get_context();

        if (access_manager::can_manage_engage($context, $userid)) {
            return true;
        }

        if (!has_capability('engage/survey:update', $context, $userid)) {
            // If the user does not have any capability to do update, then we will
            // skip it from here.
            return false;
        }

        // Otherwise, user can only update the survey when user is the owner of this very survey.
        return $owner == $userid;
    }

    /**
     * @param int           $questionid
     * @param int[]         $optionids
     * @param int|null      $userid
     *
     * @return bool
     */
    public function add_answer(int $questionid, array $optionids, ?int $userid = null): bool {
        global $USER;

        if (null == $userid) {
            $userid = $USER->id;
        }

        $question = question::from_id($questionid);
        if ($question->has_answer_of_user($userid)) {
            debugging(
                "User with id '{$userid}' already had an answer for the question '{$questionid}'",
                DEBUG_DEVELOPER
            );

            return false;
        }

        answer_factory::create_answer_for_user($question, $userid, $optionids);

        // Start updating the extra field data for the survey resource
        $this->refresh(true);
        return true;
    }

    /**
     * @return question_stat[]
     */
    public function get_question_stats(): array {
        $extra = $this->get_extra();
        if (null === $extra) {
            return [];
        }

        $extra = json_decode($extra, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \coding_exception("Cannot decode the json due to: " . json_last_error_msg());
        }

        if (!isset($extra['questions'])) {
            // No questions was stored into this cache.
            return [];
        }

        $questions = [];

        foreach ($extra['questions'] as $question_datum) {
            $questions[] = question_stat::from_parameters($question_datum);
        }

        return $questions;
    }
    /**
     * @inheritDoc
     */
    public function can_share(int $userid): bool {
        $context = $this->get_context();

        if (access_manager::can_manage_engage($context, $userid)) {
            return true;
        }

        // Check if user is allowed to share surveys.
        if (!has_capability('engage/survey:share', $context, $userid)) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function shared(share_model $share): void {
        // Create a shared event.
        if (!$share->is_notified()) {
            $event = survey_shared::from_share($share);
            $event->trigger();
        }
    }

    /**
     * @param int $userid
     */
    public function reshare(int $userid): void {
        $event = survey_reshared::from_survey($this, $userid);
        $event->trigger();
    }

    /**
     * @param bool $reload
     */
    public function refresh(bool $reload = false): void {
        if ($reload) {
            $result = new vote_result($this->survey);
            $stats = $result->get_statistic();

            $items = [];
            $editable = true;

            foreach ($stats as $stat) {
                $items[] = $stat->to_array();
                if (!$stat->is_update_able()) {
                    $editable = false;
                }
            }

            $this->resource->extra = [
                'timeexpired' => $this->survey->timeexpired,
                'questions' => $items,
                'editable' => $editable
            ];

            // Update cache should not update the time stamp.
            $this->resource->update_timestamps(false);
            $this->resource->save();
        }

        $this->resource->refresh();
        $this->survey->refresh();

        // Reset to empty array, so that on the next fetch it can actually reload from DB.
        $this->surveyquestions = [];
    }

    /**
     * @return string
     */
    public function get_url(): string {
        return link_builder::to(survey::get_resource_type(), ['id' => $this->get_id()])
            ->out();
    }

    /**
     * @param int|null $actor
     * @param string|null $source
     * @param string|null $source_url
     * @return void
     */
    public function redirect_vote_page(?int $actor = null, ?string $source = null, ?string $source_url = null): void {
        global $USER;
        if (null == $actor) {
            $actor = $USER->id;
        }

        $url = \totara_engage\link\builder::to('engage_survey')
            ->set_attributes(['id' => $this->get_id(), 'source' => $source, 'page' => 'vote'])
            ->url();

        if ($source_url) {
            $url->param('source_url', $source_url);
        }

        if ($this->get_userid() !== $actor) {
            redirect($url);
        } else {
            foreach ($this->get_question_stats() as $question) {
                if ($question->get_votes() !== 0) {
                    redirect($url);
                }
            }
        }
    }

    /**
     * @param int|null $actor
     * @param string|null $source
     * @param string|null $source_url
     * @return void
     */
    public function redirect_edit_page(?int $actor = null, ?string $source = null, ?string $source_url = null): void {
        global $USER;
        if (null == $actor) {
            $actor = $USER->id;
        }

        $url = \totara_engage\link\builder::to('engage_survey')
            ->set_attributes(['id' => $this->get_id(), 'source' => $source, 'page' => 'edit'])
            ->url();

        if ($source_url) {
            $url->param('source_url', $source_url);
        }

        if ($this->get_userid() === $actor) {
            foreach ($this->get_question_stats() as $question) {
                if ($question->get_votes() === 0) {
                    redirect($url);
                }
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function can_unshare(int $sharer_id, ?bool $is_container = false): bool {
        global $USER;

        // Check if current user is sharer.
        if ($USER->id == $sharer_id) {
            return true;
        }

        // Check if current user is the owner of the resource.
        if ($USER->id == $this->get_userid()) {
            return true;
        }

        // Check if the current user has capability to unshare this resource.
        return has_capability('engage/survey:unshare', $this->get_context(), $USER->id);
    }
}