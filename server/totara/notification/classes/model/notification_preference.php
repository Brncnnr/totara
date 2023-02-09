<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @package totara_notification
 */
namespace totara_notification\model;

use context;
use coding_exception;
use lang_string;
use totara_core\extended_context;
use totara_notification\entity\notification_preference as entity;
use totara_notification\local\schedule_helper;
use totara_notification\notification\built_in_notification;
use totara_notification\resolver\resolver_helper;
use totara_notification\schedule\time_window;

/**
 * A model class for notification preference.
 */
class notification_preference {
    /**
     * This is a threshold for number of times that the code is trying to do DB look up.
     * It is quite a reasonable number to do DB look up, as the level of cascading should not exceeding
     * 5 level down.
     * @var int
     */
    private const THRESHOLD_LOOKUP = 15;

    /**
     * @var entity
     */
    private $entity;

    /**
     * Lazy load parent. If the notification does have parent, then this property will not be null.
     * Otherwise null if this object is a parent itself.
     *
     * @var notification_preference|null
     */
    private $parent;

    /**
     * notification_preference constructor.
     * @param entity $entity
     */
    private function __construct(entity $entity) {
        $this->entity = $entity;
        $this->parent = null;
    }

    /**
     * @param entity $entity
     * @return notification_preference
     */
    public static function from_entity(entity $entity): notification_preference {
        if (!$entity->exists()) {
            throw new coding_exception("Cannot instantiate a notification preference from a non-existing entity");
        }

        return new notification_preference($entity);
    }

    /**
     * @param int $id
     * @return notification_preference
     */
    public static function from_id(int $id): notification_preference {
        $entity = new entity($id);
        return static::from_entity($entity);
    }

    /**
     * @return bool
     */
    public function is_custom_notification(): bool {
        return empty($this->entity->notification_class_name);
    }

    /**
     * Checking whether this object is parent or not. The logics of the check are quite simple:
     * + If the parent exist in this instance, then no it does not have parent.
     * + If the context_id is site context id, then no it does not have parent.
     *
     * @return bool
     */
    public function has_parent(): bool {
        if (empty($this->entity->ancestor_id)) {
            return false;
        }

        $context_id = $this->entity->context_id;
        $context = context::instance_by_id($context_id);

        if (CONTEXT_SYSTEM == $context->contextlevel) {
            return false;
        }

        $this->load_parent();
        return null !== $this->parent;
    }

    /**
     * Lazy loading the parent
     * @param bool $reset
     * @return void
     */
    private function load_parent(bool $reset = false): void {
        if (null !== $this->parent && !$reset) {
            return;
        }

        // Reset the parent, then start loading the parent just in case of some preferences.
        $this->parent = null;

        if (empty($this->entity->ancestor_id)) {
            // Nope, this record does not have ancestor id.
            return;
        }

        $extended_context = $this->get_extended_context();
        $parent_extended_context = $extended_context->get_parent();

        if (null === $parent_extended_context) {
            // Nope, this context does not have a parent context.
            return;
        }

        $parent_entity = null;
        $trial = 0;

        while (null === $parent_entity && !empty($parent_extended_context)) {
            // As long as the parent context is still available, then we are
            // still able to find out the parent record of this very instance.
            $trial += 1;

            if (static::THRESHOLD_LOOKUP === $trial) {
                throw new coding_exception("Cannot detect the parent of the notification preference");
            }

            $repository = entity::repository();

            if (is_null($parent_extended_context->get_parent())) {
                // The parent's context is at the system. This is where we just fetch the parent's by this
                // very preference's ancestor id rather than doing the fetch of the middle parent.
                /** @var entity|null $parent_entity */
                $parent_entity = $repository->find($this->entity->ancestor_id);
            } else {
                // The parent' context is not system context. Hence we can do the look up for
                // any sort of record look up in between this very context and the system context.
                $parent_entity = $repository->find_by_context_and_ancestor_id(
                    $parent_extended_context,
                    $this->entity->ancestor_id
                );
            }

            // Traverse up a parent context.
            $parent_extended_context = $parent_extended_context->get_parent();
        }

        if (null !== $parent_entity) {
            $this->parent = static::from_entity($parent_entity);
        }
    }

    /**
     * @param bool $reset
     * @return notification_preference|null
     */
    public function get_parent(bool $reset = false): ?notification_preference {
        $this->load_parent($reset);
        return $this->parent;
    }

    /**
     * @return extended_context
     */
    public function get_extended_context(): extended_context {
        return $this->entity->get_extended_context();
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
    public function get_resolver_class_name(): string {
        return $this->entity->resolver_class_name;
    }

    /**
     * @return string|null
     */
    public function get_notification_class_name(): ?string {
        return $this->entity->notification_class_name;
    }

    /**
     * Returning any default value based on the attribute name from the built-in notification.
     * This function will try to invoke the following:
     * + @see built_in_notification::get_default_subject()
     * + @see built_in_notification::get_default_body()
     * + @see built_in_notification::get_title()
     * + @see built_in_notification::get_default_additional_criteria()
     * + @see built_in_notification::get_recipient_class_name()
     * + @see built_in_notification::get_default_body_format()
     * + @see built_in_notification::get_default_subject_format()
     * + @see built_in_notification::get_default_schedule_offset()
     * + @see built_in_notification::get_default_enabled()
     * + @see built_in_notification::get_default_forced_delivery_channels()
     *
     * @param string $attribute_name
     * @return mixed|null
     */
    private function get_property_from_built_in_notification(string $attribute_name) {
        if (empty($this->entity->notification_class_name)) {
            debugging(
                "The notification preference with id '{$this->entity->id}' does not have a value for built-in notification " .
                "'{$this->entity->notification_class_name}' to get default attribute '{$attribute_name}'",
                DEBUG_DEVELOPER
            );

            return null;
        }

        $map_methods = [
            'body' => 'get_default_body',
            'subject' => 'get_default_subject',
            'title' => 'get_title',
            'additional_criteria' => 'get_default_additional_criteria',
            'schedule_offset' => 'get_default_schedule_offset',
            'subject_format' => 'get_default_subject_format',
            'recipient' => 'get_recipient_class_name',
            'recipients' => 'get_recipient_class_names',
            'body_format' => 'get_default_body_format',
            'enabled' => 'get_default_enabled',
            'forced_delivery_channels' => 'get_default_forced_delivery_channels',
        ];

        if (!isset($map_methods[$attribute_name])) {
            throw new coding_exception(
                "Unable to find the mapped method for attribute '{$attribute_name}'"
            );
        }

        $method_name = $map_methods[$attribute_name];
        return call_user_func([$this->entity->notification_class_name, $method_name]);
    }

    /**
     * Return the raw value of notification's body text.
     * @return string
     */
    public function get_body(): string {
        // We look to parent/built in only if the value's null.
        if (!is_null($this->entity->body)) {
            return $this->entity->body;
        }

        if ($this->has_parent()) {
            return $this->parent->get_body();
        }

        return $this->get_property_from_built_in_notification('body');
    }

    /**
     * Return the raw value of notification's subject text
     * @return string
     */
    public function get_subject(): string {
        // We look to parent/built in only if the value's null.
        if (!is_null($this->entity->subject)) {
            return $this->entity->subject;
        }

        if ($this->has_parent()) {
            return $this->parent->get_subject();
        }

        return $this->get_property_from_built_in_notification('subject');
    }

    /**
     * Returns the content format that we are using for the notification's subject.
     * @return int
     */
    public function get_subject_format(): int {
        $value = $this->entity->subject_format;
        if (!is_null($value)) {
            return $value;
        }

        if ($this->has_parent()) {
            return $this->parent->get_body_format();
        }

        return $this->get_property_from_built_in_notification('subject_format');
    }

    /**
     * @return string|null json encoded
     */
    public function get_additional_criteria(bool $check_relevant = true): ?string {
        // $check_relevant is a performance optimisation - no reason to check more than once.
        if ($check_relevant && !resolver_helper::is_additional_criteria_resolver($this->get_resolver_class_name())) {
            return null;
        }

        if (!empty($this->entity->additional_criteria)) {
            return $this->entity->additional_criteria;
        }

        if ($this->has_parent()) {
            return $this->parent->get_additional_criteria(false);
        }

        // This entity has no value, but it also has no parent and is not a built-in notification, so
        // null is the actual value.
        if (empty($this->entity->notification_class_name)) {
            return null;
        }

        return $this->get_property_from_built_in_notification('additional_criteria');
    }

    /**
     * @return string
     */
    public function get_title(): string {
        if (!is_null($this->entity->title)) {
            return $this->entity->title;
        }

        if ($this->has_parent()) {
            return $this->parent->get_title();
        }

        return $this->get_property_from_built_in_notification('title');
    }

    /**
     * Is enabled?
     *
     * @return bool
     */
    public function get_enabled(): ?bool {
        if (null !== $this->entity->enabled) {
            return $this->entity->enabled;
        }

        if ($this->has_parent()) {
            return $this->parent->get_enabled();
        }

        return $this->get_property_from_built_in_notification('enabled');
    }

    /**
     * Returns the content format that we are using for the notification's body.
     * @return int
     */
    public function get_body_format(): int {
        $value = $this->entity->body_format;
        if (!is_null($value)) {
            return $value;
        }

        if ($this->has_parent()) {
            return $this->parent->get_body_format();
        }

        return $this->get_property_from_built_in_notification('body_format');
    }

    /**
     * Get recipient class name
     *
     * @return string
     * @throws coding_exception
     * @deprecated Since Totara 17.0 - use get_recipients instead
     */
    public function get_recipient(): string {
        debugging('Do not use notification_preference::get_recipient any more, use notification_preference::get_recipients instead', DEBUG_DEVELOPER);
        $recipients = $this->get_recipients();
        return !empty($recipients) ? reset($recipients) : '';
    }

    /**
     * Get recipient class names
     *
     * @return array
     */
    public function get_recipients(): array {
        if (!is_null($this->entity->recipients)) {
            return json_decode($this->entity->recipients);
        }

        if ($this->has_parent()) {
            return $this->parent->get_recipients();
        }

        // Get default recipients for built_in_notification
        return $this->get_property_from_built_in_notification('recipients');
    }

    /**
     * Returns the raw schedule offset. This is used to figure out
     * what type of schedule is selected, so has to be translated first.
     *
     * @return int
     */
    public function get_schedule_offset(): int {
        $value = $this->entity->schedule_offset;
        if (null !== $value) {
            return $value;
        }

        if ($this->has_parent()) {
            return $this->parent->get_schedule_offset();
        }

        return $this->get_property_from_built_in_notification('schedule_offset');
    }

    /**
     * @return int|null
     */
    public function get_ancestor_id(): ?int {
        return $this->entity->ancestor_id;
    }

    /**
     * @return void
     */
    public function refresh(): void {
        $this->entity->refresh();

        // Resetting the parent, because we would want to re-calculate the parent.
        $this->parent = null;
    }

    /**
     * @return bool
     */
    public function is_overridden_additional_criteria(): bool {
        return !empty($this->entity->additional_criteria);
    }

    /**
     * @return bool
     */
    public function is_overridden_body(): bool {
        return !empty($this->entity->body);
    }

    /**
     * @return bool
     */
    public function is_overridden_subject(): bool {
        return !empty($this->entity->subject);
    }

    /**
     * @return bool
     */
    public function is_overridden_schedule(): bool {
        // We check for null as 0 is a valid answer and empty would
        // incorrectly handle on_event methods
        return $this->entity->schedule_offset !== null;
    }

    /**
     * @return bool
     */
    public function is_overridden_recipient(): bool {
        return !empty($this->entity->recipients);
    }

    /**
     * @return bool
     */
    public function is_overridden_enabled(): bool {
        return $this->entity->enabled !== null;
    }

    /**
     * @return void
     */
    public function delete(): void {
        $this->entity->delete();
    }

    /**
     * @return bool
     */
    public function delete_custom(): bool {
        if (!$this->is_custom_notification()) {
            throw new coding_exception("Cannot delete non-custom notification");
        }
        if (!empty($this->entity->ancestor_id)) {
            throw new coding_exception("Cannot delete notification override");
        }
        $repository = entity::repository();
        $repository->delete_custom($this->entity->id);
        return true;
    }

    /**
     * @return bool
     */
    public function exists(): bool {
        return $this->entity->exists();
    }

    /**
     * Checking whether the time now exceeding the scheduled time calculated from the event_time.
     *
     * @param int         $event_time           The epoch time - in seconds
     * @param time_window $current_time_window  The current time window of time_now and the last cron time.
     *
     * @return bool
     */
    public function is_in_time_window(int $event_time, time_window $current_time_window): bool {
        $time_now = $current_time_window->get_max_time();
        $last_cron_time = $current_time_window->get_min_time();

        // Schedule offset is already in seconds unit.
        $schedule_offset = $this->get_schedule_offset();

        // Max time is the time_now minus the schedule offset.
        $max_time = $time_now - $schedule_offset;

        // Min time is the last cron time minus the schedule offset.
        $min_time = $last_cron_time - $schedule_offset;

        return $min_time <= $event_time && $event_time < $max_time;
    }

    /**
     * @return bool
     */
    public function is_on_event(): bool {
        $schedule_offset = $this->get_schedule_offset();

        return schedule_helper::is_on_event($schedule_offset);
    }

    /**
     * @return string[]
     */
    public function get_forced_delivery_channels(): array {
        if (null !== $this->entity->forced_delivery_channels) {
            return $this->entity->get_decoded_forced_delivery_channels();
        }

        if ($this->has_parent()) {
            return $this->parent->get_forced_delivery_channels();
        }

        return $this->get_property_from_built_in_notification('forced_delivery_channels');
    }

    /**
     * @return bool
     */
    public function is_overridden_forced_delivery_channels(): bool {
        $channels = $this->entity->forced_delivery_channels;

        // We are using null, because empty array means that the notification
        // preference does not want to force any.
        return null !== $channels;
    }

    /**
     * A function to check whether this notification preference is an overridden
     * record or not.
     *
     * @return bool
     */
    public function is_an_overridden_record(): bool {
        $ancestor_id = $this->get_ancestor_id();
        if (!empty($ancestor_id)) {
            return true;
        }

        // It does not have the ancestor's id. Therefore we going to need to check whether it
        // is a custom notification or not. If it is not a custom notification, then we are
        // overridding the system built in notification.
        return !$this->is_custom_notification();
    }
}