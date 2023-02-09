<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * @author  Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\webapi\middleware;

use Closure;
use container_course\module\course_module;
use core\orm\query\exceptions\record_not_found_exception;
use invalid_parameter_exception;
use core\webapi\middleware;
use core\webapi\resolver\payload;
use core\webapi\resolver\result;
use mod_perform\entity\activity\subject_instance;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\element;
use mod_perform\models\activity\notification;
use mod_perform\models\activity\helpers\access_checks;
use mod_perform\models\activity\participant_instance;
use mod_perform\models\activity\section;
use mod_perform\models\activity\section_element as section_element_model;
use mod_perform\models\activity\track;
use mod_perform\models\response\participant_section;
use moodle_exception;

/**
 * Interceptor that uses activity related data in the incoming graphql payload
 * for authentication.
 */
class require_activity implements middleware {
    /**
     * @var Closure retriever payload->activity method that retrieves an activity
     *      using data in the specified payload.
     */
    private $retriever = null;

    /**
     * @var bool whether to populate the incoming graphql execution context's
     *      relevant context field with the activity context.
     */
    private $set_relevant_context = false;

    /**
     * Creates an object instance that validates based on an activity id in the
     * incoming payload.
     *
     * @param string $payload_keys the keys in the payload to use to extract the
     *        id from the payload. For example if the keys are "a.b.c", then the
     *        id is retrieved as $payload['a']['b']['c'].
     * @param bool $set_relevant_context if true, sets the graphql execution
     *        context's relevant context field with the activity context.
     *
     * @return require_activity the object instance.
     */
    public static function by_activity_id(
        string $payload_keys,
        bool $set_relevant_context = false
    ): require_activity {
        $retriever = function (payload $payload) use ($payload_keys): activity {
            $id = self::get_id($payload_keys, $payload);
            if (!$id) {
                throw new invalid_parameter_exception('invalid activity id');
            }

            return activity::load_by_id($id);
        };

        return new require_activity($retriever, $set_relevant_context);
    }

    /**
     * Creates an object instance that validates based on an activity notification id
     * in the incoming payload.
     *
     * @param string $payload_keys the keys in the payload to use to extract the
     *        id from the payload. For example if the keys are "a.b.c", then the
     *        id is retrieved as $payload['a']['b']['c'].
     * @param bool $set_relevant_context if true, sets the graphql execution
     *        context's relevant context field with the activity context.
     *
     * @return require_activity the object instance.
     */
    public static function by_notification_id(
        string $payload_keys,
        bool $set_relevant_context = false
    ): require_activity {
        $retriever = function (payload $payload) use ($payload_keys): activity {
            $id = self::get_id($payload_keys, $payload);
            if (!$id) {
                throw new invalid_parameter_exception('invalid notification id');
            }

            return notification::load_by_id($id)->get_activity();
        };

        return new require_activity($retriever, $set_relevant_context);
    }

    /**
     * Creates an object instance that validates based on a section id in the
     * incoming payload.
     *
     * @param string $payload_keys the keys in the payload to use to extract the
     *        id from the payload. For example if the keys are "a.b.c", then the
     *        id is retrieved as $payload['a']['b']['c'].
     * @param bool $set_relevant_context if true, sets the graphql execution
     *        context's relevant context field with the activity context.
     *
     * @return require_activity the object instance.
     */
    public static function by_section_id(
        string $payload_keys,
        bool $set_relevant_context = false
    ): require_activity {
        $retriever = function (payload $payload) use ($payload_keys): activity {
            $id = self::get_id($payload_keys, $payload);
            if (!$id) {
                throw new invalid_parameter_exception('invalid section id');
            }

            return section::load_by_id($id)->activity;
        };

        return new require_activity($retriever, $set_relevant_context);
    }

    /**
     * Creates an object instance that validates based on a section element id in the
     * incoming payload.
     *
     * @param string $payload_keys the keys in the payload to use to extract the
     *        id from the payload. For example if the keys are "a.b.c", then the
     *        id is retrieved as $payload['a']['b']['c'].
     * @param bool $set_relevant_context if true, sets the graphql execution
     *        context's relevant context field with the activity context.
     *
     * @return require_activity the object instance.
     */
    public static function by_section_element_id(
        string $payload_keys,
        bool $set_relevant_context = false
    ): require_activity {
        $retriever = function (payload $payload) use ($payload_keys): activity {
            $id = self::get_id($payload_keys, $payload);
            if (!$id) {
                throw new invalid_parameter_exception('invalid section element id');
            }

            return section_element_model::load_by_id($id)->section->activity;
        };

        return new require_activity($retriever, $set_relevant_context);
    }

    /**
     * Creates an object instance that validates based on an element id in the
     * incoming payload.
     *
     * @param string $payload_keys the keys in the payload to use to extract the
     *        id from the payload. For example if the keys are "a.b.c", then the
     *        id is retrieved as $payload['a']['b']['c'].
     * @param bool $set_relevant_context if true, sets the graphql execution
     *        context's relevant context field with the activity context.
     *
     * @return require_activity the object instance.
     */
    public static function by_element_id(
        string $payload_keys,
        bool $set_relevant_context = false
    ): require_activity {
        $retriever = function (payload $payload) use ($payload_keys): activity {
            $id = self::get_id($payload_keys, $payload);

            if (!$id) {
                throw new invalid_parameter_exception('invalid element id');
            }
            $course_module_id = element::load_by_id($id)->get_context()->instanceid;
            $activity_id = course_module::from_id($course_module_id)->get_instance();

            return activity::load_by_id($activity_id);
        };

        return new require_activity($retriever, $set_relevant_context);
    }

    /**
     * Creates an object instance that validates based on an activity track id
     * in the incoming payload.
     *
     * @param string $payload_keys the keys in the payload to use to extract the
     *        id from the payload. For example if the keys are "a.b.c", then the
     *        id is retrieved as $payload['a']['b']['c'].
     * @param bool $set_relevant_context if true, sets the graphql execution
     *        context's relevant context field with the activity context.
     *
     * @return require_activity the object instance.
     */
    public static function by_track_id(
        string $payload_keys,
        bool $set_relevant_context = false
    ): require_activity {
        $retriever = function (payload $payload) use ($payload_keys): activity {
            $id = self::get_id($payload_keys, $payload);
            if (!$id) {
                throw new invalid_parameter_exception('invalid track id');
            }

            return track::load_by_id($id)->activity;
        };

        return new require_activity($retriever, $set_relevant_context);
    }

    /**
     * Factory method to get an instance by a given subject_instance_id
     *
     * @param string $payload_keys
     * @param bool $set_relevant_context
     * @return require_activity
     */
    public static function by_subject_instance_id(
        string $payload_keys,
        bool $set_relevant_context = false
    ): require_activity {
        $retriever = function (payload $payload) use ($payload_keys): activity {
            $subject_instance_id = self::get_payload_value($payload_keys, $payload);
            if (!$subject_instance_id) {
                throw new invalid_parameter_exception('invalid subject instance id');
            }

            /** @var subject_instance $subject_instance */
            $subject_instance = subject_instance::repository()->find_or_fail($subject_instance_id);

            $activity = $subject_instance->activity();

            return new activity($activity);
        };

        return new require_activity($retriever, $set_relevant_context);
    }

    /**
     * Factory method to get an instance by an array of subject_instance_ids
     *
     * @param string $payload_keys
     * @param bool $set_relevant_context
     * @return require_activity
     */
    public static function by_subject_instance_ids(
        string $payload_keys,
        bool $set_relevant_context = false
    ): require_activity {
        $retriever = function (payload $payload) use ($payload_keys): activity {
            $subject_instance_ids = self::get_payload_value($payload_keys, $payload);

            // Wrap single values in an array.
            $subject_instance_ids = (array) $subject_instance_ids;
            if (empty($subject_instance_ids)) {
                throw new invalid_parameter_exception('invalid parameter, require at least one subject instance id');
            }

            /** @var subject_instance[] $subject_instances */
            $subject_instances = subject_instance::repository()
                ->where('id', $subject_instance_ids)
                ->with('track.activity')
                ->get()
                ->all();

            $activity = $subject_instances[0]->activity();

            foreach ($subject_instances as $subject_instance) {
                if ($subject_instance->activity()->id !== $activity->id) {
                    throw new invalid_parameter_exception('All subject instances must belong to the same activity');
                }
            }

            return new activity($activity);
        };

        return new require_activity($retriever, $set_relevant_context);
    }

    /**
     * Factory method to get an instance by a given participant_section_id
     *
     * @param string $payload_keys
     * @param bool $set_relevant_context
     * @return require_activity
     */
    public static function by_participant_section_id(
        string $payload_keys,
        bool $set_relevant_context = false
    ): require_activity {
        $retriever = function (payload $payload) use ($payload_keys): activity {
            $participant_section_id = self::get_payload_value($payload_keys, $payload);
            if (!$participant_section_id) {
                throw new invalid_parameter_exception('invalid participant section id');
            }

            $participant_section = participant_section::load_by_id($participant_section_id);

            return $participant_section->section->get_activity();
        };

        return new require_activity($retriever, $set_relevant_context);
    }

    /**
     * Factory method to get an instance by a given participant_instance_id
     *
     * @param string $payload_keys
     * @param bool $set_relevant_context
     * @return require_activity
     */
    public static function by_participant_instance_id(
        string $payload_keys,
        bool $set_relevant_context = false
    ): require_activity {
        $retriever = function (payload $payload) use ($payload_keys): activity {
            $participant_instance_id = self::get_payload_value($payload_keys, $payload);
            if (!$participant_instance_id) {
                throw new invalid_parameter_exception('invalid participant instance id');
            }

            $participant_instance = participant_instance::load_by_id($participant_instance_id);

            return $participant_instance->get_subject_instance()->get_activity();
        };

        return new require_activity($retriever, $set_relevant_context);
    }

    /**
     * Returns a value extracted from the incoming payload.
     *
     * @param string $payload_keys the keys in the payload to use to extract the
     *        value from the payload. For example if the keys are "a.b.c", then the
     *        value is retrieved as $payload['a']['b']['c'].
     * @param payload $payload the incoming payload to parse.
     *
     * @return mixed the extracted value.
     */
    private static function get_payload_value(string $payload_keys, payload $payload) {
        $keys = explode('.', $payload_keys);

        $initial = array_shift($keys);
        $result = $payload->get_variable($initial);

        if ($result) {
            foreach ($keys as $key) {
                $result = $result[$key] ?? null;
            }
        }

        return $result;
    }

    /**
     * Returns an id extracted from the incoming payload.
     * Wraps get_payload_value() and casts result to int.
     *
     * @param string $payload_keys
     * @param payload $payload
     *
     * @return int the extracted ID.
     */
    private static function get_id(string $payload_keys, payload $payload): int {
        return (int)self::get_payload_value($payload_keys, $payload);
    }

    /**
     * Default constructor.
     *
     * @param Closure $retriever payload->activity method that retrieves an activity
     *        using data in the specified payload.
     * @param bool $set_relevant_context if true, sets the graphql execution
     *        context's relevant context field with the activity context.
     */
    private function __construct(Closure $retriever, bool $set_relevant_context) {
        $this->retriever = $retriever;
        $this->set_relevant_context = $set_relevant_context;
    }

    /**
     * @inheritDoc
     */
    public function handle(payload $payload, Closure $next): result {
        global $PAGE;

        $retrieve = $this->retriever;
        try {
            $activity = $retrieve($payload);
        } catch (record_not_found_exception $exception) {
            throw new moodle_exception('invalid_activity', 'mod_perform');
        }

        \require_login(null, false, null, false, true);

        $helper = access_checks::for_activity_model($activity);
        $helper->check();

        $PAGE->set_cm($helper->get_cm(), $helper->get_course());

        if ($this->set_relevant_context) {
            $context = $activity->get_context();
            $payload->get_execution_context()->set_relevant_context($context);
        }

        // Store the loaded activity in the payload for later use
        $payload->set_variable('activity', $activity);

        return $next($payload);
    }
}
