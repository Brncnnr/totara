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
 * @author Marco Song <marco.song@totaralearning.com>
 * @package performelement_linked_review
 */

namespace performelement_linked_review\entity;

use core\collection;
use core\entity\user;
use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;
use core\orm\entity\relations\has_many;
use mod_perform\entity\activity\section_element;
use mod_perform\entity\activity\subject_instance;

/**
 * Element subject instance review content entity
 *
 * Properties:
 * @property int $section_element_id
 * @property int $subject_instance_id
 * @property int $selector_id
 * @property string $content_type
 * @property string $meta_data additional metadata stored as json, en- and decoding happens automatically
 * @property int $content_id
 * @property int $created_at
 * @property-read user $selector
 * @property-read subject_instance $subject_instance
 * @property-read section_element $section_element
 * @property-read linked_review_content_response[]|collection $responses
 *
 * @package performelement_linked_reivew\entity
 */
class linked_review_content extends entity {

    public const TABLE = 'perform_element_linked_review_content';

    public const CREATED_TIMESTAMP = 'created_at';

    /**
     * Subject instance that the content belongs to.
     *
     * @return belongs_to
     */
    public function subject_instance(): belongs_to {
        return $this->belongs_to(subject_instance::class, 'subject_instance_id');
    }

    /**
     * User who selected the content
     *
     * @return belongs_to
     */
    public function selector(): belongs_to {
        return $this->belongs_to(user::class, 'selector_id');
    }

    /**
     * Section element for this content.
     *
     * @return belongs_to
     */
    public function section_element(): belongs_to {
        return $this->belongs_to(section_element::class, 'section_element_id');
    }

    /**
     * The responses to this linked content.
     *
     * @return has_many
     */
    public function responses(): has_many {
        return $this->has_many(linked_review_content_response::class, 'linked_review_content_id');
    }

}
