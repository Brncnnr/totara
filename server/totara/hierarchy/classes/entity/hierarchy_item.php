<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @package totara_hierarchy
 */

namespace totara_hierarchy\entity;

use ReflectionClass;
use core\orm\collection;
use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;
use core\orm\entity\relations\has_many;
use totara_competency\entity\helpers\hierarchy_crumbtrail_helper;

/**
 * Class hierarchy item
 *
 * @property int $typeid
 * @property int $parentid
 * @property-read array $crumbtrail Totara sync flag
 * @property-read hierarchy_framework $framework Hierarchy item framework
 * @property-read string $display_name
 * @property-read hierarchy_item $parent
 * @property-read collection $children
 * @property-read hierarchy_type $type
 *
 * @package totara_competency\entity
 */
abstract class hierarchy_item extends entity {

    /**
     * Always append default name
     *
     * @var array
     */
    protected $extra_attributes = [
        'display_name'
    ];

    /**
     * Hierarchy item crumbtrail cached
     *
     * @var array
     */
    protected $crumbtrail_cached = null;

    /**
     * If this is called this item will have a crumbtrail attribute loaded when to_array() is called
     *
     * @return $this
     */
    public function with_crumbtrail(): hierarchy_item {
        return $this->add_extra_attribute('crumbtrail');
    }

    /**
     * @return array
     */
    public function get_crumbtrail_attribute(): array {
        if (!$this->crumbtrail_cached) {
            $this->crumbtrail_cached = (new hierarchy_crumbtrail_helper($this))->generate();
        }
        return $this->crumbtrail_cached;
    }

    /**
     * Get unified display name that can be referred to safely, just an alias in this case
     *
     * @return string
     */
    protected function get_display_name_attribute() {
        return $this->fullname;
    }

    /**
     * Get hierarchy item type, by default matches class name, can be overridden on a class level
     *
     * @return string
     */
    public function get_type() {
        return (new ReflectionClass($this))->getShortName();
    }

    /**
     * Related hierarchy item framework
     *
     * @return belongs_to
     */
    public function framework(): belongs_to {
        return $this->belongs_to(static::get_framework_class(), 'frameworkid');
    }

    /**
     * Get framework entity class name
     *
     * @return string|null
     */
    public static function get_framework_class(): ?string {
        $fw = static::class . '_framework';

        return is_a($fw, hierarchy_framework::class, true) ? $fw : null;
    }

    /**
     * Related hierarchy item type
     *
     * @return belongs_to
     */
    public function type(): belongs_to {
        return $this->belongs_to(static::get_type_class(), 'typeid');
    }

    /**
     * Get framework entity class name
     *
     * @return string|null
     */
    public static function get_type_class(): ?string {
        $fw = static::class . '_type';

        return is_a($fw, hierarchy_type::class, true) ? $fw : null;
    }

    /**
     * Immediate hierarchy item children
     *
     * @return has_many
     */
    public function children(): has_many {
        return $this->has_many(static::class, 'parentid');
    }

    /**
     * Immediate hierarchy item parent
     *
     * @return belongs_to
     */
    public function parent(): belongs_to {
        return $this->belongs_to(static::class, 'parentid');
    }

}
