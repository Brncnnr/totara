<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTDvs
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * @author  Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\models\activity\helpers;

use core_text;

use core\collection;

use mod_perform\entity\activity\activity_type as activity_type_entity;

use mod_perform\models\activity\activity;

/**
 * Encapsulates the business logic for validating the general info details of
 * an activity.
 */
class general_info_validator {
    /**
     * @var activity activity.
     */
    private $parent = null;

    /**
     * @var activity name.
     */
    private $name = null;

    /**
     * @var activity description.
     */
    private $description = null;

    /**
     * @var int type id.
     */
    private $new_type_id = null;

    /**
     * Default constructor.
     *
     * @param activity $parent parent activity.
     * @param string $name activity name.
     * @param string|null $description activity description.
     * @param int|null $new_type_id activity type id.
     */
    public function __construct(
        activity $parent,
        string $name,
        ?string $description = null,
        ?int $new_type_id = null
    ) {
        $this->parent = $parent;
        $this->name = trim($name);
        $this->description = $description;
        $this->new_type_id = $new_type_id;
    }

    /**
     * Updates the given activity entity with validated general info values.
     *
     * @return collection a set of error messages indicating which general info values have
     *         invalid values.
     */
    public function validate(): collection {
        return collection::new([
            'validate_type_id',
            'validate_name',
            'validate_only_draft_changes'
        ])->reduce(
            function (collection $errors, string $validate): collection {
                return $this->$validate($errors);
            },
            collection::new([])
        );
    }

    /**
     * Validates that the activity name is valid.
     *
     * @param collection|string[] $errors list of errors to add to if the name is invalid.
     * @return collection|string[] the updated list of errors.
     */
    private function validate_name(collection $errors): collection {
        if (empty($this->name)) {
            $errors->append('Name is required');
        }

        $max_length = activity::NAME_MAX_LENGTH;
        if (core_text::strlen($this->name) > $max_length) {
            $errors->append("Name cannot be more than $max_length characters");
        }

        return $errors;
    }

    /**
     * Validates that the (new) activity type id is valid.
     *
     * @param collection|string[] $errors list of errors to add to if the type id is invalid.
     * @return collection|string[] the updated list of errors.
     */
    private function validate_type_id(collection $errors): collection {
        if (!$this->new_type_id) {
            return $errors;
        }

        $is_valid_id = activity_type_entity::repository()
            ->where('id', $this->new_type_id)
            ->exists();

        if (!$is_valid_id) {
            $errors->append('Invalid activity type');
        }

        return $errors;
    }

    /**
     * Validates that the general info changes are valid given the parent activity
     * state.
     *
     * @param collection|string[] $errors list of errors to add to if the changes are invalid.
     * @return collection|string[] the updated list of errors.
     */
    private function validate_only_draft_changes(collection $errors): collection {
        $parent = $this->parent;

        if ($this->new_type_id
            && $this->new_type_id !== $parent->type->id
            && !$parent->is_draft()
        ) {
            $activity_id = $parent->id;
            $errors->append("Cannot change type of activity $activity_id since it is no longer a draft");
        }

        return $errors;
    }
}
