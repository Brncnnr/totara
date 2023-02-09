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
 * @package hierarchy_organisation
 */

namespace hierarchy_organisation\entity;

use core\orm\collection;
use core\entity\expand;
use core\entity\expandable;
use hierarchy_position\entity\position;
use hierarchy_position\entity\position_framework;
use totara_hierarchy\entity\hierarchy_item;
use totara_job\entity\job_assignment;

/**
 * @property string $shortname Short name
 * @property string $description Competency description
 * @property string $idnumber External systems ID number
 * @property int $frameworkid Framework ID
 * @property string $path Competency path in the hierarchy
 * @property int $parentid Parent competency ID
 * @property bool $visible Visible flag
 * @property int $timecreated Time created
 * @property int $timemodified Time modified
 * @property int $usermodified User modified
 * @property string $fullname Full competency name
 * @property int $depthlevel Depth level in the hierarchy
 * @property int $typeid Competency type ID
 * @property string $sortthread Sortorder
 * @property bool $totarasync Totara sync flag
 *
 * @method static organisation_repository repository()
 *
 * @property-read position $parent Parent item
 * @property-read collection $children Immediate children
 * @property-read position_framework $framework Position framework
 *
 * @package hierarchy_organisation\entity
 */
class organisation extends hierarchy_item implements expandable {

    use expand;

    protected const EXPAND_TABLE = job_assignment::TABLE;
    protected const EXPAND_SELECT_COLUMN = 'userid';
    protected const EXPAND_QUERY_COLUMN = 'organisationid';

    public const TABLE = 'org';
}