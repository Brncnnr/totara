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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_perform
 * @category test
 */

use core\orm\collection;
use mod_perform\constants;
use mod_perform\dates\date_offset;
use mod_perform\entity\activity\participant_instance;
use mod_perform\entity\activity\subject_instance;
use mod_perform\entity\activity\track_user_assignment;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\details\subject_instance_notification;
use mod_perform\models\activity\notification;
use mod_perform\models\activity\notification_recipient;
use mod_perform\models\activity\section;
use mod_perform\models\activity\section_relationship;
use mod_perform\models\activity\track;
use mod_perform\notification\broker;
use mod_perform\notification\clock;
use mod_perform\notification\condition;
use mod_perform\notification\factory;
use mod_perform\notification\loader;
use mod_perform\notification\recipient;
use mod_perform\notification\trigger;
use mod_perform\notification\triggerable;
use mod_perform\task\service\participant_instance_creation;
use mod_perform\task\service\subject_instance_creation;
use mod_perform\task\service\subject_instance_dto;
use totara_core\entity\relationship as relationship_entity;
use totara_core\relationship\relationship;

abstract class mod_perform_notification_testcase extends advanced_testcase {
    /** @var \mod_perform\testing\generator */
    protected $perfgen;

    /** @var \core_phpunit\message_sink */
    private $sink;

    /** @var boolean */
    private $loader_mocked = false;

    /** @var array */
    private $factory_values = [];

    public function setUp(): void {
        $this->setAdminUser();
        $this->perfgen = \mod_perform\testing\generator::instance();

        factory::reset_loader();
        factory::reset_clock();
        factory::reset_sink();
    }

    public function tearDown(): void {
        $this->perfgen = null;
        factory::reset_loader();
        factory::reset_clock();
        factory::reset_sink();

        $this->factory_values = [];
    }

    /**
     * Mock factory::loader.
     *
     * @param array|null $notifications notifications definition or set null to use the default mocked definition
     */
    protected function mock_loader(?array $notifications): void {
        if ($notifications === null) {
            $notifications = [
                'mock_one' => [
                    'class' => mod_perform_mock_broker_one::class,
                    'name' => 'mock #1',
                    'trigger_type' => trigger::TYPE_ONCE,
                    'recipients' => recipient::ALL,
                ],
                'mock_two' => [
                    'class' => mod_perform_mock_broker_two::class,
                    'name' => 'MOCK #2',
                    'trigger_type' => trigger::TYPE_BEFORE,
                    'trigger_label' => ['clear'],
                    'condition' => mod_perform_mock_condition_fail::class,
                    'recipients' => recipient::STANDARD | recipient::MANUAL,
                ],
                'mock_three' => [
                    'class' => mod_perform_mock_broker_three::class,
                    'name' => 'm0c1< #3',
                    'trigger_type' => trigger::TYPE_AFTER,
                    'trigger_label' => ['learner'],
                    'condition' => mod_perform_mock_condition_pass::class,
                    'recipients' => recipient::STANDARD,
                ],
            ];
        }
        $loader = loader::create($notifications);
        $rp = new ReflectionProperty(factory::class, 'loader');
        $rp->setAccessible(true);
        $rp->setValue(null, $loader);
        $this->loader_mocked = true;
        $this->add_mock_lang_strings();
    }

    /**
     * Nullify factory::loader.
     */
    protected function reset_loader(): void {
        factory::reset_loader();
        $this->loader_mocked = false;
    }

    /**
     * Add mocked lang strings to shut up debugging messages.
     * @param string[]|null $relationships
     */
    protected function add_mock_lang_strings(?array $relationships = null): void {
        if (!$this->loader_mocked) {
            return;
        }

        $relationships = $relationships ?? $this->get_default_relationships_for_testing();
        foreach (factory::create_loader()->get_class_keys() as $class_key) {
            foreach ($relationships as $relationship) {
                foreach (['subject', 'body'] as $what) {
                    $id = "template_{$class_key}_{$relationship}_{$what}";
                    $this->overrideLangString($id, 'mod_perform', 'mock:'.$id, true);
                }
            }
        }
    }

    /**
     * Override notification template strings for testing.
     *
     * @param string $class_key
     */
    protected function override_template_strings(string $class_key): void {
        $idnumbers = [
            constants::RELATIONSHIP_SUBJECT,
            constants::RELATIONSHIP_MANAGER,
            constants::RELATIONSHIP_APPRAISER,
            constants::RELATIONSHIP_MANAGERS_MANAGER,
            constants::RELATIONSHIP_PEER,
            constants::RELATIONSHIP_MENTOR,
            constants::RELATIONSHIP_REVIEWER,
            constants::RELATIONSHIP_EXTERNAL,
        ];
        foreach ($idnumbers as $idnumber) {
            foreach (['subject', 'body'] as $what) {
                $id = "template_{$class_key}_{$idnumber}_{$what}";
                // FIXME: do not pass the third parameter
                $this->overrideLangString($id, 'mod_perform', "{$what} of {$class_key} as {$idnumber} : " . '{$a->recipient_fullname} to {$a->subject_fullname}', true);
            }
        }
    }

    /**
     * Create an activity for testing.
     *
     * @param array $data
     * @return activity
     */
    protected function create_activity(array $data = []): activity {
        return $this->perfgen->create_activity_in_container($data);
    }

    /**
     * Create a section for testing.
     *
     * @param activity $activity
     * @param array $data
     * @return section
     */
    protected function create_section(activity $activity, array $data = []): section {
        return $this->perfgen->create_section($activity, $data);
    }

    /**
     * Return subject, appraiser and manager.
     *
     * @return string[]
     */
    protected function get_default_relationships_for_testing(): array {
        return [
            constants::RELATIONSHIP_SUBJECT,
            constants::RELATIONSHIP_APPRAISER,
            constants::RELATIONSHIP_MANAGER,
            constants::RELATIONSHIP_MANAGERS_MANAGER,
        ];
    }

    /**
     * Create section relationships for testing.
     *
     * @param section $section
     * @param string[]|null $relationships relationship id numbers or null to use get_default_relationships_for_testing()
     * @return relationship[] as idnumber => relationship
     */
    protected function create_section_relationships(section $section, array $relationships = null): array {
        if ($relationships === null) {
            $relationships = $this->get_default_relationships_for_testing();
        }
        $results = [];
        foreach ($relationships as $idnumber) {
            $rel_id = $this->perfgen->get_core_relationship($idnumber)->id;
            $results[$idnumber] = section_relationship::create($section->get_id(), $rel_id, true)->core_relationship;
        }
        return $results;
    }

    /**
     * Creates one track with one cohort assignment for the given activity.
     *
     * @param activity $activity parent activity.
     * @param integer[] $userids
     * @param date_offset|null $due_offset
     * @return track $track the generated track.
     */
    public function create_single_activity_track_and_assignment(activity $activity, array $userids, ?date_offset $due_offset = null): track {
        $track = track::create($activity, "test track");
        if ($due_offset) {
            $track->set_due_date_relative($due_offset);
            $track->update();
        }
        return $this->perfgen->create_track_assignments_with_existing_groups($track, [], [], [], $userids);
    }

    /**
     * Create participant instances on the specific track.
     * This will not trigger any notifications.
     *
     * @param track $track
     * @return collection<participant_instance> array of participant instance entities
     */
    public function create_participant_instances_on_track(track $track): collection {
        // Eat all hooks.
        $sink = $this->redirectHooks();
        (new subject_instance_creation())->generate_instances();
        $sink->clear();

        $subject_instances = subject_instance::repository()
            ->join([track_user_assignment::TABLE, 'tua'], 'track_user_assignment_id', 'id')
            ->where('tua.track_id', $track->id)
            ->get()
            ->map(function (subject_instance $subject_instance) {
                return subject_instance_dto::create_from_entity($subject_instance);
            });

        (new participant_instance_creation())->generate_instances($subject_instances);
        $sink->close();

        return participant_instance::repository()
            ->where_in('subject_instance_id', $subject_instances->pluck('id'))
            ->get();
    }

    /**
     * Get the relationship instance by the idnumber.
     *
     * @param string $idnumber one of constants
     * @return relationship
     */
    protected function get_core_relationship(string $idnumber): relationship {
        return $this->perfgen->get_core_relationship($idnumber);
    }

    /**
     * Get all available relationships keyed by the idnumber.
     *
     * @return array<string, relationship>
     */
    protected function get_all_relationships(): array {
        return relationship_entity::repository()
            ->order_by('sort_order')
            ->get()
            ->map_to(relationship::class)
            ->all();
    }

    /**
     * Activate/deactivate the recipients.
     *
     * @param notification $notification
     * @param boolean[] $relationships array of [idnumber => active]
     */
    protected function toggle_recipients(notification $notification, array $relationships): void {
        $recipients = $notification->get_recipients();
        foreach ($relationships as $idnumber => $active) {
            $relationship = $this->perfgen->get_core_relationship($idnumber);
            $rel_id = $relationship->id;
            $recipient = $recipients->find('core_relationship_id', $rel_id);
            /** @var notification_recipient $recipient */
            if ($recipient) {
                $recipient->toggle($active);
            } else {
                notification_recipient::create($notification, $relationship, $active);
            }
        }
    }

    /**
     * Start message redirection.
     */
    protected function redirect_messages() {
        if ($this->sink) {
            throw new coding_exception('do not call redirect_messages again');
        }
        $this->sink = $this->redirectMessages();
    }

    /**
     * Finish message redirection and return messages received.
     *
     * @return stdClass[]
     */
    protected function get_messages(): array {
        if (!$this->sink) {
            throw new coding_exception('get_messages is called prior to redirect_messages');
        }
        $this->sink->close();
        $messages = $this->sink->get_messages();
        usort($messages, function ($x, $y) {
            return ((int)$x->useridto <=> (int)$y->useridto) ?: strcmp($x->subject, $y->subject);
        });
        $this->sink = null;
        return $messages;
    }
}

class mod_perform_mock_broker implements broker, triggerable {
    /** @var array<string, boolean> */
    private static $triggerable = [];

    public function set_triggerable(bool $value): void {
        $class = get_class($this);
        self::$triggerable[$class] = $value;
    }

    public function get_default_triggers(): array {
        return [];
    }

    public function is_triggerable_now(condition $condition, subject_instance_notification $record): bool {
        $class = get_class($this);
        return self::$triggerable[$class] ?? false;
    }
}

class mod_perform_mock_broker_one extends mod_perform_mock_broker {
    // nothing to extend
}

class mod_perform_mock_broker_two extends mod_perform_mock_broker {
    // nothing to extend
}

class mod_perform_mock_broker_three extends mod_perform_mock_broker {
    // nothing to extend
}

class mod_perform_mock_broker_four extends mod_perform_mock_broker {
    // nothing to extend
}

class mod_perform_mock_clock extends clock {
    /** @var integer */
    private $time;

    public function __construct(int $time) {
        $this->time = $time;
    }

    public function get_time(): int {
        return $this->time;
    }
}

class mod_perform_mock_condition extends condition {
    /** @var array<string, boolean> */
    protected static $should_pass = [];

    public static function set_condition($class, bool $pass): void {
        if (is_object($class)) {
            $class = get_class($class);
        }
        static::$should_pass[$class] = $pass;
    }

    public function pass(int $base_time): bool {
        return static::$should_pass[get_class($this)] ?? false;
    }
}

class mod_perform_mock_condition_fail extends mod_perform_mock_condition {
    public function __construct() {
        parent::set_condition($this, false);
    }
}

class mod_perform_mock_condition_pass extends mod_perform_mock_condition {
    public function __construct() {
        parent::set_condition($this, true);
    }
}
