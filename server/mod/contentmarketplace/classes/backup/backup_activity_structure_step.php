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
 * @author  Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package mod_contentmarketplace
 */

namespace mod_contentmarketplace\backup;

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

use backup;
use backup_nested_element;
use mod_contentmarketplace\entity\content_marketplace;
use totara_contentmarketplace\learning_object\factory;

class backup_activity_structure_step extends \backup_activity_structure_step {

    /**
     * @inheritDoc
     */
    protected function define_structure(): backup_nested_element {
        $contentmarketplace = new backup_nested_element(
            'contentmarketplace',
            ['id'],
            [
                'name',
                'intro',
                'introformat',
                'learning_object_marketplace_component',
                'learning_object_external_id',
                'learning_object_id',
                'completion_condition',
                'time_modified',
            ]
        );
        $contentmarketplace->set_source_sql("
            SELECT activity.*,
            " . $this->get_learning_object_external_id_select_sql() . " learning_object_external_id
            FROM {" . content_marketplace::TABLE . "} activity
            WHERE activity.id = :id
        ", ['id' => backup::VAR_ACTIVITYID]);

        // Define file annotations
        $contentmarketplace->annotate_files('mod_contentmarketplace', 'intro', null);

        return $this->prepare_activity_structure($contentmarketplace);
    }

    /**
     * Produces a (hacky) SQL select statement for getting the appropriate external ID
     *
     * @return string
     */
    private function get_learning_object_external_id_select_sql(): string {
        global $DB;
        $marketplace_resolvers = factory::get_marketplace_plugin_resolvers();

        $subqueries = [];
        foreach ($marketplace_resolvers as $resolver_class) {
            $external_id_field = $DB->sql_cast_2char($resolver_class::get_external_id_field());
            $subqueries[] = "(
                SELECT {$external_id_field}
                FROM {" . $resolver_class::get_entity_class()::TABLE . "}
                WHERE id = activity.learning_object_id
                AND activity.learning_object_marketplace_component = '" . $resolver_class::get_component() . "'
            )";
        }
        $subqueries = implode(',', $subqueries);
        return "COALESCE($subqueries)";
    }

}
