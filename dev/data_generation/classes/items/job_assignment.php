<?php
/*
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package dev
 */

namespace degeneration\items;

use core\orm\query\builder;
use degeneration\App;
use degeneration\Cache;
use totara_job\entity\job_assignment as job_assignment_entity;
use totara_job\job_assignment as core_job_assignment;

class job_assignment extends item {

    /**
     * id of the user
     * @var int|null
     */
    protected $user = null;

    /** @var int|null */
    protected $manager_id = null;

    /** @var int|null */
    protected $temp_manager_id = null;

    /** @var \DateTime|null */
    protected $temp_manager_expiry_date = null;

    /** @var int|null */
    protected $appraiser_id = null;

    /** @var position|null */
    protected $position = null;

    /** @var organisation|null */
    protected $organisation = null;

    /**
     * @var int
     */
    private $sort_order;

    /**
     * @var bool
     */
    private $use_bulk = false;

    /**
     * @var job_assignment|null
     */
    private $manager_job_assignment;

    /**
     * @var job_assignment|null
     */
    private $temp_manager_job_assignment;

    /**
     * Table name of the item to generate
     *
     * @return string
     */
    public function get_entity_class(): string {
        return job_assignment::class;
    }

    /**
     * Sets the manager for this job assignment
     *
     * @param int|null $manager_id
     * @return $this
     */
    public function set_manager_id(?int $manager_id): self {
        $this->manager_id = $manager_id;

        return $this;
    }

    /**
     * Sets the temp manager for this job assignment
     *
     * @param int|null $temp_manager_id
     * @return $this
     */
    public function set_temp_manager_id(?int $temp_manager_id): self {
        $this->temp_manager_id = $temp_manager_id;
        $this->temp_manager_expiry_date = App::faker()
            ->dateTimeBetween('now', '+30 days')
            ->getTimestamp();

        return $this;
    }

    /**
     * Sets the manager for this job assignment
     *
     * @param job_assignment|null $assignment
     * @return $this
     */
    public function set_manager_job_assignment(?job_assignment $assignment): self {
        $this->manager_job_assignment = $assignment;

        return $this;
    }

    /**
     * Sets the temp manager for this job assignment
     *
     * @param job_assignment|null $assignment
     * @return $this
     */
    public function set_temp_manager_job_assignment(?job_assignment $assignment): self {
        $this->temp_manager_job_assignment = $assignment;
        $this->temp_manager_expiry_date = App::faker()
            ->dateTimeBetween('now', '+30 days')
            ->getTimestamp();

        return $this;
    }

    /**
     * Sets the appraiser for this job assignment
     *
     * @param int|null $appraiser_id
     * @return $this
     */
    public function set_appraiser_id(?int $appraiser_id): self {
        $this->appraiser_id = $appraiser_id;

        return $this;
    }

    /**
     * Sets the position for this item
     *
     * @param position|null $position $position
     * @return $this
     */
    public function set_position(?position $position): self {
        $this->position = $position;

        return $this;
    }

    /**
     * Sets the organisation for this item
     *
     * @param organisation|null $organisation
     * @return $this
     */
    public function set_organisation(?organisation $organisation): self {
        $this->organisation = $organisation;

        return $this;
    }

    /**
     * @return $this
     */
    public function use_bulk(): self {
        $this->use_bulk = true;

        return $this;
    }

    /**
     * Save a user
     *
     * @return bool
     */
    public function save(): bool {
        if (empty($this->user)) {
            throw new \coding_exception('Need a user to create the job assignment for');
        }

        $properties = [
            'userid' => $this->user,
            'usermodified' => 0,
            'positionassignmentdate' => time(),
        ];

        foreach ($this->get_properties() as $key => $property) {
            $properties[$key] = $this->evaluate_property($property);
        }

        if (!empty($this->manager_job_assignment)) {
            $properties = array_merge($properties, ['managerjaid' => $this->manager_job_assignment->get_data('id')]);
        } else if (!empty($this->manager_id)) {
            $manager_ja = core_job_assignment::get_first($this->manager_id, false);
            if (empty($manager_ja)) {
                $manager_ja = self::for_user($this->manager_id)
                    ->set_sort_order(1)
                    ->save_and_return()
                    ->get_data();
            }

            $properties = array_merge($properties, ['managerjaid' => $manager_ja->id]);
        } else {
            $properties = array_merge($properties, ['managerjaid' => null]);
        }

        if (!empty($this->appraiser_id)) {
            $properties = array_merge($properties, ['appraiserid' => $this->appraiser_id]);
        } else {
            $properties = array_merge($properties, ['appraiserid' => null]);
        }

        if (!empty($this->temp_manager_job_assignment)) {
            $properties = array_merge(
                $properties,
                [
                    'tempmanagerjaid' => $this->temp_manager_job_assignment->get_data('id'),
                    'tempmanagerexpirydate' => $this->temp_manager_expiry_date
                ]
            );
        } else if (!empty($this->temp_manager_id)) {
            $temp_manager_ja = core_job_assignment::get_first($this->temp_manager_id, false);
            if (empty($temp_manager_ja)) {
                $temp_manager_ja = self::for_user($this->temp_manager_id)
                    ->set_sort_order(1)
                    ->save_and_return()
                    ->get_data();
            }

            $properties = array_merge(
                $properties,
                [
                    'tempmanagerjaid' => $temp_manager_ja->id,
                    'tempmanagerexpirydate' => $this->temp_manager_expiry_date
                ]
            );
        } else {
            $properties = array_merge(
                $properties,
                [
                    'tempmanagerjaid' => null,
                    'tempmanagerexpirydate' => null
                ]
            );
        }

        if (!empty($this->position)) {
            $properties = array_merge($properties, ['positionid' => $this->position->get_data()->id]);
        }

        if (!empty($this->organisation)) {
            $properties = array_merge($properties, ['organisationid' => $this->organisation->get_data()->id]);
        }

        if (!$this->use_bulk) {
            $sort_order = $this->sort_order;
            if (!empty($this->sort_order)) {
                $last_job = job_assignment_entity::repository()
                    ->where('userid', $this->user)
                    ->order_by('sortorder', 'desc')
                    ->first();

                if (empty($last_job)) {
                    $sort_order = 1;
                } else {
                    $sort_order = $last_job->sortorder + 1;
                }
            }
            $properties['sortorder'] = $sort_order;

            // The user might already have a job assignment
            /** @var job_assignment_entity $assignment */
            $assignment = job_assignment_entity::repository()
                ->where('userid', $this->user)
                ->where('sortorder', $sort_order)
                ->one();

            if (!empty($assignment)) {
                $assignment->managerjaid = $properties['managerjaid'] ?? null;
                $assignment->tempmanagerjaid = $properties['tempmanagerjaid'] ?? null;
                $assignment->tempmanagerexpirydate = $properties['tempmanagerexpirydate'] ?? null;
                $assignment->appraiserid = $properties['appraiserid'] ?? null;
                $assignment->positionid = $properties['positionid'] ?? null;
                $assignment->organisationid = $properties['organisationid'] ?? null;
                $assignment->save();
            } else {
                $assignment = new job_assignment_entity($properties);
                $assignment->save();
            }

            $this->data = $assignment;

            Cache::get()->add($this);
        } else {
            if (empty($this->sort_order)) {
                throw new \coding_exception('Missing sortorder for job assignment, make sure it has one if you use bulk.');
            }
            $properties['sortorder'] = $this->sort_order;
            $properties['timecreated'] = time();
            $properties['timemodified'] = time();
            $this->data = $properties;
        }

        return true;
    }

    /**
     * Get properties
     *
     * @return array
     */
    public function get_properties(): array {
        $name = $this->position
            ? 'Job \'' . $this->position->get_data('fullname') . '\''
            : App::faker()->jobTitle;
        return [
            'fullname' => $name,
            'shortname' => App::faker()->word,
            'description' => App::faker()->bs,
            'idnumber' => uniqid('job_')
        ];
    }

    /**
     * Set the sort order, if omitted it tries to automatically determine it
     *
     * @param int $sort_order
     * @return $this
     */
    public function set_sort_order(int $sort_order) {
        $this->sort_order = $sort_order;

        return $this;
    }

    /**
     * @param user|int $user_or_id
     * @return self
     */
    public static function for_user($user_or_id): self {
        $instance = new self();
        $instance->set_user($user_or_id);

        return $instance;
    }

    /**
     * Set the user for this job assignment
     *
     * @param user|int $user_or_id
     * @return $this
     */
    public function set_user($user_or_id): self {
        if ($user_or_id instanceof user) {
            $user_or_id = $user_or_id->get_data()->id;
        }
        $this->user = $user_or_id;

        return $this;
    }

    /**
     * Fill with preloaded data
     *
     * @param array $properties
     * @return job_assignment
     */
    public function fill(array $properties): self {
        $this->data = (object) $properties;

        return $this;
    }

}