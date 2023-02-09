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
 * @author Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @package totara_core
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode as TableNode,
    Behat\Mink\Exception\ElementNotFoundException,
    Behat\Mink\Exception\ExpectationException,
    Behat\Mink\Element\NodeElement;

/**
 * The Totara core definitions class.
 *
 * This class contains the definitions for core Totara functionality.
 * Any definitions that belong to separ
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright Copyright (C) 2010-2013 Totara Learning Solutions LTD
 */
class behat_totara_core extends behat_base {

    /**
     * A tab should be visible but disabled.
     *
     * @Given /^I should see the "([^"]*)" tab is disabled$/
     */
    public function i_should_see_the_tab_is_disabled($text) {
        \behat_hooks::set_step_readonly(true);
        $text = behat_context_helper::escape($text);
        $xpath = "//div[contains(concat(' ', normalize-space(@class), ' '), ' tabtree ')]//a[contains(concat(' ', normalize-space(@class), ' '), ' nolink ') and not(@href)]/*[contains(text(), {$text})]";
        // Bootstrap 3 has different markup.
        $xpath .= "| //*[contains(concat(' ', normalize-space(@class), ' '), ' tabtree ')]//li[contains(concat(' ', normalize-space(@class), ' '), ' disabled ')]/a[not(@href) and contains(text(), {$text})]";
        $this->find(
            'xpath',
            $xpath,
            new \Behat\Mink\Exception\ExpectationException('Tab "'.$text.'" could not be found or was not disabled', $this->getSession())
        );
    }

    /**
     * We expect to be on a Totara site.
     *
     * @Given /^I am on a totara site$/
     */
    public function i_am_on_a_totara_site() {
        \behat_hooks::set_step_readonly(false);
        global $DB;
        // Set Totara defaults. This is to undo the work done in /lib/behat/classes/util.php around line 90
        set_config('enablecompletion', 1);
        set_config('forcelogin', 1);
        set_config('guestloginbutton', 0);
        set_config('enablecompletion', 1, 'moodlecourse');
        set_config('completionstartonenrol', 1, 'moodlecourse');
        set_config('enrol_plugins_enabled', 'manual,guest,self,cohort,totara_program');
        set_config('catalogtype', 'totara');
        set_config('preventexecpath', 1);
        set_config('debugallowscheduledtaskoverride', 1); // Include dev interface for resetting scheduled task "Next run".
        $DB->set_field('role', 'name', 'Site Manager', array('shortname' => 'manager'));
        $DB->set_field('role', 'name', 'Editing Trainer', array('shortname' => 'editingteacher'));
        $DB->set_field('role', 'name', 'Trainer',array('shortname' => 'teacher'));
        $DB->set_field('role', 'name', 'Learner', array('shortname' => 'student'));
        $DB->set_field('modules', 'visible', 0, array('name' => 'workshop'));
    }

    /**
     * Enable an advanced feature.
     *
     * @param string $feature Advanced feature name - e.g. 'appraisals' or 'competency_assignment'
     * @When /^I enable the "([^"]*)" advanced feature$/
     */
    public function i_enable_the_advanced_feature(string $feature): void {
        \behat_hooks::set_step_readonly(false);
        if (strpos($feature, 'enable') === 0) {
            throw new ExpectationException('Disabling features requires that "enable" be removed from the start of the setting name');
        }
        \totara_core\advanced_feature::enable($feature);
    }

    /**
     * Disable an advanced feature.
     *
     * @param string $feature Advanced feature name - e.g. 'appraisals' or 'competency_assignment'
     * @When /^I disable the "([^"]*)" advanced feature$/
     */
    public function i_disable_the_advanced_feature(string $feature): void {
        \behat_hooks::set_step_readonly(false);
        if (strpos($feature, 'enable') === 0) {
            throw new ExpectationException('Disabling features requires that "enable" be removed from the start of the setting name');
        }
        \totara_core\advanced_feature::disable($feature);
        purge_all_caches();
    }

    /**
     * Finds a Totara Main menu item url.
     *
     * @param string $text
     * @return string|null node URL or null if node not found
     */
    protected function find_totara_menu_item_href(string $text): ?string {
        // Seriously, why is there nothing that would clearly identify the Main menu items?
        /** @var NodeElement[] $nodes */
        $nodes = $this->find_all(
            'xpath',
            "//*[@data-tw-totaranav-list]//a",
            new \Behat\Mink\Exception\ExpectationException('Totara Main menu could not be found', $this->getSession())
        );
        $text = preg_quote($text, '/');
        foreach ($nodes as $k => $node) {
            $html = $node->getHtml();
            if (preg_match("/>\\s*$text\\s*</s", $html)) {
                $href = $node->getAttribute('href');
                if ($href === '') {
                    // This should not happen.
                    return null;
                }
                return $href;
            }
        }
        return null;
    }

    /**
     * Check that item is accessible from the Main menu.
     *
     * Note: Visibility of sub menus does not matter here.
     *
     * @Given /^I should see "([^"]*)" in the totara menu$/
     */
    public function i_should_see_in_the_totara_menu($text) {
        \behat_hooks::set_step_readonly(true);
        $url = $this->find_totara_menu_item_href($text);
        if ($url === null) {
            throw new \Behat\Mink\Exception\ExpectationException('Totara menu item "'.$text.'" could not be found', $this->getSession());
        }
        return true;
    }

    /**
     * Check that item is not accessible from the Main menu.
     *
     * Note: Visibility of sub menus does not matter here.
     *
     * @Given /^I should not see "([^"]*)" in the totara menu$/
     */
    public function i_should_not_see_in_the_totara_menu($text) {
        \behat_hooks::set_step_readonly(true);
        $url = $this->find_totara_menu_item_href($text);
        if ($url === null) {
            return true;
        }
        throw new \Behat\Mink\Exception\ExpectationException('Totara menu item "'.$text.'" has been found', $this->getSession());
    }

    /**
     * Click on an item in the Main menu.
     *
     * Note: Visibility of sub menus does not matter here.
     *
     * @Given /^I click on "([^"]*)" in the totara menu$/
     */
    public function i_click_on_in_the_totara_menu($text) {
        \behat_hooks::set_step_readonly(false);

        // Double check we are not interrupting any pending action.
        $this->wait_for_pending_js();

        $url = $this->find_totara_menu_item_href($text);
        if ($url === null) {
            throw new \Behat\Mink\Exception\ExpectationException('Totara menu item "'.$text.'" could not be found', $this->getSession());
        }

        if ($url === '#') {
            throw new \Behat\Mink\Exception\ExpectationException('Totara menu item "'.$text.'" is a Parent, you need to specify item with URL instead', $this->getSession());
        }

        $this->getSession()->visit($this->locate_path($url));
        $this->wait_for_pending_js();
    }

    /**
     * Checks that the specified Main menu item is or isn't highlighted/expanded.
     *
     * @Then /^Totara (|sub )menu(| drop down list) item "([^"]*)" should (|not )be (highlighted|expanded)$/
     * @param string $submenu
     * @param string $dropdown
     * @param string $text
     * @param string $should_be
     * @param string $highlighted_or_expanded
     * @throws ExpectationException
     */
     public function totara_menu_item_should_be_highlighted($submenu, $dropdown, $text, $should_be, $highlighted_or_expanded) {
         \behat_hooks::set_step_readonly(true);

         $submenu = !empty($submenu);
         $dropdown = !empty($dropdown);
         $should_be = empty($should_be);

         // Do not over-complicate everything else just because if this method!
         $text = behat_context_helper::escape($text);
         $dropdown_xpath = $dropdown ? "//ul[contains(concat(' ', normalize-space(@class), ' '), ' navExpand--list ')]" : '';
         $submenu_xpath = $submenu ? "//li[contains(concat(' ', normalize-space(@class), ' '), ' totaraNav_sub--list_item ')]" : '';
         $a_node = $this->find(
             'xpath',
             "//*[@class = 'totaraNav']{$submenu_xpath}{$dropdown_xpath}//a[contains(normalize-space(.),{$text})]",
             new \Behat\Mink\Exception\ExpectationException('Totara menu item "'.$text.'" could not be found', $this->getSession())
         );

         $li_node = $a_node->getParent();

         if ($highlighted_or_expanded === 'expanded') {
             $expected_class = "totaraNav--list_item_expanded";
         } else {
             $menu_level = $submenu ? 'sub' : 'prim';
             $expected_class = "totaraNav_{$menu_level}--list_item_selected";
         }

         $error_msg = '';
         $has_class = $li_node->hasClass($expected_class);
         if ($should_be && !$has_class) {
             $error_msg = "Item is not {$highlighted_or_expanded} when it should be. Missing expected CSS class: " . $expected_class;
         }
         if (!$should_be && $has_class) {
             $error_msg = "Item is {$highlighted_or_expanded} when it shouldn't be. Did not expect to find CSS class: " . $expected_class;
         }

         if (!empty($error_msg)) {
             throw new \Behat\Mink\Exception\ExpectationException($error_msg, $this->getSession());
         }
     }

    /**
     * Create one or more menu items for the Totara main menu
     *
     * @Given /^I create the following totara menu items:$/
     */
    public function i_create_the_following_totara_menu_items(TableNode $table) {
        \behat_hooks::set_step_readonly(false);
        $possiblemenufields = array('Parent item', 'Menu title', 'Visibility', 'Menu url address', 'Open link in new window');
        $first = false;

        $menufields = array();
        $rulefields = array();

        // We are take table c
        foreach ($table->getRows() as $row) {
            $menurows = array();
            $rulerows = array();

            if ($first === false) {
                // The first row is the headings.
                foreach ($row as $key => $field) {
                    if (in_array($field, $possiblemenufields)) {
                        $menufields[$field] = $key;
                    } else {
                        $rulefields[$field] = $key;
                    }
                }
                $first = true;
                continue;
            }

            foreach ($row as $key => $value) {
                $menurow = array();
                $rulerow = array();
                if (in_array($key, $menufields)) {
                    $menurow[] = array_search($key, $menufields);
                    $menurow[] = $row[$key];
                    $menurows[] = $menurow;
                } else {
                    $rulerow[] = array_search($key, $rulefields);
                    $rulerow[] = $row[$key];
                    $rulerows[] = $rulerow;
                }
            }
            $menutable = new TableNode($menurows);
            $ruletable = new TableNode($rulerows);

            $this->execute("behat_navigation::i_navigate_to_node_in", array("Main menu", "Site administration > Navigation"));
            $this->execute("behat_forms::press_button", "Add new menu item");
            $this->execute("behat_totara_form::i_set_the_following_totara_form_fields_to_these_values", $menutable);
            $this->execute("behat_forms::press_button", "Add");
            if (!empty($rulerows)) {
                $this->execute("behat_general::assert_page_contains_text", "Edit menu item");
                $this->execute("behat_general::i_click_on", array('Access', 'link'));
                $this->execute("behat_forms::i_expand_all_fieldsets");
                $this->execute("behat_forms::i_set_the_following_fields_to_these_values", $ruletable);
                $this->execute("behat_forms::press_button", "Save changes");
            }
        }
    }

    /**
     * Edit a Totara main menu item via the Admin interface.
     *
     * @Given /^I edit "([^"]*)" totara menu item$/
     */
    public function i_edit_totara_menu_item($text) {
        \behat_hooks::set_step_readonly(false);
        $text = behat_context_helper::escape($text);
        $xpath = "//table[@id='totaramenutable']//td[contains(concat(' ', normalize-space(@class), ' '), ' name ')]/*[contains(text(),{$text})]//ancestor::tr//a[@title='Edit']";
        $node = $this->find(
            'xpath',
            $xpath,
            new \Behat\Mink\Exception\ExpectationException('Could not find Edit action for "'.$text.'" menu item', $this->getSession())
        );
        $node->click();
    }

    /**
     * Break some menu items fo testing.
     *
     * @Given /^I use magic for Main menu to make invalid menu items$/
     */
    public function magic_for_invalid_main_menu_items() {
        global $DB;

        $dualitem = $DB->get_record('totara_navigation', array('title' => 'Dual item'), '*', MUST_EXIST);
        $orphaneditem = $DB->get_record('totara_navigation', array('title' => 'Orphaned item'), '*', MUST_EXIST);
        $orphaneditem->parentid = $dualitem->id;
        $DB->update_record('totara_navigation', $orphaneditem);

        $uninstalleditem = $DB->get_record('totara_navigation', array('title' => 'Uninstalled item'), '*', MUST_EXIST);
        $uninstalleditem->classname = '\some_plugin\totara\menu\someitem';
        $uninstalleditem->custom = 0;
        $uninstalleditem->customtitle = 0;
        $uninstalleditem->url = '';
        $DB->update_record('totara_navigation', $uninstalleditem);
        // Caching invalidation is not reliable here due to sloppy Moodle internal behat integration design, so better log off and in too.
        \totara_core\totara\menu\helper::bump_cache_revision();
    }

    /**
     * Generic focus action.
     *
     * @When /^I set self completion for "([^"]*)" in the "([^"]*)" category$/
     * @param string $course The fullname of the course we are setting up
     * @param string $category The fullname of the category containing the course
     */
    public function i_set_self_completion_for($course, $category) {
        \behat_hooks::set_step_readonly(false);
        // TOTARA: Use this step instead of admin menu
        $this->execute("behat_course::i_go_to_the_courses_management_page");
        $this->execute("behat_general::i_click_on_in_the", array($this->escape($category), 'link', ".category-listing", "css_element"));
        $this->execute("behat_general::i_click_on_in_the", array($this->escape($course), 'link', ".course-listing", "css_element"));
        $this->execute("behat_general::i_click_on_in_the", array('View', 'link', ".course-detail-listing-actions", "css_element"));
        $this->execute("behat_general::i_click_on", array('Course completio', 'link'));
        $this->execute("behat_forms::i_expand_all_fieldsets");
        $this->execute("behat_forms::i_set_the_field_to", array("criteria_self_value", "1"));
        $this->execute("behat_forms::press_button", "Save changes");
        $this->execute("behat_forms::press_button", "Turn editing on");
        $this->execute('behat_blocks::i_add_the_block', array('Self completion'));
        $this->execute("behat_forms::press_button", "Turn editing off");

    }

    /**
     * Check the program progress bar meets a given percentage.
     *
     * @Then /^I should see "([^"]*)" program progress$/
     */
    public function i_should_see_program_progress($text) {
        \behat_hooks::set_step_readonly(true);

        $text = behat_context_helper::escape($text);
        $xpath = "//div[@id = 'progressbar']//div[contains(@class, 'progressbar_container')]//div[contains(@class,'progress')]//span[contains(.,{$text})]";
        $node = $this->find(
            'xpath',
            $xpath,
            new \Behat\Mink\Exception\ExpectationException('Program progress bar "'.$text.'" could not be found', $this->getSession())
        );

        if (!$node->isVisible()) {
            throw new \Behat\Mink\Exception\ExpectationException('Program progress bar "'.$text.'" is not visible visible', $this->getSession());
        }
        return $node;
    }

    /**
     * Set a field within a program coursesets dynamically generated (and prefixed) form.
     *
     * @Then /^I set "([^"]*)" for courseset "([^"]*)" to "([^"]*)"$/
     */
    public function i_set_courseset_variable($varname, $courseset, $value) {
        \behat_hooks::set_step_readonly(false);

        $xpath = "";
        $xpath .= "//div[@id = 'course_sets_ce' or @id = 'course_sets_rc']";
        $xpath .= "//fieldset[descendant::legend[contains(.,'$courseset ')]]";
        $xpath .= "//div[@class='fitem' and descendant::label[contains(.,'$varname ')]]";
        $xpath .= "//div[@class='felement']//input";
        $node = $this->find(
            'xpath',
            $xpath,
            new \Behat\Mink\Exception\ExpectationException('Courseset setting "'.$varname.'" could not be found', $this->getSession())
        );

        if ($node->isVisible()) {
            $node->setValue($value);
        } else {
            throw new \Behat\Mink\Exception\ExpectationException('Courseset setting "'.$varname.'" is not visible', $this->getSession());
        }

        return $node;
    }

    /**
     * Winds back the timestamps for certifications so you can trigger recerts.
     *
     * @Then /^I wind back certification dates by (\d+) months$/
     */
    public function i_wind_back_certification_dates_by_months($windback) {
        \behat_hooks::set_step_readonly(true); // No browser action.
        global $DB;

        $windback = (int)$windback * (4 * WEEKSECS); // Assuming 4 weeks per month (close enough).

        // A list of all the places we need to windback, table => fields.
        $databasefields = array(
            'prog_completion' => array('timestarted', 'timedue', 'timecompleted'),
            'certif_completion' => array('timewindowopens', 'timeexpires', 'timecompleted'),
            'certif_completion_history' => array('timewindowopens', 'timeexpires', 'timecompleted', 'timemodified'),
        );

        // Windback all the timestamps by the specified amount, but don't fall into negatives.
        foreach ($databasefields as $table => $fields) {
            foreach ($fields as $field) {
                $sql = "UPDATE {{$table}}
                           SET {$field} = {$field} - {$windback}
                         WHERE {$field} > {$windback}";
                $DB->execute($sql);
            }
        }

        return true;
    }

    /**
     * Force waiting for X seconds without javascript in Totara.
     *
     * Usually needed when things need to have different timestamps and GoutteDriver is too fast.
     *
     * @Then /^I force sleep "(?P<seconds_number>\d+)" seconds$/
     * @param int $seconds
     */
    public function i_force_sleep($seconds) {
        \behat_hooks::set_step_readonly(true);
        if ($this->running_javascript()) {
            throw new \Behat\Mink\Exception\DriverException('Use \'I wait "X" seconds\' with Javascript support');
        }
        sleep($seconds);
    }

    /**
     * Force waiting for the next second.
     *
     * This is intended for places that need different timestamp in database.
     *
     * @Then /^I wait for the next second$/
     */
    public function i_wait_for_next_second() {
        \behat_hooks::set_step_readonly(true);
        $now = microtime(true);
        $sleep = ceil($now) - $now;
        if ($sleep > 0) {
            // Explicit conversion of float to int in PHP 8.1
            usleep((int) ($sleep * 1000000));
        } else {
            usleep(1000000);
        }
    }

    /**
     * Expect to see a specific image (by alt or title) within the given thing.
     *
     * @Then /^I should see the "([^"]*)" image in the "([^"]*)" "([^"]*)"$/
     */
    public function i_should_see_the_x_image_in_the_y_element($titleoralt, $containerelement, $containerselectortype) {
        \behat_hooks::set_step_readonly(true);
        // Get the container node; here we throw an exception
        // if the container node does not exist.
        $containernode = $this->get_selected_node($containerselectortype, $containerelement);

        $xpathliteral = behat_context_helper::escape($titleoralt);
        $locator = "//img[@alt={$xpathliteral} or @title={$xpathliteral}]";

        // Will throw an ElementNotFoundException if it does not exist, but, actually
        // it should not exist, so we try & catch it.
        try {
            // Would be better to use a 1 second sleep because the element should not be there,
            // but we would need to duplicate the whole find_all() logic to do it, the benefit of
            // changing to 1 second sleep is not significant.
            $this->find('xpath', $locator, false, $containernode, self::get_reduced_timeout());
        } catch (ElementNotFoundException $e) {
            throw new ExpectationException('The "' . $titleoralt . '" image was not found exists in the "' .
                $containerelement . '" "' . $containerselectortype . '"', $this->getSession());
        }

    }

    /**
     * Expect to not see a specific image (by alt or title) within the given thing.
     *
     * @Then /^I should not see the "([^"]*)" image in the "([^"]*)" "([^"]*)"$/
     */
    public function i_should_not_see_the_x_image_in_the_y_element($titleoralt, $containerelement, $containerselectortype) {
        \behat_hooks::set_step_readonly(true);
        // Get the container node; here we throw an exception
        // if the container node does not exist.
        $containernode = $this->get_selected_node($containerselectortype, $containerelement);

        $xpathliteral = behat_context_helper::escape($titleoralt);
        $locator = "//img[@alt={$xpathliteral} or @title={$xpathliteral}]";

        // Will throw an ElementNotFoundException if it does not exist, but, actually
        // it should not exist, so we try & catch it.
        try {
            // Would be better to use a 1 second sleep because the element should not be there,
            // but we would need to duplicate the whole find_all() logic to do it, the benefit of
            // changing to 1 second sleep is not significant.
            $node = $this->find('xpath', $locator, false, $containernode, self::get_reduced_timeout());
            if ($this->running_javascript() && !$node->isVisible()) {
                // It passes it is there but is not visible.
                return;
            }
        } catch (ElementNotFoundException $e) {
            // It passes.
            return;
        }
        throw new ExpectationException('The "' . $titleoralt . '" image was found in the "' .
            $containerelement . '" "' . $containerselectortype . '"', $this->getSession());
    }

    /**
     * Convenience step to force a Behat scenario to be skipped. Use anywhere in
     * a Behat scenario; all steps after this step will be skipped but the test
     * will not count as "failed". If you use it in a background section, then
     * the entire feature will be skipped.
     *
     * This is meant to be used in the situation where there are known bugs in
     * the code under test but the bugs have not been fixed yet. Another reason
     * for this step is to force the test to indicate an issue tracker reference
     * that will resolve the bugs(s).
     *
     * @Given /^I skip the scenario until issue "([^"]*)" lands$/
     */
    public function i_skip_the_scenario_until_issue_lands($issue) {
        if (!empty($issue)) {
            $msg = "THIS SCENARIO IS SKIPPED UNTIL '$issue' LANDS.";
            throw new \Moodle\BehatExtension\Exception\SkippedException($msg);
        }

        throw new ExpectationException(
            'No associated issue given for skipped scenario', $this->getSession()
        );
    }

    /**
     * Convenience step to force a Behat scenario to be skipped if a server is running on Windows.
     * Note that we do not need the opposite step so far.
     *
     * @Given /^I skip the scenario if a site is on Windows due to "([^"]*)"$/
     */
    public function i_skip_the_scenario_if_a_site_is_on_windows_due_to($reason) {
        \behat_hooks::set_step_readonly(false);
        // Although this is not the correct way to detect Windows, this is true enough.
        if (DIRECTORY_SEPARATOR === '\\') {
            throw new \Moodle\BehatExtension\Exception\SkippedException('THIS SCENARIO IS SKIPPED ON WINDOWS SITE: '.$reason);
        }
    }

    /**
     * Skip tests if PHP extension is not available.
     *
     * @Given /^I skip the scenario if PHP extension "([^"]*)" is not available$/
     */
    public function i_skip_the_scenario_if_php_extension_not_available($extension) {
        \behat_hooks::set_step_readonly(false);
        if (!extension_loaded($extension)) {
            throw new \Moodle\BehatExtension\Exception\SkippedException("THIS SCENARIO IS SKIPPED BECAUSE PHP EXTENSION $extension IS NOT AVAILABLE");
        }
    }

    /**
     * Am I on the right page? This is intended to be used
     * instead of 'I should see "Course 1"' when on course page.
     *
     * @Then /^I should see "([^"]*)" in the page title$/
     */
    public function i_should_see_in_the_page_title($text) {
        \behat_hooks::set_step_readonly(true);
        $text = behat_context_helper::escape($text);
        $xpath = "//title[contains(text(), {$text})]";
        $this->find(
            'xpath',
            $xpath,
            new ExpectationException('Text "'.$text.'" was not found in page header', $this->getSession())
        );
    }

    /**
     * I should see a date in a particular format in the specified element.
     *
     * @Then /^I should see the current date in format "([^"]*)" in the "([^"]*)" "([^"]*)"$/
     * @param string $format Date format to search for
     * @param string $node_element Element we look in
     * @param string $node_selector_type The type of selector where we look in
     */
    public function i_should_current_date_in_element(string $format, string $node_element, string $node_selector_type) {
        $date = date($format);

        // Getting the container where the text should be found.
        $container = $this->get_selected_node($node_selector_type, $node_element);

        $xpathliteral = behat_context_helper::escape($date);
        $xpath = "/descendant-or-self::*[contains(., $xpathliteral)]";
        $nodes = $this->find_all(
            'xpath',
            $xpath,
            new ExpectationException('"' . $date . '" text was not found in the "' . $node_element . '" element', $this->getSession()),
            $container
        );
    }

    /**
     * Searches for a specific term in the totara dialog.
     *
     * @Given /^I search for "([^"]*)" in the "([^"]*)" totara dialogue$/
     * @param string $term
     * @throws ExpectationException
     */
    public function i_search_for_in_the_totara_dialogue($term, $dialog) {
        \behat_hooks::set_step_readonly(true);
        $dialog = $this->get_selected_node('totaradialogue', $dialog);
        if (!$dialog) {
            throw new ExpectationException('Unable to find the "'.$dialog.'" Totara dialog', $this->getSession());
        }

        // Set the Search value via javascript to prevent the problems with Selenium when the input has focus
        $xpath = $dialog->getXPath() . "//input[@id='id_query']";
        $js  = 'var e;';
        $js .= 'e = document.evaluate(' . json_encode($xpath) . ', document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue;';
        $js .= 'e.value = ' . json_encode($term) .';';
         $this->getSession()->executeScript($js);

        $node = $dialog->find('xpath', '//input[@type="submit" and @value="Search"]');
        if (!$node) {
            throw new ExpectationException('Unable to find the search button within the "'.$dialog.'" Totara dialog', $this->getSession());
        }
        $node->press();

        // Its now loading some content via AJAX.
        $this->wait_for_pending_js();
    }

    /**
     * Clicks on a specific result in the totara dialog search results.
     *
     * @Given /^I click on "([^"]*)" from the search results in the "([^"]*)" totara dialogue$/
     * @param string $term
     * @throws ExpectationException
     */
    public function i_click_on_from_the_search_results_in_the_totara_dialogue($term, $dialog) {
        \behat_hooks::set_step_readonly(false);
        $dialog = $this->get_selected_node('totaradialogue', $dialog);
        if (!$dialog) {
            throw new ExpectationException('Unable to find the "'.$dialog.'" Totara dialog', $this->getSession());
        }
        $results = $dialog->find('xpath', '//*[@id="search-tab"]');

        $node = $results->findLink($term);
        $this->ensure_node_is_visible($node);
        $node->click();
    }

    /**
     * @Given /^I use magic for persistent login to open the login page/
     */
    public function visit_login_page() {
        \behat_hooks::set_step_readonly(false);
        // Visit login page.
        $this->getSession()->visit($this->locate_path('login/index.php'));
        $this->wait_for_pending_js();
    }

    /**
     * @Given /^I use magic for persistent login to simulate session timeout$/
     */
    public function session_timeout() {
        \behat_hooks::set_step_readonly(false);
        // Visit login page.
        $this->getSession()->visit($this->locate_path('totara/core/tests/fixtures/session_timeout.php'));
    }

    /**
     * @Given /^I use magic for persistent login to purge cookies$/
     */
    public function purge_cookies() {
        \behat_hooks::set_step_readonly(false);
        // Visit login page.
        $this->getSession()->visit($this->locate_path('totara/core/tests/fixtures/purge_cookies.php'));
    }

    /**
     * Navigates directly to the specified fixture.
     *
     * These pages are only used for acceptance testing and do not appear in the navigation.
     * For that reason we must navigate directly to them.
     *
     * @Given /^I navigate to the "([^"]*)" fixture in the "([^"]*)" plugin$/
     * @param string $name
     * @param string $path
     */
    public function i_navigate_to_the_fixture_in_the_plugin($name, $path) {
        \behat_hooks::set_step_readonly(false);
        $url = new moodle_url("/{$path}/tests/fixtures/{$name}.php");
        $this->getSession()->visit($url->out(false));
        $this->wait_for_pending_js();
    }

    /**
     * Navigates directly to the specified fixture.
     *
     * These pages are only used for acceptance testing and do not appear in the navigation.
     * For that reason we must navigate directly to them.
     *
     * @Given /^I navigate to the "([^"]*)" fixture in the "([^"]*)" plugin with the following settings$/
     * @param string $name
     * @param string $path
     */
    public function i_navigate_to_the_fixture_in_the_plugin_with_the_following_settings($name, $path, TableNode $table) {
        \behat_hooks::set_step_readonly(false);
        $settings = [];
        foreach ($table->getRows() as $row) {
            $settings[$row[0]] = $row[1];
        }
        $url = new moodle_url("/{$path}/tests/fixtures/{$name}.php", $settings);
        $this->getSession()->visit($url->out(false));
        $this->wait_for_pending_js();
    }

    /**
     * @Then /^I should see the "([^"]*)" catalog page$/
     * @param string $name
     * @param string $path
     */
    public function i_should_see_catalog_page($catalogtype) {
        \behat_hooks::set_step_readonly(true);
        switch ($catalogtype) {
            case "totara":
                $this->execute('behat_general::assert_element_contains_text', ["Find learning", ".tw-catalog__title", "css_element"]);
                $this->execute('behat_general::should_exist', ["#catalog_fts_input", "css_element"]);
                break;
            case "enhanced":
                $this->execute('behat_general::assert_page_contains_text', 'Search Courses:');
                $this->execute('behat_general::assert_element_contains_text', ["records shown", ".rb-record-count", "css_element"]);
                break;
            case "moodle":
                $this->execute(
                    'behat_general::assert_element_contains_text',
                    ["Search courses", "form[id='coursesearch']", "css_element"]
                );
                break;
            default:
                throw new Exception("The specified catalog type '{$catalogtype}' does not exist.'");
        }
    }

    /**
     * @Given /^I am on totara catalog page$/
     */
    public function i_am_on_totara_catalog_page() {
        \behat_hooks::set_step_readonly(false);
        $this->getSession()->visit($this->locate_path('totara/catalog/index.php'));
        $this->wait_for_pending_js();
    }

    /**
     * Navigate to profile page for a given user
     *
     * @Given /^I am on profile page for user "([^"]*)"$/
     */
    public function i_am_on_profile_for_user($username) {
        global $DB;

        $user = $DB->get_record('user', ['username' => $username]);

        $url = new moodle_url('/user/profile.php', ['id' => $user->id]);
        $this->getSession()->visit($this->locate_path($url->out_as_local_url(false)));
        $this->wait_for_pending_js();
    }

    /**
     * Set the global site theme for a device.
     *
     * @When /^I set the site theme to "([^"]*)" for device type "([^"]*)"$/
     *
     * @param string $theme_name The name of the theme, e.g. 'basis' or 'ventura'
     * @param string $device_type The name of the device, e.g. 'default' or 'legacy'
     */
    public function i_set_the_site_theme_to_for_device_type($theme_name, $device_type) {
        \behat_hooks::set_step_readonly(false);

        $device_theme = core_useragent::get_device_type_cfg_var_name($device_type);
        $theme = theme_config::load($theme_name);
        set_config($device_theme, $theme->name);

        $this->getSession()->reload();
    }

    /**
     * Set the global default site theme.
     *
     * @When /^I set the site theme to "([^"]*)"$/
     *
     * @param string $theme_name The name of the theme, e.g. 'basis' or 'ventura'
     */
    public function i_set_the_site_theme_to($theme_name) {
        $this->i_set_the_site_theme_to_for_device_type($theme_name, 'default');
    }

    /**
     * Open/Close the admin quick access menu.
     *
     * @When /^I toggle open the admin quick access menu$/
     * @throws ExpectationException
     */
    public function i_toggle_open_the_admin_quick_access_menu(): void {
        $menu_button = $this->find('css', '.nav-link.totara_core__QuickAccess_icon.popover-region-toggle');
        if ($menu_button === null || !$menu_button->isVisible()) {
            throw new ExpectationException("Admin quick access menu toggle button not visible", $this->getSession());
        }
        $menu_button->click();
        $this->wait_for_pending_js();
    }

    /**
     * Get the navigation items listed in the admin quick access menu.
     *
     * @return string[]
     */
    private function get_quick_access_menu_items(): array {
        return array_map(static function (NodeElement $element) {
            return $element->getText();
        }, $this->find_all('css', '#quickaccess-popover-content ul.totara_core__QuickAccess_group-list li'));
    }

    /**
     * Make sure an item appears in the admin quick access menu.
     *
     * @Then /^I should see "([^"]*)" in the admin quick access menu$/
     * @param string $expected
     * @throws ExpectationException
     */
    public function i_should_see_in_the_admin_quick_access_menu(string $expected): void {
        \behat_hooks::set_step_readonly(true);

        if (!in_array($expected, $this->get_quick_access_menu_items())) {
            throw new ExpectationException("\"{$expected}\" was not found in the admin quick access menu", $this->getSession());
        }
    }

    /**
     * Make sure an item does not appear in the admin quick access menu.
     *
     * @Then /^I should not see "([^"]*)" in the admin quick access menu$/
     * @param string $expected
     * @throws ExpectationException
     */
    public function i_should_not_see_in_the_admin_quick_access_menu(string $expected): void {
        \behat_hooks::set_step_readonly(true);

        if (in_array($expected, $this->get_quick_access_menu_items())) {
            throw new ExpectationException("\"{$expected}\" was found in the admin quick access menu", $this->getSession());
        }
    }

    /**
     * Make sure multi-lang filtering is enabled
     *
     * @Given /^the multi-language content filter is enabled/
     */
    public function the_multi_language_content_filter_is_enabled(): void {
        global $CFG;
        require_once("$CFG->libdir/filterlib.php");

        \behat_hooks::set_step_readonly(false);

        filter_set_global_state('multilang', TEXTFILTER_ON);
        filter_set_applies_to_strings('multilang', 1);
    }

    /**
     * Make sure multi-lang filtering is disabled
     *
     * @Given /^the multi-language content filter is disabled/
     */
    public function the_multi_language_content_filter_is_disabled(): void {
        global $CFG;
        require_once("$CFG->libdir/filterlib.php");

        \behat_hooks::set_step_readonly(false);

        filter_set_global_state('multilang', TEXTFILTER_OFF);
    }

    /**
     * @When /^(?:|I )confirm the browser confirmation popup$/
     */
    public function i_confirm_the_browser_confirmation_popup() {
        $this->getSession()->getDriver()->getWebDriverSession()->accept_alert();
    }

    /**
     * @When /^(?:|I )cancel the browser confirmation popup$/
     */
    public function i_cancel_the_browser_confirmation_popup() {
        $this->getSession()->getDriver()->getWebDriverSession()->dismiss_alert();
    }

    /**
     * @When /^(?:|I )should see "([^"]*)" in browser confirmation popup$/
     *
     * @param string $expected_message The message.
     * @throws ExpectationException
     */
    public function i_should_see_the_message_in_browser_confirmation_popup(string $expected_message) {
        $actual_message = $this->getSession()->getDriver()->getWebDriverSession()->getAlert_text();
        if ($expected_message != $actual_message) {
            throw new ExpectationException("\"{$expected_message}\" was not found in the popup message", $this->getSession());
        }
    }
}
