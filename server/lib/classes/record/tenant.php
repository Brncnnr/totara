<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @package totara_tenant
 */

namespace core\record;

use stdClass;
use context_tenant;

defined('MOODLE_INTERNAL') || die();

/**
 * Class representing tenant database record.
 *
 * The namespace/location of this class is derived from the location of install.xml
 * and database table name.
 *
 * NOTE: this class is not supposed to contain any logic,
 *       main purpose is to allow code completion and to cache database requests.
 *
 * @property-read int $id
 * @property-read string $name name of tenant
 * @property-read string $idnumber tenant identified [a-z][a-z0-9]*
 * @property-read string $description
 * @property-read int $descriptionformat
 * @property-read int $suspended 1 means members cannot log in and does not receive messages
 * @property-read int $categoryid tenant top course category
 * @property-read int $cohortid audience with tenant participants
 * @property-read int $timecreated timestampt with tenant creation date
 * @property-read int $usercreated id of user that created the tenant
 * @property-read context_tenant $context tenant context instance
 */
final class tenant extends stdClass {
    /** @var array */
    private static $tenants = [];

    // NOTES:
    //  - do not add any other instance properties here, we need (array) cast compatibility
    //  - keep this is sync with database table "tenant" defined in lib/db/install.xml
    //  - the properties are defined as read-only above so that PHPStorm highlights invalid modifications
    public $id;
    public $name;
    public $idnumber;
    public $description;
    public $descriptionformat;
    public $suspended;
    public $categoryid;
    public $cohortid;
    public $timecreated;
    public $usercreated;

    /**
     * tenant constructor.
     * @param array $data
     */
    protected function __construct(array $data) {
        foreach ($data as $k => $v) {
            $this->$k = $v;
        }
    }

    public function __set($name, $value) {
        debugging('Properties cannot be added to record instance', DEBUG_DEVELOPER);
    }

    public function __get($name) {
        if ($name === 'context') {
            return context_tenant::instance($this->id);
        }
        debugging('Unknown property of record instance accessed', DEBUG_DEVELOPER);
        return null;
    }

    public function __isset($name) {
        if ($name === 'context') {
            return true;
        }
        return isset($this->$name);
    }

    /**
     * Create tenant object.
     *
     * NOTE: this method is using request caching of all tenants.
     *
     * @param int $id
     * @param int $strictness MUST_EXIST or IGNORE_MISSING
     * @return tenant|null
     */
    public static function fetch(int $id, int $strictness = MUST_EXIST): ?tenant {
        global $DB;

        if (PHPUNIT_TEST) { // Caching is no good for testing.
            self::reset_caches($id);
        }

        if (!empty(self::$tenants[$id])) {
            return new self(self::$tenants[$id]);
        }

        $tenant = $DB->get_record('tenant', ['id' => $id], '*', $strictness);
        if ($tenant) {
            $tenant = (array)$tenant;
            self::$tenants[$id] = $tenant;
            return new self(self::$tenants[$id]);
        }

        return null;
    }

    /**
     * Reset internal caches.
     *
     * NOTE: the number of tenants in the system should be in the order of tens,
     *       so the size of request cache should not be an issue.
     *
     * @param int $id purge caches for one tenant or if null all tenants
     */
    public static function reset_caches(int $id = null): void {
        if ($id === null) {
            self::$tenants = [];
        } else {
            unset(self::$tenants[$id]);
        }
    }
}
