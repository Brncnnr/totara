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
 * @package totara_playlist
 */
namespace totara_playlist;

use core_container\factory;
use totara_comment\comment_helper;
use totara_engage\access\access;
use totara_engage\access\access_manager;
use totara_engage\access\accessible;
use totara_engage\link\builder;
use totara_engage\rating\rating_manager;
use totara_engage\resource\resource_factory;
use totara_engage\resource\resource_item;
use totara_engage\share\manager as share_manager;
use totara_engage\share\share as share_model;
use totara_engage\share\shareable;
use totara_engage\share\shareable_result;
use totara_playlist\entity\playlist as playlist_entity;
use totara_playlist\entity\playlist_resource;
use totara_playlist\event\playlist_created;
use totara_playlist\event\playlist_deleted;
use totara_playlist\event\playlist_reshared;
use totara_playlist\event\playlist_shared;
use totara_playlist\event\playlist_updated;
use totara_playlist\exception\playlist_exception;
use totara_playlist\local\helper;
use totara_playlist\local\image_processor;
use totara_playlist\local\image_processor\contract as image_processor_contract;
use totara_playlist\repository\playlist_resource_repository;
use totara_topic\provider\topic_provider;
use totara_topic\topic_helper;

/**
 * Model for playlist.
 */
final class playlist implements accessible, shareable {

    /**
     * The area for the summary (aka description)
     * @var string
     */
    public const SUMMARY_AREA = 'summary';

    /**
     * Playlist image file area
     * @var string
     */
    public const IMAGE_AREA = 'image';

    /**
     * Maximum rating allowed
     */
    public const RATING_MAX = 5;

    /**
     * Area used for rating.
     * @var string
     */
    public const RATING_AREA = 'playlist';

    /**
     * Area used for comment.
     * @var string
     */
    public const COMMENT_AREA = 'comment';

    /**
     * @var playlist_entity
     */
    private $playlist;

    /**
     * @var playlist_resource[]
     */
    private $resources;

    /**
     * @var \stdClass|null
     */
    private $user;

    /**
     * The processor used to generate playlist images
     *
     * @var image_processor
     */
    private $image_processor;

    /**
     * playlist constructor.
     * @param playlist_entity $entity
     */
    private function __construct(playlist_entity $entity) {
        $this->playlist = $entity;
        $this->resources = [];
        $this->user = null;
        $this->image_processor = image_processor::make();
    }

    /**
     * @return string
     */
    final public static function get_resource_type(): string {
        return 'totara_playlist';
    }

    /**
     * @param bool  $preload
     * @param int   $playlistid
     *
     * @return playlist
     */
    public static function from_id(int $playlistid, bool $preload = false): playlist {
        $entity = new playlist_entity($playlistid);
        $instance = new static($entity);

        if ($preload) {
            $instance->load_resources();
        }

        return $instance;
    }

    /**
     * @param playlist_entity $entity
     * @param bool            $preload
     * @param \stdClass|null  $user
     *
     * @return playlist
     */
    public static function from_entity(playlist_entity $entity, bool $preload = false, ?\stdClass $user = null): playlist {
        if (!$entity->exists()) {
            throw new \coding_exception(
                "Cannot instantiate a playlist form an entity that is not existing in the system"
            );
        }

        $playlist = new static($entity);

        if ($preload) {
            $playlist->load_resources();
        }

        if (null !== $user) {
            $playlist->set_user($user);
        }

        return $playlist;
    }

    /**
     * @param string        $name
     * @param int|null      $access
     * @param int|null      $contextid  The origin context where this playlist is being added.
     * @param string|null   $summary
     * @param int|null      $userid
     * @param int|null      $summary_format
     *
     * @return playlist
     */
    public static function create(string $name, int $access = null, ?int $contextid = null,
                                  ?int $userid = null, ?string $summary = null, ?int $summary_format = null): playlist {
        global $USER;

        if (null == $access) {
            // Use the private access.
            $access = access::PRIVATE;
        } else if (!access::is_valid($access)) {
            debugging("Access value is invalid with value '{$access}'", DEBUG_DEVELOPER);
            $access = access::PRIVATE;
        }

        if (empty(trim($name)) || \core_text::strlen(trim($name)) > 75) {
            throw playlist_exception::create('create');
        }

        if (null == $userid) {
            $userid = $USER->id;
        }

        if (!static::can_create($userid)) {
            throw playlist_exception::create('create');
        }

        if (null == $contextid) {
            $context = \context_user::instance($userid);
            $contextid = $context->id;
        }

        if (null === $access) {
            $access = access::PRIVATE;
        }

        if (null === $summary_format) {
            $summary_format = FORMAT_PLAIN;
        }

        $entity = new playlist_entity();
        $entity->access = $access;
        $entity->name = $name;
        $entity->userid = $userid;
        $entity->summary = $summary;
        $entity->contextid = $contextid;
        $entity->summaryformat = $summary_format;

        $entity->save();
        $playlist = new static($entity);

        $event = playlist_created::from_playlist($playlist, $userid);
        $event->trigger();

        return $playlist;
    }

    /**
     * @param int[] $topic_ids
     * @return void
     */
    public function add_topics_by_ids(array $topic_ids): void {
        $item_id = $this->playlist->id;

        foreach ($topic_ids as $topic_id) {
            topic_helper::add_topic_usage($topic_id, 'totara_playlist', 'playlist', $item_id);
        }
    }

    /**
     * @param int[] $exclude_topic_ids
     * @return void
     */
    public function remove_topics_by_ids(array $exclude_topic_ids = []): void {
        $item_id = $this->playlist->id;
        $topics = topic_provider::get_for_item($item_id, 'totara_playlist', 'playlist');

        foreach ($topics as $topic) {
            $topic_id = $topic->get_id();
            if (!in_array($topic_id, $exclude_topic_ids)) {
                topic_helper::delete_topic_usage($topic, 'totara_playlist', 'playlist', $item_id);
            }
        }
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

        if (!$this->can_delete($userid)) {
            throw playlist_exception::create('delete');
        }

        helper::decrement_resource_usage_for_playlist($this->playlist->id);

        // Deleting comments.
        comment_helper::purge_area_comments(
            static::get_resource_type(),
            'comment',
            $this->playlist->id
        );

        // Deleting ratings.
        $rating_mangager = rating_manager::instance($this->playlist->id, 'totara_playlist', static::RATING_AREA);
        $rating_mangager->delete();

        $event = playlist_deleted::from_playlist($this, $userid);
        $event->trigger();

        share_manager::delete($this->playlist->id, static::get_resource_type());

        // Delete the banner image.
        $processor = $this->image_processor;
        $images = [
            $processor->get_image_for_playlist($this),
            $processor->get_image_for_playlist($this, true)
        ];
        foreach ($images as $image) {
            if ($image) {
                $image->delete();
            }
        }

        // Delete resource links from the playlist
        /** @var playlist_resource_repository $repo */
        $repo = playlist_resource::repository();
        $repo->delete_resources_by_playlistid($this->playlist->id);

        $this->playlist->delete();
    }

    /**
     * @param int $userid
     * @return bool
     */
    public function can_delete(int $userid): bool {
        $context = $this->get_context();

        // Admin can do anything.
        if (access_manager::can_manage_engage($context, $userid)) {
            return true;
        }

        return $this->playlist->userid == $userid;
    }

    /**
     * @param int|null $userid
     * @return bool
     */
    public static function can_create(?int $userid = null): bool {
        global $USER;

        if (null == $userid) {
            $userid = $USER->id;
        }

        $context = \context_user::instance($userid);
        return has_capability('totara/playlist:create', $context, $userid);
    }

    /**
     * @param resource_item $resource
     * @return bool
     */
    public function can_resource_be_added(resource_item $resource): bool {
        if ($resource->is_public() || $resource->is_restricted()) {
            // The resource is pretty much has the proper state to be
            // added into the playlist.
            return true;
        }

        // If playlist is private or restricted then you must be the owner of the resource.
        if (!$this->is_public() && !$this->is_restricted()) {
            if ($resource->get_userid() != $this->playlist->userid) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param int           $userid
     * @param resource_item $resource
     *
     * @return bool
     */
    public function can_user_add_resource(int $userid, resource_item $resource): bool {
        if (!$this->can_user_contribute($userid)) {
            return false;
        }

        if (!$this->can_resource_be_added($resource)) {
            // Either the resource can be added or not.
            return false;
        }

        return access_manager::can_access($resource, $userid);
    }

    /**
     * @param int           $userid
     * @param resource_item $resource
     *
     * @return bool
     */
    public function can_user_remove_resource(int $userid, resource_item $resource): bool {
        if (access_manager::can_manage_engage($resource->get_context(), $userid)) {
            return true;
        }

        return $this->can_user_contribute($userid);
    }


    /**
     * @param resource_item $resource
     * @param int|null      $userid
     *
     * @return void
     */
    public function add_resource(resource_item $resource, ?int $userid = null): void {
        global $USER;
        if (null == $userid) {
            $userid = $USER->id;
        }

        if (!$this->can_user_add_resource($userid, $resource)) {
            throw playlist_exception::create('addresource');
        }

        // Resource might need to have its access and shares updated.
        $this->update_resource($resource);

        // Link resource to playlist.
        $entity = new playlist_resource();
        $entity->playlistid = $this->playlist->id;
        $entity->resourceid = $resource->get_id();
        $entity->userid = $userid;
        $entity->save();

        // Increase resource usage.
        $resource->increase_resource_usage();

        $this->resources[] = $entity;

        // Update the modification timestamp.
        $this->playlist->set_updated_timestamp();
        $this->playlist->update();

        // Update the image
        $this->image_processor->update_playlist_images($this);
    }

    /**
     * Remove a resource from the playlist
     *
     * @param resource_item $resource
     * @param int|null $user_id
     */
    public function remove_resource(resource_item $resource, ?int $user_id = null): void {
        global $USER;

        if (null == $user_id || $user_id == 0) {
            $user_id = $USER->id;
        }

        if (!$this->can_user_remove_resource($user_id, $resource)) {
            throw playlist_exception::create('removeresource');
        }

        // Remove resource.
        /** @var playlist_resource_repository $repo */
        $repo = playlist_resource::repository();
        $repo->remove_resource($this->get_id(), $resource->get_id());

        // Decrease resource usage.
        $resource->decrease_resource_usage();

        // Update the image
        $this->image_processor->update_playlist_images($this);
    }

    /**
     * Resources might need to have their access and shares updated.
     */
    private function update_resources(): void {
        foreach ($this->resources as $playlist_resource) {
            $resource = resource_factory::create_instance_from_id($playlist_resource->resourceid);
            $this->update_resource($resource);
        }
    }

    /**
     * Update resource's access and share properties.
     *
     * @param resource_item $resource
     */
    private function update_resource(resource_item $resource): void {
        // When adding a resource to a playlist then that resource needs to have same
        // access and sharing settings as playlist, unless the resource is public.
        // A confirmation dialogue should have already notified the user of this change.
        if (!$resource->is_public() && !$this->is_private()) {
            // At this point resource can either be:
            //   1. private and needs to be updated to restricted.
            //   2. restricted and needs to be updated to public.
            if ($resource->get_access() !== $this->get_access()) {
                $data = ['access' => $this->get_access()];
                $resource->update($data);
            }

            // Clone current playlist shares onto resource. Playlist shares will be
            // added to resource in addition to the shares it currently has.
            $share = share_manager::get_share($this, playlist::get_resource_type());
            if (!empty($share)) {
                share_manager::clone_shares($resource, $resource::get_resource_type(), $share->id);
            }
        }
    }

    /**
     * @return void
     */
    public function load_resources(): void {
        $this->resources = [];

        /** @var playlist_resource_repository $repo */
        $repo = playlist_resource::repository();
        $this->resources = $repo->get_all_for_playlist($this->playlist->id);
    }

    /**
     * @return int
     */
    public function get_id(): int {
        return $this->playlist->id;
    }

    /**
     * @return bool
     */
    public function is_public(): bool {
        return access::is_public($this->playlist->access);
    }

    /**
     * @return bool
     */
    public function is_private(): bool {
        return access::is_private($this->playlist->access);
    }

    /**
     * @return bool
     */
    public function is_restricted(): bool {
        return access::is_restricted($this->playlist->access);
    }

    /**
     * @param int $resourceid
     * @return bool
     */
    public function has_resource(int $resourceid): bool {
        foreach ($this->resources as $resource) {
            if ($resource->resourceid == $resourceid) {
                return true;
            }
        }

        /** @var playlist_resource_repository $repo */
        $repo = playlist_resource::repository();
        $resource = $repo->find_resource($resourceid, $this->playlist->id);

        if (null == $resource) {
            return false;
        }

        $this->resources[] = $resource;
        return true;
    }

    /**
     * @param int $userid
     * @return bool
     */
    public function can_user_contribute(int $userid): bool {
        $context = $this->get_context();

        if (CONTEXT_USER == $context->contextlevel) {
            return $this->can_update($userid);
        } else {
            if (CONTEXT_COURSE == $context->contextlevel) {
                $container = factory::from_id($context->instanceid);

                // Todo: Add container interface.
                return false;
            } else {
                debugging(
                    "Invalid origin of context with level '{$context->contextlevel}' being used for playlist '{$this->playlist->id}",
                    DEBUG_DEVELOPER
                );
            }
        }

        return true;
    }

    /**
     * @return \context
     */
    public function get_context(): \context {
        $context_id = $this->get_contextid();
        return \context::instance_by_id($context_id);
    }

    /**
     * @return int
     */
    public function get_contextid(): int {
        $context_id = $this->playlist->contextid;

        if (null !== $context_id) {
            return $context_id;
        }

        $user_id = $this->playlist->userid;
        $context = \context_user::instance($user_id);

        return $context->id;
    }

    /**
     * @return int
     */
    public function get_userid(): int {
        return $this->playlist->userid;
    }

    /**
     * @return \stdClass
     */
    public function get_user(): \stdClass {
        if (null === $this->user) {
            $user_id = $this->playlist->userid;
            $this->user = \core_user::get_user($user_id, '*', MUST_EXIST);
        }

        return $this->user;
    }

    /**
     * @param \stdClass $user
     * @return void
     */
    public function set_user(\stdClass $user): void {
        if (!property_exists($user, 'id')) {
            throw new \coding_exception("Dummy data of user does not have property 'id'");
        }

        $user_id = $this->playlist->userid;
        if ($user_id != $user->id) {
            throw new \coding_exception("The dummy data of user is not valid");
        }

        $this->user = $user;
    }

    /**
     * @return bool
     */
    public function exists(): bool {
        return 0 != $this->playlist->id && $this->playlist->exists();
    }

    /**
     * @param bool $format
     * @return string
     */
    public function get_name(bool $format = true): string {
        if (!$format) {
            return $this->playlist->name;
        }

        return format_string($this->playlist->name);
    }

    /**
     * @return string
     */
    public function get_summary(): string {
        return $this->playlist->summary ?? '';
    }

    /**
     * @return int
     */
    public function get_timecreated(): int {
        return $this->playlist->timecreated;
    }

    /**
     * @return int
     */
    public function get_timemodified(): ?int {
        return $this->playlist->timemodified;
    }

    /**
     * @return int
     */
    public function get_access(): int {
        return $this->playlist->access;
    }

    /**
     * @return string
     */
    public function get_access_code(): string {
        $access_value = $this->get_access();
        return access::get_code($access_value);
    }

    /**
     * @return int
     */
    public function get_summaryformat(): int {
        return $this->playlist->summaryformat;
    }

    /**
     * @param int $user_id
     * @return bool
     */
    public function can_update(int $user_id): bool {
        if (access_manager::can_manage_engage($this->get_context(), $user_id)) {
            return true;
        }

        return $this->playlist->userid == $user_id;
    }

    /**
     * @param string|null   $name
     * @param int|null      $access
     * @param string|null   $summary
     * @param int|null      $summary_format
     * @param int|null      $userid
     *
     * @return void
     */
    public function update(?string $name = null, ?int $access = null, ?string $summary = null,
                           ?int $summary_format = null, ?int $userid = null): void {
        global $USER, $DB;
        $transaction = $DB->start_delegated_transaction();

        if (null == $userid) {
            $userid = $USER->id;
        }

        if (!$this->can_update($userid)) {
            throw playlist_exception::create('update');
        }

        if (null === $access) {
            $access = $this->playlist->access;
        }

        $old_access = $this->playlist->access;
        if (!access_manager::can_update_access($old_access, $access)) {
            throw playlist_exception::create('update');
        }

        if (empty($name)) {
            // Fallback to the current name of playlist.
            $name = $this->playlist->name;
        }

        // Updating the inner playlist.
        $this->do_update($name, $access, $summary, $summary_format);

        // Resources might need to have their access and shares updated.
        $this->update_resources();
        $transaction->allow_commit();

        // Note: we don't care much about the processes after everything related to the playlist
        // had done updated. Hence no transaction from here.
        $event = playlist_updated::from_playlist($this, $userid);
        $event->trigger();
    }

    /**
     * Note that this function does do any logic checks but just purely write to data layer.
     *
     * @param string $name
     * @param int $access
     * @param string|null $summary
     * @param int|null $summary_format
     *
     * @return void
     */
    protected function do_update(string $name, int $access, ?string $summary = null,
                                 ?int $summary_format = null): void {
        $this->playlist->name = $name;
        $this->playlist->access = $access;

        if (null !== $summary) {
            // If it is empty string - we still want it.
            // We only don't like the null value pretty much.
            $this->playlist->summary = $summary;
        }

        if (null !== $summary_format) {
            $this->playlist->summaryformat = $summary_format;
        }

        $this->playlist->update();
    }

    /**
     * @return string
     */
    public function get_url(): string {
        return builder::to(self::get_resource_type(), ['id' => $this->get_id()])
            ->out(true);
    }

    /**
     * @inheritDoc
     */
    public function can_share(int $userid): bool {
        $context = $this->get_context();

        if (access_manager::can_manage_engage($context, $userid)) {
            return true;
        }

        // Every one can share the playlist. Note that the intention of this function
        // does not check whether the playlist is able to be shared or not.
        return has_capability('totara/playlist:share', $context, $userid);
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
     * @inheritDoc
     */
    public function shared(share_model $share): void {
        // Create event for share.
        if (!$share->is_notified()) {
            $playlist = playlist::from_id($share->get_item_id());
            $event = playlist_shared::from_share($share, $playlist->get_context());
            $event->trigger();
        }

        // Resources might need to have their access and shares updated.
        $this->update_resources();
    }

    /**
     * @return playlist_resource[]
     */
    public function get_resources(): array {
        return $this->resources;
    }

    /**
     * @param int $userid
     * @return bool
     */
    public function can_reshare(int $userid): bool {
        return $this->get_userid() !== $userid;
    }

    /**
     * @param int $userid
     */
    public function reshare(int $userid): void {
        $event = playlist_reshared::from_playlist($this, $userid);
        $event->trigger();
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
        return has_capability('totara/playlist:unshare', $this->get_context(), $USER->id);
    }

    /**
     * Checking whether the owner of this very playlist has been deleted or not.
     *
     * @return bool
     */
    public function is_available(): bool {
        global $DB;
        $valid_owner_sql = '
            SELECT 1 FROM "ttr_user"
            WHERE id = :owner_id
            AND deleted = 0 AND confirmed = 1
        ';

        $owner_id = $this->playlist->userid;
        return $DB->record_exists_sql($valid_owner_sql, ['owner_id' => $owner_id]);
    }

    /**
     * @return bool
     */
    public function has_non_public_resources(): bool {
        /** @var playlist_resource_repository $repository */
        $repository = playlist_resource::repository();
        return $repository->has_non_public_resources($this->playlist->id);
    }

    /**
     * Override the used image processor.
     *
     * @param image_processor_contract $image_processor
     */
    public function set_image_processor(image_processor_contract $image_processor): void {
        $this->image_processor = $image_processor;
    }
}