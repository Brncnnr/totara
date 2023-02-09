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
 * @package core_orm
 * @category test
 */

use core\orm\entity\entity;

defined('MOODLE_INTERNAL') || die();

/**
 * Class core_orm_entity_property_testcase
 *
 * @package core
 * @group orm
 */
class core_orm_entity_property_testcase extends advanced_testcase {

    private $property_identifier = '@property';

    private $property_read_identifier = '@property-read';

    public function test_entities_have_phpdoc_properties() {
        global $DB;

        $entities = core_component::get_namespace_classes('entity', entity::class);

        /** @var entity $entity_classname */
        foreach ($entities as $entity_classname) {
            $table = $entity_classname::TABLE;

            $reflection_class = new ReflectionClass($entity_classname);
            $doc_comment = $reflection_class->getDocComment();
            $properties = $this->parse_doc_comment($doc_comment);

            $columns = $DB->get_columns($table);

            /** @var database_column_info $column_info */
            foreach ($columns as $column_name => $column_info) {
                // id is in the base class
                if ($column_name == 'id') {
                    continue;
                }

                $type = strpos($column_info->type, 'int') !== false ? 'int' : 'string';

                $this->assertArrayHasKey(
                    "\$$column_name",
                    $properties,
                    "No @property phpdoc for column '{$column_name}' found in entity '".$entity_classname."'.\n".
                    "Consider: @property {$type} \${$column_name}"
                );
            }
        }
    }

    private function parse_doc_comment(string $doc_comment): array {
        $prop_docs = explode("\n", $doc_comment);

        $properties = [];

        foreach ($prop_docs as $prop_doc) {
            if (str_contains($prop_doc, $this->property_read_identifier)) {
                $prop_definition = $this->parse_property($prop_doc, $this->property_read_identifier);
            } else if (str_contains($prop_doc, $this->property_identifier)) {
                $prop_definition = $this->parse_property($prop_doc, $this->property_identifier);
            } else {
                continue;
            }
            $properties[$prop_definition['property']] = $prop_definition;
        }

        return $properties;
    }

    private function parse_property($prop_doc, $identifier): array {
        $prop_starts = strpos($prop_doc, $identifier);
        $prop_definition_string = substr($prop_doc, $prop_starts + strlen($identifier));
        $prop_definition = preg_split('/\s+/', trim($prop_definition_string));

        return [
            'type' => explode('|', array_shift($prop_definition)),
            'property' => array_shift($prop_definition),
            'description' => implode(' ', $prop_definition),
        ];
    }

}
