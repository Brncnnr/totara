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
 * @package totara_engage
 */
namespace totara_engage\resource;

use coding_exception;
use totara_engage\access\access;
use totara_engage\access\access_manager;
use totara_engage\exception\resource_exception;
use totara_engage\entity\engage_resource;
use core\orm\query\builder;
use totara_engage\access\accessible;
use totara_engage\local\helper as local_helper;
use totara_engage\resource\input\access_validator;
use totara_engage\resource\input\definition;
use totara_engage\resource\input\topic_validator;
use totara_engage\share\manager as share_manager;
use totara_engage\share\{shareable, shareable_result};
use totara_engage\share\recipient\manager as recipient_manager;
use totara_topic\provider\topic_provider;
use totara_topic\topic_helper;

/**
 * To implement or adding a new type of resource into the totara engage, then this class is where you
 * should be extending from.
 */
abstract class resource_item implements accessible, shareable {
    /**
     * @var engage_resource
     */
    protected $resource;

    /**
     * resource constructor.
     * @param engage_resource $entity
     */
    final protected function __construct(engage_resource $entity) {
        $this->resource = $entity;
    }

    /**
     * @return string
     */
    final public static function get_resource_type(): string {
        return local_helper::get_component_name(static::class);
    }

    /**
     * @param int $resourceid
     * @return resource_item
     */
    public static function from_resource_id(int $resourceid): resource_item {
        $entity = new engage_resource($resourceid);
        return new static($entity);
    }

    /**
     * @param int $instanceid
     * @param string $resourcetype
     * @return resource_item
     */
    public static function from_instance(int $instanceid, string $resourcetype): resource_item {
        global $DB;
        $resource_id = $DB->get_field(
            engage_resource::TABLE,
            'id',
            [
                'instanceid' => $instanceid,
                'resourcetype' => $resourcetype
            ]
        );

        return static::from_resource_id($resource_id);
    }

    /**
     * This function will invoke your own instance creation method, but before that, it will try to create
     * the most common record first.
     *
     * @param array    $data        The extra data - mostly from FORM or GraphQL input, that your resource
     *                              item needs.
     * @param int|null $userid
     * @return resource_item
     */
    final public static function create(array $data, int $userid = null): resource_item {
        global $USER, $DB;

        if (null == $userid) {
            $userid = $USER->id;
        }

        // Start the transaction before anything else.
        $transaction = $DB->start_delegated_transaction();
        $resourcetype = static::get_resource_type();

        if (!static::can_create($userid)) {
            throw resource_exception::create('create', $resourcetype);
        }

        $definitions = static::get_data_definitions();
        $data = helper::sanitize_instance_data($definitions, $data);

        $record = new engage_resource();
        $record->resourcetype = $resourcetype;
        $record->name = $data['name'];
        $record->userid = $userid;

        $context = \context_user::instance($userid);
        $record->contextid = $context->id;

        // By defaullt, all the resource should be private.
        $record->access = access::PRIVATE;
        if (isset($data['access'])) {
            $record->access = $data['access'];
        }

        // We need to save the record first, as to reserve a row for this particular resource.
        $record->save();

        if (!$record->exists()) {
            throw new coding_exception("The resource record is not able to be created in the system");
        }

        $instanceid = static::do_create($data, $record, $userid);

        // Start updating the resource record, with instance id and the extra attribute
        $record->instanceid = $instanceid;
        $record->update_timestamps(false);
        $record->update();

        // Re-fetching the data.
        $resource = static::from_resource_id($record->id);

        // Add topics.
        if (!empty($data['topics'])) {
            $resource->add_topics_by_ids($data['topics']);
        }

        // Share the resource.
        if (!empty($data['shares'])) {
            $recipients = recipient_manager::create_from_array($data['shares']);
            share_manager::share($resource, static::get_resource_type(), $recipients);
        }

        // Commit the transaction before the event start being emitted.
        $transaction->allow_commit();

        // Building cache.
        $resource->refresh(true);

        static::post_create($resource, $data, $userid);
        return $resource;
    }

    /**
     * If the children does not support topics, then this function should be overridden
     * to do nothing.
     *
     * @param int[] $topic_ids
     * @return void
     */
    public function add_topics_by_ids(array $topic_ids): void {
        $component = static::get_resource_type();
        $item_id = $this->get_id();

        foreach ($topic_ids as $topic_id) {
            topic_helper::add_topic_usage(
                $topic_id,
                $component,
                'engage_resource',
                $item_id
            );
        }
    }

    /**
     * If the child does not support topics, then this function should be overridden
     * to do nothing.
     *
     * @param int[] $exclude_topic_ids
     * @return void
     */
    public function remove_topics_by_ids(array $exclude_topic_ids = []): void {
        $item_id = $this->get_id();
        $component = static::get_resource_type();

        $topics = topic_provider::get_for_item($item_id, $component, 'engage_resource');
        foreach ($topics as $topic) {
            $topic_id = $topic->get_id();
            if (!in_array($topic_id, $exclude_topic_ids)) {
                topic_helper::delete_topic_usage($topic, $component, 'engage_resource', $item_id);
            }
        }
    }

    /**
     * Post creation hook, when everything is already done. This should be a place to trigger an event.
     *
     * @param resource_item $item
     * @param array         $data
     * @param int|null      $user_id    The actor who is responsible for creation of this specific resource.
     *
     * @return void
     */
    protected static function post_create(resource_item $item, array $data, ?int $user_id = null): void {
    }

    /**
     * @param int|null $userid
     * @param array $data
     * @return void
     */
    public function update(array $data, int $userid = null): void {
        global $USER, $DB;

        if (null == $userid) {
            $userid = $USER->id;
        }

        // Start the transaction before anything else.
        $transaction = $DB->start_delegated_transaction();
        $type = static::get_resource_type();

        if (!$this->can_update($userid)) {
            throw resource_exception::create('update', $type);
        }

        $oldaccess = $this->resource->access;
        if (!isset($data['access'])) {
            // If access is not set, assign old access to it.
            $data['access'] = $oldaccess;
        }

        if (!access_manager::can_update_access($oldaccess, $data['access'])) {
            throw resource_exception::create('updateaccess', $type);
        }

        $definitions = static::get_data_definitions();
        $data = helper::sanitize_instance_data($definitions, $data, helper::SANITIZE_ON_UPDATE);

        $result = $this->do_update($data, $userid);

        if ($result) {
            if (isset($data['access'])) {
                if ($oldaccess != $data['access']) {
                    $this->resource->access = $data['access'];
                }
            }

            if (isset($data['name'])) {
                $oldname = $this->resource->name;
                if ($oldname != $data['name']) {
                    $this->resource->name = $data['name'];
                }
            }

            // Update time modified, even nothing has been changed.
            $this->resource->update();

            // The transaction needs to be committed before post update, as post update is where the event
            // is being triggered.
            $transaction->allow_commit();

            // Rebuild cache.
            $this->refresh(true);
            $this->post_update($userid);
        } else {
            debugging("Unable to update the resource of component '{$type}'", DEBUG_DEVELOPER);
        }
    }

    /**
     * Post update hook, which it should be a place to trigger any kind of events or updating
     * cache field if neccessary.
     *
     * @param int|null $user_id
     * @return void
     */
    protected function post_update(?int $user_id = null): void {
    }

    /**
     * @param int|null $userid
     * @return void
     */
    public function delete(int $userid = null): void {
        global $USER;

        if (null == $userid) {
            $userid = $USER->id;
        }

        if (!$this->is_exists(true)) {
            throw new coding_exception('Cannot delete a resource instance that is not existing in the system');
        }

        if (!$this->can_delete($userid)) {
            throw resource_exception::create('delete', static::get_resource_type());
        }

        $this->do_delete($userid);
    }

    /**
     * @return bool
     */
    public function is_deleted(): bool {
        return $this->resource->deleted();
    }

    /**
     * @param bool $dbcheck
     * @return bool
     */
    public function is_exists(bool $dbcheck = false): bool {
        if (!$this->resource->exists()) {
            return false;
        }

        if ($dbcheck) {
            $id = $this->resource->id;

            $builder = builder::table(engage_resource::TABLE);
            $builder->where('id', $id);

            return $builder->exists();
        }

        return true;
    }

    /**
     * Extended the logic check to check against the owner user of this resource.
     * Since the resource is no longer available when user is deleted/suspended or any
     * non confirmed user.
     *
     * @return bool
     */
    public function is_available(): bool {
        global $DB;
        $valid_owner_sql = '
            SELECT 1 FROM "ttr_user" WHERE id = :owner_id 
            AND deleted = 0 AND confirmed = 1
        ';

        $owner_id = $this->get_userid();
        return $DB->record_exists_sql($valid_owner_sql, ['owner_id' => $owner_id]);
    }

    /**
     * @return int|null
     */
    public function get_timemodified(): ?int {
        return $this->resource->timemodified;
    }

    /**
     * @return int
     */
    public function get_timecreated(): int {
        return $this->resource->timecreated;
    }

    /**
     * Returning an id associate in table '{engage_resource}' not the actual id of
     * the instance table.
     * @return int
     */
    public function get_id(): int {
        return $this->resource->id;
    }

    /**
     * @return int
     */
    public function get_instanceid(): int {
        $instanceid = $this->resource->instanceid;

        if (null === $instanceid) {
            debugging("The instanceid of the resource has not yet been set", DEBUG_DEVELOPER);
            return 0;
        }

        return (int) $instanceid;
    }

    /**
     * @return string|null
     */
    public function get_extra(): ?string {
        return $this->resource->extra;
    }

    /**
     * @return int
     */
    public function get_userid(): int {
        return $this->resource->userid;
    }

    /**
     * @return string
     */
    public function get_resourcetype(): string {
        return $this->resource->resourcetype;
    }

    /**
     * @return bool
     */
    public function is_restricted(): bool {
        return access::is_restricted($this->resource->access);
    }

    /**
     * @return bool
     */
    public function is_public(): bool {
        return access::is_public($this->resource->access);
    }

    /**
     * Returning the visibility string that only machine can understand.
     * @return string
     */
    public function get_access_string(): string {
        return access::get_string($this->resource->access);
    }

    /**
     * @return string
     */
    public function get_access_code(): string {
        return access::get_code($this->resource->access);
    }

    /**
     * @return bool
     */
    public function is_private(): bool {
        return access::is_private($this->resource->access);
    }

    /**
     * @param bool $format
     * @return string
     */
    public function get_name(bool $format = true): string {
        $name = $this->resource->name;

        if (null === $name) {
            return '';
        }

        if ($format) {
            return format_string($name);
        }

        return $name;
    }

    /**
     * @return int
     */
    public function get_access(): int {
        return $this->resource->access;
    }

    /**
     * @return \context
     */
    public function get_context(): \context {
        return \context_user::instance($this->resource->userid);
    }

    /**
     * @return int
     */
    public function get_context_id(): int {
        if (empty($this->resource->contextid)) {
            $context = $this->get_context();
            $this->resource->contextid = $context->id;
            $this->resource->save();
        }

        return $this->resource->contextid;
    }

    /**
     * @return array
     */
    public function to_array(): array {
        return $this->resource->to_array();
    }

    /**
     * Usage should be increased when resources are created in the playlist.
     *
     * @return void
     */
    public function increase_resource_usage(): void {
        $usage = $this->resource->countusage;
        $this->resource->countusage = ++$usage;
        $this->resource->save();
    }

    /**
     * Usage should be decreased when playlists are deleted.
     *
     * @return void
     */
    public function decrease_resource_usage(): void {
        $usage = $this->resource->countusage;
        $this->resource->countusage = --$usage;
        $this->resource->save();
    }

    /**
     * Returning resource usage that needs to be rendered on the resource card and
     * Subclass can override the method for specific resource.
     *
     * @return Int
     */
    public static function get_resource_usage(int $instanceid): int {
        $entity = new engage_resource($instanceid);

        return $entity->countusage;
    }

    /**
     * Returning an array of keys that are either optional or required for param $data in the
     * functions that do create/update the resource instance.
     *
     * @return definition[]
     */
    protected static function get_data_definitions(): array {
        return [
            definition::from_parameters(
                'access',
                [
                    'default' => access::PRIVATE,
                    'validators' => [new access_validator()],
                ]
            ),
            definition::from_parameters('name'),
            definition::from_parameters(
                'topics',
                [
                    'default' => [],
                    'validators' => [new topic_validator()]
                ]
            )
        ];
    }

    /**
     * @inheritDoc
     */
    public function get_shareable(): shareable_result {
        global $USER;

        // Private access.
        if ($this->is_private()) {
            return new shareable_result(false, 'error:shareprivate');
        }

        // Restricted access but this is the owner who wants to share.
        if ($this->is_restricted() && $this->get_userid() != $USER->id) {
            return new shareable_result(false, 'error:sharerestricted');
        }

        return new shareable_result(true);
    }

    /**
     * @param int $userid
     * @return bool
     */
    public function can_reshare(int $userid): bool {
        return $this->get_userid() !== $userid;
    }

    /**
     * @param int $userid  The actor id
     * @return bool
     */
    abstract protected function do_delete(int $userid): bool;

    /**
     * @param int $userid
     * @param array $data
     * @return bool
     */
    abstract protected function do_update(array $data, int $userid): bool;

    /**
     * @param int $userid
     * @return bool
     */
    abstract public function can_delete(int $userid): bool;

    /**
     * @param int $userid
     * @return bool
     */
    abstract public static function can_create(int $userid): bool;

    /**
     * @param int $userid
     * @return bool
     */
    abstract public function can_update(int $userid): bool;

    /**
     * Returning the instance id, after creation. Using static method here, because we don't want the children to
     * access to the state of this very resource when doing the creation.
     *
     * @param array             $data
     * @param engage_resource   $entity
     * @param int               $userid
     *
     * @return int
     */
    abstract protected static function do_create(array $data, engage_resource $entity, int $userid): int;

    /**
     * Reload the data from the DB. Ideally, $reload will be used to define whether it should
     * re-calculate the cache or not.
     *
     * @param bool $reload Whether to recalculate the cache or not.
     * @return void
     */
    abstract public function refresh(bool $reload = false): void;
}