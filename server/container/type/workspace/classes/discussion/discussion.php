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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_workspace
 */
namespace container_workspace\discussion;

use coding_exception;
use container_workspace\entity\workspace_discussion;
use container_workspace\repository\discussion_repository;
use container_workspace\workspace;
use context;
use context_course;
use context_user;
use core\json_editor\document;
use core\json_editor\node\abstraction\has_extra_linked_file;
use core\json_editor\node\attachment;
use core\json_editor\node\audio;
use core\json_editor\node\file\base_file;
use core\json_editor\node\image;
use core\json_editor\node\video;
use core_container\factory;
use core_user;
use moodle_url;
use stdClass;
use stored_file;
use totara_comment\loader\comment_loader;
use totara_reaction\loader\reaction_loader;

/**
 * Model class for discussion
 */
final class discussion {
    /**
     * This area is being used in several places:
     * + comments
     * + file-area
     * + reaction
     *
     * @var string
     */
    public const AREA = 'discussion';

    /**
     * Comment was deleted via the reportedcontent report.
     * Kept at 1 to be consistent with the same type of values in
     * other components.
     *
     * @var int
     */
    public const REASON_DELETED_REPORTED = 1;

    /**
     * @var workspace_discussion
     */
    private $entity;

    /**
     * @var stdClass|null
     */
    private $user;

    /**
     * Total number of comments related to this discussion. Note that this does not include any count of the replies.
     * @var int|null
     */
    private $total_comments;

    /**
     * Total number of reactions of this discussion.
     * @var int|null
     */
    private $total_reactions;

    /**
     * @var bool
     */
    private $prevent_delete_files_on_update;

    /**
     * discussion constructor.
     * @param workspace_discussion $entity
     */
    private function __construct(workspace_discussion $entity) {
        $this->entity = $entity;
        $this->user = null;

        $this->total_comments = null;
        $this->total_reactions = null;
        $this->prevent_delete_files_on_update = false;
    }

    /**
     * @param int $discussion_id
     * @return discussion
     */
    public static function from_id(int $discussion_id): discussion {
        $entity = new workspace_discussion($discussion_id);
        return new static($entity);
    }

    /**
     * @param workspace_discussion $entity
     * @param stdClass|null       $user
     *
     * @return discussion
     */
    public static function from_entity(workspace_discussion $entity, ?stdClass $user = null): discussion {
        $discussion = new static($entity);

        if (null !== $user) {
            $discussion->set_user($user);
        }

        return $discussion;
    }

    /**
     * Given the $content and its format, this function will try to process on the file and returning the
     * processed content that had been transform all the URL to placeholder.
     *
     * Note that this function will only process the files if $draft_id is provided.
     *
     * @param string   $content
     * @param int      $content_format
     * @param int      $discussion_id
     * @param int|null $user_id
     * @param int|null $draft_id
     *
     * @return string
     */
    protected static function process_content_with_files(string $content, int $content_format, int $discussion_id,
                                                         ?int $user_id = null, ?int $draft_id = null): string {
        global $CFG, $USER, $DB;

        if (null === $draft_id || 0 === $draft_id) {
            return $content;
        }

        require_once("{$CFG->dirroot}/lib/filelib.php");

        if (null === $user_id || 0 === $user_id) {
            $user_id = $USER->id;
        }

        $user_context = context_user::instance($user_id);

        if (FORMAT_JSON_EDITOR == $content_format) {
            // It is a json editor content. Therefore we can make sure that if there is are files or not.
            //
            // Check that if the discussion's content has any files included or not. This happened, because the
            // deletion on the content editor does not trigger to delete the draft file. Therefore we will have
            // to run this extra process on the content to make sure that we are not including any
            // trailing draft files.
            $document = document::create($content);
            $node_types = [
                attachment::get_type(),
                audio::get_type(),
                image::get_type(),
                video::get_type(),
            ];

            $nodes = $document->find_nodes_by_types($node_types);

            // Start processing on files so that we can remove the trailing files.
            $fs = get_file_storage();
            $files = $fs->get_area_files(
                $user_context->id,
                'user',
                'draft',
                $draft_id,
                'itemid, filepath, filename',
                false
            );

            // Start indexing the file names from the json node.
            $file_names = [];

            /** @var base_file $file_node */
            foreach ($nodes as $file_node) {
                $file_names[] = $file_node->get_filename();

                if ($file_node instanceof has_extra_linked_file) {
                    $extra_file = $file_node->get_extra_linked_file();

                    if (null !== $extra_file) {
                        $file_names[] = $extra_file->get_filename();
                    }
                }
            }

            // Now start looping the files to remove the files that are not appearing within the json
            // nodes content and start removing them.
            foreach ($files as $file) {
                $filename = $file->get_filename();
                if (!in_array($filename, $file_names, true)) {
                    $file->delete();
                }
            }
        }

        $workspace_id = $DB->get_field(
            'workspace_discussion',
            'course_id',
            ['id' => $discussion_id],
            MUST_EXIST
        );

        // Simulate the form data.
        $form_data = new stdClass();
        $form_data->content_editor = [
            'format' => $content_format,
            'text' => $content,
            'itemid' => $draft_id,
        ];

        $form_data = file_postupdate_standard_editor(
            $form_data,
            'content',
            ['maxfiles' => -1],
            context_course::instance($workspace_id),
            workspace::get_type(),
            static::AREA,
            $discussion_id
        );

        return $form_data->content;
    }

    /**
     * Note that this function does not include any capability check nor emitting any event.
     *
     * @param string   $content
     * @param int      $workspace_id
     * @param int|null $draft_id
     * @param int|null $content_format
     * @param int|null $actor_id
     *
     * @return discussion
     */
    public static function create(string $content, int $workspace_id, ?int $draft_id = null,
                                  ?int $content_format = null, ?int $actor_id = null): discussion {
        global $USER, $CFG;

        if (empty($content)) {
            throw new coding_exception("Cannot create a discussion with empty content");
        }

        if (null === $actor_id || 0 === $actor_id) {
            $actor_id = $USER->id;
        }

        if (null === $content_format) {
            $content_format = FORMAT_PLAIN;
        }

        require_once("{$CFG->dirroot}/lib/filelib.php");

        // Convert the editor content to plain text.
        // Note that this should be happening before processing the files, as processing files
        // can cause the draft files to be removed.
        $content_text = content_to_text($content, $content_format);

        if (null !== $draft_id) {
            // After converted to text, we will have to rewrite the
            // file urls in order to get rid of those hardcoded URL in the content.
            $content_text = file_rewrite_urls_to_pluginfile($content_text, $draft_id);
        }

        $entity = new workspace_discussion();
        $entity->content_format = $content_format;

        // We will have to store the raw content first, in order to produce the record's id.
        // Then we will be using this id to help saving the files.
        // Note that at this point, we cannot produce any content_text because the content itself has not yet
        // been thru the processing of files yet.
        $entity->content = $content;
        $entity->user_id = $actor_id;
        $entity->course_id = $workspace_id;
        $entity->timestamp = time();
        $entity->save();

        $entity->content = static::process_content_with_files(
            $content,
            $content_format,
            $entity->id,
            $actor_id,
            $draft_id
        );

        // Updating the time to produce and the content_text it self.
        $entity->content_text = $content_text;

        // Updating the content after saving file should not updating the time stamp.
        $entity->update_timestamps(false);
        $entity->save();

        $discussion = static::from_id($entity->id);
        if ($actor_id == $USER->id) {
            $discussion->set_user($USER);
        }

        return $discussion;
    }

    /**
     * Please note that this function does not include any capabilities check.
     *
     * @param string   $content
     * @param int|null $draft_id
     * @param int|null $content_format
     * @param int|null $actor_id
     *
     * @return void
     */
    public function update_content(string $content, ?int $draft_id = null, ?int $content_format = null,
                                   ?int $actor_id = null): void {
        global $USER, $CFG;

        if (empty($content)) {
            throw new coding_exception("Cannot update a discussion with empty content");
        }

        if (null === $actor_id || 0 === $actor_id) {
            $actor_id = $USER->id;
        }

        if (null === $content_format) {
            $content_format = $this->entity->content_format;
        }

        require_once("{$CFG->dirroot}/lib/filelib.php");

        // Convert the editor content to plain text.
        // Note that this should be happening before processing the files, as processing files
        // can cause the draft files to be removed.
        $content_text = content_to_text($content, $content_format);

        if (null !== $draft_id) {
            // After converted to text, we will have to rewrite the
            // file urls in order to get rid of those hardcoded URL in the content.
            $content_text = file_rewrite_urls_to_pluginfile($content_text, $draft_id);
        }

        $this->entity->content = static::process_content_with_files(
            $content,
            $content_format,
            $this->entity->id,
            $actor_id,
            $draft_id
        );

        if ((null === $draft_id || 0 === $draft_id) && !$this->prevent_delete_files_on_update) {
            // Draft's id is empty, therefore we should go thru the current trailing files
            // to remove them.
            $fs = get_file_storage();

            $context = context_course::instance($this->entity->course_id);
            $fs->delete_area_files(
                $context->id,
                workspace::get_type(),
                static::AREA,
                $this->entity->id
            );
        }

        $this->entity->content_format = $content_format;
        $this->entity->content_text = $content_text;

        $this->entity->timestamp = time();

        // This is to make sure that we update the time modified, because some part of the system
        // set it to not update the time modified at all.
        $this->entity->update_timestamps(true);
        $this->entity->save();
    }

    /**
     * Note that this function will not check for any capabilities, nor does deleting any references related
     * to the discussion. Such as discussion's likes/comments.
     *
     * @return void
     */
    public function delete(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/filelib.php");

        // Start deleting the files related to the discussion.
        $fs = get_file_storage();
        $context = context_course::instance($this->entity->course_id);

        $fs->delete_area_files(
            $context->id,
            workspace::get_type(),
            discussion::AREA,
            $this->entity->id
        );

        $this->entity->delete();
    }

    /**
     * Delete the discussion, but keep the base discussion record.
     * Used when it's deleted via the reportedcontent report.
     *
     * @param int      $reason Status code indicating why the comment was deleted. Defaults to by user.
     * @param int|null $time_deleted
     * @return bool
     */
    public function soft_delete(int $reason = self::REASON_DELETED_USER, ?int $time_deleted = null): bool {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/filelib.php");

        // Start deleting the files related to the discussion.
        $fs = get_file_storage();
        $context = context_course::instance($this->entity->course_id);

        $fs->delete_area_files(
            $context->id,
            workspace::get_type(),
            discussion::AREA,
            $this->entity->id
        );

        $this->entity->content = null;
        $this->entity->content_format = FORMAT_PLAIN;

        $this->entity->time_deleted = $time_deleted ?? time();
        $this->entity->reason_deleted = $reason;

        $this->entity->update_timestamps(false);
        $this->entity->update();

        return $this->is_soft_deleted();
    }

    /**
     * @return bool
     */
    public function is_pinned(): bool {
        $value = $this->entity->time_pinned;
        return null !== $value;
    }

    /**
     * @return stdClass
     */
    public function get_user(): stdClass {
        if (null === $this->user) {
            $user_id = $this->entity->user_id;
            $this->user = core_user::get_user($user_id);
        }

        return $this->user;
    }

    /**
     * @return int
     */
    public function get_user_id(): int {
        return $this->entity->user_id;
    }

    /**
     * @param stdClass $user
     * @return void
     */
    public function set_user(stdClass $user): void {
        if (!property_exists($user, 'id')) {
            throw new coding_exception("Cannot set the user record when the record does not have an id of itself");
        }

        $user_id = $this->entity->user_id;
        if ($user_id != $user->id) {
            throw new coding_exception(
                "Cannot set the user record to someone else that is not an owner of the discussion"
            );
        }

        $this->user = $user;
    }

    /**
     * @return int
     */
    public function get_workspace_id(): int {
        return $this->entity->course_id;
    }

    /**
     * @return workspace
     */
    public function get_workspace(): workspace {
        $workspace_id = $this->entity->course_id;

        /** @var workspace $workspace */
        $workspace = factory::from_id($workspace_id);

        // Factory will load from cache - hence this should be fast enough.
        return $workspace;
    }

    /**
     * @return int
     */
    public function get_id(): int {
        return $this->entity->id;
    }

    /**
     * @return ?string
     */
    public function get_content(): ?string {
        return $this->entity->content;
    }

    /**
     * @return string
     */
    public function get_content_text(): string {
        return $this->entity->content_text;
    }

    /**
     * @return int
     */
    public function get_content_format(): int {
        return (int) $this->entity->content_format;
    }

    /**
     * @return int
     */
    public function get_time_created(): int {
        return $this->entity->time_created;
    }

    /**
     * @return int|null
     */
    public function get_time_modified(): ?int {
        return $this->entity->time_modified;
    }

    /**
     * @param int $value
     * @return void
     */
    public function set_total_reactions(int $value): void {
        $this->total_reactions = $value;
    }

    /**
     * @param int $value
     * @return void
     */
    public function set_total_comments(int $value): void {
        $this->total_comments = $value;
    }

    /**
     * @return int
     */
    public function get_total_comments(): int {
        if (null === $this->total_comments) {
            $this->total_comments = comment_loader::count_comments(
                $this->entity->id,
                workspace::get_type(),
                self::AREA
            );
        }

        return $this->total_comments;
    }

    /**
     * @return int
     */
    public function get_total_reactions(): int {
        if (null === $this->total_reactions) {
            $this->total_reactions = reaction_loader::count(
                $this->entity->id,
                workspace::get_type(),
                self::AREA
            );
        }

        return $this->total_reactions;
    }

    /**
     * This function will bump the timestamp of the discussion, so that we can tell the recently updated.
     * @return void
     */
    public function touch(): void {
        $this->entity->timestamp = time();
        $this->entity->do_not_update_timestamps();

        $this->entity->save();
    }

    /**
     * Returning the array of stored file record, only if there are files.
     *
     * Note that this function will only return the files that are uploaded under
     * the discussion's content. The files that are uploaded under comments/replies
     * that related to the discussion will be excluded from this function.
     *
     * @return stored_file[]
     */
    public function get_files(): array {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/filelib.php");

        $fs = get_file_storage();
        $context = context_course::instance($this->entity->course_id);

        $stored_files = $fs->get_area_files(
            $context->id,
            workspace::get_type(),
            static::AREA,
            $this->entity->id
        );

        if (empty($stored_files)) {
            return [];
        }

        return array_filter(
            $stored_files,
            function (stored_file $file): bool {
                return !$file->is_directory();
            }
        );
    }

    /**
     * Returning the workspace's context.
     *
     * @return context
     */
    public function get_context(): context {
        return $this->get_workspace()->get_context();
    }

    /**
     * @return bool
     */
    public function is_soft_deleted(): bool {
        return !!$this->entity->time_deleted;
    }

    /**
     * @return int|null
     */
    public function get_reason_deleted(): ?int {
        return $this->entity->reason_deleted;
    }

    /**
     * @return string
     */
    public static function get_entity_table(): string {
        return workspace_discussion::TABLE;
    }

    /**
     * @return moodle_url
     */
    public function get_url(): moodle_url {
        return new moodle_url(
            "/container/type/workspace/discussion.php",
            ['id' => $this->entity->id]
        );
    }

    /**
     * Prevent the update_content method from deleting any hanging discussion files.
     * Only available to unit & behat tests.
     *
     * @param bool $prevent_delete_files_on_update
     */
    public function set_prevent_delete_files_on_update(bool $prevent_delete_files_on_update): void {
        if ((!defined('PHPUNIT_TEST') || !PHPUNIT_TEST) && (!defined('BEHAT_TEST') || !BEHAT_TEST)) {
            throw new coding_exception('Cannot call set_prevent_delete_files_on_update outside of a phpunit or behat test.');
        }
        $this->prevent_delete_files_on_update = $prevent_delete_files_on_update;
    }

    /**
     * @return void
     */
    public function reload(): void {
        $this->entity->refresh();
    }

    /**
     * Metadata function to return the repository instance of discussion's entity.
     *
     * @return discussion_repository
     */
    public static function get_entity_repository(): discussion_repository {
        return workspace_discussion::repository();
    }
}