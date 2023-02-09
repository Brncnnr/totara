<?php
/*
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package core
 */

namespace core\testing;

use stdClass, coding_exception;

/**
 * Data generator class for PHPUnit, behat and other tools that need to create fake test sites.
 */
abstract class block_generator extends component_generator {
    /** @var number of created instances */
    protected $instancecount = 0;

    /**
     * To be called from data reset code only,
     * do not use in tests.
     * @return void
     */
    public function reset() {
        parent::reset();
        $this->instancecount = 0;
    }

    /**
     * Returns block name
     * @return string name of block that this class describes
     */
    public function get_blockname() {
        $matches = null;
        if (!preg_match('/^block_([a-z0-9_]+)\\\\testing\\\\generator$/', get_class($this), $matches)) {
            throw new coding_exception('Invalid block generator class name: ' . get_class($this));
        }

        if (empty($matches[1])) {
            throw new coding_exception('Invalid block generator class name: ' . get_class($this));
        }
        return $matches[1];
    }

    /**
     * Fill in record defaults.
     *
     * @param stdClass $record
     * @return stdClass
     */
    protected function prepare_record(stdClass $record) {
        $record->blockname = $this->get_blockname();
        if (!isset($record->parentcontextid)) {
            $record->parentcontextid = \context_system::instance()->id;
        }
        if (!isset($record->showinsubcontexts)) {
            $record->showinsubcontexts = 0;
        }
        if (!isset($record->pagetypepattern)) {
            $record->pagetypepattern = '*';
        }
        if (!isset($record->subpagepattern)) {
            $record->subpagepattern = null;
        }
        if (!isset($record->defaultregion)) {
            $record->defaultregion = 'side-pre';
        }
        if (!isset($record->defaultweight)) {
            $record->defaultweight = 5;
        }
        if (!isset($record->configdata)) {
            $record->configdata = null;
        }
        return $record;
    }

    /**
     * Create a test block instance.
     *
     * The $record passed in becomes the basis for the new row added to the
     * block_instances table. You only need to supply the values of interest.
     * Any missing values have sensible defaults filled in.
     *
     * The $options array provides additional data, not directly related to what
     * will be inserted in the block_instance table, which may affect the block
     * that is created. The meanings of any data passed here depends on the particular
     * type of block being created.
     *
     * @param array|stdClass $record forms the basis for the entry to be inserted in the block_instances table.
     * @param array $options further, block-specific options to control how the block is created.
     * @return stdClass the block_instance record that has just been created.
     */
    public function create_instance($record = null, $options = array()) {
        global $DB;

        $this->instancecount++;

        $record = (object)(array)$record;
        $this->preprocess_record($record, $options);
        $record = $this->prepare_record($record);

        if (empty($record->timecreated)) {
            $record->timecreated = time();
        }
        if (empty($record->timemodified)) {
            $record->timemodified = time();
        }

        $id = $DB->insert_record('block_instances', $record);
        \context_block::instance($id);

        $instance = $DB->get_record('block_instances', array('id' => $id), '*', MUST_EXIST);
        return $instance;
    }

    /**
     * Can be overridden to do block-specific processing. $record can be modified
     * in-place.
     *
     * @param stdClass $record the data, before defaults are filled in.
     * @param array $options further, block-specific options, as passed to {@link create_instance()}.
     */
    protected function preprocess_record(stdClass $record, array $options) {
    }
}
