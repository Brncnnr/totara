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

namespace mod_perform\data_providers\activity;

use coding_exception;
use core\collection;
use mod_perform\entity\activity\activity_setting as activity_setting_entity;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\activity_setting;

/**
 * Handles groups of performance activity settings.
 */
class activity_settings {

    /**
     * @var activity parent activity.
     */
    private $activity;

    /**
     * @var collection|activity_setting[]|null settings.
     */
    private $items = null;

    /**
     * @param activity $parent parent activity.
     * @param collection|null $settings optionally can pass already loaded instances if preloaded
     */
    public function __construct(activity $parent, collection $settings = null) {
        $this->activity = $parent;
        if ($settings && $settings->count() > 0) {
            foreach ($settings as $setting) {
                if (!$setting instanceof activity_setting) {
                    throw new coding_exception('Expected a collection of activity_setting model instances');
                }
            }
            $this->items = $settings;
        }
    }

    /**
     * Returns the activity settings.
     *
     * @return collection|activity_setting[] the list of activity settings.
     */
    public function get(): collection {
        if (is_null($this->items)) {
            $this->fetch();
        }

        return $this->items;
    }

    /**
     * Fetches activity settings from the database.
     *
     * @return activity_settings this object.
     */
    public function fetch(): activity_settings {
        $this->items = activity_setting_entity::repository()
            ->where('activity_id', $this->activity->id)
            ->get()
            ->map_to(activity_setting::class);

        return $this;
    }

    /**
     * Get the parent activity model.
     *
     * @return activity the parent activity
     */
    public function get_activity(): activity {
        return $this->activity;
    }

    /**
     * Returns the value of the setting with the given name.
     *
     * @param string $name the setting to look up.
     * @param mixed $other the value to return if the setting does not exist.
     *
     * @return mixed the setting value.
     */
    public function lookup(string $name, $other = null) {
        $setting = $this->get()->find(
            function (activity_setting $setting) use ($name): bool {
                return $setting->name === $name;
            }
        );

        return $setting->value ?? $other;
    }

    /**
     * Completely removes existing activity settings.
     *
     * @return activity_settings this object.
     */
    public function clear(): activity_settings {
        foreach ($this->get() as $setting) {
            $setting->delete();
        }

        $this->items = null;
        return $this;
    }

    /**
     * Removes activity settings.
     *
     * @param array $names setting names to remove.
     *
     * @return activity_settings this object.
     */
    public function remove(array $names): activity_settings {
        $updated = [];

        // Refetch the settings to make sure we have what is currently in the DB.
        foreach ($this->fetch()->get() as $setting) {
            if (in_array($setting->name, $names)) {
                $setting->delete();
            } else {
                $updated[] = $setting;
            }
        }

        $this->items = collection::new($updated);
        return $this;
    }

    /**
     * Updates an existing set of activity settings. If the setting name already
     * exists, its value is replaced; if the name does not exist, it is added to
     * the settings.
     *
     * @param array $values mapping of setting names to values.
     *
     * @return activity_settings this object.
     */
    public function update(array $values): activity_settings {
        $updated = [];

        // Refetch the settings to make sure we have what is currently in the DB.
        foreach ($this->fetch()->get()->all() as $setting) {
            $name = $setting->name;

            $updated[$name] = array_key_exists($name, $values)
                ? $setting->update($values[$name])
                : $setting;
        }

        foreach ($values as $name => $value) {
            if (!array_key_exists($name, $updated)) {
                $updated[$name] = activity_setting::create($this->activity, $name, $value);
            }
        }

        $this->items = collection::new($updated);

        return $this;
    }
}
