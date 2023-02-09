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

use core\entity\user;
use core\orm\collection;
use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;
use core\orm\entity\relations\has_many;
use core\orm\entity\relations\has_many_through;
use core\orm\entity\relations\has_one;
use core\orm\entity\relations\has_one_through;

defined('MOODLE_INTERNAL') || die();

/**
 * Class orm_entity_relation_testcase
 *
 * @package core
 * @group orm
 */
abstract class orm_entity_relation_testcase extends advanced_testcase {

    /**
     * Date generator shortcut
     *
     * @return \core\testing\generator
     */
    protected function generator() {
        return self::getDataGenerator();
    }

    /**
     * Remove the created table after test
     */
    protected function tearDown(): void {
        parent::tearDown();
        $this->drop_tables();
    }

    /**
     * @return moodle_database
     */
    protected function db() {
        return $GLOBALS['DB'];
    }

    /**
     * @return database_manager
     */
    protected function db_man() {
        return $this->db()->get_manager();
    }

    /**
     * Create sample table for the entity
     */
    protected function create_tables() {
        $this->create_parent_table()
            ->create_child_table()
            ->create_passport_table()
            ->create_sibling_table()
            ->create_pivot_table();
    }

    /**
     * Create table for sample_parent_entity::class
     *
     * @return $this
     */
    protected function create_parent_table() {
        $name = sample_parent_entity::TABLE;

        if ($this->db_man()->table_exists($name)) {
            return $this;
        }

        $table = new xmldb_table($name);

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, 'John Doe');

        $table->add_field('created_at', XMLDB_TYPE_INTEGER, '10', null);
        $table->add_field('updated_at', XMLDB_TYPE_INTEGER, '10', null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        $this->db_man()->create_table($table);

        return $this;
    }

    /**
     * Drop table for sample_parent_entity::class
     *
     * @return $this
     */
    protected function drop_parent_table() {
        return $this->drop_table(sample_parent_entity::TABLE);
    }

    /**
     * Create table for sample_passport_entity::class
     *
     * @return $this
     */
    protected function create_passport_table() {
        $name = sample_passport_entity::TABLE;

        if ($this->db_man()->table_exists($name)) {
            return $this;
        }

        $table = new xmldb_table($name);

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, 'John Doe');

        $table->add_field('parent_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);

        $table->add_field('created_at', XMLDB_TYPE_INTEGER, '10', null);
        $table->add_field('updated_at', XMLDB_TYPE_INTEGER, '10', null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        $this->db_man()->create_table($table);

        return $this;
    }

    /**
     * Drop table for sample_passport_entity::class
     *
     * @return $this
     */
    protected function drop_passport_table() {
        return $this->drop_table(sample_passport_entity::TABLE);
    }

    /**
     * Create table for sample_parent_entity::class
     *
     * @return $this
     */
    protected function create_child_table() {
        $name = sample_child_entity::TABLE;

        if ($this->db_man()->table_exists($name)) {
            return $this;
        }

        $table = new xmldb_table($name);

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, 'John Doe');
        $table->add_field('type', XMLDB_TYPE_INTEGER, '10', null, false, null, 0);
        $table->add_field('parent_id', XMLDB_TYPE_INTEGER, '10', null, false);
        $table->add_field('description', XMLDB_TYPE_TEXT);

        $table->add_field('created_at', XMLDB_TYPE_INTEGER, '10', null);
        $table->add_field('updated_at', XMLDB_TYPE_INTEGER, '10', null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        $this->db_man()->create_table($table);

        return $this;
    }

    /**
     * Drop table for sample_child_entity::class
     *
     * @return $this
     */
    protected function drop_child_table() {
        return $this->drop_table(sample_child_entity::TABLE);
    }

    /**
     * Create table for sample_parent_entity::class
     *
     * @return $this
     */
    protected function create_sibling_table() {
        $name = sample_sibling_entity::TABLE;

        if ($this->db_man()->table_exists($name)) {
            return $this;
        }

        $table = new xmldb_table($name);

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, 'John Doe');
        $table->add_field('type', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
        $table->add_field('child_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        $this->db_man()->create_table($table);

        return $this;
    }

    /**
     * Drop table for sample_child_entity::class
     *
     * @return $this
     */
    protected function drop_sibling_table() {
        return $this->drop_table(sample_sibling_entity::TABLE);
    }
    /**
     * Create table for sample_parent_entity::class
     *
     * @return $this
     */
    protected function create_pivot_table() {
        $name = sample_pivot_entity::TABLE;

        if ($this->db_man()->table_exists($name)) {
            return $this;
        }

        $table = new xmldb_table($name);

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('parent_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('sibling_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('type', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
        $table->add_field('meta', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        $this->db_man()->create_table($table);

        return $this;
    }

    /**
     * Drop table for sample_child_entity::class
     *
     * @return $this
     */
    protected function drop_pivot_table() {
        return $this->drop_table(sample_pivot_entity::TABLE);
    }

    /**
     * Drop table with a given name
     *
     * @param string $name Table name
     * @return $this
     */
    protected function drop_table(string $name) {
        if ($this->db_man()->table_exists($name)) {
            $this->db_man()->drop_table(new xmldb_table($name));
        }

        return $this;
    }

    /**
     * Remove the created table after test
     *
     * @return $this
     */
    protected function drop_tables() {
        return $this->drop_child_table()
            ->drop_parent_table()
            ->drop_passport_table()
            ->drop_sibling_table()
            ->drop_pivot_table();
    }

    /**
     * Returns a set of sample records to test against
     *
     * @return array
     */
    protected function sample_records() {
        return [
            sample_parent_entity::class => [
                [
                    'name' => 'Mobile phones',
                    'created_at' => time(),
                    'updated_at' => time(),
                ],
                [
                    'name' => 'Tablets',
                    'created_at' => time(),
                    'updated_at' => time(),
                ],
                [
                    'name' => 'Personal Computers',
                    'created_at' => time(),
                    'updated_at' => time(),
                ],
                [
                    'name' => 'Calculators',
                    'created_at' => '946684799', // A second prior to another millennium
                    'updated_at' => '946684799', // A second prior to another millennium
                ],
                [
                    'name' => 'Bluetooth speakers',
                    'created_at' => '946684798', // Two seconds prior to another millennium
                    'updated_at' => '946684798', // Two seconds prior to another millennium
                ],
            ],

            sample_passport_entity::class => [
                [
                    'name' => 'Precision passport',
                    'parent_id' => 4,
                    'created_at' => '946684799', // A second prior to another millennium
                    'updated_at' => '946684799', // A second prior to another millennium
                ],
                [
                    'name' => 'Performance passport',
                    'parent_id' => 3,
                    'created_at' => time(),
                    'updated_at' => time(),
                ],
                [
                    'name' => 'Durability passport',
                    'parent_id' => 2,
                    'created_at' => time(),
                    'updated_at' => time(),
                ],
                [
                    'name' => 'Quality passport',
                    'parent_id' => 1,
                    'created_at' => time(),
                    'updated_at' => time(),
                ],
            ],

            sample_child_entity::class => [
                // Mobile phones:
                [
                    'name' => 'Samsung Galaxy 6',
                    'type' => 1,
                    'parent_id' => 1,
                    'description' => '',
                    'created_at' => '633679200', // 30th Jan 1990
                    'updated_at' => '633679200', // 30th Jan 1990
                ],
                [
                    'name' => 'Samsung Galaxy Note',
                    'type' => 1,
                    'parent_id' => 1,
                    'description' => '',
                    'created_at' => '946684799', // A second prior to another millennium
                    'updated_at' => '946684799', // A second prior to another millennium
                ],
                [
                    'name' => 'Apple iPhone X',
                    'type' => 1,
                    'parent_id' => 1,
                    'description' => '',
                    'created_at' => time(), // Now
                    'updated_at' => time(), // Now
                ],
                // Tablets:
                [
                    'name' => 'Apple iPad',
                    'type' => 2,
                    'parent_id' => 2,
                    'description' => '',
                    'created_at' => '633679200', // 30th Jan 1990
                    'updated_at' => '633679200', // 30th Jan 1990
                ],
                [
                    'name' => 'Samsung Galaxy Tab',
                    'type' => 3,
                    'parent_id' => 2,
                    'description' => '',
                    'created_at' => '633679200', // 30th Jan 1990
                    'updated_at' => '633679200', // 30th Jan 1990
                ],
                [
                    'name' => 'Lenovo Tab',
                    'type' => 3,
                    'parent_id' => 2,
                    'description' => '',
                    'created_at' => '633679200', // 30th Jan 1990
                    'updated_at' => '633679200', // 30th Jan 1990
                ],
                // Personal computers
                [
                    'name' => 'HP Personal computer',
                    'type' => 4,
                    'parent_id' => 3,
                    'description' => '',
                    'created_at' => '1356048000', // 21th Dec 2012
                    'updated_at' => '1356048000', // 21th Dec 2012
                ],
                [
                    'name' => 'DELL Personal computer',
                    'type' => 4,
                    'parent_id' => 3,
                    'description' => '',
                    'created_at' => '1356048000', // 21th Dec 2012
                    'updated_at' => '1356048000', // 21th Dec 2012
                ],
                [
                    'name' => 'Apple iMac PRO',
                    'type' => 5,
                    'parent_id' => 3,
                    'description' => '',
                    'created_at' => '633679200', // 21th Dec 2012
                    'updated_at' => '633679200', // 21th Dec 2012
                ],
                // Calculators:
                [
                    'name' => 'Casio vintage calculator',
                    'type' => 6,
                    'parent_id' => 4,
                    'description' => '',
                    'created_at' => time(), // Now
                    'updated_at' => time(), // Now
                ],
                [
                    'name' => 'Citizen vintage calculator',
                    'type' => 6,
                    'parent_id' => 4,
                    'description' => '',
                    'created_at' => time(), // Now
                    'updated_at' => time(), // Now
                ],
                [
                    'name' => 'Modern scientific calculator',
                    'type' => 7,
                    'parent_id' => 4,
                    'description' => '',
                    'created_at' => time(), // Now
                    'updated_at' => time(), // Now
                ],
                // Bluetooth speakers:
                // There is no bluetooth speakers currently available... :(

                // Orphan record
                [
                    'name' => 'The Big Unknown',
                    'type' => 69,
                    'parent_id' => null,
                    'description' => 'The Big Unknown is so well known so it doesn\'t need a description',
                    'created_at' => 0, // Now
                    'updated_at' => 0, // Now
                ],
            ],

            sample_sibling_entity::class => [
                [
                    'name' => 'Red Mi Note 7',
                    'child_id' => 1,
                    'type' => 3,
                ],
                [
                    'name' => 'HTC Desire',
                    'child_id' => 1,
                ],
                [
                    'name' => 'Red Mi Note 9',
                    'child_id' => 2,
                ],
                [
                    'name' => 'HTC Desire 5',
                    'child_id' => 2,
                    'type' => 3,
                ],
                [
                    'name' => 'Apple iPhone XS',
                    'child_id' => 3,
                ],
                [
                    'name' => 'Apple iPhone XR',
                    'child_id' => 3,
                    'type' => 2,
                ],
                [
                    'name' => 'Asus Tablet',
                    'child_id' => 4,
                ],
                [
                    'name' => 'Asus Giant Tablet',
                    'child_id' => 4,
                ],
                [
                    'name' => 'Apple Mac Pro',
                    'child_id' => 9,
                ],
                [
                    'name' => 'Hp Workstation',
                    'child_id' => 9,
                ],
                [
                    'name' => 'Electronica vintage soviet calculator',
                    'child_id' => 10,
                    'type' => 2,
                ],
                [
                    'name' => 'Commodore vintage calculator',
                    'child_id' => 11,
                ],
                [
                    'name' => 'Sinclair Cambridge Programmable vintage calculator',
                    'child_id' => 12,
                    'type' => 1,
                ],
            ],

            // These actually make less sense than the other ones
            // It's many to many essentially at the moment is used to test weird combination of has one through
            sample_pivot_entity::class => [
                [
                    'parent_id' => 1,
                    'sibling_id' => 1,
                ],
                [
                    'parent_id' => 2,
                    'sibling_id' => 1,
                ],
                [
                    'parent_id' => 3,
                    'sibling_id' => 5,
                ],
                [
                    'parent_id' => 4,
                    'sibling_id' => 6,
                ],
            ]
        ];
    }

    /**
     * Populate database with a few sample record to test more complex things like sorting, filtering, etc.
     *
     * @return array
     */
    protected function create_sample_records() {

        $this->create_tables();
        $records = $this->sample_records();

        foreach ($records as $entity => &$items) {
            foreach ($items as &$record) {
                $record['id'] = $this->db()->insert_record($entity::TABLE, (object) $record);
            }
        }

        return $records;
    }

}

/**
 * Class sample_entity used for testing a entity
 *
 * @property string $name
 * @property int $parent_id
 * @property int $created_at
 * @property int $updated_at
 * @property-read collection $children Children items
 * @property-read collection $siblings Children sibling items
 * @property-read sample_sibling_entity $a_sibling First sibling item connected using a pivot table
 * @property-read sample_passport_entity $passport Passport entity
 * @property-read user $reserved_word_relation To test relation with reserved wor
 * @property-read collection|user[] $reserved_word_relations To test relation with reserved wor
 */
class sample_parent_entity extends entity {

    public const TABLE = 'test__sample_parent';

    public const CREATED_TIMESTAMP = 'created_at';
    public const UPDATED_TIMESTAMP = 'updated_at';

    /**
     * Passport
     *
     * @return has_one
     */
    public function passport(): has_one {
        return $this->has_one(sample_passport_entity::class, 'parent_id');
    }

    /**
     * Sample siblings
     *
     * @return has_many_through
     */
    public function siblings(): has_many_through {
        return $this->has_many_through(
            sample_child_entity::class,
            sample_sibling_entity::class,
            'id',
            'parent_id',
            'id',
            'child_id'
        );
    }

    /**
     * Return a sibling if it exists
     *
     * @return has_one_through
     */
    public function a_sibling(): has_one_through {
        return $this->has_one_through(
            sample_pivot_entity::class,
            sample_sibling_entity::class,
            'id',
            'parent_id',
            'sibling_id',
            'id'
        );
    }

    /**
     * Children relationship
     *
     * @return has_many
     */
    public function children(): has_many {
        return $this->has_many(sample_child_entity::class, 'parent_id');
    }

    public function reversed_children(): has_many {
        return $this->has_many(sample_child_entity::class, 'parent_id')
            ->order_by('id', 'desc');
    }

    /**
     * user is a reserved word in some databases
     *
     * @return has_one_through
     */
    public function reserved_word_relation(): has_one_through {
        return $this->has_one_through(
            sample_pivot_entity::class,
            user::class,
            'id',
            'parent_id',
            'sibling_id',
            'id'
        );
    }

    /**
     * user is a reserved word in some databases
     *
     * @return has_many_through
     */
    public function reserved_word_relations(): has_many_through {
        return $this->has_many_through(
            sample_pivot_entity::class,
            user::class,
            'id',
            'parent_id',
            'sibling_id',
            'id'
        );
    }
}

/**
 * Class sample_entity used for testing a entity
 *
 * @property string $name
 * @property int $created_at
 * @property int $updated_at
 * @property-read collection $children Children items
 */
class sample_passport_entity extends entity {

    public const TABLE = 'test__sample_passport';

    public const CREATED_TIMESTAMP = 'created_at';
    public const UPDATED_TIMESTAMP = 'updated_at';

    protected $with = [
        'parent'
    ];

    /**
     * Children relationship
     *
     * @return belongs_to
     */
    public function parent(): belongs_to {
        return $this->belongs_to(sample_parent_entity::class, 'parent_id');
    }

    public function children(): has_many_through {
        return $this->has_many_through(
            sample_parent_entity::class,
            sample_child_entity::class,
            'parent_id',
            'id',
            'id',
            'parent_id'
        );
    }
}

/**
 * Class sample_entity used for testing a entity
 *
 * @property string $name
 * @property int $parent_id
 * @property int $type
 * @property string $description
 * @property int $created_at
 * @property int $updated_at
 *
 * @property-read string $capital_name Name returned in capital case
 * @property-read sample_parent_entity $parent Name
 */
class sample_child_entity extends entity {

    public const TABLE = 'test__sample_child';

    public function parent(): belongs_to {
        return $this->belongs_to(sample_parent_entity::class, 'parent_id');
    }

    /**
     * This is here only to test clashes between relations and normal properties
     *
     * @return has_one
     */
    public function type(): has_one {
        return $this->has_one(sample_child_entity::class, 'id');
    }

    public function a_type(): belongs_to {
        return $this->belongs_to(sample_child_entity::class, 'type');
    }

    public function passport(): has_one_through {
        return $this->has_one_through(
            sample_passport_entity::class,
            sample_parent_entity::class,
            'id',
            'parent_id',
            'parent_id',
            'id'
        );
    }

    public const CREATED_TIMESTAMP = 'created_at';
    public const UPDATED_TIMESTAMP = 'updated_at';
}

/**
 * This is a sample sibling entity
 *
 * Class sample_sibling_entity
 */
class sample_sibling_entity extends entity {

    public const TABLE = 'test__sample_sibling';

    /**
     * Child this sibling belongs to
     *
     * @return belongs_to
     */
    public function child(): belongs_to {
        return $this->belongs_to(sample_child_entity::class, 'child_id');
    }

}

/**
 * This is a sample sibling entity
 *
 * Class sample_sibling_entity
 *
 * @property-read sample_sibling_entity $sibling Related sibling model
 * @property-read sample_parent_entity $parent Related parent model
 */
class sample_pivot_entity extends entity {

    public const TABLE = 'test__sample_pivot';

    /**
     * Sibling this belongs to
     *
     * @return belongs_to
     */
    public function sibling(): belongs_to {
        return $this->belongs_to(sample_sibling_entity::class, 'sibling_id');
    }

    /**
     * Parent this belongs to
     *
     * @return belongs_to
     */
    public function parent(): belongs_to {
        return $this->belongs_to(sample_parent_entity::class, 'parent_id');
    }

}