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
 */

namespace mod_perform\notification;

use coding_exception;
use core\orm\collection;
use mod_perform\entity\activity\manual_relationship_selection_progress;
use mod_perform\entity\activity\subject_instance as subject_instance_entity;
use mod_perform\models\activity\activity as activity_model;
use mod_perform\models\activity\notification as notification_model;
use mod_perform\models\activity\notification_recipient as notification_recipient_model;
use mod_perform\models\activity\subject_instance as subject_instance_model;
use totara_core\advanced_feature;
use totara_core\relationship\relationship;

/**
 * The dealer_participant_selection class.
 * @deprecated Since Totara 17.0
 */
class dealer_participant_selection extends dealer {
    /** @var subject_instance_entity[] */
    private $subject_instances;

    /**
     * Constructor. *Do not instantiate this class directly. Use the factory class.*
     *
     * @param subject_instance_entity[] $subject_instances
     * @throws coding_exception
     * @internal
     * @deprecated Since Totara 17.0
     */
    public function __construct(array $subject_instances) {
        debugging('Do not use dealer_participant_selection any more, perform now uses centralised notifications', DEBUG_DEVELOPER);
        if (!empty(array_filter($subject_instances, function ($x) {
            return !($x instanceof subject_instance_entity);
        }))) {
            throw new coding_exception('subject_instances must be an array of subject_instance entities');
        }
        $collection = new collection($subject_instances);
        subject_instance_entity::repository()
            ->with('manual_relationship_selection_progress.manual_relationship_selectors.user')
            ->load_relations($collection);
        $this->subject_instances = $collection;
    }

    /**
     * @inheritDoc
     * @deprecated since Totara 17.0
     */
    public function dispatch(string $class_key): void {
        debugging('Do not use dispatch any more, perform now uses centralised notifications', DEBUG_DEVELOPER);
        foreach ($this->subject_instances as $instance) {
            $activity = activity_model::load_by_entity($instance->activity());
            $notification = notification_model::load_by_activity_and_class_key($activity, $class_key);
            $mailer = factory::create_mailer_on_notification($notification);
            if (!$mailer) {
                // The notification is not active, recipients are not set, etc.
                continue;
            }
            $placeholders = placeholder::from_subject_instance(subject_instance_model::load_by_entity($instance));
            $recipients = notification_recipient_model::load_by_notification($notification, true);
            /** @var relationship[] $relationships */
            $relationships = [];
            foreach ($recipients as $recipient) {
                $relationships[$recipient->core_relationship_id] = $recipient->relationship;
            }
            $already_sent_to_selector_ids = [];
            foreach ($instance->manual_relationship_selection_progress as $progress) {
                // Don't send out notification for users who already selected
                if ($progress->status != manual_relationship_selection_progress::STATUS_PENDING) {
                    continue;
                }

                $hide_suspended = get_config(null, 'perform_hide_suspended_users');

                $selector_relationship = $relationships[$progress->manual_relationship_selection->selector_relationship_id] ?? false;
                if ($selector_relationship) {
                    foreach ($progress->manual_relationship_selectors as $selector) {
                        if ($hide_suspended && $selector->user->suspended) {
                            continue;
                        }

                        // Only send notification if user is not deleted and notification hasn't been sent yet
                        if (!$selector->notified_at && $selector->user->deleted == 0) {
                            $placeholders->set_participant($selector->user, $selector_relationship);

                            // Only send once per subject_instance to the same selector. Always mark as notified.
                            if (!in_array($selector->user_id, $already_sent_to_selector_ids)) {
                                $mailer->post($selector->user, $selector_relationship, $placeholders);
                                $already_sent_to_selector_ids[] = $selector->user_id;
                            }

                            $selector->notified_at = time();
                            $selector->save();
                        }
                    }
                }
            }
        }
    }
}
