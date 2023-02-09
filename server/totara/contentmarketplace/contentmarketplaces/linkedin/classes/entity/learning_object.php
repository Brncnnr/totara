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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package contentmarketplace_linkedin
 */

namespace contentmarketplace_linkedin\entity;

use coding_exception;
use contentmarketplace_linkedin\constants;
use core\entity\course;
use core\orm\entity\entity;
use contentmarketplace_linkedin\repository\learning_object_repository;
use core\orm\collection;
use core\orm\entity\relations\has_many_through;
use stdClass;
use mod_contentmarketplace\entity\content_marketplace;

/**
 * A LinkedIn learning object that has been fetched and stored locally within Totara.
 *
 * Properties:
 * @property string $urn
 * @property string $title
 * @property string|null $description
 * @property string|null $description_include_html
 * @property string|null $short_description
 * @property string $locale_language
 * @property string $locale_country
 * @property int $last_updated_at
 * @property int $published_at
 * @property int|null $retired_at
 * @property string $asset_type
 * @property string|null $level
 * @property string|null $primary_image_url
 * @property int|null $time_to_complete
 * @property string|null $web_launch_url
 * @property string|null $sso_launch_url
 * @property string|null $availability
 *
 * @property-read classification[]|collection $subjects
 * @property-read classification[]|collection $classifications
 * @property-read course[]|collection         $courses
 *
 * @method static learning_object_repository repository
 *
 * @package contentmarketplace_linkedin\entity
 */
class learning_object extends entity {

    /**
     * @var string
     */
    public const TABLE = 'marketplace_linkedin_learning_object';

    /**
     * @return string
     */
    public static function repository_class_name(): string {
        return learning_object_repository::class;
    }

    /**
     * @param stdClass $record
     * @return void
     */
    public function set_attributes_from_record(stdClass $record): void {
        $attributes = get_object_vars($record);
        $this->set_attributes_from_array_record($attributes);
    }

    /**
     * @param array $record
     * @return void
     */
    public function set_attributes_from_array_record(array $record): void {
        foreach ($record as $attribute_name => $value) {
            if ('urn' === $attribute_name) {
                if ($this->exists() && $value !== $this->urn) {
                    // This is illegal.
                    throw new coding_exception("Update the urn from an existing record is forbidden");
                }
            }

            $this->set_attribute($attribute_name, $value);
        }
    }

    /**
     * @return has_many_through
     */
    public function classifications(): has_many_through {
        return $this->has_many_through(
            learning_object_classification::class,
            classification::class,
            'id',
            'learning_object_id',
            'classification_id',
            'id'
        );
    }

    /**
     * @return has_many_through
     */
    public function subjects(): has_many_through {
        return $this->classifications()
            ->where('type', constants::CLASSIFICATION_TYPE_SUBJECT)
            ->order_by('name')
            ->order_by('id');
    }

    /**
     * @return has_many_through
     */
    public function courses(): has_many_through {
        return $this->has_many_through(
            content_marketplace::class,
            course::class,
            'id',
            'learning_object_id',
            'course',
            'id'
        )
            ->order_by('fullname')
            ->order_by('id');
    }
}
