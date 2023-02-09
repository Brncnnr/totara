<?php
/**
 * This file is part of Totara Core
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package core_course
 */

namespace core_course\totara_catalog\course\dataholder_factory;

use totara_catalog\dataholder;
use totara_catalog\dataholder_factory;
use totara_contentmarketplace\totara_catalog\course_logo_dataholder_factory;

class course_logo extends dataholder_factory {
    /**
     * @return dataholder[]
     */
    public static function get_dataholders(): array {
        return course_logo_dataholder_factory::get_dataholders();
    }
}