<?php
/**
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core
 */
namespace core\entity;

use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;

/**
 * Entity class for table "enrol"
 *
 * @property int            $id
 * @property string         $enrol
 * @property int            $status
 * @property int            $courseid
 * @property int            $sortorder
 * @property string|null    $name
 * @property int            $enrolperiod
 * @property int            $enrolstartdate
 * @property int            $enrolenddate
 * @property int            $expirynotify
 * @property int            $expirythreshold
 * @property int            $notifyall
 * @property string|null    $password
 * @property string|null    $cost
 * @property string|null    $currency
 * @property int            $roleid
 * @property int|null       $customint1
 * @property int|null       $customint2
 * @property int|null       $customint3
 * @property int|null       $customint4
 * @property int|null       $customint5
 * @property int|null       $customint6
 * @property int|null       $customint7
 * @property int|null       $customint8
 * @property string|null    $customchar1
 * @property string|null    $customchar2
 * @property string|null    $customchar3
 * @property string|null    $customdec1
 * @property string|null    $customdec2
 * @property string|null    $customtext1
 * @property string|null    $customtext2
 * @property string|null    $customtext3
 * @property string|null    $customtext4
 * @property int            $timecreated
 * @property int            $timemodified
 * @property-read  cohort|null $cohort
 *
 * @method static enrol_repository repository()
 */
final class enrol extends entity {
    /**
     * @var string
     */
    public const TABLE = 'enrol';

    /**
     * @var string
     */
    public const CREATED_TIMESTAMP = 'timecreated';

    /**
     * @var string
     */
    public const UPDATED_TIMESTAMP = 'timemodified';

    /**
     * @return bool
     */
    public function is_enabled(): bool {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/enrollib.php");

        $status = $this->get_attribute('status');
        return ENROL_INSTANCE_ENABLED === (int) $status;
    }

    /**
     * @return bool
     */
    public function is_disabled(): bool {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/enrollib.php");

        $status = $this->get_attribute('status');
        return ENROL_INSTANCE_DISABLED === (int) $status;
    }

    /**
     * Cohort associated with enrol method.
     * When using this relationship, filter enrols where enrol = cohort.
     *
     * @return belongs_to
     */
    public function cohort(): belongs_to {
        return $this->belongs_to(cohort::class,  'customint1')->select('*');
    }
}