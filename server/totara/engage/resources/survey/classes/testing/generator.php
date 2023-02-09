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

namespace engage_survey\testing;

use totara_engage\access\access;
use engage_survey\totara_engage\resource\survey;
use totara_engage\answer\answer_type;
use core_user\totara_engage\share\recipient\user as user_recipient;
use totara_engage\share\manager as share_manager;
use totara_engage\share\share as share_model;
use totara_engage\share\shareable;
use totara_topic\provider\topic_provider;

final class generator extends \core\testing\component_generator {
    /**
     * @var array
     */
    private static $questions;

    /**
     * @var array
     */
    private static $options;

    /**
     * By default the access is private for survey.
     *
     * @param string|null $question
     * @param array       $options
     * @param int         $answertype
     * @param array       $parameters
     * @return survey
     */
    public function create_survey(?string $question = null, array $options = [],
                                  int $answertype = answer_type::MULTI_CHOICE,
                                  $parameters = []): survey {

        if (null === $question) {
            $question = $this->generate_question();

            if (\core_text::strlen($question) > 75) {
                $question = \core_text::substr($question, 0, 75);
            }
        }

        if (empty($options)) {
            $options = $this->generate_options();
        }

        $data = $parameters;
        $data['questions'] = [
            [
                'value' => $question,
                'answertype' => $answertype,
                'options' => $options
            ]
        ];

        if (isset($parameters['userid'])) {
            $context = \context_user::instance($parameters['userid']);
            $data['contextid'] = $context->id;
        }

        /** @var survey $survey */
        $survey = survey::create($data, $parameters['userid'] ?? null);
        return $survey;
    }

    /**
     * @param string|null   $question
     * @param array         $options
     * @param int           $answer_type
     *
     * @return survey
     */
    public function create_restricted_survey(?string $question = null, array $options = [],
                                             int $answer_type = answer_type::MULTI_CHOICE): survey {
        $parameters = ['access' => access::RESTRICTED];
        return $this->create_survey($question, $options, $answer_type, $parameters);
    }

    /**
     * @param string|null $question
     * @param array $options
     * @param int $answer_type
     *
     * @return survey
     */
    public function create_public_survey(?string $question = null, array $options = [],
                                         int $answer_type = answer_type::MULTI_CHOICE): survey {
        $parameters = ['access' => access::PUBLIC];
        return $this->create_survey($question, $options, $answer_type, $parameters);
    }

    /**
     * @param int $numberofoptions
     * @return string[]
     */
    private function generate_options(int $numberofoptions = 2): array {
        global $CFG;

        if (!isset(static::$options)) {
            $path = "{$CFG->dirroot}/totara/engage/resources/survey/tests/fixtures/options.php";
            static::$options = require($path);
        }

        $rtn = [];
        for ($i = 0; $i < $numberofoptions; $i++) {
            $index = rand(0, (count(static::$options) - 1));
            $rtn[] = static::$options[$index];
        }

        return $rtn;
    }

    /**
     * @return string
     */
    private function generate_question(): string {
        global $CFG;

        if (!isset(static::$questions)) {
            $path = "{$CFG->dirroot}/totara/engage/resources/survey/tests/fixtures/questions.php";
            static::$questions = require($path);
        }

        $index = rand(0, (count(static::$questions) - 1));
        return static::$questions[$index];
    }

    /**
     * @param int $count
     * @return array
     */
    public function create_users(int $count): array {
        $users = [];
        for ($x = 1; $x <= $count; ++$x) {
            $user['firstname'] = "Some{$x}";
            $user['lastname'] = "Any{$x}";
            $users[] = $this->datagenerator->create_user($user);
        }

        return $users;
    }

    /**
     * @param array $users
     * @return array
     */
    public function create_user_recipients(array $users): array {
        $recipients = [];
        foreach ($users as $user) {
            $recipients[] = new user_recipient($user->id);
        }
        return $recipients;
    }

    /**
     * @param shareable $survey
     * @param array $recipients
     * @return share_model[]
     */
    public function share_survey(shareable $survey, array $recipients): array {
        $context = $survey->get_context();

        // Make the create method public so we can test it.
        $class = new \ReflectionClass(share_manager::class);
        $method = $class->getMethod('create');
        $method->setAccessible(true);

        return $method->invokeArgs(null, [
            $survey->get_id(),
            $survey->get_userid(),
            survey::get_resource_type(),
            $recipients,
            $context->id
        ]);
    }

    /**
     * @param int $permission
     * @param int $userid
     * @param \context $context
     *
     * @return void
     */
    public function set_capabilities(int $permission, int $userid, \context $context): void {
        $roles = get_archetype_roles('user');
        foreach ($roles as $role) {
            // Can share a survey.
            role_assign($role->id, $userid, $context->id);
            assign_capability('engage/survey:share', $permission, $role->id, $context, true);
        }
    }

    /**
     * Callback from behat data generator.
     *
     * @param array $parameters
     * @return survey
     */
    public function create_survey_from_params(array $parameters): survey {
        global $DB;

        if (!isset($parameters['question']) || !isset($parameters['username'])) {
            throw new \coding_exception(
                "Survey question and username are required"
            );
        }

        $user_id = $DB->get_field('user', 'id', ['username' => $parameters['username']]);
        $access = access::get_value($parameters['access']);

        if (access::is_public($access) && empty($parameters['topics'])) {
            throw new \coding_exception("Cannot create a public survey without topics");
        }

        $data = [
            'userid' => $user_id,
            'access' => $access,
            'contextid' => \context_user::instance($user_id)->id,

            // List of topic's id.
            'topics' => []
        ];

        if (!empty($parameters['topics'])) {
            $topics = explode(",", $parameters['topics']);
            $topics = array_map('trim', $topics);

            foreach ($topics as $topic_name) {
                $topic = topic_provider::find_by_name($topic_name);
                if (null === $topic) {
                    debugging("Cannot find topic by name '{$topic_name}'", DEBUG_DEVELOPER);
                    continue;
                }

                $data['topics'][] = $topic->get_id();
            }
        }

        $options = [];
        if (!empty($parameters['options'])) {
            $options = array_map('trim', explode(",", $parameters['options']));
        }

        return $this->create_survey($parameters['question'], $options, answer_type::MULTI_CHOICE, $data);
    }
}