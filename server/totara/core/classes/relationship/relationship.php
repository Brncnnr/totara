<?php
/*
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\relationship;

use coding_exception;
use context;
use core\orm\entity\model;
use core\orm\query\builder;
use core_component;
use moodle_exception;
use totara_core\entity\relationship as relationship_entity;
use totara_core\entity\relationship_resolver as relationship_resolver_entity;

/**
 * A dynamically defined way of identifying the many users that are associated with a single user based on a given input.
 * @link https://help.totaralearning.com/display/PROD/Relationships Documentation
 *
 * @property-read int $id
 * @property-read string $idnumber
 * @property-read int $type
 * @property-read int $sort_order
 * @property-read int $created_at
 * @property-read string $name
 *
 * @package totara_core\relationship
 */
class relationship extends model {

    /**
     * @var relationship_entity
     */
    protected $entity;

    protected $entity_attribute_whitelist = [
        'id',
        'idnumber',
        'type',
        'sort_order',
        'type',
        'created_at',
    ];

    protected $model_accessor_whitelist = [
        'name',
        'name_plural',
    ];

    /**
     * @return string|relationship_entity
     */
    protected static function get_entity_class(): string {
        return relationship_entity::class;
    }

    /**
     * Gets a relationship based on the given idnumber
     *
     * @param string $idnumber
     * @return static
     * @throws coding_exception
     */
    public static function load_by_idnumber(string $idnumber) {
        $entity = static::get_entity_class()::repository()
            ->where('idnumber', $idnumber)
            ->one(true);

        return static::load_by_entity($entity);
    }

    /**
     * Get this relationship's name.
     *
     * @return string
     */
    public function get_name(): string {
        // In the future, relationships can be user (admin) specified, including their names. (Stored in the DB)
        // But until then, the name of a relationship will be provided by a lang string.
        $component = $this->entity->component ?? 'totara_core';
        $string_name = 'relationship_name_' . $this->entity->idnumber;
        if (get_string_manager()->string_exists($string_name, $component)) {
            return get_string($string_name, $component);
        }
        return get_string('unknown_relationship_name', 'totara_core');
    }

    /**
     * Get this relationship's plural version of it's name.
     *
     * @return string
     */
    public function get_name_plural(): string {
        // In the future, relationships can be user (admin) specified, including their names. (Stored in the DB)
        // But until then, the name of a relationship will be provided by a lang string.
        $component = $this->entity->component ?? 'totara_core';
        $string_name = 'relationship_name_plural_' . $this->entity->idnumber;
        if (get_string_manager()->string_exists($string_name, $component)) {
            return get_string($string_name, $component);
        }
        return get_string('unknown_relationship_name', 'totara_core');
    }

    /**
     * Get the timestamp for when this relationship was created.
     *
     * @return int
     */
    public function get_date_created(): int {
        return $this->entity->created_at;
    }

    /**
     * Get the users from the given raw data.
     *
     * @param array $data e.g. ['job_assignment_id' => 2]
     * @param context $context
     * @return relationship_resolver_dto[]
     */
    public function get_users(array $data, context $context): array {
        /** @var relationship_resolver_dto[] $relationship_resolver_dtos */
        $relationship_resolver_dtos = [];
        foreach ($this->get_resolvers() as $relationship_resolver) {
            $relationship_resolver_dtos[] = $relationship_resolver->get_users($data, $context);
        }
        // NOTE: It is possible for a relationship to return duplicate user_ids if there are multiple resolvers.
        // The resolvers themselves should always return unique user_ids, so it is not a problem for now.
        return array_merge(...$relationship_resolver_dtos);
    }

    /**
     * Resolver instances associated with this relationship.
     *
     * @return relationship_resolver[] Relationship resolvers.
     */
    public function get_resolvers(): array {
        $resolver_entities = $this->entity->resolvers;
        $resolver_models = [];
        foreach ($resolver_entities as $entity) {
            $class_name = $entity->class_name;
            $resolver_models[] = new $class_name($this);
        }
        return $resolver_models;
    }

    /**
     * Create a new relationship.
     *
     * @param string[] $resolver_class_names Array of relationship resolver class names, e.g. [subject::class, manager::class]
     * @param string $idnumber Unique string identifier for this relationship.
     * @param int $sort_order
     * @param int|null $type Optional type identifier - defaults to standard type.
     * @param string|null $component Plugin that the relationship is exclusive to. Defaults to being available for all.
     * @return relationship
     */
    public static function create(
        array $resolver_class_names,
        string $idnumber,
        int $sort_order = 1,
        int $type = null,
        string $component = null
    ) {
        self::validate_resolvers($resolver_class_names);

        if (trim($idnumber) === '' || strlen($idnumber) > 255) {
            throw new coding_exception('Must specify an idnumber longer than 0 characters and less than 255 characters');
        }
        if (totara_idnumber_exists(relationship_entity::TABLE, $idnumber)) {
            throw new moodle_exception('idnumbertaken');
        }
        if ($type !== null && !in_array($type, [relationship_entity::TYPE_STANDARD, relationship_entity::TYPE_MANUAL])) {
            throw new coding_exception('Invalid type specified: ' . $type);
        }
        if ($component !== null && core_component::get_component_directory($component) === null) {
            throw new coding_exception('Specified component/plugin ' . $component . ' does not exist!');
        }

        $relationship = builder::get_db()->transaction(
            function () use ($resolver_class_names, $idnumber, $sort_order, $type, $component) {
                $relationship = new relationship_entity();
                $relationship->idnumber = $idnumber;
                $relationship->type = $type ?? relationship_entity::TYPE_STANDARD;
                $relationship->component = $component;
                $relationship->sort_order = $sort_order;
                $relationship->save();

                foreach ($resolver_class_names as $resolver_class_name) {
                    $relationship_resolver = new relationship_resolver_entity();
                    $relationship_resolver->relationship_id = $relationship->id;
                    $relationship_resolver->class_name = $resolver_class_name;
                    $relationship_resolver->save();
                }

                return $relationship;
            }
        );

        return self::load_by_entity($relationship);
    }

    /**
     * Delete this relationship.
     */
    public function delete(): void {
        $this->entity->delete();
    }

    /**
     * Make sure the specified array contains valid subclasses of relationship_resolver.
     *
     * @param array $resolver_class_names
     * @throws coding_exception
     */
    private static function validate_resolvers(array $resolver_class_names): void {
        if (empty($resolver_class_names)) {
            throw new coding_exception('Must specify at least one relationship resolver!');
        }

        foreach ($resolver_class_names as $resolver_class_name) {
            if (!is_a($resolver_class_name, relationship_resolver::class, true)) {
                throw new coding_exception($resolver_class_name . ' must be an instance of ' . relationship_resolver::class);
            }
        }

        if (!relationship_resolver::are_resolvers_compatible(...$resolver_class_names)) {
            throw new coding_exception(
                'The specified resolvers do not share at least one common input and are therefore incompatible.'
            );
        }
    }

    /**
     * Can the specified fields be accepted as input to this relationship?
     *
     * @param string[] $fields fields to check e.g. ['job_assignment_id'] or ['user_id', 'course_id']
     * @return bool
     */
    public function is_acceptable_input(array $fields): bool {
        if (empty($fields)) {
            throw new coding_exception('Must specify at least one field to relationship::is_acceptable_input()');
        }

        foreach ($this->get_resolvers() as $resolver) {
            if (!$resolver::is_acceptable_input($fields)) {
                return false;
            }
        }

        return true;
    }

}
