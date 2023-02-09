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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_engage
 */
namespace totara_engage\share;

use core_user\totara_engage\share\recipient\user;
use totara_engage\entity\share as share_entity;
use totara_engage\entity\share_recipient;
use totara_engage\entity\share_recipient as recipient_entity;
use totara_engage\event\share_created;
use totara_engage\exception\share_exception;
use totara_engage\repository\share_recipient_repository;
use totara_engage\repository\share_repository;
use totara_engage\share\recipient\manager as recipient_manager;
use totara_engage\share\recipient\recipient;
use totara_engage\task\share_notify_task;
use core\task\manager as task_manager;
use totara_engage\local\helper as engage_helper;

final class manager {

    /**
     * Create new database entries.
     *
     * @param int $itemid
     * @param int $ownerid
     * @param string $component
     * @param recipient[] $recipients
     * @param $contextid
     * @param int|null $sharerid
     * @return share[]
     */
    private static function create(int $itemid, int $ownerid, string $component, array $recipients,
                                   $contextid, int $sharerid = null): array {
        global $USER;
        $sharerid = $sharerid ?? $USER->id;

        $share_entity = static::create_share($itemid, $ownerid, $component, $contextid);

        $shares = [];
        foreach ($recipients as $recipient) {
            $recipient_entity = static::create_recipient($share_entity->id, $sharerid, $recipient);
            $shares[] = new share($share_entity, $recipient_entity);
        }

        return $shares;
    }

    /**
     * This is the main sharing function that will validate the instance that wants to be shared
     * and if it's sharable then a share will be created. The post-share functionality will also be
     * executed.
     *
     * @param shareable $instance
     * @param string $component
     * @param recipient[] $recipients
     * @param int|null $actor_id
     * @return int
     */
    public static function share(shareable $instance, string $component,
                                 array $recipients, ?int $actor_id = null): int {
        global $USER;

        if (empty($actor_id)) {
            $actor_id = $USER->id;
        }

        // First check if user is allowed to share this instance.
        if (!$instance->can_share($actor_id)) {
            throw new share_exception('error:sharecapability', $component);
        }

        // Get the shareable state of the instance.
        $sharable = $instance->get_shareable();
        if (!$sharable->is_shareable()) {
            throw new share_exception($sharable->get_reason(), $component);
        }

        // Validate recipients
        foreach ($recipients as $recipient) {
            $recipient->validate();
            $recipient->validate_against_share_item($instance);
            $recipient->validate_against_actor($actor_id);
        }

        // Get context in which instance is being shared.
        $context = $instance->get_context();

        // Create shares for this resource.
        $shares = self::create($instance->get_id(), $instance->get_userid(), $component, $recipients, $context->id);

        foreach ($shares as $share) {
            if ($share->get_recipient_component() === 'core_user' && $share->get_recipient_area() === 'user') {
                $task = new share_notify_task();
                $task->set_component('totara_engage');
                $task->set_custom_data(
                    [
                        'component' => helper::get_provider_type($share->get_component()),
                        'recipient_id' => $share->get_recipient_instanceid(),
                        'sharer_id' => $share->get_sharer_id(),
                        'item_name' => helper::get_resource_name($share->get_component(), $share->get_item_id()),
                        'share_component' => $share->get_component(),
                        'item_id' => $share->get_item_id(),
                    ]
                );

                task_manager::queue_adhoc_task($task);

            } else {
                // Trigger share event for workspace.
                share_created::from_share($share)->trigger();
            }
        }

        if (empty($shares)) {
            // The process should have crashed earlier but this is just in case.
            throw new \coding_exception('Failed creating shares');
        }

        // Do any post share stuff.
        foreach ($shares as $share) {
            $instance->shared($share);
        }

        // Re-share occurs if the user isn't the same as the resource owner
        if ($instance->can_reshare($actor_id)) {
            $instance->reshare($actor_id);
        }

        // Return the share id.
        return reset($shares)->get_id();
    }

    /**
     * @param shareable $item
     * @param recipient $recipient
     * @param int|null $actor_id
     * @return int
     */
    public static function share_to_recipient(shareable $item, recipient $recipient, ?int $actor_id = null): int {
        $component = engage_helper::get_component_name($item);
        return self::share($item, $component, [$recipient], $actor_id);
    }

    /**
     * Retrieve share or otherwise create a new share.
     *
     * @param int $itemid
     * @param int $ownerid
     * @param string $component
     * @param int $contextid
     * @return share_entity
     */
    private static function create_share(int $itemid, int $ownerid, string $component, int $contextid): share_entity {
        /** @var share_repository $repo */
        $repo = share_entity::repository();
        $share_entity = $repo->get_share($itemid, $component);
        if (empty($share_entity)) {
            $share_entity = new share_entity();
            $share_entity->itemid = $itemid;
            $share_entity->ownerid = $ownerid;
            $share_entity->component = $component;
            $share_entity->contextid = $contextid;
            $share_entity->save();
        }
        return $share_entity;
    }

    /**
     * Create a new recipient. as recipients can have multiple sharers with same shareid, we do not need to check
     * recipient exits or not.
     *
     * @param int $shareid
     * @param int $sharerid
     * @param recipient $recipient
     * @return recipient_entity
     */
    private static function create_recipient(int $shareid, int $sharerid, recipient $recipient): recipient_entity {
        /** @var share_recipient_repository $repo */
        $repo = recipient_entity::repository();
        $recipient_entity = $repo->get_recipient_by_visibility(
            $shareid, $recipient->get_id(), $recipient->get_area(), $recipient->get_component()
        );

        if (empty($recipient_entity)) {
            $recipient_entity = new recipient_entity();
            $recipient_entity->shareid = $shareid;
            $recipient_entity->sharerid = $sharerid;
            $recipient_entity->instanceid = $recipient->get_id();
            $recipient_entity->area = $recipient->get_area();
            $recipient_entity->component = $recipient->get_component();
            $recipient_entity->visibility = share::VISIBILITY_VISIBLE;
            $recipient_entity->notified = share::NOT_NOTIFIED;
            $recipient_entity->save();
        }

        return $recipient_entity;
    }

    /**
     * @param shareable $instance
     * @param string $component
     * @return share_entity|null
     */
    public static function get_share(shareable $instance, string $component): ?share_entity {
        /** @var share_repository $repo */
        $repo = share_entity::repository();
        return $repo->get_share($instance->get_id(), $component);
    }

    /**
     * Clone shares from one item to another.
     *
     * @param shareable $instance
     * @param string $component
     * @param int $fromid
     */
    public static function clone_shares(shareable $instance, string $component, int $fromid): void {
        /** @var share_repository $repo_share */
        $repo_share = share_entity::repository();
        /** @var share_recipient_repository $repo_recipient */
        $repo_recipient = recipient_entity::repository();

        // Get share for instance.
        $share = $repo_share->get_share($instance->get_id(), $component);

        // If instance is shared already we just need to clone the differences,
        // otherwise just clone all the recipients onto the resource.
        if (!empty($share)) {
            $recipients = $repo_recipient->get_difference($fromid, $share->id);
        } else {
            $recipients = $repo_recipient->get_recipients($fromid);
        }

        // Clone the recipients onto the instance.
        if (!empty($recipients)) {
            $recipients = recipient_manager::create_from_entity($recipients);
            self::share($instance, $component, $recipients);
        }
    }

    /**
     * @param int $id
     * @param string $component
     */
    public static function delete(int $id, string $component): void {
        /** @var share_repository $repo */
        $share_repo = share_entity::repository();

        /** @var share_recipient_repository $repo */
        $recipient_repo = recipient_entity::repository();

        $entity = $share_repo->get_share($id, $component);

        // The resource has not been shared.
        if (empty($entity)) {
            return;
        }

        $share_repo->delete_share_by_id($entity->id);
        $recipient_repo->delete_recipient_by_shareid($entity->id);
    }

    /**
     * Hide visibility for the recipient.
     *
     * @param int $recipient_id
     * @param shareable $instance
     */
    public static function unshare(int $recipient_id, shareable $instance): void {
        // Confirm that the recipient directly relates to the item being shared.
        /** @var share_entity $share */
        $share = share_entity::repository()->get_share($instance->get_id(), $instance::get_resource_type());
        if (!$share->recipients()
            ->where('id', $recipient_id)
            ->where('visibility', 1)
            ->exists()
        ) {
            throw new \coding_exception('Invalid recipient_id for shared item');
        }

        /** @var share_recipient_repository $repo */
        $repo = recipient_entity::repository();
        /** @var share_recipient $entity */
        $recipient = $repo->get_recipient_by_id($recipient_id);

        if (empty($recipient)) {
            throw new \coding_exception("No recipient with {$recipient_id} is found");
        }

        if ($recipient->area !== user::AREA) {
            /** @var recipient $share_recipient */
            $share_recipient = \totara_engage\share\recipient\helper::get_recipient_class($recipient->component, $recipient->area);
            $share_recipient = new $share_recipient($recipient->instanceid);
            if (!$share_recipient->can_unshare_resources()) {
                throw new share_exception('error:sharecapability', $instance::get_resource_type());
            }
        } else {
            if (!$instance->can_unshare($recipient->sharerid, $recipient->area !== user::AREA)) {
                throw new share_exception('error:sharecapability', $instance::get_resource_type());
            }
        }

        $recipient->visibility = 0;
        $recipient->update();
    }
}