<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_flavour
 */

use flavour_learn\definition as learn_definition;
use flavour_perform\definition as perform_definition;
use totara_core\advanced_feature;
use \totara_flavour\helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests flavour helper class
 */
class totara_flavour_helper_testcase extends advanced_testcase {

    /**
     * True if the test flavour has been installed and is available
     * @var bool
     */
    protected $testflavouravailable = false;

    protected function setUp(): void {
        global $CFG;
        require_once($CFG->libdir . '/adminlib.php');
        parent::setUp();
        // When/if we have a second core flavour we should convert our tests to use that instead of the test flavour.
        // The test flavour is available at TL-7812
        $this->testflavouravailable = file_exists("$CFG->dirroot/totara/flavour/flavours/test/classes/definition.php");
    }

    protected function tearDown(): void {
        global $CFG;
        $this->setAdminUser();
        // Make sure the $ADMIN static is not messed up by our flavours.
        unset($CFG->forceflavour);
        unset($CFG->showflavours);
        unset($CFG->currentflavour);
        admin_get_root(true, false);
        $this->testflavouravailable = null;
        parent::tearDown();
    }

    public function test_expected_defaults() {
        global $CFG;

        // Verify default settings.
        $this->assertObjectNotHasAttribute('forceflavour', $CFG);
        $this->assertObjectNotHasAttribute('showflavours', $CFG);
        $this->assertObjectNotHasAttribute('currentflavour', $CFG);
        $this->assertEquals(advanced_feature::DISABLED, get_config('moodle', 'enableappraisals'));

        // We need some flavours for testing.
        $this->assertFileExists("$CFG->dirroot/totara/flavour/flavours/learn/classes/definition.php");

        if ($this->testflavouravailable) {
            // We can only test this if the test flavour is installed.
            $this->assertFileExists("$CFG->dirroot/totara/flavour/flavours/test/classes/definition.php");
        }
    }

    public function test_get_active_flavour_definition_forced() {
        global $CFG;

        if (!$this->testflavouravailable) {
            // If you get this and want to test forced definitions you must install the test plugin at TL-7812.
            $this->markTestSkipped('Forced flavour settings can only be tested if the test definition has been installed.');
            return true; // Not needed but keeps it clear.
        }

        // Nothing active - learn is default.
        $result = helper::get_active_flavour_definition();
        $this->assertInstanceOf('flavour_learn\\definition', $result);

        // Forced flavour.
        set_config('currentflavour', 'flavour_test', 'totara_flavour');
        $CFG->forceflavour = '';
        $result = helper::get_active_flavour_definition();
        $this->assertNull($result);

        $CFG->forceflavour = 'test';
        $result = helper::get_active_flavour_definition();
        $this->assertInstanceOf('flavour_test\\definition', $result);

        $CFG->forceflavour = 'learn';
        $result = helper::get_active_flavour_definition();
        $this->assertInstanceOf('flavour_learn\\definition', $result);

        // Nothing during initial install unless forced.
        set_config('currentflavour', 'flavour_test', 'totara_flavour');

        $this->assertFalse(during_initial_install());
        unset($CFG->rolesactive);
        $this->assertTrue(during_initial_install());

        unset($CFG->forceflavour);
        $result = helper::get_active_flavour_definition();
        $this->assertNull($result);

        $CFG->forceflavour = 'learn';
        $result = helper::get_active_flavour_definition();
        $this->assertInstanceOf('flavour_learn\\definition', $result);

        // Invalid forced.
        $CFG->forceflavour = 'learnxxx';
        $result = helper::get_active_flavour_definition();
        $this->assertNull($result);
        $this->assertDebuggingCalled('Invalid flavour specified in $CFG->forceflavour');
    }

    public function test_get_active_flavour_definition_manual() {
        // Nothing active.
        set_config('currentflavour', '', 'totara_flavour');
        $result = helper::get_active_flavour_definition();
        $this->assertNull($result);

        // Manually activated flavour.
        if ($this->testflavouravailable) {
            set_config('currentflavour', 'flavour_test', 'totara_flavour');
            $result = helper::get_active_flavour_definition();
            $this->assertInstanceOf('flavour_test\\definition', $result);
        }

        set_config('currentflavour', 'flavour_learn', 'totara_flavour');
        $result = helper::get_active_flavour_definition();
        $this->assertInstanceOf('flavour_learn\\definition', $result);

        unset_config('currentflavour', 'totara_flavour');
        $result = helper::get_active_flavour_definition();
        $this->assertNull($result);

        // Invalid manual flavour.
        set_config('currentflavour', 'flavour_xxxxx', 'totara_flavour');
        $result = helper::get_active_flavour_definition();
        $this->assertNull($result);
    }

    public function test_get_available_flavour_definitions() {
        $result = helper::get_available_flavour_definitions();
        $this->assertIsArray($result);
        if ($this->testflavouravailable) {
            $this->assertArrayHasKey('flavour_test', $result);
        }
        $this->assertArrayHasKey('flavour_learn', $result);
        foreach ($result as $component => $flavour) {
            $this->assertStringStartsWith('flavour_', $component);
            $this->assertInstanceOf('totara_flavour\\definition', $flavour);
        }
    }

    public function test_get_active_flavour_notice() {
        global $CFG, $PAGE;

        /** @var core_admin_renderer $output */
        $output = $PAGE->get_renderer('core', 'admin');

        // No flavour.
        set_config('currentflavour', '', 'totara_flavour');
        $result = helper::get_active_flavour_notice($output);
        $this->assertNull($result);

        $CFG->forceflavour = 'learn';
        $result = helper::get_active_flavour_notice($output);
        $this->assertNotNull($result);

        if ($this->testflavouravailable) {
            // Upgrade notice to full.
            $CFG->forceflavour = 'test';
            $result = helper::get_active_flavour_notice($output);
            $this->assertNotNull($result);
        }
    }

    public function test_get_active_flavour_component() {
        global $CFG;

        // No flavour - defaults to learn.
        $result = helper::get_active_flavour_component();
        $this->assertEquals('flavour_learn', $result);

        $CFG->forceflavour = 'perform';
        $result = helper::get_active_flavour_component();
        $this->assertSame('flavour_perform', $result);
    }

    public function test_get_default_settings() {
        global $CFG;

        // No flavour - defaults to learn.
        $learn_definition = new learn_definition();
        $result = helper::get_defaults_setting();
        $this->assertSame($learn_definition->get_default_settings(), $result);

        $CFG->forceflavour = 'perform';
        $perform_definition = new perform_definition();
        $result = helper::get_defaults_setting();
        $this->assertSame($perform_definition->get_default_settings(), $result);

        if ($this->testflavouravailable) {
            $CFG->forceflavour = 'test';
            $result = helper::get_defaults_setting();
            $this->assertSame(array(), $result);
        }
    }

    public function test_get_enforced_settings() {
        global $CFG;

        if (!$this->testflavouravailable) {
            // If you get this and want to test forced settings you must install the test plugin at TL-7812.
            $this->markTestSkipped('Forced flavour settings can only be tested if the test definition has been installed.');
            return true; // Not needed but keeps it clear.
        }

        // No flavour set - uses learn by default.
        $result = helper::get_enforced_settings();
        $learn_definition = new learn_definition();
        $this->assertSame($learn_definition->get_enforced_settings(), $result);

        $CFG->forceflavour = 'perform';
        $result = helper::get_enforced_settings();
        $perform_definition = new perform_definition();
        $this->assertSame($perform_definition->get_enforced_settings(), $result);

        $CFG->forceflavour = 'test';
        $result = helper::get_enforced_settings();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('moodle', $result);
        $this->assertArrayNotHasKey('', $result);
        // Test a few settings to verify the format.
        $this->assertSame(advanced_feature::DISABLED, $result['moodle']['enableappraisals']);
        $this->assertSame('', $result['theme_customtotararesponsive']['customcss']);
    }

    public function test_get_prohibited_settings() {
        global $CFG;

        if (!$this->testflavouravailable) {
            // If you get this and want to test prohibited settings you must install the test plugin at TL-7812.
            $this->markTestSkipped('Prohibited flavour settings can only be tested if the test definition has been installed.');
            return true; // Not needed but keeps it clear.
        }

        // No flavour defaults to learn.
        $learn_definition = new learn_definition();
        $result = helper::get_prohibited_settings();
        $this->assertSame($learn_definition->get_prohibited_settings(), $result);

        $CFG->forceflavour = 'perform';
        $perform_definition = new perform_definition();
        $result = helper::get_prohibited_settings();
        $this->assertSame($perform_definition->get_prohibited_settings(), $result);

        $CFG->forceflavour = 'test';
        $result = helper::get_prohibited_settings();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('moodle', $result);
        $this->assertArrayNotHasKey('', $result);
        // Test a few settings to verify the format.
        $this->assertSame(true, $result['moodle']['enableappraisals']);
        $this->assertSame(true, $result['theme_customtotararesponsive']['customcss']);
    }

    public function test_execute_post_install_steps() {
        global $CFG;

        if (!$this->testflavouravailable) {
            // If you get this and want to test post install steps you must install the test plugin at TL-7812.
            $this->markTestSkipped('Flavour post install steps can only be tested if the test definition has been installed.');
            return true; // Not needed but keeps it clear.
        }

        $this->setAdminUser();

        $CFG->forceflavour = 'test';
        helper::execute_post_install_steps();

        $this->assertSame('flavour_test', get_config('totara_flavour', 'currentflavour'));
        $this->assertEquals(advanced_feature::DISABLED, get_config('moodle', 'enableappraisals'));
    }

    public function test_execute_post_upgrade_steps() {
        global $CFG;

        if (!$this->testflavouravailable) {
            // If you get this and want to test post upgrade steps you must install the test plugin at TL-7812.
            $this->markTestSkipped('Flavour post upgrade steps can only be tested if the test definition has been installed.');
            return true; // Not needed but keeps it clear.
        }

        $this->setAdminUser();

        $CFG->forceflavour = 'test';
        helper::execute_post_upgrade_steps();

        $this->assertSame('flavour_test', get_config('totara_flavour', 'currentflavour'));
        $this->assertEquals(advanced_feature::DISABLED, get_config('moodle', 'enableappraisals'));
    }

    public function test_execute_post_upgradesettings_steps() {
        global $CFG;

        if (!$this->testflavouravailable) {
            // If you get this and want to test post upgrade settings steps you must install the test plugin at TL-7812.
            $this->markTestSkipped('Flavour post upgrade settings steps can only be tested if the test definition has been installed.');
            return true; // Not needed but keeps it clear.
        }

        $this->setAdminUser();

        $CFG->forceflavour = 'test';
        helper::execute_post_upgradesettings_steps();

        $this->assertSame('flavour_test', get_config('totara_flavour', 'currentflavour'));
        $this->assertEquals(advanced_feature::DISABLED, get_config('moodle', 'enableappraisals'));
    }

    public function test_set_active_flavour() {

        if (!$this->testflavouravailable) {
            // If you get this and want to test setting the active flavour you must install the test plugin at TL-7812.
            $this->markTestSkipped('Flavour set active can only be tested if the test definition has been installed.');
            return true; // Not needed but keeps it clear.
        }

        $this->setAdminUser();

        helper::set_active_flavour('flavour_test');

        $this->assertSame('flavour_test', get_config('totara_flavour', 'currentflavour'));
        $this->assertEquals(advanced_feature::DISABLED, get_config('moodle', 'enableappraisals'));

        helper::set_active_flavour('flavour_learn');

        $this->assertSame('flavour_learn', get_config('totara_flavour', 'currentflavour'));
        $this->assertEquals(advanced_feature::DISABLED, get_config('moodle', 'enableappraisals'));

        // Invalid value.
        try {
            helper::set_active_flavour('test');
            $this->fail('coding_exception expected for invalid flavour name');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
            $this->assertEquals('Coding error detected, it must be fixed by a programmer: Invalid flavour component name', $ex->getMessage());
        }
    }
}
