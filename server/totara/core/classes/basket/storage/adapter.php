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
 * @package totara_core
 */

namespace totara_core\basket\storage;

defined('MOODLE_INTERNAL') || die();

/**
 * DEPRECATED
 *
 * generic storage adapter to store data linked ot an id.
 *
 * @deprecated since Totara 13
 */
interface adapter {

    /**
     * save given data with assigned id, data and id types are deliberately not set
     * as we the storage itself does not set rules on how it should look like.
     *
     * @param mixed string $id
     * @param mixed $data
     * @return mixed
     *
     * @deprecated since Totara 13
     */
    public function save($id, $data);

    /**
     * load data from storage by given id
     *
     * @param $id
     * @return mixed
     *
     * @deprecated since Totara 13
     */
    public function load($id);

    /**
     * delete data for the given id from the storage
     *
     * @param $id
     * @return mixed
     *
     * @deprecated since Totara 13
     */
    public function delete($id);

}