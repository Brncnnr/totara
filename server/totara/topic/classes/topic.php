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
 * @package totara_topic
 */
namespace totara_topic;

use core_tag\entity\tag_area;
use core_tag\entity\tag_instance;
use core_tag\repository\tag_area_repository;
use core_tag\repository\tag_instance_repository;
use totara_topic\event\topic_delete;
use totara_topic\exception\topic_exception;
use totara_topic\exception\topic_not_found;
use core_tag\entity\tag_collection;
use totara_topic\local\helper;
use totara_topic\task\delete_notify_task;
use core\task\manager;

/**
 * Topic model class which contains the tag, collection
 */
final class topic {
    /**
     * @var \core_tag_tag
     */
    private $tag;

    /**
     * @var tag_collection
     */
    private $collection;

    /**
     * Preventing this class from constructing outside of factory class.
     * topic constructor.
     */
    private function __construct() {
        $this->tag = null;
        $this->collection = null;
    }

    /**
     * @param int $id
     * @return topic
     */
    public static function from_id(int $id): topic {
        global $CFG;

        $tag = \core_tag_tag::get($id, '*');
        if (!$tag) {
            throw new topic_not_found($id);
        }

        if ($CFG->topic_collection_id != $tag->tagcollid) {
            debugging("The tag was found, however it is not belong to topic collection");
            throw new topic_not_found($id);
        }

        $topic = new static();
        $topic->tag = $tag;

        return $topic;
    }

    /**
     * @param \core_tag_tag $tag
     * @return topic
     */
    public static function from_tag(\core_tag_tag $tag): topic {
        global $CFG;

        if ($CFG->topic_collection_id != $tag->tagcollid) {
            throw new topic_not_found($tag->id);
        }

        $topic = new static();
        $topic->tag = $tag;

        return $topic;
    }

    /**
     * @param string      $value
     * @param int|null    $userid               The user's id of whoever creating this topic.
     * @param string|null $description
     * @param int|null    $descriptionformat
     *
     * @return topic
     */
    public static function create(string $value, ?int $userid = null,
                                  ?string $description = null, ?int $descriptionformat = null): topic {
        global $CFG, $USER;

        if (null == $userid) {
            // Including zero check.
            $userid = $USER->id;
        }

        $tagcollid = $CFG->topic_collection_id;
        $existing = \core_tag_tag::get_by_name($tagcollid, $value);

        if (false !== $existing) {
            throw new topic_exception("alreadyexist");
        } else if (!static::can_create($userid)) {
            throw new topic_exception("nocaptoadd");
        }

        $tags = \core_tag_tag::create_if_missing($tagcollid, [$value], true);
        $tag = reset($tags);

        if (null !== $description) {
            $record = new \stdClass();
            $record->description = $description;

            if (null === $descriptionformat) {
                $descriptionformat = FORMAT_MOODLE;
            }

            $record->descriptionformat = $descriptionformat;
            $tag->update($record);
        }

        $topic = new static();
        $topic->tag = $tag;

        return $topic;
    }

    /**
     * Create an array of new topics.
     *
     * @param array $topics Passed in by caller, e.g. form or other source.
     * @return array $duplicates Array of topics duplicated either in input array, or topics already in system.
     * @throws \coding_exception
     * @throws topic_exception
     */
    public static function create_bulk(array $topics = []): array {
        // Is there anything to process?
        $duplicates = [];
        if (empty($topics)) {
            return $duplicates;
        }

        // Clean submitted parameters and strip out blanks.
        $values= [];
        foreach ($topics as $topic) {
            $topic = clean_param($topic, PARAM_RAW_TRIMMED);
            if ($topic !== '') {
                $values[] = $topic;
            }
        }

        // Check for duplicates in user submitted topics.
        $duplicates = helper::get_duplicated($values);
        if (empty($duplicates)) {
            // Check for duplicates between user submitted topics and topics already in the system.
            $duplicates = helper::get_duplicated_against_system($values);
            if (empty($duplicates)) {
                // No duplications, go ahead and create these topics.
                foreach ($values as $value) {
                    self::create($value);
                }
            }
        }

        return $duplicates;
    }

    /**
     * @param int $userid
     * @return bool
     */
    public static function can_create(int $userid): bool {
        // Currently, there is no functionality for tenant specific.
        // Therefore, we are using the \context_system instead.
        $context = \context_system::instance();

        return has_capability('totara/topic:add', $context, $userid);
    }

    /**
     * @param int $userid
     * @return bool
     */
    public function can_update(int $userid): bool {
        // Currently, there is no functionality for tenant specific.
        // Therefore, we are using the \context_system instead.
        $context = \context_system::instance();

        return has_capability('totara/topic:update', $context, $userid);
    }

    /**
     * @param string      $value
     * @param int|null    $userid               The user's id of whoever is about to update the topic
     * @param string|null $description
     * @param int|null    $descriptionformat
     *
     * @return void
     */
    public function update(string $value, ?int $userid = null,
                           ?string $description = null, ?int $descriptionformat = null): void {

        global $CFG, $USER;

        if (null == $userid) {
            $userid = $USER->id;
        }

        if (!$this->can_update($userid)) {
            throw new topic_exception("nocaptoupdate");
        }

        $existing = \core_tag_tag::get_by_name($CFG->topic_collection_id, $value);
        if (false !== $existing && $existing->id != $this->tag->id) {
            throw new topic_exception("alreadyexist");
        }

        $record = new \stdClass();
        $record->rawname = $value;

        if (null !== $description) {
            $record->description = $description;
        }

        if (null !== $descriptionformat) {
            $record->descriptionformat = $descriptionformat;
        }

        $this->tag->update($record);
    }

    /**
     * @param int $userid
     *
     * @return bool
     */
    public function can_delete(int $userid): bool {
        // Currently, there is no functionality for tenant specific.
        // Therefore, we are using the \context_system instead.
        $context = \context_system::instance();

        return has_capability('totara/topic:delete', $context, $userid);
    }

    /**
     * @param int|null $userid  The actor id.
     * @return void
     */
    public function delete(int $userid = null): void {
        global $USER;

        if (null == $userid) {
            $userid = $USER->id;
        }

        if (!$this->can_delete($userid)) {
            throw new topic_exception('nocaptodelete');
        }

        $id = $this->tag->id;

        // Trigger an event out, before the tag get deleted.
        $event = topic_delete::from_topic($this);
        $event->trigger();

        // Start queing the adhoc task to be run for sending notification email. But before so,
        // we are building a components metadata.
        $components = [];

        /** @var tag_instance_repository $repo */
        $repo = tag_instance::repository();
        $instances = $repo->get_instances_of_tag($id);

        foreach ($instances as $instance) {
            $component = $instance->component;

            if (!isset($components[$component])) {
                $components[$component] = [];
            }

            $itemtype = $instance->itemtype;
            $components[$component][$itemtype][] = $instance->itemid;
        }

        $task = new delete_notify_task();
        $task->set_component('totara_topic');

        $task->set_custom_data(
            [
                'actor' => $userid,
                'topicvalue' => $this->tag->rawname,
                'components' => $components
            ]
        );

        manager::queue_adhoc_task($task);

        // Start doing the actual deletion.
        \core_tag_tag::delete_tags([$id]);
    }

    /**
     * Checking whether the topic is being added into the specific tag_area or not.
     *
     * @param string $component
     * @param string $itemtype
     *
     * @return bool
     */
    public function can_be_added(string $component, string $itemtype): bool {
        global $CFG;

        if (defined('PHPUNIT_TEST') && PHPUNIT_TEST) {
            if ('totara_topic' === $component && 'topic' === $itemtype) {
                // Only allow 'totara_topic' as a component and 'topic' as itemtype when doing the test.
                return true;
            }
        }

        if (!\core_tag_area::is_enabled($component, $itemtype)) {
            return false;
        }

        /** @var tag_area_repository $repo */
        $repo = tag_area::repository();
        $area = $repo->find_for_component($component, $itemtype);

        if (null == $area || !$area->enabled) {
            return false;
        } else if ($CFG->topic_collection_id != $area->tagcollid) {
            debugging("The tag collection id of tag area is not a topic collection id.", DEBUG_DEVELOPER);
            return false;
        }

        return true;
    }

    /**
     * @return tag_collection
     */
    public function get_tag_collection(): tag_collection {
        if (null === $this->collection) {
            $collectionid = $this->tag->tagcollid;
            $this->collection = new tag_collection($collectionid);
        }

        return $this->collection;
    }

    /**
     * @return int
     */
    public function get_id(): int {
        return $this->tag->id;
    }

    /**
     * @return string
     */
    public function get_raw_name(): string {
        return $this->tag->rawname;
    }

    /**
     * @param bool $as_html if true will return htmlspecialchars encoded string.
     *
     * @return string
     */
    public function get_display_name($as_html = true): string {
        return $this->tag->get_display_name($as_html);
    }
}