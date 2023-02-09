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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_notification
 */
namespace totara_notification\builder;

use coding_exception;
use invalid_parameter_exception;
use totara_core\extended_context;
use totara_notification\delivery\channel_helper;
use totara_notification\model\notification_preference;
use totara_notification\entity\notification_preference as entity;
use totara_notification\resolver\abstraction\additional_criteria_resolver;
use totara_notification\resolver\abstraction\scheduled_event_resolver;
use totara_notification\resolver\notifiable_event_resolver;
use totara_notification\resolver\resolver_helper;
use totara_notification\schedule\schedule_after_event;
use totara_notification\schedule\schedule_before_event;

/**
 * Treat the builder like a placeholder instance that should be used per one
 * notification preference only.
 *
 * If you would want to modify (upgrade/create) another notification preference,
 * please bring up a new instance of the builder.
 */
class notification_preference_builder {
    /**
     * @var array
     */
    private $record_data;

    /**
     * notification_preference_builder constructor.
     * @param string $resolver_class_name
     * @param extended_context $extended_context
     */
    public function __construct(string $resolver_class_name, extended_context $extended_context) {
        if (!is_subclass_of($resolver_class_name, notifiable_event_resolver::class)) {
            throw new invalid_parameter_exception('Class provided is not a notification resolver!');
        }

        $this->record_data = [
            'resolver_class_name' => $resolver_class_name,
            'context_id' => $extended_context->get_context_id(),
            'component' => $extended_context->get_component(),
            'area' => $extended_context->get_area(),
            'item_id' => $extended_context->get_item_id(),
        ];
    }

    /**
     * @param int $preference_id
     * @return notification_preference_builder
     */
    public static function from_exist(int $preference_id): notification_preference_builder {
        return static::from_exist_model(
            notification_preference::from_id($preference_id)
        );
    }

    /**
     * @param notification_preference $notification_preference
     * @return notification_preference_builder
     */
    public static function from_exist_model(notification_preference $notification_preference): notification_preference_builder {
        $builder = new static(
            $notification_preference->get_resolver_class_name(),
            $notification_preference->get_extended_context()
        );

        $builder->record_data['id'] = $notification_preference->get_id();
        return $builder;
    }

    /**
     * By setting this value to NULL, you are more likely to reset the notification record to
     * fallback to the ancestor notification preference, if it has any.
     *
     * Otherwise error will be thrown, for those notification preference that does not have ancestor or parents.
     *
     * @param string|null $body
     * @return void
     */
    public function set_body(?string $body): void {
        $this->record_data['body'] = $body;
    }

    /**
     * @param int|null $body_format
     * @return void
     */
    public function set_body_format(?int $body_format): void {
        $this->record_data['body_format'] = $body_format;
    }

    /**
     * By setting this value to NULL, you are more likely to reset the notification record to
     * fallback to the ancestor notification preference, if it has any.
     *
     * Otherwise error will be thrown, for those notification preference that does not have ancestor or parents.
     *
     * @param string|null $subject
     * @return void
     */
    public function set_subject(?string $subject): void {
        $this->record_data['subject'] = $subject;
    }

    /**
     * @param int|null $subject_format
     * @return void
     */
    public function set_subject_format(?int $subject_format): void {
        $this->record_data['subject_format'] = $subject_format;
    }

    /**
     * By setting this value to NULL, you are more likely to reset the notification record to
     * fallback to the ancestor notification preference, if it has any.
     *
     * Otherwise error will be thrown, for those notification preference that does not have ancestor or parents.
     *
     * @param string|null $title
     * @return void
     */
    public function set_title(?string $title): void {
        $this->record_data['title'] = $title;
    }

    /**
     * By setting this value to NULL, you are more likely to reset the notification record to
     * fallback to the ancestor notification preference, if it has any.
     *
     * Additional criteria can be null.
     *
     * @param string|null $additional_criteria
     * @return void
     */
    public function set_additional_criteria(?string $additional_criteria): void {
        if (!is_null($additional_criteria)) {
            $decoded_additional_criteria = @json_decode(
                $additional_criteria,
                true,
                32,
                JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE | JSON_BIGINT_AS_STRING
            );
            if (!is_array($decoded_additional_criteria)) {
                throw new invalid_parameter_exception('json decoding failed');
            }

            $resolver_class_name = $this->record_data['resolver_class_name'];
            if (resolver_helper::is_additional_criteria_resolver($resolver_class_name)) {
                $extended_context = extended_context::make_with_id(
                    $this->record_data['context_id'],
                    $this->record_data['component'],
                    $this->record_data['area'],
                    $this->record_data['item_id']
                );

                /** @var additional_criteria_resolver $resolver_class_name */
                if (!$resolver_class_name::is_valid_additional_criteria($decoded_additional_criteria, $extended_context)) {
                    throw new invalid_parameter_exception('additional_criteria is not valid');
                }
            } else {
                if (!empty($decoded_additional_criteria)) {
                    throw new invalid_parameter_exception('additional_criteria should be null or empty array for this resolver');
                }
            }
        }

        $this->record_data['additional_criteria'] = $additional_criteria;
    }

    /**
     * By setting this value to NULL, you are more likely to reset the notification record to
     * fallback to the ancestor notification preference, if it has any.
     *
     * This must be the raw offset value (for example, a negative value for a before_event).
     *
     * @param int|null $offset
     * @return void
     */
    public function set_schedule_offset(?int $offset): void {
        if ($offset == 0) {
            $this->record_data['schedule_offset'] = $offset;
            return;
        }

        $resolver_class = $this->record_data['resolver_class_name'];
        if (!is_subclass_of($resolver_class, scheduled_event_resolver::class)) {
            throw new invalid_parameter_exception('Only on-event schedule is allowed for this resolver: ' . $resolver_class);
        }

        /** @var scheduled_event_resolver $resolver_class */
        $available_schedules = $resolver_class::get_notification_available_schedules();

        if ($offset > 0 && !in_array(schedule_after_event::class, $available_schedules)) {
            throw new invalid_parameter_exception('After schedule not allowed for this resolver');
        }

        if ($offset < 0 && !in_array(schedule_before_event::class, $available_schedules)) {
            throw new invalid_parameter_exception('Before schedule not allowed for this resolver');
        }

        $this->record_data['schedule_offset'] = $offset;
    }

    /**
     * @param int|null $ancestor_id
     * @return void
     */
    public function set_ancestor_id(?int $ancestor_id): void {
        if (isset($this->record_data['id'])) {
            debugging(
                "Do not set the ancestor's id of notification preference when updating a record",
                DEBUG_DEVELOPER
            );

            return;
        }

        $this->record_data['ancestor_id'] = $ancestor_id;
    }

    /**
     * @param string|null $notification_class_name
     * @return void
     */
    public function set_notification_class_name(?string $notification_class_name): void {
        $this->record_data['notification_class_name'] = $notification_class_name;
    }

    /**
     * @param string|null $recipient
     * @return void
     * @deprecated since Totara 17.0
     */
    public function set_recipient(?string $recipient): void {
        debugging('Do not use notification_preference_builder::set_recipient any more, use notification_preference_builder::set_recipients instead', DEBUG_DEVELOPER);
        if (empty($recipient)) {
            $recipients = [];
            if (is_null($recipient)) {
                $recipients = null;
            }
        } else {
            $recipients = [$recipient];
        }
        $this->set_recipients($recipients);
    }

    /**
     * @param array|null $recipients
     * @return void
     */
    public function set_recipients(?array $recipients): void {
        if (is_array($recipients)) {
            $this->record_data['recipients'] = json_encode($recipients);
            return;
        }
        $this->record_data['recipients'] = $recipients;
    }

    /**
     * @param bool|null $enabled
     * @return void
     */
    public function set_enabled(?bool $enabled): void {
        $this->record_data['enabled'] = $enabled;
    }

    /**
     * Passing $delivery_channels as null to tell the system that we are going to inherit from the
     * parent notification preference.
     *
     * @param string[]|null $delivery_channels  An array of the delivery channel's identifier.
     * @return void
     */
    public function set_forced_delivery_channels(?array $delivery_channels): void {
        if (null !== $delivery_channels) {
            // Start validating the delivery channels class name.

            foreach ($delivery_channels as $identifier) {
                if (!channel_helper::is_valid_delivery_channel($identifier)) {
                    throw new coding_exception(
                        "The delivery channel '{$identifier}' is not a valid delivery channel identifier"
                    );
                }
            }
        }

        $this->record_data['forced_delivery_channels'] = $delivery_channels;
    }

    /**
     * Parameter should safe us from modifying data via references.
     * Note that this function is set out to be static, because we would not want this function to
     * interact with any instance's data of the class at all. Treat it like a helper function
     *
     * @param array $record_data
     * @return array
     */
    protected static function prepare_data_for_create_new(array $record_data): array {
        $extended_context = extended_context::make_with_id(
            $record_data['context_id'],
            $record_data['component'],
            $record_data['area'],
            $record_data['item_id'],
        );

        if (isset($record_data['ancestor_id'])) {
            // If the ancestor's id is set, we should check whether this notification preference
            // is created within system context or not.
            if (CONTEXT_SYSTEM == $extended_context->get_context()->contextlevel) {
                throw new coding_exception(
                    "The ancestor's id should not be set when the context is in system"
                );
            }

            $ancestor = notification_preference::from_id($record_data['ancestor_id']);
            $ancestor_extended_context = $ancestor->get_extended_context();

            // Check that the ancestor is a natural context - extended contexts can't have children contexts.
            if (!$ancestor_extended_context->is_natural_context()) {
                throw new coding_exception(
                    "It is not possible for a non-natural context to have children, so all ancestors must be natural contexts"
                );
            }

            // Check if the context path of the ancestor is in the context path of this very notification
            // preference that we are trying to create.
            $ancestor_context_path = $ancestor_extended_context->get_context()->path; // The ancestor must be a natural context.
            $current_context_path = $extended_context->get_context()->path; // May or may not be the same as the extended context.

            if (0 !== stripos($current_context_path, $ancestor_context_path)) {
                // If the current context path does not contain the ancestor context path at the
                // start of the string then we are overriding a notification preference that reference
                // ancestor at some path that does not go to this very path.
                throw new coding_exception(
                    "The context path of ancestor does not appear in the context path of the overridden preference"
                );
            }

            if (empty($record_data['notification_class_name'])) {
                // Time to find out the notification's name based on the ancestor.
                $record_data['notification_class_name'] = $ancestor->get_notification_class_name();
            }

            if (isset($record_data['title'])) {
                throw new coding_exception(
                    "For overriding notification preference, the field 'title' must not be overridden."
                );
            }
        }

        return $record_data;
    }

    /**
     * @param array $record_data
     * @return array
     */
    protected static function prepare_data_for_update(array $record_data): array {
        if (isset($record_data['ancestor_id'])) {
            // For updating data, we do not allow to update the ancestor's id.
            unset($record_data['ancestor_id']);
        }

        if (isset($record_data['notification_class_name'])) {
            // For updating data, we do not allow to update the built in notification's class name.
            unset($record_data['notification_class_name']);
        }

        unset($record_data['id']);
        return $record_data;
    }

    /**
     * Either we are upgrading the existing record or create a new record.
     *
     * Note that this function will not do any sort of smart check
     * regard to the existence of the record. Please do the check before
     * this function is called.
     *
     * @return notification_preference
     */
    public function save(): notification_preference {
        $entity = new entity();
        if (!isset($this->record_data['id'])) {
            // Create a new record data.
            $record_data = self::prepare_data_for_create_new($this->record_data);

            if (!isset($record_data['notification_class_name']) && !isset($record_data['ancestor_id'])) {
                // When the notification preference is for the custom, meaning that when the notification_class_name
                // is not provided. Hence the fields below will be required by the business logic.
                $required_fields = [
                    'body',
                    'body_format',
                    'subject',
                    'title',
                    'schedule_offset',
                    'subject_format',
                    ['recipient', 'recipients'], // can be either - recipient is deprecated but needs to be checked until removed
                    'enabled'
                ];

                foreach ($required_fields as $required_field) {
                    if (is_array($required_field)) {
                        $found = false;
                        foreach ($required_field as $alternative) {
                            if (isset($record_data[$alternative])) {
                                $found = true;
                            }
                        }
                        if (!$found) {
                            $alternative_fields = implode(', ',$required_field);
                            throw new coding_exception("When creating a new record one of the following fields are required: '{$alternative_fields}'");
                        }
                        continue;
                    }
                    if (!isset($record_data[$required_field]) || '' === $record_data[$required_field]) {
                        throw new coding_exception("When creating a new record the following field is required: '{$required_field}'");
                    }
                }
            }
        } else {
            $record_data = self::prepare_data_for_update($this->record_data);
            $entity->set_id_attribute($this->record_data['id']);

            // We need to instantiate the model to do several checks beforehand.
            $entity->refresh();
            $notification_preference = notification_preference::from_entity($entity);

            if ($notification_preference->is_custom_notification() && !$notification_preference->has_parent()) {
                $text_fields = [
                    'body',
                    'subject',
                    'title',
                    ['recipient', 'recipients'], // recipient is deprecated but needs to be checked until removed
                ];

                foreach ($text_fields as $field) {
                    if (is_array($field)) {
                        $passed = true;
                        foreach ($field as $alternative) {
                            if (array_key_exists($alternative, $this->record_data) && empty($record_data[$alternative])) {
                                $passed = false;
                            }
                        }
                        if (!$passed) {
                            $alternative_fields = implode(', ', $field);
                            throw new coding_exception(
                                "Cannot reset the fields '{$alternative_fields}' for custom notification that does not have parent(s)"
                            );
                        }
                        continue;
                    }
                    if (array_key_exists($field, $this->record_data) && empty($record_data[$field])) {
                        throw new coding_exception(
                            "Cannot reset the field '{$field}' for custom notification that does not have parent(s)"
                        );
                    }
                }

                // Special treatment for these keys', because the value of these fields
                // can be set to zero or FALSE, which it can make the validation go wrong easily.
                $special_keys = [
                    'body_format',
                    'subject_format',
                    'schedule_offset',
                    'enabled'
                ];

                foreach ($special_keys as $special_key) {
                    if (array_key_exists($special_key, $this->record_data) && null === $this->record_data[$special_key]) {
                        throw new coding_exception(
                            "Cannot reset the field '{$special_key}' for custom " .
                            "notification that does not have parent(s)"
                        );
                    }
                }
            }
        }

        if (isset($record_data['forced_delivery_channels'])) {
            // Attribute 'forced_delivery_channels' is treated differently from the rest attributes.
            $entity->set_decoded_forced_delivery_channels($record_data['forced_delivery_channels']);

            // Remove the attribute from the record_data, so that we don't re-set it again.
            unset($record_data['forced_delivery_channels']);
        }

        foreach ($record_data as $k => $v) {
            $entity->set_attribute($k, $v);
        }

        $entity->save();
        $entity->refresh();

        return notification_preference::from_entity($entity);
    }
}