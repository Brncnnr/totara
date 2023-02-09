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

use totara_core\advanced_feature;
use \totara_flavour\overview;
use \totara_flavour\helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests flavour overview class
 */
class totara_flavour_overview_testcase extends advanced_testcase {

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
            $this->assertFileExists("$CFG->dirroot/totara/flavour/flavours/test/classes/definition.php");
        }
    }

    public function test_current_flavour() {
        global $CFG;
        $this->setAdminUser();

        // Defaults to learn
        $overview = new overview();
        $this->assertSame('flavour_learn', $overview->currentflavour);

        if ($this->testflavouravailable) {
            $CFG->forceflavour = 'test';
            helper::set_active_flavour('flavour_test');
            $overview = new overview();
            $this->assertSame('flavour_test', $overview->currentflavour);
        }

        unset($CFG->forceflavour);
        helper::set_active_flavour('flavour_perform');
        $overview = new overview();
        $this->assertSame('flavour_perform', $overview->currentflavour);
    }

    public function test_flavours() {
        global $CFG;

        $this->setAdminUser();

        // Show learn if nothing configured.
        $overview = new overview();
        $this->assertSame(array('flavour_learn'), array_keys($overview->flavours));
        $this->assertInstanceOf('flavour_learn\\definition', $overview->flavours['flavour_learn']);

        // Show configured in specified order.
        $CFG->showflavours = 'engage,learn';
        $overview = new overview();
        $this->assertSame(array('flavour_engage', 'flavour_learn'), array_keys($overview->flavours));
        $this->assertInstanceOf('flavour_learn\\definition', $overview->flavours['flavour_learn']);
        $this->assertInstanceOf('flavour_engage\\definition', $overview->flavours['flavour_engage']);

        // Hide all flavours (default still shown).
        $CFG->showflavours = '';
        $overview = new overview();
        $this->assertSame(array('flavour_learn'), array_keys($overview->flavours));

        // Make sure active is included, as last if not in the list.
        helper::set_active_flavour('flavour_perform');
        unset($CFG->showflavours);
        $overview = new overview();
        $this->assertSame(array('flavour_perform'), array_keys($overview->flavours));
        $this->assertInstanceOf('flavour_perform\\definition', $overview->flavours['flavour_perform']);

        $CFG->showflavours = 'learn,engage,learn_engage,learn_perform,learn_perform_engage,learn_professional,perform_engage';
        $overview = new overview();
        $this->assertSame(array(
            'flavour_learn',
            'flavour_engage',
            'flavour_learn_engage',
            'flavour_learn_perform',
            'flavour_learn_perform_engage',
            'flavour_learn_professional',
            'flavour_perform_engage',
            'flavour_perform',
        ), array_keys($overview->flavours));

        $CFG->showflavours = '';
        $overview = new overview();
        $this->assertSame(array('flavour_perform'), array_keys($overview->flavours));
        $this->assertInstanceOf('flavour_perform\\definition', $overview->flavours['flavour_perform']);
    }

    public function test_get_flavour_to_enforce() {
        global $CFG;

        if (!$this->testflavouravailable) {
            // If you get this and want to test the overview of flavours you must install the test plugin at TL-7812.
            $this->markTestSkipped('You must install the test flavour in order to test the overview functionality.');
            return true; // Not needed but keeps it clear.
        }

        $this->setAdminUser();

        $overview = new overview();
        $result = $overview->get_flavour_to_enforce();
        $this->assertSame('flavour_learn', $result);

        $CFG->forceflavour = 'test';
        $overview = new overview();
        $result = $overview->get_flavour_to_enforce();
        $this->assertSame('flavour_test', $result);

        helper::set_active_flavour('flavour_test');
        $overview = new overview();
        $result = $overview->get_flavour_to_enforce();
        $this->assertSame('flavour_test', $result);

        set_config('enablegoals', advanced_feature::ENABLED);
        $overview = new overview();
        $result = $overview->get_flavour_to_enforce();
        $this->assertSame('flavour_test', $result);

        helper::set_active_flavour('flavour_test');
        $overview = new overview();
        $result = $overview->get_flavour_to_enforce();
        $this->assertSame('flavour_test', $result);

        $CFG->enablegoals = 1;
        $CFG->config_php_settings['enablegoals'] = 1;
        $overview = new overview();
        $result = $overview->get_flavour_to_enforce();
        $this->assertSame('flavour_test', $result);
    }
}
