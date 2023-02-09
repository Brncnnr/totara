<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Array based data iterator.
 *
 * @package    core_phpunit
 * @copyright  2012 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_phpunit;

use Iterator, ArrayAccess, Countable, ArrayIterator;
use Exception, Error;

/**
 * Based on array iterator code from PHPUnit documentation by Sebastian Bergmann
 * with new constructor parameter for different array types.
 *
 * @package    core
 * @category   phpunit
 * @copyright  2012 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class ArrayDataSet implements Iterator, ArrayAccess, Countable {
    /** @var DataSet_Table[] */
    protected $tables = array();

    /** @var ArrayIterator */
    private $iterator;

    public function __construct(array $array) {
        foreach ($array as $tablename => $data) {
            if (isset($data[0][0])) {
                $columns = array_shift($data);
                $rows = [];
                foreach ($data as $values) {
                    $rows[] = array_combine($columns, $values);
                }
            } else {
                $rows = $data;
            }
            $this->tables[$tablename] = new DataSet_Table($rows);
        }

        $this->iterator = new ArrayIterator($this->tables);
    }

    public function getTableNames(): array {
        return array_keys($this->tables);
    }

    public function getTableMetaData(string $tablename) {
        return new DataSet_Table_Metadata(array_keys($this->tables[$tablename][0]));
    }

    public function getIterator() {
        return new ArrayIterator($this->tables);
    }

    public function getTables(): array {
        return $this->tables;
    }

    public function getTable(string $tablename): DataSet_Table {
        return $this->tables[$tablename];
    }

    #[\ReturnTypeWillChange]
    public function count() {
        return count($this->tables);
    }

    #[\ReturnTypeWillChange]
    public function current() {
        return $this->iterator->current();
    }

    #[\ReturnTypeWillChange]
    public function next() {
        $this->iterator->next();
    }

    #[\ReturnTypeWillChange]
    public function key() {
        return $this->iterator->key();
    }

    #[\ReturnTypeWillChange]
    public function valid() {
        return $this->iterator->valid();
    }

    #[\ReturnTypeWillChange]
    public function rewind() {
        $this->iterator->rewind();
    }

    #[\ReturnTypeWillChange]
    public function offsetExists($offset) {
        return array_key_exists($offset, $this->tables);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset) {
        return $this->tables[$offset];
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value) {
        throw new Exception('Cannot modify dataset tables');
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($offset) {
        throw new Exception('Cannot modify dataset tables');
    }

    public static function createFromCSV($files, $delimiter, $enclosure, $escape) {
        $array = [];
        foreach ($files as $tablename => $file) {
            $lines = array_map('str_getcsv', file($file));
            $columns = array_shift($lines);
            $rows = [];
            foreach ($lines as $record) {
                $rows[] = array_combine($columns, $record);
            }
            $array[$tablename] = $rows;
        }
        return new self($array);
    }

    public static function createFromXML($xmlFile) {
        $xml = simplexml_load_string(file_get_contents($xmlFile));

        if ($xml->getName() !== 'dataset') {
            throw new Error('Invalid data set file: ' . $xmlFile);
        }

        $array = [];

        foreach ($xml->children() as $table) {
            if ($table->getName() !== 'table') {
                continue;
            }
            $tablename = strval($table->attributes()['name']);
            $array[$tablename] = [];
            $columns = [];
            foreach ($table as $column) {
                if ($column->getName() !== 'column') {
                    continue;
                }
                $columns[] = $column->__toString();
            }
            if (!$columns) {
                continue;
            }
            $rows = [];
            foreach ($table as $row) {
                if ($row->getName() !== 'row') {
                    continue;
                }
                $record = [];
                foreach ($row as $value) {
                    if ($value->getName() !== 'value') {
                        continue;
                    }
                    $record[] = $value->__toString();
                }
                $rows[] = array_combine($columns, $record);
            }
            $array[$tablename] = $rows;
        }
        return new self($array);
    }

    public static function createFromFlatXML($xmlFile) {
        $xml = simplexml_load_string(file_get_contents($xmlFile));

        if ($xml->getName() !== 'dataset') {
            throw new Error('Invalid data set file: ' . $xmlFile);
        }

        $array = [];
        foreach ($xml->children() as $table) {
            $tablename = $table->getName();
            if (!isset($array[$tablename])) {
                $array[$tablename] = [];
            }
            $row = [];
            foreach ($table->attributes() as $column) {
                $row[$column->getName()] = $column->__toString();
            }
            if ($row) {
                $array[$tablename][] = $row;
            }
        }
        return new self($array);
    }
}
