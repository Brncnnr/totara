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

use core\record\tenant;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests covering tenant record class.
 */
class totara_tenant_core_record_tenant_testcase extends advanced_testcase {
    public function test_class() {
        global $DB;

        $generator = \totara_tenant\testing\generator::instance();
        $generator->enable_tenants();
        $this->setAdminUser();

        $tenant = $generator->create_tenant();

        $record = tenant::fetch($tenant->id);
        $this->assertInstanceOf(tenant::class, $record);
        $this->assertInstanceOf('stdClass', $record);

        $dbrecord = $DB->get_record('tenant', ['id' => $tenant->id]);
        $this->assertSame((array)$record, (array)$dbrecord);

        $this->assertTrue(isset($record->context));
        $this->assertFalse(property_exists($record, 'context'));
        $this->assertInstanceOf(context_tenant::class, $record->context);
        $this->assertSame($record->context->instanceid, $record->id);

        $this->assertDebuggingNotCalled();
        $record->xxxx;
        $this->assertDebuggingCalled('Unknown property of record instance accessed');

        $this->assertFalse(property_exists($record, 'xxxx'));
        $this->assertDebuggingNotCalled();
        $record->xxxx = 1;
        $this->assertDebuggingCalled('Properties cannot be added to record instance');
        $this->assertFalse(property_exists($record, 'xxxx'));

        $this->assertNull(tenant::fetch(-1, IGNORE_MISSING));

        try {
            tenant::fetch(-1, MUST_EXIST);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(\dml_missing_record_exception::class, $e);
        }
    }
}
