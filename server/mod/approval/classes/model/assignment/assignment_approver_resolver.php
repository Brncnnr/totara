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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\model\assignment;

use coding_exception;
use context_system;
use core\entity\user;
use core\orm\collection;
use core\orm\entity\repository;
use core\orm\query\exceptions\record_not_found_exception;
use core\tenant_orm_helper;
use mod_approval\model\assignment\approver_type\relationship;
use mod_approval\model\assignment\approver_type\user as user_approver_type;
use totara_core\entity\relationship as relationship_entity;
use totara_core\relationship\relationship as relationship_model;
use totara_core\relationship\relationship_resolver_dto;
use totara_job\entity\job_assignment;

/**
 * Application Approver Resolver
 */
final class assignment_approver_resolver {
    /** @var array */
    private static $resolver_mapper = [
        relationship::TYPE_IDENTIFIER => 'resolve_relationship',
        user_approver_type::TYPE_IDENTIFIER => 'resolve_user',
    ];

    /** @var array */
    private $data;

    /**
     * Private constructor.
     *
     * @param array $data
     */
    private function __construct(array $data) {
        $this->data = $data;
    }

    /**
     * Create an instance for a user.
     *
     * @param integer $user_id The user id
     * @param integer|null $job_assignment_id The optional job assignment for the user
     * @return self
     */
    public static function from_user(int $user_id, ?int $job_assignment_id = null): self {
        $data = ['user_id' => $user_id];
        if ($job_assignment_id) {
            $data['job_assignment_id'] = $job_assignment_id;
        }
        return new self($data);
    }

    /**
     * Get a list of all the approver users provided by assignment approvers.
     *
     * @param collection|assignment_approver[] $approvers
     * @return collection|user[] as [id => user]
     */
    public function resolve($approvers): collection {
        $collection = new collection();
        foreach ($approvers as $approver) {
            try {
                $entity = $approver->get_approver_entity();
            } catch (record_not_found_exception $ex) {
                // Just swallow it.
                continue;
            }
            $method = self::$resolver_mapper[$approver->type] ?? false;
            if (!$method) {
                throw new coding_exception('Unknown approver type: ' . $approver->type);
            }
            $this->{$method}($entity, $collection);
        }
        return $collection;
    }

    /**
     * Add relevant user to the $users collection.
     *
     * @param user $entity
     * @param collection|user[] $users as [id => user]
     */
    private function resolve_user(user $entity, collection $users): void {
        if (!isset($users[$entity->id])) {
            $users->set($entity, $entity->id);
        }
    }

    /**
     * Add relevant users to the $users collection.
     *
     * @param relationship_model $model
     * @param collection|user[] $users as [id => user]
     */
    private function resolve_relationship(relationship_model $model, collection $users): void {
        /**
         * Wouldn't it be nice if we could use the manager relationship resolver?
         * But alas we cannot, because it doesn't know about temporary managers. :-(
         *
        $relationship_model = relationship::load_by_entity($entity);
        $relationship_resolver_dtos = $relationship_model->get_users($this->data, context_system::instance());
        $userids = relationship_resolver_dto::get_user_ids($relationship_resolver_dtos);
        foreach ($userids as $userid) {
            if (!isset($users[$userid])) {
                $users->set(new user($userid), $userid);
            }
        }
         */

        // Copied from manager resolver and extended.
        if ($model->idnumber == 'manager') {
            $managers = job_assignment::repository();
            if (!empty($this->data['job_assignment_id'])) {
                $managers->where('id', $this->data['job_assignment_id']);
            } else {
                $managers->where('userid', $this->data['user_id']);
            }
            $tempmanagers = clone $managers;
            $manager_ids = $managers->select_raw('DISTINCT manager_job.userid')
                ->join([job_assignment::TABLE, 'manager_job'], 'managerjaid', 'id')
                ->where_not_null('manager_job.userid')
                ->get();
            $tempmanager_ids = $tempmanagers->select_raw('DISTINCT tempmanager_job.userid')
                ->join([job_assignment::TABLE, 'tempmanager_job'], 'tempmanagerjaid', 'id')
                ->where('tempmanagerexpirydate', '>', time())
                ->where_not_null('tempmanager_job.userid')
                ->get();
            foreach ($tempmanager_ids as $item) {
                $manager_ids->append($item);
            }
            foreach ($manager_ids as $item) {
                if (!isset($users[$item->userid])) {
                    $users->set(new user($item->userid), $item->userid);
                }
            }
        } else {
            throw new coding_exception("Unimplemented approver relationship type");
        }
    }
}
