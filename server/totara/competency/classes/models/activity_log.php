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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\models;

use core\orm\entity\entity;
use totara_competency\models\assignment as assignment_model;

/**
 * Class activity_log_data
 *
 * Represents data relating to a single row in the activity log.
 */
abstract class activity_log {

    /**
     * The entity that this item of data relates to.
     *
     * @var entity|null
     */
    private $entity;

    /**
     * Load an instance of this model using data from the entity passed in.
     *
     * @param entity $entity
     * @return activity_log
     */
    abstract public static function load_by_entity(entity $entity): activity_log;

    /**
     * @return entity
     */
    public function get_entity(): entity {
        return $this->entity;
    }

    /**
     * @param entity $entity
     */
    protected function set_entity(entity $entity): activity_log {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Timestamp of the date corresponding to this data.
     *
     * @return int
     */
    abstract public function get_date(): int;

    /**
     * Override this method if a non-null proficient status can be returned for a given child class.
     *
     * @return bool|null True if proficient. False if not. Null if proficiency is not relevant here.
     */
    public function get_proficient_status(): ?bool {
        return null;
    }

    /**
     * @return assignment_model|null
     */
    public function get_assignment(): ?assignment_model {
        if (!$this->entity->relation_exists('assignment')) {
            throw new \coding_exception('The entity used for activity log has to have an assignment relation');
        }
        $assignment = $this->entity->assignment;
        if (!empty($assignment)) {
            return assignment_model::load_by_entity($assignment);
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function get_assignment_action(): ?string {
        return null;
    }

    /**
     * Get the human-readable description for this activity log entry.
     *
     * @return string
     */
    abstract public function get_description(): string;

    /**
     * Get a comment string for an activity log entry.
     *
     * Override this in the subclasses if needed.
     *
     * @return string
     */
    public function get_comment(): string {
        return '';
    }

    /**
     * @param string $field
     * @return mixed
     */
    public function get_field(string $field) {
        switch ($field) {
            case 'timestamp':
                return $this->get_date();
            case 'description':
                return $this->get_description();
            case 'comment':
                return $this->get_comment();
            case 'proficient_status':
                return $this->get_proficient_status();
            case 'assignment':
                return $this->get_assignment();
            case 'assignment_action':
                return $this->get_assignment_action();
            case 'type':
                return (new \ReflectionClass($this))->getShortName();
        }

        throw new \coding_exception('Invalid field', 'Field not found in activity log model: ' . $field);
    }

    /**
     * @param string $field
     * @return bool
     */
    public function has_field(string $field): bool {
        if (in_array($field, ['timestamp', 'description', 'comment', 'proficient_status', 'assignment', 'assignment_action', 'type'])) {
            return true;
        }
        return false;
    }
}