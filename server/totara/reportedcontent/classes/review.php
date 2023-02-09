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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package totara_reportedcontent
 */

namespace totara_reportedcontent;

use totara_reportedcontent\entity\review as review_entity;

/**
 * Reviewing and actions on reported content
 */
final class review {
    const DECISION_PENDING = 0;
    const DECISION_REMOVE = 1;
    const DECISION_APPROVE = 2;

    /**
     * @var review_entity
     */
    protected $entity;

    /**
     * @var \stdClass|null
     */
    protected $target_user;

    /**
     * @var \stdClass|null
     */
    protected $reviewer;

    /**
     * @var \stdClass|null
     */
    protected $complainer;

    /**
     * review constructor.
     *
     * @param review_entity $entity
     */
    private function __construct(review_entity $entity) {
        $this->entity = $entity;
        $this->target_user = null;
        $this->complainer = null;
        $this->reviewer = null;
    }

    /**
     * @param int $id
     * @return review
     * @throws \coding_exception
     */
    public static function from_id(int $id): review {
        $entity = new review_entity($id);
        return new static($entity);
    }

    /**
     * @param int $item_id
     * @param int $context_id
     * @param string $component
     * @param string $area
     * @param string $url
     * @param string $content
     * @param int $format
     * @param int $time_content
     * @param int $target_user_id
     * @param int $complainer_id
     * @return review
     */
    public static function create(
        int $item_id,
        int $context_id,
        string $component,
        string $area,
        string $url,
        string $content,
        int $format,
        int $time_content,
        int $target_user_id,
        int $complainer_id
    ): review {
        $entity = new review_entity();
        $entity->area = $area;
        $entity->component = $component;
        $entity->item_id = $item_id;
        $entity->context_id = $context_id;
        $entity->complainer_id = $complainer_id;
        $entity->url = $url;
        $entity->status = static::DECISION_PENDING;

        // The resolver already figured this out
        $entity->target_user_id = $target_user_id;
        $entity->content = $content;
        $entity->format = $format;
        $entity->time_content = $time_content;

        $entity->save();
        return static::from_entity($entity);
    }

    /**
     * @param review_entity $entity
     * @return review
     */
    public static function from_entity(review_entity $entity): review {
        return new static($entity);
    }

    /**
     * @return \stdClass
     */
    public function get_target_user(): \stdClass {
        if (null === $this->target_user) {
            $user_id = $this->entity->target_user_id;
            if (empty($user_id)) {
                throw new \coding_exception("Cannot load the target_user record because the target_user's id was not set");
            }

            $user = \core_user::get_user($user_id, '*', MUST_EXIST);
            unset($user->password);

            $this->set_target_user($user);
        }
        return $this->target_user;
    }

    /**
     * @param \stdClass $target_user
     */
    public function set_target_user(\stdClass $target_user): void {
        $this->target_user = $target_user;
    }

    /**
     * @return \stdClass
     */
    public function get_complainer(): \stdClass {
        if (null === $this->complainer) {
            $user_id = $this->entity->complainer_id;
            if (empty($user_id)) {
                throw new \coding_exception("Cannot load the complainer record because the complainer's id was not set");
            }

            $user = \core_user::get_user($user_id, '*', MUST_EXIST);
            unset($user->password);

            $this->set_complainer($user);
        }
        return $this->complainer;
    }

    /**
     * @param \stdClass $complainer
     */
    public function set_complainer(\stdClass $complainer): void {
        $this->complainer = $complainer;
    }

    /**
     * @return \stdClass|null
     */
    public function get_reviewer(): ?\stdClass {
        // Reviewer is optional
        if (empty($this->entity->reviewer_id)) {
            return null;
        }

        if (null === $this->reviewer) {
            $user_id = $this->entity->reviewer_id;

            $user = \core_user::get_user($user_id, '*', MUST_EXIST);
            unset($user->password);

            $this->set_reviewer($user);
        }
        return $this->reviewer;
    }

    /**
     * @param \stdClass $reviewer
     */
    public function set_reviewer(\stdClass $reviewer): void {
        $this->reviewer = $reviewer;
    }

    /**
     * @return int
     */
    public function get_context_id(): int {
        return $this->entity->context_id;
    }

    /**
     * @return bool|\context
     */
    public function get_context() {
        return \context::instance_by_id($this->get_context_id());
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
    public function get_id(): int {
        return $this->entity->id;
    }

    /**
     * @return string
     */
    public function get_url(): string {
        return $this->entity->url;
    }

    /**
     * @return string
     */
    public function get_content(): string {
        return $this->entity->content;
    }

    /**
     * @return int|null
     */
    public function get_status(): ?int {
        return $this->entity->status;
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
    public function get_time_content(): ?int {
        return $this->entity->time_content;
    }

    /**
     * @return int|null
     */
    public function get_time_reviewed(): ?int {
        return $this->entity->time_reviewed;
    }

    /**
     * @return int
     */
    public function get_item_id(): int {
        return $this->entity->item_id;
    }

    /**
     * @return string
     */
    public function get_component(): string {
        return $this->entity->component;
    }

    /**
     * @return string
     */
    public function get_area(): string {
        return $this->entity->area;
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
     * Set the status & reviewer on the entity, this happens together
     *
     * @param int $status
     * @param int $reviewer_id
     */
    public function do_review(int $status, int $reviewer_id): void {
        $this->entity->reviewer_id = $reviewer_id;
        $this->entity->status = $status;
        $this->entity->time_reviewed = time();
        $this->entity->update();
    }

    /**
     * Note that this function will not run thru any capabilities check, as it
     * should have been done prior to calling this function.
     *
     * @return void
     */
    public function delete(): void {
        $this->entity->delete();
    }
}