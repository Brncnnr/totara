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
namespace totara_notification\manager;

use coding_exception;
use core\orm\query\builder;
use null_progress_trace;
use progress_trace;
use Throwable;
use totara_notification\entity\notifiable_event_queue;
use totara_notification\loader\notification_preference_loader;
use totara_notification\local\helper;
use totara_notification\manager\notification_queue_manager;
use totara_notification\resolver\resolver_helper;

class event_queue_manager {
    /**
     * @var progress_trace
     */
    private $trace;

    /**
     * event_queue_manager constructor.
     * @param progress_trace|null $trace
     */
    public function __construct(?progress_trace $trace = null) {
        $this->trace = $trace ?? new null_progress_trace();
    }

    /**
     * @return void
     */
    public function process_queues(): void {
        $repository = notifiable_event_queue::repository();
        $all_queues = $repository->get_lazy();

        /** @var notifiable_event_queue $queue */
        foreach ($all_queues as $queue) {
            try {
                builder::get_db()->transaction(function () use ($queue) {
                    if (!resolver_helper::is_valid_event_resolver($queue->resolver_class_name)) {
                        throw new coding_exception(
                            "The resolver class name is not a notifiable event resolver: '{$queue->resolver_class_name}'"
                        );
                    }

                    $resolver_class_name = $queue->resolver_class_name;
                    $extended_context = $queue->get_extended_context();
                    $is_additional_criteria_resolver = resolver_helper::is_additional_criteria_resolver($resolver_class_name);

                    if (helper::is_resolver_disabled_by_any_context(
                        $resolver_class_name,
                        $extended_context
                    )) {
                        // Remove the item from the queue, even though it was not processed, because events that occur
                        // when a resolver is disabled should not be processed, and we do not want to try to process
                        // the event again.
                        $queue->delete();
                        return;
                    }

                    $preferences = notification_preference_loader::get_notification_preferences(
                        $queue->get_extended_context(),
                        $queue->resolver_class_name
                    );

                    foreach ($preferences as $preference) {
                        try {
                            if (!$preference->is_on_event()) {
                                // Skip those notification preference that are not set for on event.
                                continue;
                            }

                            //Check the status from additional criteria.
                            if ($is_additional_criteria_resolver) {
                                $event_data = $queue->get_decoded_event_data();
                                $raw_additional_criteria = $preference->get_additional_criteria();

                                if (!helper::needs_notification($raw_additional_criteria, $event_data, $resolver_class_name, $extended_context)) {
                                    continue;
                                }
                            }

                            // Attempt to send the messages.
                            $notif_queue_manager = new notification_queue_manager($this->trace);
                            $notif_queue_manager->dispatch_from_preference(
                                $preference,
                                $queue->get_decoded_event_data(),
                                $queue->time_created
                            );
                        } catch (Throwable $e) {
                            // If an exception occurred, the queued event will remain in the queue and will be processed again later.
                            $this->trace->output(
                                "Error sending preference with id {$preference->get_id()} event queue {$queue->id}: {$e->getMessage()}"
                            );
                        }
                    }

                    // Remove the item from the queue, even if a notification was not queued, because events only trigger
                    // notifications that are available at the time the event occurs, and we do not want to try to
                    // process the event again.
                    $queue->delete();
                });
            } catch (Throwable $e) {
                // If any thowable event occurred, the queued event will remain in the queue and will be processed again later.
                $this->trace->output(
                    "Cannot send notification event queue record with id '{$queue->id}': {$e->getMessage()}"
                );
            }
        }

        $all_queues->close();
    }
}
