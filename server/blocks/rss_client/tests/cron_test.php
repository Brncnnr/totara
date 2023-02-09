<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * PHPunit tests for rss client cron.
 *
 * @package    block_rss_client
 * @copyright  2015 University of Nottingham
 * @author     Neill Magill <neill.magill@nottingham.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../../moodleblock.class.php');
require_once(__DIR__ . '/../block_rss_client.php');

/**
 * Class for the PHPunit tests for rss client cron.
 *
 * @package    block_rss_client
 * @copyright  2015 Universit of Nottingham
 * @author     Neill Magill <neill.magill@nottingham.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_rss_client_cron_testcase extends advanced_testcase {
    /**
     * Test that when a record has a skipuntil time that is greater
     * than the current time the attempt is skipped.
     */
    public function test_skip() {
        global $DB, $CFG;
        // Create a RSS feed record with a skip until time set to the future.
        $record = (object) array(
            'userid' => 1,
            'title' => 'Skip test feed',
            'preferredtitle' => '',
            'description' => 'A feed to test the skip time.',
            'shared' => 0,
            'url' => 'http://example.com/rss',
            'skiptime' => 330,
            'skipuntil' => time() + 300,
        );
        $DB->insert_record('block_rss_client', $record);

        $block = new block_rss_client();
        ob_start();

        // Silence SimplePie php notices.
        $errorlevel = error_reporting($CFG->debug & ~E_USER_NOTICE);
        $block->cron();
        error_reporting($errorlevel);

        $cronoutput = ob_get_clean();
        $this->assertStringContainsString('skipping until ' . userdate($record->skipuntil), $cronoutput);
        $this->assertStringContainsString('0 feeds refreshed (took ', $cronoutput);
    }

    /**
     * Test that when a feed has an error the skip time is increaed correctly.
     */
    public function test_error() {
        global $DB, $CFG;
        $time = time();
        // A record that has failed before.
        $record = (object) array(
            'userid' => 1,
            'title' => 'Skip test feed',
            'preferredtitle' => '',
            'description' => 'A feed to test the skip time.',
            'shared' => 0,
            'url' => 'http://example.com/rss',
            'skiptime' => 330,
            'skipuntil' => $time - 300,
        );
        $record->id = $DB->insert_record('block_rss_client', $record);

        // A record that has not failed before.
        $record2 = (object) array(
            'userid' => 1,
            'title' => 'Skip test feed',
            'preferredtitle' => '',
            'description' => 'A feed to test the skip time.',
            'shared' => 0,
            'url' => 'http://example.com/rss2',
            'skiptime' => 0,
            'skipuntil' => 0,
        );
        $record2->id = $DB->insert_record('block_rss_client', $record2);

        // A record that is near the maximum wait time.
        $record3 = (object) array(
            'userid' => 1,
            'title' => 'Skip test feed',
            'preferredtitle' => '',
            'description' => 'A feed to test the skip time.',
            'shared' => 0,
            'url' => 'http://example.com/rss3',
            'skiptime' => block_rss_client::CLIENT_MAX_SKIPTIME - 5,
            'skipuntil' => $time - 1,
        );
        $record3->id = $DB->insert_record('block_rss_client', $record3);

        // Run the cron.
        $block = new block_rss_client();
        ob_start();

        // Silence SimplePie php notices.
        $errorlevel = error_reporting($CFG->debug & ~E_USER_NOTICE);
        $block->cron();
        error_reporting($errorlevel);

        $cronoutput = ob_get_clean();
        $skiptime1 = $record->skiptime * 2;
        $message1 = 'http://example.com/rss Error: could not load/find the RSS feed - skipping for ' . $skiptime1 . ' seconds.';
        $this->assertStringContainsString($message1, $cronoutput);
        $skiptime2 = 330; // Assumes that the cron time in the version file is 300.
        $message2 = 'http://example.com/rss2 Error: could not load/find the RSS feed - skipping for ' . $skiptime2 . ' seconds.';
        $this->assertStringContainsString($message2, $cronoutput);
        $skiptime3 = block_rss_client::CLIENT_MAX_SKIPTIME;
        $message3 = 'http://example.com/rss3 Error: could not load/find the RSS feed - skipping for ' . $skiptime3 . ' seconds.';
        $this->assertStringContainsString($message3, $cronoutput);
        $this->assertStringContainsString('0 feeds refreshed (took ', $cronoutput);

        // Test that the records have been correctly updated.
        $newrecord = $DB->get_record('block_rss_client', array('id' => $record->id));
        $this->assertEquals($skiptime1, $newrecord->skiptime);
        $this->assertGreaterThanOrEqual($time + $skiptime1, $newrecord->skipuntil);
        $newrecord2 = $DB->get_record('block_rss_client', array('id' => $record2->id));
        $this->assertEquals($skiptime2, $newrecord2->skiptime);
        $this->assertGreaterThanOrEqual($time + $skiptime2, $newrecord2->skipuntil);
        $newrecord3 = $DB->get_record('block_rss_client', array('id' => $record3->id));
        $this->assertEquals($skiptime3, $newrecord3->skiptime);
        $this->assertGreaterThanOrEqual($time + $skiptime3,  $newrecord3->skipuntil);
    }
}
