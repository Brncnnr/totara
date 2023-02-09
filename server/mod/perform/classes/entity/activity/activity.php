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
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\entity\activity;

use core\orm\collection;
use core\orm\entity\entity;
use core\orm\entity\relations\has_many;
use core\orm\entity\relations\has_one;
use core\orm\query\builder;
use core\orm\query\table;
use mod_perform\notification\factory;
use mod_perform\notification\loader;
use mod_perform\models\activity\element_plugin;

/**
 * Activity entity
 *
 * Properties:
 * @property-read int $id ID
 * @property int $type_id activity type
 * @property int $course ID of parent course
 * @property string $description
 * @property string $name Activity name
 * @property bool $anonymous_responses Are all responses anonymous on this activity.
 * @property int $status
 * @property int $created_at
 * @property int $updated_at
 *
 * Relationships:
 * @property-read collection|notification[] $notifications
 * @property-read collection|notification[] $active_notifications
 * @property-read collection|section[] $sections @deprecated Please use sections_ordered or sections_ordered_with_respondable_element_count
 * @property-read collection|section[] $sections_ordered
 * @property-read collection|track[] $tracks
 * @property-read collection|activity_setting[] $settings
 * @property-read collection|manual_relationship_selection[] $manual_relation_selection
 * @property-read activity_type $type
 * @property-read collection|section[] sections_ordered_with_respondable_element_count
 *
 * @method static activity_repository repository()
 *
 * @package mod_perform\entity
 */
class activity extends entity {
    public const TABLE = 'perform';

    public const CREATED_TIMESTAMP = 'created_at';
    public const UPDATED_TIMESTAMP = 'updated_at';
    public const SET_UPDATED_WHEN_CREATED = true;

    /**
     * Relationship with section entities.
     * @depreacted Please use sections_ordered or sections_ordered_with_respondable_element_count
     *
     * @return has_many
     */
    public function sections(): has_many {
        return $this->has_many(section::class, 'activity_id');
    }

    /**
     * Relationship with section entities ordered by sort order.
     *
     * @return has_many
     */
    public function sections_ordered(): has_many {
        return $this->has_many(section::class, 'activity_id')
            ->order_by('sort_order');
    }

    /**
     * Relationship with section and count element for each section
     *
     * @return has_many
     */
    public function sections_ordered_with_respondable_element_count(): has_many {
        $elements = element_plugin::get_element_plugins(true, false);
        $respondable_plugins = array_keys($elements);

        $sub_query = builder::table('perform_section_element', 'se');
        $sub_query->select_raw('se.section_id AS section_id, COUNT(se.id) AS count')
            ->join([element::TABLE, 'e'], 'se.element_id', 'e.id')
            ->left_join([element::TABLE, 'e2'], 'e.id', 'e2.parent')
            ->where_in('e.plugin_name', $respondable_plugins)
            ->or_where_in('e2.plugin_name', $respondable_plugins)
            ->group_by('se.section_id');

        $table = new table($sub_query);
        $table->as('pse');

        return $this->has_many(section::class, 'activity_id')
            ->as('s')
            ->left_join($table, 's.id', 'pse.section_id')
            ->select_raw('s.id, s.sort_order, pse.count AS respondable_element_count')
            ->group_by_raw('s.id, s.sort_order, pse.count')
            ->order_by_raw('s.id, s.sort_order');
    }

    /**
     * Tracks for this activity.
     *
     * @return has_many
     */
    public function tracks(): has_many {
        return $this->has_many(track::class, 'activity_id');
    }

    /**
     * Activity type.
     *
     * @return has_one the relationship.
     */
    public function type(): has_one {
        return $this->has_one(activity_type::class, 'id', 'type_id');
    }

    /**
     * All manual relation selection entries
     *
     * @return has_many
     */
    public function manual_relation_selection(): has_many {
        return $this->has_many(manual_relationship_selection::class, 'activity_id');
    }

    /**
     * Get the settings for this activity
     *
     * @return has_many
     */
    public function settings(): has_many {
        return $this->has_many(activity_setting::class, 'activity_id');
    }

    /**
     * Get the notifications for this activity.
     *
     * @return has_many
     */
    public function notifications(): has_many {
        return $this->has_many(notification::class, 'activity_id');
    }

    /**
     * Get the active notifications for this activity.
     *
     * @return has_many
     */
    public function active_notifications(): has_many {
        $loader = factory::create_loader();
        $class_keys = $loader->get_class_keys(loader::HAS_CONDITION);

        return $this->notifications()
            ->as('n')
            ->where_exists(
                builder::table(notification_recipient::TABLE)
                    ->where_field('notification_id', 'n.id')
                    ->where('active', true)
            )
            ->where('active', true)
            ->where('class_key', $class_keys);
    }

    /**
     * Bool casting.
     *
     * @return bool
     */
    public function get_anonymous_responses_attribute(): bool {
        return $this->get_attributes_raw()['anonymous_responses'] ?? false;
    }

    /**
     * Bool casting.
     *
     * @param bool $value
     * @return bool
     */
    public function set_anonymous_responses_attribute(bool $value): bool {
        return (bool) $this->set_attribute_raw('anonymous_responses', $value);
    }

    /**
     * Get the manual relationship configurations for the activity.
     *
     * @return has_many
     */
    public function manual_relationships(): has_many {
        return $this->has_many(manual_relationship_selection::class, 'activity_id');
    }
}
