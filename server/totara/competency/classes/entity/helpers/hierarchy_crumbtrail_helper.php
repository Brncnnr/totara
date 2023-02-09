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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_userstatus
 */

namespace totara_competency\entity\helpers;


use core\format;
use totara_hierarchy\entity\hierarchy_item;
use core\orm\query\builder;
use core\webapi\formatter\field\string_field_formatter;

class hierarchy_crumbtrail_helper {

    /**
     * @var hierarchy_item
     */
    private $entity;

    /**
     * @param hierarchy_item $item
     */
    public function __construct(hierarchy_item $item) {
        $this->entity = $item;
    }

    /**
     * Generates a crumbtrail for the given competency
     *
     * @return array
     */
    public function generate(): array {
        // Check for mandatory attributes. Without them we cannot generate a crumbtrail.
        if (empty($this->entity->frameworkid)
            || empty($this->entity->path)
            || empty($this->entity->id)
            || $this->entity->parentid === null
        ) {
            return [];
        }

        // The framework marks the upper most level but it's stored separately
        $framework = $this->entity->framework;

        // Start with the framework as first item
        $crumbtrail = [
            $this->create_crumbtrail_item(
                $framework->id,
                $framework->fullname,
                'framework',
                null,
                false,
                true
            )
        ];

        // We need item type
        $type = $this->entity->get_type();

        // We remove the trailing slash to make exploding and the check easier
        $ancestors_ids = array_map('intval', explode('/', ltrim($this->entity->path, '/')));
        // We are only interested in the ancestors so we strip the last level,
        // the current competency already represents the last level
        array_pop($ancestors_ids);

        if (!empty($ancestors_ids)) {
            $ancestors = builder::table($this->entity->get_table())
                ->where('id', $ancestors_ids)
                ->order_by('path')
                ->results_as_arrays()
                ->get();

            foreach ($ancestors as $ancestor) {
                $crumbtrail[] = $this->create_crumbtrail_item(
                    $ancestor['id'],
                    $ancestor['fullname'],
                    $type,
                    $ancestor['parentid']
                );
            }
        }

        // Push the competency itself into the result as last and active item.
        $crumbtrail[] = $this->create_crumbtrail_item(
            $this->entity->id,
            $this->entity->fullname,
            $type,
            $this->entity->parentid,
            true,
            false,
            true
        );

        return $crumbtrail;
    }

    /**
     * Creates a new crumbtrail item
     *
     * @param int $id
     * @param string $name
     * @param string $type
     * @param int|null $parent_id
     * @param bool $active
     * @param bool $first
     * @param bool $last
     * @return array
     */
    private function create_crumbtrail_item(int $id, string $name, string $type,
        int $parent_id = null, bool $active = false, bool $first = false, bool $last = false): array {

        $string_formatter = new string_field_formatter(format::FORMAT_HTML, \context_system::instance());

        return [
            'id' => $id,
            'name' => $string_formatter->format($name),
            'parent_id' => $parent_id,
            'type' => $type,
            'active' => $active,
            'first' => $first,
            'last' => $last
        ];
    }

}