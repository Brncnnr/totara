<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 Totara Learning Solutions LTD
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
 * @package totara_comment
 */
namespace totara_comment;

use coding_exception;
use context;
use context_user;
use core\json_editor\document;
use core\json_editor\node\abstraction\has_extra_linked_file;
use core\json_editor\node\attachment;
use core\json_editor\node\audio;
use core\json_editor\node\file\base_file;
use core\json_editor\node\image;
use core\json_editor\node\video;
use core_user;
use stdClass;
use stored_file;
use totara_comment\event\comment_created;
use totara_comment\event\comment_soft_deleted;
use totara_comment\event\comment_updated;
use totara_comment\event\reply_created;
use totara_comment\event\reply_soft_deleted;
use totara_comment\exception\comment_exception;
use totara_comment\loader\comment_loader;
use totara_comment\pagination\cursor;
use totara_reaction\reaction_helper;
use totara_reportedcontent\reported_content_helper;

/**
 * A helper class to purge the all the comments related to the instance.
 */
final class comment_helper {
    /**
     * Preventing this class from being constructed.
     * comment_helper constructor.
     */
    private function __construct() {
    }

    /**
     * Purging all the comments from the database's table, which the comments are related to
     * the instance of a plugin that is using totara_comment component.
     * Note that this API will also purge the replies.
     *
     * @param string $component
     * @param string $area
     * @param int    $instance_id
     *
     * @return void
     */
    public static function purge_area_comments(string $component, string $area, int $instance_id): void {
        // This cursor will be used in the whole processing, as everytime when the
        // record is deleted, the cursor should be reset.
        $cursor = new cursor([
            'limit' => 100,
            'page' => 1,
        ]);

        $paginator = comment_loader::get_paginator(
            $instance_id,
            $component,
            $area,
            $cursor
        );

        $comments = $paginator->get_items()->all();
        while (!empty($comments)) {
            foreach ($comments as $comment) {
                static::purge_comment($comment);
            }

            $paginator = comment_loader::get_paginator(
                $instance_id,
                $component,
                $area,
                $cursor
            );

            $comments = $paginator->get_items()->all();
        }
    }

    /**
     * Purging a single comment. If a reason is provided then the comment will be soft-deleted.
     *
     * @param comment  $comment
     * @param int|null $delete_reason
     * @return void
     */
    public static function purge_comment(comment $comment, ?int $delete_reason = null): void {
        if ($comment->is_reply()) {
            debugging("Comment is a reply which it will not be purged", DEBUG_DEVELOPER);
            return;
        }

        // Purge all the replies related to the comment.
        static::purge_all_replies_of_comment($comment);

        $component = $comment->get_component();
        $resolver = resolver_factory::create_resolver($component);

        $context_id = $resolver->get_context_id(
            $comment->get_instanceid(),
            $comment->get_area()
        );

        static::remove_related_content($comment, $context_id);

        // Delete reported content records
        reported_content_helper::purge_area_review(
            $comment->get_id(),
            'totara_comment',
            comment::COMMENT_AREA,
            $context_id
        );

        // If a reason is provided, then this is a soft-delete
        // Otherwise purge it properly
        if (null !== $delete_reason) {
            $comment->soft_delete(null, $delete_reason);
        } else {
            $comment->delete();
        }
    }

    /**
     * @param comment $comment
     * @return void
     */
    protected static function purge_all_replies_of_comment(comment $comment): void {
        // Doing deletion of replies with 100 replies per time.
        $paginator = comment_loader::get_replies($comment, 1, 100);
        $replies = $paginator->get_items()->all();

        while (!empty($replies)) {
            /** @var comment $reply */
            foreach ($replies as $reply) {
                static::purge_reply($reply);
            }

            $paginator = comment_loader::get_replies($comment, 1, 100);
            $replies = $paginator->get_items()->all();
        }
    }

    /**
     * Purging a single reply.
     *
     * @param comment $reply
     * @return void
     */
    public static function purge_reply(comment $reply): void {
        if (!$reply->is_reply()) {
            debugging("Reply is a comment, which it will not be purged", DEBUG_DEVELOPER);
            return;
        }

        $reply_id = $reply->get_id();
        $component = $reply->get_component();

        $resolver = resolver_factory::create_resolver($component);
        $context_id = $resolver->get_context_id(
            $reply->get_instanceid(),
            $reply->get_area()
        );

        self::remove_related_content($reply, $context_id);

        // Delete reported content records
        reported_content_helper::purge_area_review(
            $reply_id,
            'totara_comment',
            comment::REPLY_AREA,
            $context_id
        );

        // Finally, start deleting the record of reply.
        $reply->delete();
    }


    /**
     * Returning the processed content which all the file urls will be transformed
     * to a placeholder.
     *
     * If $draft_id is not being set, then current content will be returned.
     *
     * @param string   $content
     * @param int      $content_format
     * @param int      $comment_id
     * @param int      $context_id
     * @param int|null $draft_id
     * @param int|null $user_id
     *
     * @return string
     */
    private static function process_content_with_files(string $content, int $content_format, int $comment_id,
                                                       int $context_id, ?int $draft_id = null,
                                                       ?int $user_id = null): string {
        global $CFG, $USER, $DB;

        if (null === $draft_id || 0 === $draft_id) {
            // Nothing to process.
            return $content;
        }

        if (null === $user_id || 0 === $user_id) {
            $user_id = $USER->id;
        }

        require_once("{$CFG->dirroot}/lib/filelib.php");
        $fs = get_file_storage();

        if (FORMAT_JSON_EDITOR == $content_format) {
            $document = document::create($content);
            $node_types = [
                attachment::get_type(),
                video::get_type(),
                audio::get_type(),
                image::get_type(),
            ];

            $nodes = $document->find_nodes_by_types($node_types);
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

            $user_context = context_user::instance($user_id);
            $draft_files = $fs->get_area_files(
                $user_context->id,
                'user',
                'draft',
                $draft_id,
                'itemid, filepath, filename',
                false
            );

            foreach ($draft_files as $file) {
                $filename = $file->get_filename();
                if (!in_array($filename, $file_names, true)) {
                    // This draft file is not appearing within the content of the comment, therefore
                    // it will be deleted prior to the point where it is moved to the actual area.
                    $file->delete();
                }
            }
        }

        // Fetch data to find out context and the comment's area.
        $comment_record = $DB->get_record(
            'totara_comment',
            ['id' => $comment_id],
            'parentid, instanceid, area'
        );

        $comment_area = comment::COMMENT_AREA;
        if (null !== $comment_record->parentid) {
            $comment_area = comment::REPLY_AREA;
        }

        // Emulate the form data for editor function to work.
        $form_data = new stdClass();
        $form_data->content_editor = [
            'text' => $content,
            'format' => $content_format,
            'itemid' => $draft_id,
        ];

        $options = ['maxfiles' => -1];
        $context = context::instance_by_id($context_id);

        // Note that we are save files against the totara_comment and its area. Instead of using
        // different places. The only difference is that the context - since totara_comment does
        // not use any context, therefore it will have to fetch the context from the component where
        // it is being used and use that context instead.
        $form_data = file_postupdate_standard_editor(
            $form_data,
            'content',
            $options,
            $context,
            'totara_comment',
            $comment_area,
            $comment_id
        );

        return $form_data->content;
    }

    /**
     * @param string   $raw_content
     * @param int      $content_format
     * @param int|null $draft_id
     *
     * @return string
     */
    private static function convert_to_content_text(string $raw_content, int $content_format, ?int $draft_id): string {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/filelib.php");

        $content_text = content_to_text($raw_content, $content_format);

        if (null === $draft_id) {
            return $content_text;
        }

        // Note that we can only return the content text that had been removed the draft file url only.
        return file_rewrite_urls_to_pluginfile($content_text, $draft_id);
    }

    /**
     * An API to create the comment.
     *
     * @param string   $component
     * @param string   $area
     * @param int      $instance_id
     * @param string   $content
     * @param int|null $content_format
     * @param int|null $draft_id
     * @param int|null $actor_id
     *
     * @return comment
     */
    public static function create_comment(string $component, string $area, int $instance_id, string $content,
                                          ?int $content_format = null, ?int $draft_id = null, ?int $actor_id = null): comment {
        global $USER;

        if (empty($content)) {
            throw new coding_exception("Cannot create a comment with empty content");
        }

        if (null === $actor_id || 0 === $actor_id) {
            $actor_id = $USER->id;
        }

        $resolver = resolver_factory::create_resolver($component);
        if (!$resolver->is_allow_to_create($instance_id, $area, $actor_id)) {
            throw comment_exception::on_create();
        }

        $content_format = static::get_format($content_format);
        $comment = comment::create(
            $instance_id,
            $content,
            $area,
            $component,
            $content_format,
            $actor_id
        );

        // Convert the raw content to content text. Note that this process has to be done before saving the
        // files to the actual area. This is happening because when the files are saved - draft files will be removed.
        $content_text = static::convert_to_content_text($content, $content_format, $draft_id);

        $context_id = $resolver->get_context_id($instance_id, $area);
        $processed_content = static::process_content_with_files(
            $content,
            $content_format,
            $comment->get_id(),
            $context_id,
            $draft_id,
            $actor_id
        );

        $comment->update_content($processed_content, $content_format, false);
        $comment->update_content_text($content_text);

        if ($actor_id == $USER->id) {
            $comment->set_user($USER);
        } else {
            $user = core_user::get_user($actor_id, '*', MUST_EXIST);
            unset($user->password);

            $comment->set_user($user);
        }

        $context = context::instance_by_id($context_id);

        $event = comment_created::from_comment($comment, $context, $actor_id);
        $event->add_record_snapshot(comment::get_entity_table(), $comment->to_record());
        $event->trigger();

        return $comment;
    }

    /**
     * Creating a reply for the parent comment's id.
     *
     * @param int      $parent_comment_id
     * @param string   $content
     * @param int|null $draft_id
     * @param int|null $content_format
     * @param int|null $actor_id
     *
     * @return comment
     */
    public static function create_reply(int $parent_comment_id, string $content, ?int $draft_id = null,
                                        ?int $content_format = null, ?int $actor_id = null): comment {
        global $USER;

        if (empty($content)) {
            throw new coding_exception("Cannot create a reply with empty content");
        }

        if (null === $actor_id || 0 === $actor_id) {
            $actor_id = $USER->id;
        }

        $parent_comment = comment::from_id($parent_comment_id);
        if ($parent_comment->is_reply()) {
            throw new coding_exception("Cannot create a reply of another reply");
        }

        $component = $parent_comment->get_component();
        $resolver = resolver_factory::create_resolver($component);

        $instance_id = $parent_comment->get_instanceid();
        $area = $parent_comment->get_area();

        if (!$resolver->is_allow_to_create($instance_id, $area, $actor_id)) {
            throw comment_exception::on_create();
        }

        $content_format = static::get_format($content_format);

        // Convert the raw content to content text. Note that this process has to be done before saving the
        // files to the actual area. This is happening because when the files are saved - draft files will be removed.
        $content_text = static::convert_to_content_text($content, $content_format, $draft_id);

        $reply = comment::create(
            $instance_id,
            $content,
            $area,
            $component,
            $content_format,
            $actor_id,
            $parent_comment_id
        );

        $context_id = $resolver->get_context_id($instance_id, $area);
        $processed_content = static::process_content_with_files(
            $content,
            $content_format,
            $reply->get_id(),
            $context_id,
            $draft_id,
            $actor_id
        );

        $reply->update_content($processed_content, $content_format, false);
        $reply->update_content_text($content_text);

        if ($actor_id == $USER->id) {
            $reply->set_user($USER);
        } else {
            $user = core_user::get_user($actor_id, '*', MUST_EXIST);

            unset($user->password);
            $reply->set_user($user);
        }

        $event = reply_created::from_reply($reply, $context_id, $actor_id);
        $event->add_record_snapshot(comment::get_entity_table(), $reply->to_record());
        $event->trigger();

        return $reply;
    }

    /**
     * Update the content of a comment.
     * Note that this function will try to bump the timestamp for of updating content.
     *
     * @param int      $comment_id
     * @param string   $content
     * @param int|null $draft_id
     * @param int|null $content_format
     * @param int|null $actor_id
     *
     * @return comment
     */
    public static function update_content(int $comment_id, string $content, ?int $draft_id = null,
                                          ?int $content_format = null, ?int $actor_id = null): comment {
        global $USER;

        if (null === $actor_id || 0 === $actor_id) {
            $actor_id = $USER->id;
        }

        $comment = comment::from_id($comment_id);
        $user_id = $comment->get_userid();

        $component = $comment->get_component();
        $resolver = resolver_factory::create_resolver($component);

        // If the comment is deleted, it cannot be updated further
        if ($comment->is_soft_deleted()) {
            throw comment_exception::on_update();
        }

        if ($user_id != $actor_id) {
            // If the actor is not the author of this very comment, then we should run another check that are being
            // implemented at the child level.
            if (!$resolver->is_allow_to_update($comment, $actor_id)) {
                throw comment_exception::on_update();
            }
        }

        if (null === $content_format) {
            $content_format = $comment->get_format();
        } else {
            $content_format = static::get_format($content_format);
        }

        $context_id = $resolver->get_context_id(
            $comment->get_instanceid(),
            $comment->get_area()
        );

        // Convert the raw content to content text. Note that this process has to be done before saving the
        // files to the actual area. This is happening because when the files are saved - draft files will be removed.
        $content_text = static::convert_to_content_text($content, $content_format, $draft_id);

        $processed_content = static::process_content_with_files(
            $content,
            $content_format,
            $comment_id,
            $context_id,
            $draft_id,
            $actor_id
        );

        $comment->update_content($processed_content, $content_format);
        $comment->update_content_text($content_text);

        $context = context::instance_by_id($context_id);
        $event = comment_updated::from_comment($comment, $context, $actor_id);
        $event->add_record_snapshot(comment::get_entity_table(), $comment->to_record());
        $event->trigger();

        return $comment;
    }

    /**
     * Set the flag deleted of the comment, instead of hard deleting the record.
     *
     * @param int      $comment_id
     * @param int|null $actor_id
     *
     * @param int|null $delete_reason
     * @return comment
     */
    public static function soft_delete(int $comment_id, ?int $actor_id = null, ?int $delete_reason = null): comment {
        global $USER;

        if (null === $actor_id || 0 === $actor_id) {
            $actor_id = $USER->id;
        }

        $comment = comment::from_id($comment_id);
        $user_id = $comment->get_userid();

        $component = $comment->get_component();
        $resolver = resolver_factory::create_resolver($component);

        if ($user_id != $actor_id) {
            // If the actor is not the author of this very comment, then we should run another check that are being
            // implemented at the child level.
            if (!$resolver->is_allow_to_delete($comment, $actor_id)) {
                throw comment_exception::on_soft_delete();
            }
        }

        $context_id = $resolver->get_context_id(
            $comment->get_instanceid(),
            $comment->get_area()
        );

        if ($delete_reason === comment::REASON_DELETED_REPORTED) {
            // This is an administrative delete, we need to remove any files & likes/reactions
            self::remove_related_content($comment, $context_id);
        }

        if ($comment->is_reply()) {
            $event = reply_soft_deleted::from_reply($comment, $context_id, $actor_id);
            $event->add_record_snapshot(comment::get_entity_table(), $comment->to_record());

            $event->trigger();
        } else {
            $event = comment_soft_deleted::from_comment($comment, $context_id, $actor_id);
            $event->add_record_snapshot(comment::get_entity_table(), $comment->to_record());

            $event->trigger();
        }

        $comment->soft_delete(null, $delete_reason ?? comment::REASON_DELETED_USER);
        return $comment;
    }

    /**
     * Will perform a hard-delete of a comment or reply.
     * Note: No capability check is performed here, you need to verify with the
     * interactor beforehand.
     *
     * Child replies of this comment will not be removed.
     *
     * @param comment $comment
     */
    public static function delete(comment $comment): void {
        $component = $comment->get_component();
        $resolver = resolver_factory::create_resolver($component);

        $context_id = $resolver->get_context_id(
            $comment->get_instanceid(),
            $comment->get_area()
        );

        // Remove files & reactions
        self::remove_related_content($comment, $context_id);

        $comment->delete();
    }

    /**
     * Will perform a hard-delete of all replies attached to a comment
     *
     * @param comment $comment
     */
    public static function delete_replies_of_comment(comment $comment): void {
        if ($comment->is_reply()) {
            debugging("Cannot delete replies of a reply, must provid a comment.", DEBUG_DEVELOPER);
            return;
        }

        // Doing deletion of replies with 100 replies per time.
        $paginator = comment_loader::get_replies($comment, 1, 100);
        $replies = $paginator->get_items()->all();

        while (!empty($replies)) {
            /** @var comment $reply */
            foreach ($replies as $reply) {
                static::delete($reply);
            }

            $paginator = comment_loader::get_replies($comment, 1, 100);
            $replies = $paginator->get_items()->all();
        }
    }

    /**
     * Fetching all the stored files that are related to the comment instance.
     * This function will not include any directories to the list of the result.
     *
     * @param comment $comment
     * @return stored_file[]
     */
    public static function get_files(comment $comment): array {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/filelib.php");

        $component = $comment->get_component();
        $resolver = resolver_factory::create_resolver($component);

        $context_id = $resolver->get_context_id(
            $comment->get_instanceid(),
            $comment->get_area()
        );

        $comment_area = comment::COMMENT_AREA;
        if ($comment->is_reply()) {
            $comment_area = comment::REPLY_AREA;
        }

        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $context_id,
            'totara_comment',
            $comment_area,
            $comment->get_id()
        );

        return array_filter(
            $files,
            function (stored_file $file): bool {
                return !$file->is_directory();
            }
        );
    }

    /**
     * This provide the ability to use different format in different component resolver.
     *
     * @param int|null $format
     * @return int
     */
    private static function get_format(int $format = null): int {
        if (null === $format) {
            return FORMAT_MOODLE;
        }

        $formats = [
            FORMAT_MOODLE,
            FORMAT_HTML,
            FORMAT_PLAIN,
            FORMAT_MARKDOWN,
            FORMAT_JSON_EDITOR,
        ];

        if (!in_array($format, $formats)) {
            debugging(
                "Invalid comment text format used '{$format}'",
                DEBUG_DEVELOPER
            );

            $format = FORMAT_MOODLE;
        }

        return (int) $format;
    }

    /**
     * Delete any related items, including files and reactions.
     *
     * @param comment $comment
     * @param int     $context_id
     */
    private static function remove_related_content(comment $comment, int $context_id): void {
        global $CFG;

        $comment_id = $comment->get_id();
        $area = $comment->is_reply() ? comment::REPLY_AREA : comment::COMMENT_AREA;

        // Purge all the reactions of the comment.
        reaction_helper::purge_area_reactions(
            'totara_comment',
            $area,
            $comment_id
        );

        // Purge all the files that are uploaded to the comment.
        require_once("{$CFG->dirroot}/lib/filelib.php");
        $fs = get_file_storage();
        $fs->delete_area_files(
            $context_id,
            'totara_comment',
            $area,
            $comment_id
        );
    }

    /**
     * @param string $area
     * @return bool
     */
    public static function is_valid_area(string $area): bool {
        return in_array($area, [comment::COMMENT_AREA, comment::REPLY_AREA]);
    }

    /**
     * @param string $area
     * @return void
     */
    public static function validate_comment_area(string $area): void {
        if (!static::is_valid_area($area)) {
            throw new coding_exception("Invalid area '{$area}'");
        }
    }
}