<?php
/**
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_msteams
 */

defined('MOODLE_INTERNAL') || die();

// NOTE: Declare one by one instead of bulky `use \totara_msteams\{foo, bar}` to possibly avoid merge conflict

use totara_core\http\client;
use totara_msteams\botfw\auth\authoriser;
use totara_msteams\botfw\auth\bearer;
use totara_msteams\botfw\auth\jwt;
use totara_msteams\botfw\auth\token\token;
use totara_msteams\botfw\hook\hook;
use totara_msteams\botfw\logger\logger;
use totara_msteams\botfw\notification\notification;
use totara_msteams\botfw\notification\subscription;
use totara_msteams\botfw\resolver\resolver;
use totara_msteams\botfw\router\router;
use totara_msteams\botfw\storage\storage;
use totara_msteams\botfw\validator\validator;
use totara_msteams\check\checkable;
use totara_msteams\check\status;
use totara_msteams\check\verifier;
use totara_msteams\manifest\generator;
use totara_msteams\manifest\output;
use totara_msteams\oidcclient;
use totara_msteams\watcher\watchers;

require_once(__DIR__ . '/../../core/tests/code_quality_testcase.php');

/**
 * Class totara_msteams_code_quality_testcase
 */
class totara_msteams_code_quality_testcase extends totara_core_code_quality_testcase_base {

    /**
     * @var string[]
     */
    private $tested_classes = [
        // self test
        totara_msteams_code_quality_testcase::class,

        oidcclient::class,
        checkable::class,
        verifier::class,
        status::class,
        generator::class,
        watchers::class,

        // bot framework
        bearer::class,
        jwt::class,
        subscription::class,
        hook::class,
    ];

    /**
     * @inheritDoc
     */
    protected function get_classes_to_test(): array {
        $tested_classes = $this->tested_classes;
        // Load all checkable classes
        self::add_inherited_classes($tested_classes, 'checkable', null, 'classes/check');
        // Load all exception classes
        // self::add_inherited_classes($tested_classes, 'exception', null, 'classes/exception');
        // Load all output classes
        self::add_inherited_classes($tested_classes, null, output::class, 'classes/manifest/outputs');
        // Load all template classes
        self::add_inherited_classes($tested_classes, 'output', null, 'classes/output');
        // Load all template builder classes
        self::add_inherited_classes($tested_classes, 'output\builder', null, 'classes/output/builder');
        // Load all xxx_helper and xxx_list classes
        self::add_matching_classes($tested_classes, '/^totara_msteams\\\\[^\\\\]+(_helper|_list)$/', 'classes');
        self::add_inherited_classes($tested_classes, 'my\\helpers', null, 'classes/my');
        // Add bot framework classes
        self::add_matching_classes($tested_classes, '/^totara_msteams\\\\botfw\\\\[^\\\\]+(_builder)$/', 'classes/botfw');
        self::add_inherited_classes($tested_classes, 'botfw', null, 'classes/botfw');
        self::add_inherited_classes($tested_classes, 'botfw\\http', null, 'classes/botfw/http');
        self::add_inherited_classes($tested_classes, 'botfw\\card', null, 'classes/botfw/card');
        self::add_inherited_classes($tested_classes, 'botfw\\internal', null, 'classes/botfw/internal');
        self::add_inherited_classes($tested_classes, 'botfw\\util', null, 'classes/botfw/internal');
        // NOTE: ORM classes are not compatible with code quality test at the moment.
        // self::add_inherited_classes($tested_classes, 'botfw\\entity', entity::class, 'classes/botfw/entity');
        // self::add_inherited_classes($tested_classes, 'botfw\\repository', repository::class, 'classes/botfw/repository');
        self::add_inherited_classes($tested_classes, null, client::class, 'classes/botfw');
        self::add_inherited_classes($tested_classes, null, token::class, 'classes/botfw');
        self::add_inherited_classes($tested_classes, null, authoriser::class, 'classes/botfw');
        self::add_inherited_classes($tested_classes, null, logger::class, 'classes/botfw');
        self::add_inherited_classes($tested_classes, null, notification::class, 'classes/botfw');
        self::add_inherited_classes($tested_classes, null, router::class, 'classes/botfw');
        self::add_inherited_classes($tested_classes, null, resolver::class, 'classes/botfw');
        self::add_inherited_classes($tested_classes, null, storage::class, 'classes/botfw');
        self::add_inherited_classes($tested_classes, null, validator::class, 'classes/botfw');
        return $tested_classes;
    }
}
