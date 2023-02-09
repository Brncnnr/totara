<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Maria Torres <maria.torres@totaralms.com>
 * @package mod_facetoface
 */

// NOTE: no MOODLE_INTERNAL used, this file may be required by behat before including /config.php.
require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode as TableNode;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ExpectationException;

/**
 * Contains functions used by behat to test functionality.
 *
 * @package    mod_facetoface
 * @category   test
 */
class behat_facetoface extends behat_base {

    /**
     * Opens the seminar event dashboard page.
     *
     * @Given /^I am on "(?P<seminarname_string>(?:[^"]|\\")*)" seminar homepage$/
     * @throws coding_exception
     * @param string $seminarname
     * @return void
     */
    public function i_am_on_seminar_homepage(string $seminarname) {
        \behat_hooks::set_step_readonly(false);
        global $DB;
        $facetoface = $DB->get_record('facetoface', ['name' => $seminarname], 'id', MUST_EXIST);
        $url = new moodle_url('/mod/facetoface/view.php', ['f' => $facetoface->id]);
        $this->getSession()->visit($this->locate_path($url->out_as_local_url(false)));
        $this->wait_for_pending_js();
    }

    /**
     * Create a session in the future based on the current date.
     *
     * @Given /^I fill seminar session with relative date in form data:$/
     * @param TableNode $data
     */
    public function i_fill_seminar_session_with_relative_date_in_form_data(TableNode $data) {
        $timestartzone = '';
        $timefinishzone = '';

        $startmodify = array();
        $finishmodify = array();

        $rows = array();
        foreach ($data->getRows() as $row) {
            switch ($row[0]) {
                case 'timestart[day]':
                    if (!empty($row[1])) {
                        $startmodify[] = $row[1] . ' days';
                    }
                    break;
                case 'timestart[month]':
                    if (!empty($row[1])) {
                        $startmodify[] = $row[1] . ' months';
                    }
                    break;
                case 'timestart[year]':
                    if (!empty($row[1])) {
                        $startmodify[] = $row[1] . ' years';
                    }
                    break;
                case 'timestart[hour]':
                    if (!empty($row[1])) {
                        $startmodify[] = $row[1] . ' hours';
                    }
                    break;
                case 'timestart[minute]':
                    if (!empty($row[1])) {
                        $startmodify[] = $row[1] . ' minutes';
                    }
                    break;
                case 'timestart[timezone]':
                    if (!empty($row[1])) {
                        $timestartzone = $row[1];
                    }
                    break;
                case 'timefinish[day]':
                    if (!empty($row[1])) {
                        $finishmodify[] = $row[1] . ' days';
                    }
                    break;
                case 'timefinish[month]':
                    if (!empty($row[1])) {
                        $finishmodify[] = $row[1] . ' months';
                    }
                    break;
                case 'timefinish[year]':
                    if (!empty($row[1])) {
                        $finishmodify[] = $row[1] . ' years';
                    }
                    break;
                case 'timefinish[hour]':
                    if (!empty($row[1])) {
                        $finishmodify[] = $row[1] . ' hours';
                    }
                    break;
                case 'timefinish[minute]':
                    if (!empty($row[1])) {
                        $finishmodify[] = $row[1] . ' minutes';
                    }
                    break;
                case 'timefinish[timezone]':
                    if (!empty($row[1])) {
                        $timefinishzone = $row[1];
                    }
                    break;
                case 'sessiontimezone':
                    // Developers often forget to fill all timezones, so do it for them to get expected results.
                    if (!empty($row[1])) {
                        if (empty($timefinishzone)) {
                            $timefinishzone = $row[1];
                        }
                        if (empty($timestartzone)) {
                            $timestartzone = $row[1];
                        }
                        $rows[] = $row;
                    }
                    break;
                default:
                    $rows[] = $row;
                    break;
            }
        }

        // Timezones first!
        if (!$timestartzone) {
            $timestartzone = 'Australia/Perth'; // Behat default.
        }
        $rows[] = array('timestart[timezone]', $timestartzone);
        if (!$timefinishzone) {
            $timefinishzone = 'Australia/Perth'; // Behat default.
        }
        $rows[] = array('timefinish[timezone]', $timefinishzone);

        $startdate = new DateTime('now', new DateTimeZone($timestartzone));
        foreach ($startmodify as $modify) {
            $startdate->modify($modify);
        }
        $mindiff = $startdate->format("i") % 5;
        if ($mindiff != 0) {
            $startdate->modify('+ ' . (5 - $mindiff) . ' minutes');
        }

        $finishdate = new DateTime('now', new DateTimeZone($timefinishzone));
        foreach ($finishmodify as $modify) {
            $finishdate->modify($modify);
        }
        $mindiff = $finishdate->format('i') % 5;
        if ($mindiff != 0) {
            $finishdate->modify('+ ' . (5 - $mindiff) . ' minutes');
        }

        // Replace values for timestart.
        $rows[] = array('timestart[day]', (int) $startdate->format('d'));
        $rows[] = array('timestart[month]', (int) $startdate->format('m'));
        $rows[] = array('timestart[year]', (int) $startdate->format('Y'));
        $rows[] = array('timestart[hour]', (int) $startdate->format('H'));
        $rows[] = array('timestart[minute]', (int) $startdate->format('i'));

        // Replace values for timefinish.
        $rows[] = array('timefinish[day]', (int) $finishdate->format('d'));
        $rows[] = array('timefinish[month]', (int) $finishdate->format('m'));
        $rows[] = array('timefinish[year]', (int) $finishdate->format('Y'));
        $rows[] = array('timefinish[hour]', (int) $finishdate->format('H'));
        $rows[] = array('timefinish[minute]', (int) $finishdate->format('i'));

        /** @var behat_forms $behatformcontext */
        $behatformcontext = behat_context_helper::get('behat_forms');
        $behatformcontext->i_set_the_following_fields_to_these_values(new TableNode($rows));
    }

    /**
     * Click on a selected link that is located in a table row.
     *
     * @Given /^I click on the link "([^"]*)" in row (\d+)$/
     */
    public function i_click_on_the_link_in_row($text, $row) {
        \behat_hooks::set_step_readonly(false);
        $xpath = "//table//tbody//tr[{$row}]//a[text()='{$text}']";
        $node = $this->find(
            'xpath',
            $xpath,
            new \Behat\Mink\Exception\ExpectationException('Could not find specific link "'.$text.'" in the row' . $row . $xpath, $this->getSession())
        );
        $node->click();
    }

    /**
     * Get the xpaths for an action menu item.
     *
     * @param string $action
     * @param string $row
     * @return array of [xpath_dropdown, xpath_menuitem]
     */
    private static function resolve_xpaths_for_action(string $action, string $row): array {
        $tablexpath = '//table[contains(@class, \'mod_facetoface__sessionlist__table\')]';
        if (preg_match('/^#(\d+)$/', $row, $matches)) {
            // This a row index. e.g. #1
            $rowxpath = $tablexpath.'//tbody//tr['.$matches[1].']';
        } else {
            $rowxpath = $tablexpath.'//tbody//tr[contains(., '.behat_context_helper::escape($row).')]';
        }
        $label = get_string('moreactions', 'mod_facetoface');
        $ddxpath = $rowxpath.'//button[@aria-label='.behat_context_helper::escape($label).']';
        $menuxpath = $rowxpath.'//a[@role="menuitem" and contains(., '.behat_context_helper::escape($action).')]';
        return [$ddxpath, $menuxpath];
    }

    /**
     * Click on an action menu item that is located in a table row.
     *
     * @Given /^I click on the seminar event action "([^"]*)" in row "([^"]*)"$/
     * @param string $action the text of an action menu item.
     * @param string $row the row text, or the row number in the format #1
     */
    public function i_click_on_the_seminar_event_action_in_row(string $action, string $row) {
        \behat_hooks::set_step_readonly(false);
        [$ddxpath, $menuxpath] = self::resolve_xpaths_for_action($action, $row);
        $ddnode = $this->find(
            'xpath',
            $ddxpath,
            new ExpectationException("Could not find a meatballs button in the row \"{$row}\"", $this->getSession())
        );
        $ddnode->click();
        $this->wait_for_pending_js();
        $menunode = $this->find(
            'xpath',
            $menuxpath,
            new ExpectationException("The menu item \"{$action}\" is not presented in the row \"{$row}\"", $this->getSession())
        );
        $menunode->click();
    }

    /**
     * Checks the provided action menu item should exist in a table row.
     *
     * @Given /^I should see the seminar event action "([^"]*)" in row "([^"]*)"$/
     * @param string $action the text of an action menu item.
     * @param string $row the row text, or the row number in the format #1
     */
    public function i_should_see_the_seminar_event_action_in_row(string $action, string $row) {
        \behat_hooks::set_step_readonly(true);
        [$ddxpath, $menuxpath] = self::resolve_xpaths_for_action($action, $row);
        $this->find(
            'xpath',
            $menuxpath,
            new ExpectationException("The menu item \"{$action}\" is not presented in the row \"{$row}\"", $this->getSession())
        );
    }

    /**
     * Checks the provided action menu item should not exist in a table row.
     *
     * @Given /^I should not see the seminar event action "([^"]*)" in row "([^"]*)"$/
     * @param string $action the text of an action menu item.
     * @param string $row the row text, or the row number in the format #1
     */
    public function i_should_not_see_the_seminar_event_action_in_row(string $action, string $row) {
        try {
            $this->i_should_see_the_seminar_event_action_in_row($action, $row);
            // Throw exception if found.
        } catch (ExpectationException $e) {
            if (strpos($e->getMessage(), "The menu item \"{$action}\" is not presented in the row \"{$row}\"") !== false) {
                // Passed.
                return;
            }
            // Rethrow any unexpected exception.
            throw $e;
        }
        throw new ExpectationException("The menu item \"{$action}\" is presented in the row \"{$row}\"", $this->getSession());
    }

    /**
     * Return the xpath for location a sidebar panel.
     *
     * @param string $title
     * @return string xpath
     */
    private static function resolve_xpath_for_sidebar_panel(string $title): string {
        return ".//h3[contains(., ".behat_context_helper::escape($title).")]/ancestor::*[contains(concat(' ', @class, ' '), ' mod_facetoface__eventinfo__sidebar__panel ')]";
    }

    /**
     * Click on an item in a sidebar on the seminar event page.
     *
     * @Given /^I click on "([^"]*)" "([^"]*)" in the seminar event sidebar "([^"]*)"$/
     * @param string $element
     * @param string $selectortype
     * @param string $title
     */
    public function i_click_on_in_the_seminar_event_sidebar(string $element, string $selectortype, string $title) {
        $xpath = self::resolve_xpath_for_sidebar_panel($title);
        $this->execute('behat_general::i_click_on_in_the', [$element, $selectortype, $xpath, 'xpath_element']);
    }

    /**
     * Checks the provided item should exist in a sidebar on the seminar event page.
     *
     * @Given /^I should see "([^"]*)" "([^"]*)" in the seminar event sidebar "([^"]*)"$/
     * @param string $element
     * @param string $selectortype
     * @param string $title
     */
    public function i_should_see_in_the_seminar_event_sidebar(string $element, string $selectortype, string $title) {
        $xpath = self::resolve_xpath_for_sidebar_panel($title);
        $this->execute('behat_general::should_exist_in_the', [$element, $selectortype, $xpath, 'xpath_element']);
    }

    /**
     * Checks the provided item should not exist in a sidebar on the seminar event page.
     *
     * @Given /^I should not see "([^"]*)" "([^"]*)" in the seminar event sidebar "([^"]*)"$/
     * @param string $element
     * @param string $selectortype
     * @param string $title
     */
    public function i_should_not_see_in_the_seminar_event_sidebar(string $element, string $selectortype, string $title) {
        $xpath = self::resolve_xpath_for_sidebar_panel($title);
        $this->execute('behat_general::should_not_exist_in_the', [$element, $selectortype, $xpath, 'xpath_element']);
    }

    /**
     * Use magic to alter facetoface cut off to value which is not allowed in UI so that we do not have to wait in tests.
     *
     * @Given /^I use magic to set Seminar "([^"]*)" to send capacity notification two days ahead$/
     */
    public function i_use_magic_to_set_seminar_cutoff_one_day_back($facetofacename) {
        \behat_hooks::set_step_readonly(false);
        global $DB;

        $facetoface = $DB->get_record('facetoface', array('name' => $facetofacename), '*', MUST_EXIST);
        $session = $DB->get_record('facetoface_sessions', array('facetoface' => $facetoface->id), '*', MUST_EXIST);
        $session->sendcapacityemail = 1;
        $session->cutoff = DAYSECS * 2;
        $DB->update_record('facetoface_sessions', $session);
    }

    /**
     * Make duplicates of notification title (in all seminar activities of all courses). Titles must match exactly.
     *
     * @Given /^I make duplicates of seminar notification "([^"]*)"$/
     */
    public function i_make_duplicates_of_seminar_notification($title) {
        \behat_hooks::set_step_readonly(false);
        global $DB;
        $notifications = $DB->get_records_select('facetoface_notification',
            $DB->sql_compare_text('title') . ' = :title', array('title' => $title));
        foreach ($notifications as $note) {
            $note->id = null;
            $DB->insert_record('facetoface_notification', $note);
        }
    }

    /**
     * Checks if a custom room of the given name exists in the database.
     *
     * @Given /^a seminar custom room called "([^"]*)" (should not|should) exist$/
     *
     * @throws \Behat\Mink\Exception\ExpectationException
     * @param string $roomname
     * @param string $should
     */
    public function a_seminar_custom_room_called_should_exist($roomname, $should) {
        \behat_hooks::set_step_readonly(true);
        global $DB;

        $params = array(
            'custom' => 1,
            'name' => $roomname
        );
        $exists = $DB->record_exists('facetoface_room', $params);
        if ($should === 'should') {
            if (!$exists) {
                throw new \Behat\Mink\Exception\ExpectationException('Seminar custom room by the name of "' . $roomname . '" does not exist', $this->getSession());
            }
        } else {
            if ($exists) {
                throw new \Behat\Mink\Exception\ExpectationException('Seminar custom room by the name of "' . $roomname . '" still exists', $this->getSession());
            }
        }
    }

    /**
     * Checks if a custom asset of the given name exists in the database.
     *
     * @Given /^a seminar custom asset called "([^"]*)" (should not|should) exist$/
     *
     * @throws \Behat\Mink\Exception\ExpectationException
     * @param string $assetname
     * @param string $should
     */
    public function a_seminar_custom_asset_called_should_exist($assetname, $should) {
        \behat_hooks::set_step_readonly(true);
        global $DB;

        $params = array(
            'custom' => 1,
            'name' => $assetname
        );
        $exists = $DB->record_exists('facetoface_asset', $params);
        if ($should === 'should') {
            if (!$exists) {
                throw new \Behat\Mink\Exception\ExpectationException('Seminar custom asset by the name of "' . $assetname . '" does not exist', $this->getSession());
            }
        } else {
            if ($exists) {
                throw new \Behat\Mink\Exception\ExpectationException('Seminar custom asset by the name of "' . $assetname . '" still exists', $this->getSession());
            }
        }
    }

    /**
     * Checks if a custom facilitator of the given name exists in the database.
     * @Given /^a seminar custom facilitator called "([^"]*)" (should not|should) exist$/
     * @throws \Behat\Mink\Exception\ExpectationException
     * @param string $facilitatorname
     * @param string $should
     */
    public function a_seminar_custom_facilitator_called_should_exist($facilitatorname, $should) {
        \behat_hooks::set_step_readonly(true);
        global $DB;

        $params = array(
            'custom' => 1,
            'name' => $facilitatorname
        );
        $exists = $DB->record_exists('facetoface_facilitator', $params);
        if ($should === 'should') {
            if (!$exists) {
                throw new \Behat\Mink\Exception\ExpectationException('Seminar custom facilitator by the name of "' . $facilitatorname . '" does not exist', $this->getSession());
            }
        } else {
            if ($exists) {
                throw new \Behat\Mink\Exception\ExpectationException('Seminar custom facilitator by the name of "' . $facilitatorname . '" still exists', $this->getSession());
            }
        }
    }

    /**
     * Clicks on the "Edit session" link for a Facetoface session.
     *
     * @throws \Behat\Mink\Exception\ExpectationException
     * @When /^I click to edit the (facetoface|seminar) session in row (\d+)$/
     * @param int $row
     */
    public function i_click_to_edit_the_facetoface_session_in_row($term, $row) {
        $this->i_click_on_the_seminar_event_action_in_row(get_string('editsession', 'mod_facetoface'), '#'.$row);
    }

    /**
     * Clicks to edit the Seminar event date in the given table row.
     *
     * @throws \Behat\Mink\Exception\ExpectationException
     * @When /^I click to edit the seminar event date at position (\d+)$/
     * @param int $position
     */
    public function i_click_to_edit_the_seminar_event_date_at_position($position) {
        \behat_hooks::set_step_readonly(false);
        $titleliteral = behat_context_helper::escape(get_string('editdate', 'facetoface'));
        $xpath = "//table[contains(@class, 'f2fmanagedates')]/tbody/tr[{$position}]//a/span[@title={$titleliteral}]/parent::a";
        /** @var \Behat\Mink\Element\NodeElement[] $nodes */
        $nodes = $this->find_all('xpath', $xpath);
        if (empty($nodes) || count($nodes) > 1) {
            throw new \Behat\Mink\Exception\ExpectationException('Unable to find the edit event date link on row '.$position, $this->getSession());
        }
        $node = reset($nodes);
        $node->click();
    }

    /**
     * @When I visit the attendees page for session :arg1 with action :arg2
     * @param int $sessionid Face to face session ID
     * @param string $action The action to perform
     */
    public function i_visit_the_attendees_page_for_session_with_action($sessionid, $action){
        \behat_hooks::set_step_readonly(false);
        $page = $action . '.php';
        $path = "/mod/facetoface/attendees/{$page}?s={$sessionid}";
        $this->getSession()->visit($this->locate_path($path));
        $this->wait_for_pending_js();
    }

    /**
     * Selects an attendees job assignment.
     *
     * Must start out on the attendees page.
     *
     * @When /^I set the Seminar signup job assignment to "([^"]*)" for "([^"]*)"$/
     * @param string $jobassignment
     * @param string $username
     */
    public function i_set_the_seminar_signup_job_assignment_to_for($jobassignment, $username) {
        $username_escaped = $this->getSession()->getSelectorsHandler()->xpathLiteral($username);
        $edit_escaped = $this->getSession()->getSelectorsHandler()->xpathLiteral(get_string('edit'));
        $xpath = "//table[@id='facetoface_sessions']//td[contains(@class, 'user_namelink') and contains(., {$username_escaped})]/ancestor::tr/td[contains(@class, 'session_positionnameedit')]//a[contains(., {$edit_escaped})]";
        $nodes = $this->find_all('xpath', $xpath);
        if (empty($nodes) || count($nodes) > 1) {
            throw new \Behat\Mink\Exception\ExpectationException('Unable to find the edit job assignment icon for '.$username, $this->getSession());
        }
        $node = reset($nodes);
        $node->click();

        $data = new TableNode([['selectjobassign', $jobassignment]]);
        /** @var behat_forms $behatformcontext */
        $behatformcontext = behat_context_helper::get('behat_forms');
        $behatformcontext->i_set_the_following_fields_to_these_values($data);

        /** @var Behat\Mink\Element\NodeElement[] $nodes */
        $nodes = $this->find_all('xpath', "//form//input[@type='submit' and @value='Update job assignment']");
        if (empty($nodes)) {
            throw new \Behat\Mink\Exception\ExpectationException('Unable to find the save button when editing seminar signup job assignment for '.$username, $this->getSession());
        }
        foreach ($nodes as $node) {
            if ($node->isVisible()) {
                $node->click();
                break;
            }
        }
    }

    /**
     * We are using legacy seminar notifications
     *
     * @Given /^I (am|am not) using legacy seminar notifications$/
     */
    public function i_am_using_legacy_seminar_notifications(string $using_legacy) {
        \behat_hooks::set_step_readonly(false);

        // Set seminar notification defaults
        $am_using = ($using_legacy === 'am');
        set_config('facetoface_allow_legacy_notifications', $am_using);
        set_config('legacy_notifications', $am_using, 'facetoface');
    }

}
