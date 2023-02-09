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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

use core\orm\entity\entity;
use core\orm\entity\model;
use mod_approval\model\workflow\ordinal\ordinal;

class mod_approval_test_parent_entity extends entity {
    public const TABLE = 'approval_test_parent';
    public static function table(): xmldb_table {
        $table = new xmldb_table(self::TABLE);
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('timing', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        return $table;
    }
}

class mod_approval_test_parent_model extends model {
    protected $entity_attribute_whitelist = [
        'id',
        'timing',
    ];
    protected static function get_entity_class(): string {
        return mod_approval_test_parent_entity::class;
    }
}

class mod_approval_test_child_entity extends entity {
    public const TABLE = 'approval_test_child';
    public static function table(): xmldb_table {
        $table = new xmldb_table(self::TABLE);
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('parenting', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('ordinary', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('tag', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timer', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('foreign', XMLDB_KEY_FOREIGN, ['parenting'], mod_approval_test_parent_entity::TABLE, ['id']);
        $table->add_index('unique', XMLDB_INDEX_UNIQUE, ['parenting', 'ordinary']);
        return $table;
    }
}

class mod_approval_test_child_model extends model {
    protected $entity_attribute_whitelist = [
        'id',
        'parenting',
        'tag',
        'timer',
    ];
    protected $model_accessor_whitelist = [
        'number',
    ];
    protected static function get_entity_class(): string {
        return mod_approval_test_child_entity::class;
    }
    public function get_number(): int {
        return $this->entity->ordinary;
    }
}

class mod_approval_test_notrack_ordinal implements ordinal {
    public function table_name(): string {
        return mod_approval_test_child_entity::TABLE;
    }
    public function foreign_key(): string {
        return 'parenting';
    }
    public function ordinal_field(): string {
        return 'ordinary';
    }
    public function timestamp_field(): ?string {
        return null;
    }
    public function map_ordinal_number(model $item): int {
        return $item->number;
    }
}

class mod_approval_test_track_ordinal extends mod_approval_test_notrack_ordinal {
    public function timestamp_field(): ?string {
        return 'timer';
    }
}
