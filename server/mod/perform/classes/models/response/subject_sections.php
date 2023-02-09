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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\models\response;

use core\collection;
use mod_perform\models\activity\subject_instance;

/**
 * Holds the related activity sections for a specific subject.
 */
class subject_sections {

    /**
     * @var subject_instance parent subject.
     */
    private $subject;

    /**
     * @var collection|section_participants[] related sections.
     */
    private $sections;

    /**
     * @param subject_instance $subject_instance parent subject.
     * @param collection|section_participants[] $sections associated sections.
     */
    public function __construct(subject_instance $subject_instance, collection $sections) {
        $this->subject = $subject_instance;
        $this->sections = $sections;
    }

    /**
     * Returns the subject instance this belongs to
     *
     * @return subject_instance the parent subject.
     */
    public function get_subject_instance(): subject_instance {
        return $this->subject;
    }

    /**
     * Returns the associated sections
     *
     * @return collection|section_participants[] associated sections.
     */
    public function get_sections(): collection {
        return $this->sections;
    }

    /**
     * Given a set of subject instances, returns sections and their participants
     * related to those subject instances (and by extension, activities since 1
     * activity => one subject instance).
     *
     * @param collection|subject_instance[] $subject_instances target subject instances.
     * @return collection|section_participants[] section participant objects.
     */
    public static function create_from_subject_instances(collection $subject_instances): collection {
        return $subject_instances
            ->map(
                function (subject_instance $subject): subject_sections {
                    $sections = section_participants::create_from_subject_instance($subject);
                    return new subject_sections($subject, $sections);
                }
            );
    }

}
