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
 * @package totara_comment
 */
namespace totara_comment;

use core\orm\query\builder;
use totara_comment\entity\comment as comment_entity;
use totara_reaction\loader\reaction_loader;

/**
 * A model class for comment
 */
final class comment {
    /**
     * Constants for defining how many comments|replies should be loaded per-page.
     * @var int
     */
    public const ITEMS_PER_PAGE = 20;

    /**
     * Comment was deleted by the user/an admin
     * @var int
     */
    public const REASON_DELETED_USER = 0;

    /**
     * Comment was deleted via the reportedcontent report
     * @var int
     */
    public const REASON_DELETED_REPORTED = 1;

    /**
     * Using for comment instance that communicate with other plugins
     * @var string
     */
    public const COMMENT_AREA = 'comment';

    /**
     * Using for reply instance that communicate with other plugins
     * @var string
     */
    public const REPLY_AREA = 'reply';

    /**
     * @var comment_entity
     */
    protected $entity;

    /**
     * @var \stdClass|null
     */
    protected $user;

    /**
     * @var int|null
     */
    protected $totalreactions;

    /**
     * @var int
     */
    private $totalreplies;

    /**
     * comment constructor.
     * @param comment_entity $entity
     */
    private function __construct(comment_entity $entity) {
        $this->entity = $entity;
        $this->user = null;
        $this->totalreplies = null;
        $this->totalreactions = null;
    }

    /**
     * @param int $id
     * @return comment
     */
    public static function from_id(int $id): comment {
        $entity = new comment_entity($id);
        return new static($entity);
    }

    /**
     * @param \stdClass $record
     * @return comment
     */
    public static function from_record(\stdClass $record): comment {
        $entity = new comment_entity($record);
        return static::from_entity($entity);
    }

    /**
     * @param comment_entity $entity
     * @param \stdClass|null $user
     *
     * @return comment
     */
    public static function from_entity(comment_entity $entity, ?\stdClass $user = null): comment {
        $comment = new static($entity);

        if (null !== $user) {
            $comment->set_user($user);
        }

        return $comment;
    }

    /**
     * We need to save the content of the comment first, as it will create the entry of comment. Then we use
     * that comment entry to handle the file of the content. If there is any.
     *
     * @param int       $instanceid
     * @param string    $content
     * @param string    $area
     * @param string    $component
     * @param int       $format
     * @param int       $actorid
     * @param int|null  $parentid
     *
     * @return comment
     */
    public static function create(int $instanceid, string $content, string $area, string $component, int $format,
                                  int $actorid, ?int $parentid = null) : comment {
        $entity = new comment_entity();
        $entity->instanceid = $instanceid;
        $entity->userid = $actorid;
        $entity->content = $content;
        $entity->format = $format;
        $entity->area = $area;
        $entity->component = $component;
        $entity->parentid = $parentid;
        $entity->timedeleted = null;
        $entity->reasondeleted = null;

        $entity->save();
        return static::from_entity($entity);
    }

    /**
     * @return \stdClass
     */
    public function get_user(): \stdClass {
        if (null === $this->user) {
            $userid = $this->entity->userid;

            if (null == $userid) {
                throw new \coding_exception("Cannot load the user record because the user's id was not set");
            }

            $user = \core_user::get_user($userid, '*', MUST_EXIST);

            // Clear the password for safety reason.
            unset($user->password);

            $this->set_user($user);
        }

        return $this->user;
    }

    /**
     * @param \stdClass $user
     * @return void
     */
    public function set_user(\stdClass $user): void {
        if (null !== $this->user) {
            return;
        }

        $userid = $this->entity->userid;
        if ($userid != $user->id) {
            throw new \coding_exception("The user record is not matching with the creator of the comment");
        }

        $this->user = $user;
    }

    /**
     * Note: The deletion of comment will not perform any actions upon capability checking.
     *
     * @return bool
     */
    public function delete(): bool {
        $this->entity->delete();
        return $this->entity->deleted();
    }

    /**
     * @param int|null $now
     * @param int $reason Status code indicating why the comment was deleted. Defaults to by user.
     * @return bool
     */
    public function soft_delete(?int $now = null, int $reason = self::REASON_DELETED_USER): bool {
        if (null == $now) {
            $now = time();
        }
        $this->entity->content = null;
        $this->entity->contenttext = null;
        $this->entity->format = null;

        $this->entity->timedeleted = $now;
        $this->entity->reasondeleted = $reason;

        $this->entity->update_timestamps(false);
        $this->entity->update();

        return $this->is_soft_deleted();
    }

    /**
     * @return int
     */
    public function get_userid(): int {
        return $this->entity->userid;
    }

    /**
     * @return int
     */
    public function get_id(): int {
        return $this->entity->id;
    }

    /**
     * @return bool
     */
    public function exists(): bool {
        return $this->entity->exists();
    }

    /**
     * @return int
     */
    public function get_instanceid(): int {
        return $this->entity->instanceid;
    }

    /**
     * @return string
     */
    public function get_content(): string {
        return $this->entity->content ?? '';
    }

    /**
     * @return int
     */
    public function get_format(): int {
        $format = $this->entity->format;

        if (null === $format) {
            // There can be chance that the format has been deleted.
            return (int) FORMAT_PLAIN;
        }

        return (int) $format;
    }

    /**
     * @return int
     */
    public function get_timecreated(): int {
        return $this->entity->timecreated;
    }

    /**
     * @return int|null
     */
    public function get_time_soft_deleted(): ?int {
        return $this->entity->timedeleted;
    }

    /**
     * @return string
     */
    public function get_component(): string {
        return $this->entity->component;
    }

    /**
     * Returning the area where this comment is being used - or belongging to.
     * @return string
     */
    public function get_area(): string {
        return $this->entity->area;
    }

    /**
     * Returning the parent comment's id. If the value returned is null, then this comment record
     * is a comment otherwise it is a reply.
     *
     * @return int|null
     */
    public function get_parent_id(): ?int {
        $parentid = $this->entity->parentid;

        if (empty($parentid)) {
            return null;
        }

        return $parentid;
    }

    /**
     * @return bool
     */
    public function is_reply(): bool {
        $parentid = $this->get_parent_id();
        return !empty($parentid);
    }

    /**
     * @return int
     */
    public function get_total_replies(): int {
        if (null === $this->totalreplies) {
            $builder = builder::table(comment_entity::TABLE);
            $builder->where('parentid', $this->entity->id);

            $this->totalreplies = (int) $builder->count();
        }

        return $this->totalreplies;
    }

    /**
     * @param int $value
     * @return void
     */
    public function set_total_replies(int $value): void {
        $this->totalreplies = $value;
    }

    /**
     * @return int
     */
    public function get_total_reactions(): int {
        if (null === $this->totalreactions) {
            $area = static::COMMENT_AREA;
            if ($this->is_reply()) {
                $area = static::REPLY_AREA;
            }

            $id = $this->get_id();
            $this->totalreactions = reaction_loader::count($id, 'totara_comment', $area);
        }

        return $this->totalreactions;
    }

    /**
     * @param int $value
     * @return void
     */
    public function set_total_reactions(int $value): void {
        $this->totalreactions = $value;
    }

    /**
     * @return int|null
     */
    public function get_timemodified(): ?int {
        return $this->entity->timemodified;
    }

    /**
     * @return comment_entity
     */
    public function get_entity(): comment_entity {
        return $this->entity;
    }

    /**
     * @param string    $content
     * @param bool      $flagupdate
     * @param int|null  $format
     *
     * @return void
     */
    public function update_content(string $content, ?int $format = null, bool $flagupdate = true): void {
        $this->entity->content = $content;
        if (null !== $format) {
            $this->entity->format = $format;
        }

        $this->entity->update_timestamps($flagupdate);
        $this->entity->update();

        $this->entity->refresh();
    }

    /**
     * @param string $content_text
     *
     * @return void
     */
    public function update_content_text(string $content_text): void {
        $this->entity->contenttext = $content_text;
        $this->entity->do_not_update_timestamps();

        $this->entity->save();
        $this->entity->refresh();
    }

    /**
     * @return bool
     */
    public function is_soft_deleted(): bool {
        $time = $this->entity->timedeleted;
        return null !== $time;
    }

    /**
     * @return bool
     */
    public function is_edited(): bool {
        return (null !== $this->entity->timemodified);
    }

    /**
     * @return int|null
     */
    public function get_reason_deleted(): ?int {
        return $this->entity->reasondeleted;
    }

    /**
     * @return string
     */
    public static function get_component_name(): string {
        return 'totara_comment';
    }

    /**
     * @return string
     */
    public static function get_entity_table(): string {
        return comment_entity::TABLE;
    }

    /**
     * @param string $area
     * @return bool
     */
    public static function is_valid_area(string $area): bool {
        return in_array($area, [static::COMMENT_AREA, static::REPLY_AREA]);
    }

    /**
     * Either returning {@see comment::COMMENT_AREA} or {@see comment::REPLY_AREA}.
     * Note that this function does not return the instance's area.
     *
     * @return string
     */
    public function get_comment_area(): string {
        if ($this->is_reply()) {
            return static::REPLY_AREA;
        }

        return static::COMMENT_AREA;
    }

    /**
     * @return \stdClass
     */
    public function to_record(): \stdClass {
        $record = $this->entity->to_array();
        return (object) $record;
    }

    /**
     * @return string
     */
    public function get_content_text(): string {
        return $this->entity->contenttext ?? '';
    }
}