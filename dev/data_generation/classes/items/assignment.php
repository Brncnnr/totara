<?php
/*
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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package totara_competency
 */

namespace degeneration\items;

use totara_competency\entity\assignment as assignment_entity;
use degeneration\App;
use totara_competency\user_groups;

/**
 * Class competency
 *
 * @method assignment_entity get_data()
 *
 * @package degeneration\items
 */
class assignment extends item {

    /**
     * Assignment status
     *
     * @var int
     */
    protected $status = 1;

    /**
     * Competency assignment type
     *
     * @var null
     */
    protected $type = null;

    /**
     * Competency assignment type
     *
     * @var null
     */
    protected $id = null;

    /**
     * Competency assignment competency
     *
     * @var null|competency
     */
    protected $competency = null;

    /**
     * User group to assign
     *
     * @var null|int
     */
    protected $user_group = null;

    /**
     * User group type
     *
     * @var null|int
     */
    protected $user_group_type = null;

    /**
     * User group id
     *
     * @var null|int
     */
    protected $user_group_id = null;

    /**
     * Set assignment type
     *
     * @param string $type
     * @return $this
     */
    public function set_type(string $type) {
        $this->type = $type;

        return $this;
    }

    /**
     * Set user group type
     *
     * @param string $type
     * @return $this
     */
    public function set_user_group_type(string $type) {
        $this->user_group_type = $type;

        return $this;
    }

    /**
     * Set user group type
     *
     * @param int $id
     * @return $this
     */
    public function set_user_group_id(int $id) {
        $this->user_group_id = $id;

        return $this;
    }

    /**
     * Set assigned competency
     *
     * @param competency $competency
     * @return $this
     */
    public function set_competency(competency $competency) {
        $this->competency = $competency;

        return $this;
    }

    /**
     * Create assignment for a user group
     *
     * @param item $user_group
     * @return $this
     */
    public function for(item $user_group) {

        switch (get_class($user_group)) {
            case organisation::class:
                $this->set_user_group_type(user_groups::ORGANISATION);
                break;

            case position::class:
                $this->set_user_group_type(user_groups::POSITION);
                break;

            case user::class:
                $this->set_user_group_type(user_groups::USER);
                break;

            case audience::class:
                $this->set_user_group_type(user_groups::COHORT);
                break;

            default:
                throw new \Exception('Invalid item passed, cannot create an assignment for it');
        }

        $this->set_user_group_id($user_group->get_data('id'));

        return $this;
    }

    /**
     * Get assignment type
     *
     * @return string|null
     */
    public function get_type(): ?string {
        return $this->type;
    }

    /**
     * Get assignment status
     *
     * @return int
     */
    public function get_status(): int {
        return $this->status;
    }

    /**
     * Get entity class
     *
     * @return string|null
     */
    public function get_entity_class(): ?string {
        return assignment_entity::class;
    }

    /**
     * Get list of properties to be added to the generated item
     *
     * @return array
     */
    public function get_properties(): array {
        return [
            'competency_id' => $this->competency->get_data()->id ?? null,
            'user_group_type' => $this->user_group_type,
            'user_group_id' => $this->user_group_id,
            'type' => $this->get_type() ?? assignment_entity::TYPE_ADMIN,
            'status' => $this->get_status(),
            'optional' => '0',
            'created_by' => get_admin()->id,
            'created_at' => time(),
            'updated_at' => time(),
        ];
    }

    /**
     * Save assignment
     *
     * @return bool
     */
    public function save(): bool {

        $properties = $this->get_properties();

        if (empty($properties['competency_id'])) {
            throw new \Exception('Competency is required to create an assignment');
        }

        if (empty($properties['user_group_type'])) {
            throw new \Exception('User group type is required to create an assignment');
        }

        if (empty($properties['user_group_id'])) {
            throw new \Exception('User group id is required to create an assignment');
        }

        return parent::save();
    }

    /**
     * Get a totara competency assignment generator
     *
     * @return \\totara_competency\testing\assignment_generator
     */
    public function generator() {
        return App::competency_generator()->assignment_generator();
    }
}