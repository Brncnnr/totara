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
 * @author  Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\traits;

defined('MOODLE_INTERNAL') || die();

/**
 * Class crud_mapper
 */
trait crud_mapper {

    /**
     * Load object from a database record.
     * @param integer $strictness   IGNORE_MISSING, IGNORE_MULTIPLE or MUST_EXIST
     * @return self
     */
    protected function crud_load(int $strictness = MUST_EXIST): self {
        global $DB;

        if (!$this->id) {
            return $this;
        }

        $record = $DB->get_record(self::DBTABLE, ['id' => $this->id], '*', $strictness);
        if (!$record) {
            $this->id = 0;
            return $this;
        }
        $this->map_object($record);
        return $this;
    }

    /**
     * Save object to a database.
     */
    protected function crud_save(): void {
        global $DB;

        $todb = $this->unmap_object();

        if ($this->id) {
            $DB->update_record(self::DBTABLE, $todb);
        } else {
            $this->id = $DB->insert_record(self::DBTABLE, $todb);
        }
        // Reload object with new values.
        $this->crud_load();
    }

    /**
     * Load object from a given object.
     * @param \stdClass $object
     * @param boolean $strict   Set false to suppress debugging() messages for non-existent properties.
     * @return self
     */
    protected function map_object(\stdClass $object, bool $strict = true): self {

        foreach ((array)$object as $property => $value) {
            if (property_exists($this, $property)) {
                $this->{$property} = $value;
            } else if ($strict) {
                debugging("Provided object does not have a \"{$property}\" field", DEBUG_DEVELOPER);
            }
        }
        return $this;
    }

    /**
     * Convert object into a generic object.
     * @return \stdClass
     */
    protected function unmap_object(): \stdClass {
        global $DB;

        $columns = array_keys($DB->get_columns(self::DBTABLE));

        $todb = new \stdClass();
        foreach (get_object_vars($this) as $property => $value) {
            if (in_array($property, $columns)) {
                $todb->{$property} = $value;
            }
        }
        return $todb;
    }

    /**
     * Load record from $id, if it is the invalid $id, that does not exist within the database.
     * @param int $id
     * @return self
     */
    public static function seek(int $id): self {
        $self = new static();
        $self->id = $id;
        return $self->crud_load(IGNORE_MISSING);
    }
}
