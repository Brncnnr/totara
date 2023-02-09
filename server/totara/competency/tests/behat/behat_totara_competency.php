<?php
/*
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_competency
 */

use Behat\Gherkin\Node\TableNode as TableNode;
use Behat\Mink\Exception\ExpectationException;
use core\collection;
use core\entity\user;
use Behat\Mink\Element\NodeElement;
use totara_competency\entity\assignment;
use totara_competency\models\assignment as assignment_model;
use totara_competency\pathway_factory;
use totara_criteria\criterion;
use totara_criteria\criterion_factory;
use totara_hierarchy\entity\competency;

class behat_totara_competency extends behat_base {

    private const COMPETENCY_PROFILE_LIST_VIEW_TOGGLE_LOCATOR = '//*[@aria-label="Display formats"]//*[@aria-label="Show tables"]/*';
    private const COMPETENCY_PROFILE_MANUAL_ACHIEVEMENT_LOCATOR = '.tui-pathwayManualAchievementRater';
    private const COMPETENCY_PROFILE_MANUAL_ACHIEVEMENT_ROLE_LOCATOR = '.tui-pathwayManualAchievementRater__overview-role';
    private const COMPETENCY_PROFILE_MANUAL_ACHIEVEMENT_CONTENT_LOCATOR = '.tui-dataTableCell__content';
    private const TOTARA_COMPETENCY_PROFILE_PATH = 'totara/competency/profile/index.php';
    private const TOTARA_COMPETENCY_PROFILE_DETAIL_PATH = 'totara/competency/profile/details/index.php';
    private const TOTARA_COMPETENCY_USER_ASSIGNMENT_PATH = 'totara/competency/profile/assign/index.php';
    private const TOTARA_COMPETENCY_ACHIEVEMENT_PATHS_PATH = 'totara/competency/competency_edit.php';
    private const TOTARA_COMPETENCY_COPY_PATHWAYS_PAGE = 'totara/competency/pathway_copy.php';
    private const COMPETENCY_RATING_SCALE_OVERVIEW_TITLE = '.tui-competencyRatingScaleOverview__title';
    private const COMPETENCY_RATING_SCALE_OVERVIEW_LIST_ITEM = '.tui-competencyRatingScaleOverview__list-item';
    private const COMPETENCY_RATING_SCALE_OVERVIEW_SCALE_VALUE_NAME = '.tui-competencyRatingScaleOverview__scaleValue-name';
    private const SVG_ICON_STATE_SUCCESS = '.tui-svgIcon--state-success';

    /**
     * @var \totara_competency\testing\generator
     */
    protected $generator;

    private $pathway_display_names = [];
    private $criteria_display_names = [];
    private $criteria_item_types = [];

    /**
     * Opens the current users competency profile.
     *
     * @When /^I navigate to my competency profile$/
     * @When /^I navigate to the competency profile of user "(?P<user>(?:[^"]|\\")*)"$/
     * @param string|int|null $user
     * @return void
     * @throws Exception
     */
    public function i_navigate_to_my_competency_profile($user = null): void {
        behat_hooks::set_step_readonly(false);

        $query_params = [];
        if ($user) {
            $query_params['user_id'] = $this->resolve_user_id($user);
        }

        $detail_page_url = new moodle_url(self::TOTARA_COMPETENCY_PROFILE_PATH, $query_params);

        $this->getSession()->visit($this->locate_path($detail_page_url->out(false)));
    }

    /**
     * @When /^I navigate to the competency self assignment page$/
     * @return void
     * @throws Exception
     */
    public function i_navigate_to_the_competency_self_assignment_page(): void {
        behat_hooks::set_step_readonly(false);

        $this->getSession()->visit($this->locate_path(self::TOTARA_COMPETENCY_USER_ASSIGNMENT_PATH));
    }

    /**
     * @Then /^I should be on my competency profile$/
     */
    public function i_should_be_on_my_competency_profile(): void {
        $expected_path = $this->locate_path(self::TOTARA_COMPETENCY_PROFILE_PATH);
        $actual_url = $this->getSession()->getCurrentUrl();

        if (strpos($actual_url, $expected_path) === false) {
            $exception_message = "Expected the current url to be {$expected_path}, instead was {$actual_url}";
            throw new ExpectationException($exception_message, $this->getSession());
        }
    }

    /**
     * @Given /^I navigate to the competency user assignment page for guest user$/
     * @return void
     * @throws Exception
     */
    public function i_navigate_to_the_competency_user_assignment_page_for_guest_user(): void {
        behat_hooks::set_step_readonly(false);

        /** @var User $user */
        $user = User::repository()
            ->select('id')
            ->where('username', 'guest')
            ->order_by('id')
            ->first();

        $url = $this->locate_path((string) new moodle_url(self::TOTARA_COMPETENCY_USER_ASSIGNMENT_PATH, ['user_id' => $user->id]));

        $this->getSession()->visit($url);
    }

    /**
     * @When /^I change the competency profile to list view$/
     */
    public function i_change_the_competency_profile_to_list_view(): void {
        behat_hooks::set_step_readonly(false);

        $this->find('xpath', self::COMPETENCY_PROFILE_LIST_VIEW_TOGGLE_LOCATOR)->click();
    }

    /**
     * @Then /^The competency profile rating scale popover title should be "([^"]*)"$/
     * @param string $expected_text
     */
    public function competency_profile_rating_scale_popover_title_should_be(string $expected_text): void {
        $popover = $this->get_behat_tui()->find_visible_popover();

        $text = trim($popover->find('css', self::COMPETENCY_RATING_SCALE_OVERVIEW_TITLE)->getText());

        if ($text !== $expected_text) {
            throw new ExpectationException("Expected title {$expected_text}, but found {$text}", $this->getSession());
        }
    }

    /**
     * @Then /^The competency profile rating scale popover values should match:$/
     * @param TableNode $table_node
     */
    public function competency_profile_rating_scale_popover_values_should_match(TableNode $table_node): void {
        $actual_list_items = $this->get_behat_tui()
            ->find_visible_popover()
            ->findAll('css', self::COMPETENCY_RATING_SCALE_OVERVIEW_LIST_ITEM);

        $actual_list_item_count = count($actual_list_items);
        $expected_list_item_count = count($table_node->getHash());

        if ($actual_list_item_count !== $expected_list_item_count) {
            throw new ExpectationException(
                "Expected {$actual_list_item_count}, but found {$expected_list_item_count}",
                $this->getSession()
            );
        }

        foreach ($table_node->getHash() as $index => $expectation) {
            $actual_value_name = trim($actual_list_items[$index]->find(
                'css', self::COMPETENCY_RATING_SCALE_OVERVIEW_SCALE_VALUE_NAME)->getText()
            );

            if ($expectation['value_name'] !== $actual_value_name) {
                throw new ExpectationException(
                    "Expected '{$expectation['value_name']}' for item {$index}, but found '{$actual_value_name}'",
                    $this->getSession()
                );
            }

            $actual_is_proficient_value = $actual_list_items[$index]->find('css', self::SVG_ICON_STATE_SUCCESS);

            if (!in_array($expectation['is_proficient'], ['yes', 'no'], true)){
                throw new ExpectationException(
                    "You must supply either 'yes' or 'no' for is_proficient {$expectation['is_proficient']} was supplied",
                    $this->getSession()
                );
            }

            if ($expectation['is_proficient'] === 'yes' && $actual_is_proficient_value === null) {
                throw new ExpectationException(
                    "Expected '{$expectation['value_name']}' (item {$index}) to be marked as is_proficient but it was not",
                    $this->getSession()
                );
            }

            if ($expectation['is_proficient'] === 'no' && $actual_is_proficient_value !== null) {
                throw new ExpectationException(
                    "Expected '{$expectation['value_name']}' (item {$index}) to be not be marked as is_proficient but it was",
                    $this->getSession()
                );
            }
        }
    }

    /**
     * Create a scale with a name and scale values.
     *
     * @Given /^a competency scale called "(?P<scalename_string>(?:[^"]|\\")*)" exists with the following values:$/
     * @param string $scalename
     * @param TableNode $table
     * @throws Exception
     */
    public function competency_scale_called_exists($scalename, TableNode $table) {
        $scale_values = $this->parse_table(
            $table,
            ['name', 'proficient', 'default', 'sortorder'],
            ['idnumber', 'description']
        );

        $this->get_data_generator()->create_scale($scalename, null, $scale_values);
    }

    /**
     * Archive all assignments for a given competency.
     *
     * @Given /^all assignments for the "(?P<competency_string>(?:[^"]|\\")*)" competency are archived at "([^"]*)"$/
     * @param string $competency
     * @param string $date
     * @throws coding_exception
     */
    public function all_assignments_for_the_competency_are_archived(string $competency, string $date): void {
        /** @var collection|assignment[] $assignments */
        $assignments = assignment::repository()
            ->filter_by_active()
            ->join([competency::TABLE, 'comp'], 'competency_id', 'id')
            ->where('comp.idnumber', $competency)
            ->get();

        if (count($assignments) === null) {
            throw new \InvalidArgumentException("No competency exists with the idnumber {$competency}");
        }

        foreach ($assignments as $assignment) {
            assignment_model::load_by_entity($assignment)->archive();

            if ($date) {
                $assignment->archived_at = (new DateTime($date))->getTimestamp();
                $assignment->save();
            }
        }
    }

    /**
     * @Given /^I navigate to the competency profile details page for the "([^"]*)" competency$/
     * @Given /^I navigate to the competency profile details page for competency id "([^"]*)"$/
     * @Given /^I navigate to the competency profile details page for the "([^"]*)" competency and user "([^"]*)"$/
     * @param string|int $competency
     * @param string|int|null $user
     * @throws moodle_exception
     */
    public function i_navigate_to_the_competency_profile_details_page_for($competency, $user = null): void {
        behat_hooks::set_step_readonly(false);

        $competency_id = $this->resolve_competency_id($competency);
        $query_params = ['competency_id' => $competency_id];

        if ($user) {
            $query_params['user_id'] = $this->resolve_user_id($user);
        }

        $detail_page_url = new moodle_url(self::TOTARA_COMPETENCY_PROFILE_DETAIL_PATH, $query_params);

        $this->getSession()->visit($this->locate_path($detail_page_url->out(false)));
    }

    /**
     * @Given /^I navigate to the competency pathways copying page for the "([^"]*)" competency$/
     *
     * @param string $fullname competency fullname.
     *
     * @throws moodle_exception
     */
    public function i_navigate_to_the_competency_pathways_copying_page_for(string $fullname): void {
        behat_hooks::set_step_readonly(false);

        $competency_id = $this->resolve_competency_id($fullname);
        $query_params = ['id' => $competency_id];
        $url = new moodle_url(self::TOTARA_COMPETENCY_COPY_PATHWAYS_PAGE, $query_params);

        $this->getSession()->visit($this->locate_path($url->out(false)));
    }

    private function resolve_competency_id($competency): int {
        if (is_numeric($competency)) {
            return $competency;
        }

        /** @var competency $competency */
        $competency = competency::repository()->where('fullname', $competency)->get()->first();

        return $competency->id;
    }

    private function resolve_user_id($user): int {
        if (is_numeric($user)) {
            return $user;
        }

        /** @var user $user_entity */
        $user_entity = user::repository()->where('username', $user)->get()->first();

        return $user_entity->id;
    }

    /**
     * Turn the table into an array of key=>value records.
     *
     * @param TableNode $table
     * @param array $required_columns
     * @param array $optional_columns
     * @return array
     * @throws Exception
     */
    private function parse_table(TableNode $table, array $required_columns, array $optional_columns = []): array {
        $table = $table->getHash();
        $first_row = reset($table);

        // Check required fields are present.
        foreach ($required_columns as $column) {
            if (!isset($first_row[$column])) {
                throw new Exception("The {$column} field must be defined!");
            }
        }

        // Copy values, ready to pass on to the generator.
        $records = [];

        foreach ($table as $row) {
            $record = [];
            foreach ($row as $field_name => $value) {
                if (in_array($field_name, $required_columns)) {
                    $record[$field_name] = $value;
                } else if (in_array($field_name, $optional_columns)) {
                    $record[$field_name] = $value;
                } else {
                    throw new Exception("Unknown field {$field_name} in the table definition!");
                }
            }
            $records[] = $record;
        }

        return $records;
    }

    /**
     * @return \totara_competency\testing\generator
     */
    private function get_data_generator() {
        return \totara_competency\testing\generator::instance();
    }

    /**
     * Archive all assignments for a given assignment type.
     * @Given /^all assignments for the "([^"]*)" assignment type are archived$/
     *
     * @param string $assignment_type
     * @throws Exception
     */
    public function all_assignments_for_the_assignment_type_are_archived($assignment_type) {
        $assignments = assignment::repository()
            ->filter_by_active()
            ->where('user_group_type', $assignment_type)
            ->get_lazy();
        foreach ($assignments as $assignment) {
            assignment_model::load_by_entity($assignment)->archive();
        }
    }

    /**
     * @Given /^I navigate to the competency achievement paths page for the "(?P<competency>(?:[^"]|\\")*)" competency$/
     *
     * @param string|int $competency
     * @throws moodle_exception
     */
    public function i_navigate_to_the_competency_achievement_paths_page_for($competency): void {
        \behat_hooks::set_step_readonly(false);

        $competency_id = $this->resolve_competency_id($competency);
        $query_params = ['s' => 'achievement_paths', 'id' => $competency_id];
        $detail_page_url = new moodle_url(self::TOTARA_COMPETENCY_ACHIEVEMENT_PATHS_PATH, $query_params);

        $this->getSession()->visit($this->locate_path($detail_page_url->out(false)));
    }

    /**
     * Add an achievement path of the specified type
     * @Given /^I add a "(?P<pathway_type>[^"]*)" pathway$/
     * @param string $pathway_type
     */
    public function i_add_a_pathway(string $pathway_type): void {
        \behat_hooks::set_step_readonly(false);

        $xpath = "//*[@data-tw-editachievementpaths-add-pathway]//option[@value='$pathway_type']";
        /** @var NodeElement $option */
        $option = null;

        try {
            $option = $this->find('xpath', $xpath);
        } catch (Exception $e) {
            throw new ExpectationException(
                "Invalid pathway type \"$pathway_type\".",
                $this->getSession()
            );
        }

        $option->click();
    }

    /**
     * @Given /^I (should|should not) see "(?P<pathway_type>(?:[^"]|\\")*)" pathway$/
     * @Given /^I (should|should not) see "(?P<pathway_type>(?:[^"]|\\")*)" pathway "(before|after)" criteria groups$/
     * @param string $not
     * @param string $pathway_type
     * @param string|null $position
     * @throws Exception
     */
    public function i_should_see_pathway_(string $not, string $pathway_type, ?string $position = null) {
        \behat_hooks::set_step_readonly(true);

        $expected = ($not === 'should');
        $msg = '"' . $pathway_type . '" pathway' . ($position === null ? '' : ' "' . $position . '" criteria groups') ;

        /** @var NodeElement $node */
        $node = null;

        try {
            $node = $this->find_nth_pathway_node_with_type($pathway_type, "1", $position);
        } catch (Exception $e) {
            if ($expected) {
                throw new ExpectationException(
                    $msg . ' could not be found',
                    $this->getSession()
                );
            }
            return;
        }

        if (!$expected) {
            throw new ExpectationException(
                $msg . ' found when it should not be there',
                $this->getSession()
            );
        }
    }

    /**
     * @Given /^I (should|should not) see "(?P<text_string>(?:[^"]|\\")*)" in "(?P<pathway_type>(?:[^"]|\\")*)" pathway$/
     * @Given /^I (should|should not) see "(?P<text_string>(?:[^"]|\\")*)" in "(?P<pathway_type>(?:[^"]|\\")*)" pathway "(?P<pathway_idx>[^"][0-9]*)"$/
     * @Given /^I (should|should not) see "(?P<text_string>(?:[^"]|\\")*)" in "(?P<pathway_type>(?:[^"]|\\")*)" pathway "(?P<pathway_idx>[^"][0-9]*)" "(before|after)" criteria groups$/
     * @param string $not
     * @param string $search_string
     * @param string $pathway_type
     * @param string|null $pathway_idx
     * @param string|null $position
     * @throws Exception
     */
    public function i_should_see_in_pathway(string $not, string $text_string, string $pathway_type,
        ?string $pathway_idx = null, ?string $position = null
    ) {
        \behat_hooks::set_step_readonly(true);

        $expected = ($not === 'should');
        $msg = $this->get_pathway_description($pathway_type, $pathway_idx, $position);

        /** @var NodeElement $pathway_node */
        $pathway_node = null;

        try {
            $pathway_node = $this->find_nth_pathway_node_with_type($pathway_type, $pathway_idx, $position);
        } catch (Exception $e) {
            throw new ExpectationException(
                $msg . ' could not be found',
                $this->getSession()
            );
        }

        $text_node = null;
        $fnd = true;
        try {
            $xpath = ".//span[contains(., '$text_string')]";
            $text_node = $pathway_node->find('xpath', $xpath);
        } catch (Exception $e) {
            $fnd = false;
        }

        if ($fnd) {
            try {
                $this->ensure_node_is_visible($text_node);
            } catch (Exception $e) {
                $fnd = false;
            }
        }

        if ($fnd !== $expected) {
            $msg = '"' . $text_string . '"' . ($fnd ? '' : ' not') . ' found in ' . $msg . ($fnd ? ' when it should not be there' : '');
            throw new ExpectationException($msg, $this->getSession());
        }
    }

    /**
     * @Given /^I click on "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" in "(?P<pathway_type>(?:[^"]|\\")*)" pathway$/
     * @Given /^I click on "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" in "(?P<pathway_type>(?:[^"]|\\")*)" pathway "(?P<pathway_idx>[^"][0-9]*)"$/
     * @Given /^I click on "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" in "(?P<pathway_type>(?:[^"]|\\")*)" pathway "(?P<pathway_idx>[^"][0-9]*)" "(before|after)" criteria groups$/
     * @param string $element Element we look for
     * @param string $selector_type The type of what we look for
     * @param string $pathway_type
     * @param string|null $pathway_idx
     * @param string|null $position
     */
    public function i_click_on_in_pathway(string $element, string $selector_type, string $pathway_type,
        ?string $pathway_idx = null, ?string $position = null
    ) {
        \behat_hooks::set_step_readonly(false);

        /** @var NodeElement $pathway_node */
        $pathway_node = $this->find_nth_pathway_node_with_type($pathway_type, $pathway_idx, $position);
        /** @var NodeElement $node */
        $element_node = $this->find_element_in_node($pathway_node, $element, $selector_type);

        try {
            $this->ensure_node_is_visible($element_node);
            $element_node->click();
        } catch (Exception $e) {
            $msg = '"' . $element . '" "' . $selector_type . '" in ' .
                $this->get_pathway_description($pathway_type, $pathway_idx, $position) .
                ' is not clickable';
            throw new ExpectationException($msg, $this->getSession());
        }
    }

    /**
     * @Given /^"(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" (should|should not) be visible in "(?P<pathway_type>(?:[^"]|\\")*)" pathway$/
     * @Given /^"(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" (should|should not) be visible in "(?P<pathway_type>(?:[^"]|\\")*)" pathway "(?P<pathway_idx>[^"][0-9]*)"$/
     * @Given /^"(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" (should|should not) be visible in "(?P<pathway_type>(?:[^"]|\\")*)" pathway "(?P<pathway_idx>[^"][0-9]*)" "(before|after)" criteria groups$/
     * @param string $element Element we look for
     * @param string $selector_type The type of what we look for
     * @param string $not
     * @param string $pathway_type
     * @param string|null $pathway_idx
     * @param string|null $position
     */
    public function element_in_pathway_should_be_visible(string $element, string $selector_type, string $not,
        string $pathway_type, ?string $pathway_idx = null, ?string $position = null
    ) {
        \behat_hooks::set_step_readonly(true);

        /** @var NodeElement $pathway_node */
        $pathway_node = $this->find_nth_pathway_node_with_type($pathway_type, $pathway_idx, $position);
        /** @var NodeElement $element_node */
        $element_node = $this->find_element_in_node($pathway_node, $element, $selector_type);

        $expected = ($not === 'should');
        $visible = $element_node->isVisible();

        if ($expected != $visible) {
            $msg = '"' . $element . '" "' . $selector_type . '" is ' . ($visible ? '' : 'not ') .
                'visible in ' . $this->get_pathway_description($pathway_type, $pathway_idx, $position);
            throw new ExpectationException($msg, $this->getSession());
        }
    }

    /**
     * Find the nth pathway with the specified name before/after criteria groups
     * @param string $pathway_type
     * @param string|null $pathway_idx
     * @param string|null $position
     * @return NodeElement
     */
    private function find_nth_pathway_node_with_type(string $pathway_type, ?string $pathway_idx = null, ?string $position = null): NodeElement {
        $div = ($position ?? 'before') === 'after' ? 'high-sortorder' : 'low-sortorder';

        $display_name = $this->get_pathway_display_name($pathway_type);
        $xpath = "//*[@data-tw-editachievementpaths-group=\"$div\"]" .
            "//*[contains(., \"$display_name\")]" .
            "/ancestor::div[@data-tw-editachievementpaths-pathway-key]";

        /** @var NodeElement[] $pathway_nodes */
        $pathway_nodes = null;

        try {
            $pathway_nodes = $this->find_all('xpath', $xpath);
        } catch (Exception $e) {
            throw new ExpectationException(
                "\"$pathway_type\" pathway could not be found",
                $this->getSession()
            );
        }

        $nth = (int)($pathway_idx ?? "1");
        if ($nth <= 0) {
            throw new ExpectationException(
                'Invalid pathway index "' . $pathway_idx . '"',
                $this->getSession()
            );
        }

        if (count($pathway_nodes) < $nth) {
            $msg = '"' . $pathway_type . '"' . ($pathway_idx !== null ? " \"$pathway_idx\"" : '') . ' could not be found';
            throw new ExpectationException($msg, $this->getSession());
        }

        return $pathway_nodes[$nth - 1];
    }

    /**
     * @Given /^I should see the following singlevalue scale values:$/
     * @param TableNode $table
     * @throws Exception
     */
    public function singlevalue_scale_values_exists(TableNode $table) {
        \behat_hooks::set_step_readonly(true); // Backend action.

        $expected_scale_values = $this->parse_table(
            $table,
            ['name']
        );

        foreach ($expected_scale_values as $expected) {
            try {
                $scale_value_node = $this->find_scale_value_node($expected['name']);
            } catch (Exception $e) {
                $msg = '"' . $expected['name'] . '" could not be found';
                throw new ExpectationException($msg, $this->getSession());
                return;
            }
        }
    }

    /**
     * @param NodeElement $node
     * @param string $element
     * @param string $selector_type
     */
    private function find_element_in_node(NodeElement $node, string $element, string $selector_type) {
        // Getting Mink selector and locator.
        list($selector, $locator) = $this->transform_selector($selector_type, $element);

        // Gets the node based on the requested selector type and locator within the pathway.
        return $this->find($selector, $locator, false, $node);
    }

    /**
     * Add a criteria group containing the specified criterion to the scale value's achievement paths
     * @Given /^I add a criteria group with "(?P<criterion_type>[^"]*)" criterion to "(?P<scale_value_name>(?:[^"]|\\")*)" scalevalue$/
     * @param string $criterion_type
     * @param string $scale_value_name
     * @throws Exception
     */
    public function i_add_criteria_group_with_criterion_to_scalevalue(string $criterion_type, string $scale_value_name) {
        \behat_hooks::set_step_readonly(false);

        $scale_value_node = $this->find_scale_value_node($scale_value_name);
        $this->select_criterion_type($scale_value_node, $criterion_type);
    }

    /**
     * Add a criterion with the specified type to the nth criteria group resulting in the specific scale value
     * @Given /^I add a "(?P<criterion_type>[^"]*)" criterion to criteria group "(?P<group_idx>[^"][0-9]*)" in "(?P<scale_value_name>(?:[^"]|\\")*)" scalevalue$/
     * @param string $criterion_type
     * @param string $group_idx
     * @param string $scale_value_name
     * @throws Exception
     */
    public function i_add_criterion_to_nth_criteria_group_in_scalevalue(string $criterion_type,
        string $group_idx, string $scale_value_name
    ) {
        \behat_hooks::set_step_readonly(false);

        $criteria_group_node = $this->find_nth_criteria_group($scale_value_name, $group_idx);
        $this->select_criterion_type($criteria_group_node, $criterion_type);
    }

    /**
     * Verify the criteria in criteria group
     * @Given /^I should see "(?P<criterion_type>(?:[^"]|\\")*)" criterion in criteria group "(?P<group_idx>[^"][0-9]*)" in "(?P<scale_value_name>(?:[^"]|\\")*)" scalevalue$/
     * @param string $criterion_type
     * @param string $group_idx
     * @param string $scale_value_name
     * @throws Exception
     */
    public function i_should_see_criterion_in_nth_criteria_group_in_scalevalue(string $criterion_type,
        string $group_idx, string $scale_value_name
    ) {
        \behat_hooks::set_step_readonly(true);

        $this->find_nth_criterion_in_nth_criteria_group($criterion_type, "1", $group_idx, $scale_value_name);
    }

    /**
     * @Given /^the "(?P<criterion_type>[^"]*)" criterion type option should be (enabled|disabled) in "(?P<scale_value_name>(?:[^"]|\\")*)" scalevalue$/
     * @param string $criterion_type
     * @param string $enabled
     * @param string $scale_value_name
     */
    public function the_criterion_type_option_should_be_enabled_in_scalevalue(string $criterion_type, string $enabled,
        string $scale_value_name
    ) {
        \behat_hooks::set_step_readonly(true);

        $scale_value_node = $this->find_scale_value_node($scale_value_name);
        /** @var NodeElement $criterion_type_option */
        $criterion_type_option = $this->find_criterion_type_option($scale_value_node, $criterion_type);
        $expected = $enabled === 'enabled';
        $actual = !$criterion_type_option->hasAttribute('disabled');

        if ($expected != $actual) {
            $msg = '"' . $criterion_type . '" option is ' . ($actual ? 'enabled' : 'disabled') . ' when expected to be ' .
                ($enabled ? 'enabled' : 'disabled'
            );
            throw new ExpectationException($msg, $this->getSession());
        }
    }

    /**
     * @Given /^the "(?P<criterion_type>[^"]*)" criterion type option should be (enabled|disabled) in criteria group "(?P<group_idx>[^"][0-9]*)" in "(?P<scale_value_name>(?:[^"]|\\")*)" scalevalue$/
     * @param string $criterion_type
     * @param string $enabled
     * @param string $group_idx
     * @param string $scale_value_name
     */
    public function the_criterion_type_option_should_be_enabled_in_nth_criteria_group_in_scalevalue(string $criterion_type,
        string $enabled, string $group_idx, string $scale_value_name
    ) {
        \behat_hooks::set_step_readonly(true);

        $criteria_group_node = $this->find_nth_criteria_group($scale_value_name, $group_idx);
        /** @var NodeElement $criterion_type_button */
        $criterion_type_option = $this->find_criterion_type_option($criteria_group_node, $criterion_type);
        $expected = $enabled === 'enabled';
        $actual = !$criterion_type_option->hasAttribute('disabled');

        if ($expected != $actual) {
            $msg = '"' . $criterion_type . '" option is ' . ($actual ? 'enabled' : 'disabled') . ' when expected to be ' .
                ($enabled ? 'enabled' : 'disabled');
            throw new ExpectationException($msg, $this->getSession());
        }
    }

    /**
     * @When /^I click on "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" in "(?P<criterion_type>(?:[^"]|\\")*)" criterion "(?P<criterion_idx>[^"][0-9]*)" in criteria group "(?P<group_idx>[^"][0-9]*)" in "(?P<scale_value_name>(?:[^"]|\\")*)" scalevalue$/
     * @param string $element Element we look for
     * @param string $selector_type The type of what we look for
     * @param string $criterion_type
     * @param string $criterion_idx
     * @param string $group_idx
     * @param string $scale_value_name
     */
    public function i_click_on_in_nth_criterion_in_nth_criteria_group_in_scalevalue(
        string $element, string $selector_type, string $criterion_type, string $criterion_idx,
        string $group_idx, string $scale_value_name
    ) {
        \behat_hooks::set_step_readonly(false);

        /** @var NodeElement $criterion_node */
        $criterion_node = $this->find_nth_criterion_in_nth_criteria_group($criterion_type, $criterion_idx,
            $group_idx, $scale_value_name
        );
        /** @var NodeElement $element_node */
        $element_node = $this->find_element_in_node($criterion_node, $element, $selector_type);

        try {
            $this->ensure_node_is_visible($element_node);
            $element_node->click();
        } catch (Exception $e) {
            $msg = '"' . $element . '" "' . $selector_type . '" in ' .
                $this->get_criterion_description($criterion_type, $criterion_idx, $group_idx, $scale_value_name) .
                ' is not clickable';
            throw new ExpectationException($msg, $this->getSession());
        }
    }

    /**
     * @When /^"(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" in criteria group "(?P<group_idx>[^"][0-9]*)" in "(?P<scale_value_name>(?:[^"]|\\")*)" scalevalue (should|should not) be visible$/
     * @param string $element Element we look for
     * @param string $selector_type The type of what we look for
     * @param string $group_idx
     * @param string $scale_value_name
     * @param string $not
     */
    public function element_in_nth_criteria_group_in_scalevalue_should_be_visible(
        string $element, string $selector_type, string $group_idx, string $scale_value_name, string $not
    ) {
        \behat_hooks::set_step_readonly(true);

        /** @var NodeElement $group_node */
        $group_node = $this->find_nth_criteria_group($scale_value_name, $group_idx);
        /** @var NodeElement $element_node */
        $element_node = $this->find_element_in_node($group_node, $element, $selector_type);

        $expected = ($not === 'should');
        $visible = $element_node->isVisible();

        if ($expected != $visible) {
            $msg = '"' . $element . '" "' . $selector_type . '" in ' .
                $this->get_nth_criteria_group_description($group_idx, $scale_value_name) .
                ' is ' . ($visible ? '' : 'not ') . 'visible';
            throw new ExpectationException($msg, $this->getSession());
        }
    }

    /**
     * @When /^"(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" in "(?P<criterion_type>(?:[^"]|\\")*)" criterion "(?P<criterion_idx>[^"][0-9]*)" in criteria group "(?P<group_idx>[^"][0-9]*)" in "(?P<scale_value_name>(?:[^"]|\\")*)" scalevalue (should|should not) be visible$/
     * @param string $element Element we look for
     * @param string $selector_type The type of what we look for
     * @param string $criterion_type
     * @param string $criterion_idx
     * @param string $group_idx
     * @param string $scale_value_name
     * @param string $not
     */
    public function element_in_nth_criterion_in_nth_criteria_group_in_scalevalue_should_be_visible(
        string $element, string $selector_type, string $criterion_type, string $criterion_idx,
        string $group_idx, string $scale_value_name, string $not
    ) {
        \behat_hooks::set_step_readonly(true);

        /** @var NodeElement $criterion_node */
        $criterion_node = $this->find_nth_criterion_in_nth_criteria_group($criterion_type, $criterion_idx,
            $group_idx, $scale_value_name
        );
        /** @var NodeElement $element_node */
        $element_node = $this->find_element_in_node($criterion_node, $element, $selector_type);

        $expected = ($not === 'should');
        $visible = $element_node->isVisible();

        if ($expected != $visible) {
            $msg = '"' . $element . '" "' . $selector_type . '" in ' .
                $this->get_criterion_description($criterion_type, $criterion_idx, $group_idx, $scale_value_name) .
                ' is ' . ($visible ? '' : 'not ') . 'visible';
            throw new ExpectationException($msg, $this->getSession());
        }
    }

    /**
     * @When /^I (should|should not) see "(?P<text_string>(?:[^"]|\\")*)" in "(?P<criterion_type>(?:[^"]|\\")*)" criterion "(?P<criterion_idx>[^"][0-9]*)" in criteria group "(?P<group_idx>[^"][0-9]*)" in "(?P<scale_value_name>(?:[^"]|\\")*)" scalevalue$/
     * @param string $not
     * @param string $text_string
     * @param string $criterion_type
     * @param string $criterion_idx
     * @param string $group_idx
     * @param string $scale_value_name
     */
    public function i_should_see_in_nth_criterion_in_nth_criteria_group_in_scalevalue(
        string $not, string $text_string, string $criterion_type, string $criterion_idx,
        string $group_idx, string $scale_value_name
    ) {
        \behat_hooks::set_step_readonly(true);

        $expected = ($not === 'should');
        $msg = $this->get_criterion_description($criterion_type, $criterion_idx, $group_idx, $scale_value_name);

        try {
            /** @var NodeElement $criterion_node */
            $criterion_node = $this->find_nth_criterion_in_nth_criteria_group($criterion_type, $criterion_idx,
                $group_idx, $scale_value_name
            );
        } catch (Exception $e) {
            throw new ExpectationException(
                $msg . ' could not be found',
                $this->getSession()
            );
        }

        $text_node = null;
        $fnd = true;
        try {
            $xpath = ".//span[contains(., '$text_string')]";
            $text_node = $criterion_node->find('xpath', $xpath);
        } catch (Exception $e) {
            $fnd = false;
        }

        if ($text_node === null) {
            $fnd = false;
        }

        if ($fnd) {
            try {
                $this->ensure_node_is_visible($text_node);
            } catch (Exception $e) {
                $fnd = false;
            }
        }

        if ($fnd !== $expected) {
            $msg = '"' . $text_string . '"' . ($fnd ? '' : ' not') . ' found in ' . $msg . ($fnd ? ' when it should not be there' : '');
            throw new ExpectationException($msg, $this->getSession());
        }
    }

    /**
     * @When /^I (should|should not) see error indicator for "(?P<criterion_type>(?:[^"]|\\")*)" criterion "(?P<criterion_idx>[^"][0-9]*)" in criteria group "(?P<group_idx>[^"][0-9]*)" in "(?P<scale_value_name>(?:[^"]|\\")*)" scalevalue$/
     * @param string $not
     * @param string $criterion_type
     * @param string $criterion_idx
     * @param string $group_idx
     * @param string $scale_value_name
     */
    public function i_should_see_error_indicator_for_nth_criterion_in_nth_criteria_group_in_scalevalue(
        string $not, string $criterion_type, string $criterion_idx,
        string $group_idx, string $scale_value_name
    ) {
        \behat_hooks::set_step_readonly(true);

        $expected = ($not === 'should');
        $msg = $this->get_criterion_description($criterion_type, $criterion_idx, $group_idx, $scale_value_name);

        try {
            /** @var NodeElement $criterion_node */
            $criterion_node = $this->find_nth_criterion_in_nth_criteria_group($criterion_type, $criterion_idx,
                $group_idx, $scale_value_name
            );
        } catch (Exception $e) {
            throw new ExpectationException(
                $msg . ' could not be found',
                $this->getSession()
            );
        }

        $warning_node = null;
        $fnd = true;
        try {
            $xpath = './/div[@class="criterion_title"]//span[@data-flex-icon="notification-warning"]';
            $warning_node = $criterion_node->find('xpath', $xpath);
        } catch (Exception $e) {
            $fnd = false;
        }

        if ($fnd) {
            try {
                $this->ensure_node_is_visible($warning_node);
            } catch (Exception $e) {
                $fnd = false;
            }
        }

        if ($fnd !== $expected) {
            $msg = 'An error indication is' . ($fnd ? '' : ' not') . ' found for ' . $msg . ($fnd ? ' when it should not be there' : '');
            throw new ExpectationException($msg, $this->getSession());
        }
    }

    /**
     * @When /^I (should|should not) see "(?P<error_text>(?:[^"]|\\")*)" error for "(?P<item>(?:[^"]|\\")*)" item in "(?P<criterion_type>(?:[^"]|\\")*)" criterion "(?P<criterion_idx>[^"][0-9]*)" in criteria group "(?P<group_idx>[^"][0-9]*)" in "(?P<scale_value_name>(?:[^"]|\\")*)" scalevalue$/
     * @param string $not
     * @param string $error_text
     * @param string $item
     * @param string $criterion_type
     * @param string $criterion_idx
     * @param string $group_idx
     * @param string $scale_value_name
     */
    public function i_should_see_error_for_item_in_nth_criterion_in_nth_criteria_group_in_scalevalue(
        string $not, string $error_text, string $item, string $criterion_type, string $criterion_idx,
        string $group_idx, string $scale_value_name
    ) {
        \behat_hooks::set_step_readonly(true);

        $expected = ($not === 'should');
        $msg = 'Item "' . $item . '" in ' . $this->get_criterion_description($criterion_type, $criterion_idx, $group_idx, $scale_value_name);

        /** @var NodeElement $item_node */
        $item_node = null;
        try {
            $item_node = $this->find_item_in_criterion($item, $criterion_type, $criterion_idx,
                $group_idx, $scale_value_name
            );
        } catch (Exception $e) {
            throw new ExpectationException(
                $msg . ' could not be found',
                $this->getSession()
            );
        }

        $warning_node = null;
        $fnd = true;
        try {
            $xpath = './/span[@class="tw-editAchievementPaths__criterionForm-warning"]//span[contains(., "' . $error_text . '")]';
            $warning_node = $item_node->find('xpath', $xpath);
        } catch (Exception $e) {
            $fnd = false;
        }

        if ($fnd) {
            try {
                $this->ensure_node_is_visible($warning_node);
            } catch (Exception $e) {
                $fnd = false;
            }
        }

        if ($fnd !== $expected) {
            $msg = 'Error "' . $error_text . '"' . ($fnd ? '' : ' not') . ' found for ' . $msg . ($fnd ? ' when it should not be there' : '');
            throw new ExpectationException($msg, $this->getSession());
        }
    }

    /**
     * @When /^I remove "(?P<item>(?:[^"]|\\")*)" item in "(?P<criterion_type>(?:[^"]|\\")*)" criterion "(?P<criterion_idx>[^"][0-9]*)" in criteria group "(?P<group_idx>[^"][0-9]*)" in "(?P<scale_value_name>(?:[^"]|\\")*)" scalevalue$/
     * @param string $item
     * @param string $criterion_type
     * @param string $criterion_idx
     * @param string $group_idx
     * @param string $scale_value_name
     */
    public function i_remove_item_in_nth_criterion_in_nth_criteria_group_in_scalevalue(
        string $item, string $criterion_type, string $criterion_idx, string $group_idx, string $scale_value_name
    ) {
        \behat_hooks::set_step_readonly(true);

        /** @var NodeElement $item_node */
        $item_node = $this->find_item_in_criterion($item, $criterion_type, $criterion_idx, $group_idx, $scale_value_name);
        $locator = '[data-tw-' . $this->get_criterion_item_type($criterion_type) . '-item-remove]';

        try {
            $remove_button = $item_node->find('css', $locator);
            $remove_button->click();
        } catch (Exception $e) {
            $msg = 'Item "' . $item . '" in ' .
                $this->get_criterion_description($criterion_type, $criterion_idx, $group_idx, $scale_value_name) .
                ' could not be found';
            throw new ExpectationException($msg, $this->getSession());
        }
    }

    /**
     * @When /^I toggle criterion detail of "(?P<criterion_type>(?:[^"]|\\")*)" criterion "(?P<criterion_idx>[^"][0-9]*)" in criteria group "(?P<group_idx>[^"][0-9]*)" in "(?P<scale_value_name>(?:[^"]|\\")*)" scalevalue$/
     * @param string $criterion_type
     * @param string $criterion_idx
     * @param string $group_idx
     * @param string $scale_value_name
     */
    public function i_toggle_criterion_detail(string $criterion_type, string $criterion_idx,
        string $group_idx, string $scale_value_name
    ) {
        \behat_hooks::set_step_readonly(false);

        /** @var NodeElement $criterion_node */
        $criterion_node = $this->find_nth_criterion_in_nth_criteria_group($criterion_type, $criterion_idx,
            $group_idx, $scale_value_name
        );

        try {
            $toggle = $this->find('css', 'button[aria-expanded] span', false, $criterion_node);
            $toggle->click();
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'element not interactable: element has zero size') !== false) {
                $toggle->findById('criterion_title--coursecompletion_1')->click();
            } else {
                $msg = $criterion_type . '" criterion " has no detail';
                throw new ExpectationException($msg, $this->getSession());
            }
        }
    }

    /**
     * @Given /^I should see "(?P<group_count>[^"][0-9]*)" criteria groups in "(?P<scale_value_name>(?:[^"]|\\")*)" scalevalue$/
     * @param string $group_count
     * @param string $scale_value_name
     * @throws Exception
     */
    public function i_should_see_count_criteria_groups_in_scalevalue(string $group_count, string $scale_value_name) {
        \behat_hooks::set_step_readonly(true);

        $expected = (int)$group_count;

        /** @var NodeElement $scalevalue_node */
        $scale_value_node = $this->find_scale_value_node($scale_value_name);

        try {
            /** @var NodeElement[] $criteria_group_nodes */
            $criteria_group_nodes = $this->find_all('css', "[data-tw-editachievementpaths-pathway-key]",
                false, $scale_value_node
            );
        } catch (Exception $e) {
            if ($expected != 0) {
                $msg = "Found 0 criteria groups in \"$scale_value_name\" when expecting $expected";
                throw new ExpectationException($msg, $this->getSession());
            }
            return;
        }

        $actual = count($criteria_group_nodes);
        if ($actual != $expected) {
            $msg = "Found $actual criteria groups in \"$scale_value_name\" when expecting $expected";
            throw new ExpectationException($msg, $this->getSession());
        }
    }

    /**
     * @When /^criterion aggregation should be set to complete "(?P<aggregation>(all|[^"][0-9]*))" in "(?P<criterion_type>(?:[^"]|\\")*)" criterion "(?P<criterion_idx>[^"][0-9]*)" in criteria group "(?P<group_idx>[^"][0-9]*)" in "(?P<scale_value_name>(?:[^"]|\\")*)" scalevalue$/
     * @param string $aggregation
     * @param string $criterion_type
     * @param string $criterion_idx
     * @param string $group_idx
     * @param string $scale_value_name
     */
    public function criterion_aggregation_is_set_to(string $aggregation, string $criterion_type, string $criterion_idx,
        string $group_idx, string $scale_value_name
    ) {
        \behat_hooks::set_step_readonly(false);

        $aggregation_nodes = $this->get_criterion_aggregation_nodes($criterion_type, $criterion_idx,
            $group_idx, $scale_value_name
        );

        /** @var NodeElement $node */
        $node = null;

        if ($aggregation === 'all') {
            $node = $aggregation_nodes['method_all'];
            if (!$node->isSelected()) {
                $msg = 'Aggregation method of  ' .
                    $this->get_criterion_description($criterion_type, $criterion_idx, $group_idx, $scale_value_name) .
                    ' is set to "any ' . $node->getValue() . '" when expecting "all"';
                throw new ExpectationException($msg, $this->getSession());
            }
        } else {
            $node = $aggregation_nodes['method_any'];
            if (!$node->isSelected()) {
                $msg = 'Aggregation method of  ' .
                    $this->get_criterion_description($criterion_type, $criterion_idx, $group_idx, $scale_value_name) .
                    ' is set to "all" when expecting "any ' . $aggregation_nodes['req_items']->getValue() . '"';
                throw new ExpectationException($msg, $this->getSession());
            }

            $node = $aggregation_nodes['req_items'];
            if ($node->getValue() != $aggregation) {
                $msg = 'Number or required items of ' .
                    $this->get_criterion_description($criterion_type, $criterion_idx, $group_idx, $scale_value_name) .
                    ' is set to "' . $node->getValue() . '" when exepecting "' . $aggregation . '""';
                throw new ExpectationException($msg, $this->getSession());
            }
        }
    }

    /**
     * @When /^I set criterion aggregation to complete "(?P<aggregation>(all|[^"][0-9]*))" in "(?P<criterion_type>(?:[^"]|\\")*)" criterion "(?P<criterion_idx>[^"][0-9]*)" in criteria group "(?P<group_idx>[^"][0-9]*)" in "(?P<scale_value_name>(?:[^"]|\\")*)" scalevalue$/
     * @param string $aggregation
     * @param string $criterion_type
     * @param string $criterion_idx
     * @param string $group_idx
     * @param string $scale_value_name
     */
    public function i_set_criterion_aggregation(string $aggregation, string $criterion_type, string $criterion_idx,
        string $group_idx, string $scale_value_name
    ) {
        \behat_hooks::set_step_readonly(false);

        /** @var NodeElement[] $criterion_nodes */
        $aggregation_nodes = $this->get_criterion_aggregation_nodes($criterion_type, $criterion_idx,
            $group_idx, $scale_value_name
        );

        /** @var NodeElement $node */
        $method_node = $aggregation === 'all' ? $aggregation_nodes['method_all'] : $aggregation_nodes['method_any'];

        try {
            $method_node->click();
            if ($aggregation !== 'all') {
                $aggregation_nodes['req_items']->setValue($aggregation);
            }
        } catch (Exception $e) {
            $msg = 'Could not set aggregation detail for ' .
                $this->get_criterion_description($criterion_type, $criterion_idx, $group_idx, $scale_value_name);
            throw new ExpectationException($msg, $this->getSession());
        }
    }

    /**
     * @When /^"(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" in "(?P<scale_value_name>(?:[^"]|\\")*)" scalevalue (should|should not) be visible$/
     * @param string $element Element we look for
     * @param string $selector_type The type of what we look for
     * @param string $scale_value_name
     * @param string $not
     */
    public function element_in_scalevalue_should_be_visible(
        string $element, string $selector_type, string $scale_value_name, string $not
    ) {
        \behat_hooks::set_step_readonly(true);

        /** @var NodeElement $scale_value_node */
        $scale_value_node = $this->find_scale_value_node($scale_value_name);
        $element_node = $this->find_element_in_node($scale_value_node, $element, $selector_type);

        $expected = ($not === 'should');
        $visible = $element_node->isVisible();

        if ($expected != $visible) {
            $msg = '"' . $element . '" "' . $selector_type . '" in scale value' . $scale_value_name .
                ' is ' . ($visible ? '' : 'not ') . 'visible';
            throw new ExpectationException($msg, $this->getSession());
        }
    }

    /**
     * @Given /^I should see "(?P<search_text>(?:[^"]|\\")*)" "(courses|other competencies|child competencies)" completed towards achieving "(?P<scale_value>(?:[^"]|\\")*)" in the competency profile$/
     * @param string $search_text
     * @param string $achievement_type
     * @param string $scale_value
     * @throws Exception
     */
    public function i_should_see_in_the_profile_achievement(string $search_text, string $achievement_type, string $scale_value) {
        \behat_hooks::set_step_readonly(true);

        $goal_class = 'tui-criteria';
        $goal_title = '';
        switch ($achievement_type) {
            case 'courses':
                $goal_class .= 'Course';
                $goal_title = 'Complete courses';
                break;
            case 'other competencies':
            case 'child competencies':
                $goal_class .= 'Competency';
                $goal_title = 'Achieve proficiency in ' . $achievement_type;
                break;
        }
        $goal_class .= 'Achievement__goal';

        $xpath = '//div[@class="tui-competencyAchievementsScale" ' .
            'and .//span[@class="tui-competencyAchievementsScale__title" and contains(., "' . $scale_value . '")]]' .
            '//div[@class="' . $goal_class . '" and contains(., "' . $goal_title . '") and ' .
            '//span[@class="tui-progressCircle__circle-text" and contains(., "' . $search_text . '")]]';

        try {
            $this->find('xpath', $xpath);
        } catch (Exception $e) {
            throw new ExpectationException(
                "Could not find progress indicating that \"${search_text}\" {$achievement_type} were completed towards achieving \"${scale_value}\"",
                $this->getSession()
            );
        }
    }

    /**
     * Find the node for the specified scalevalue
     *
     * @param string $scale_value_name
     * @return NodeElement
     */
    private function find_scale_value_node(string $scale_value_name): NodeElement {
        $xpath = '//h4[@class="tw-editScaleValuePaths__scaleHeader-title"' .
            ' and contains(.,"' . $scale_value_name . '")]' .
            '/ancestor::div[@class="tw-editScaleValuePaths__scales-scale"]';
        $scalevalue_node = $this->find('xpath', $xpath);

        if ($scalevalue_node === null) {
            throw new ExpectationException(
                'Scale value "' . $scale_value_name . '" could not be found',
                $this->getSession()
            );
        }

        return $scalevalue_node;
    }

    /**
     * Find nth criteria group with the scale value
     *
     * @param string $scale_value_name
     * @param string $group_idx
     * @return NodeElement
     */
    private function find_nth_criteria_group(string $scale_value_name, string $group_idx): NodeElement {
        $nth = (int)$group_idx;
        if ($nth <= 0) {
            throw new ExpectationException(
                'Invalid criteria group index "' . $group_idx . '"',
                $this->getSession()
            );
        }

        /** @var NodeElement $scalevalue_node */
        $scale_value_node = $this->find_scale_value_node($scale_value_name);

        /** @var NodeElement[] $criteria_group_nodes */
        $criteria_group_nodes = $this->find_all('css', "[data-tw-editachievementpaths-pathway-key]",
            false, $scale_value_node
        );
        if (count($criteria_group_nodes) < $nth) {
            throw new ExpectationException(
                'criteria group "' . $group_idx . '" could not be found in scalevalue "' . $scale_value_name . '"',
                $this->getSession()
            );
        }

        return $criteria_group_nodes[$group_idx - 1];
    }

    /**
     * @param string $criterion_type
     * @param string $criterion_idx
     * @param string $group_idx
     * @param string $scale_value_name
     * @return NodeElement
     */
    private function find_nth_criterion_in_nth_criteria_group(string $criterion_type, string $criterion_idx,
        string $group_idx, string $scale_value_name
    ): NodeElement {
        $criteria_group_node = $this->find_nth_criteria_group($scale_value_name, $group_idx);

        $display_name = $this->get_criterion_display_name($criterion_type);
        $xpath = "//*[@data-tw-editscalevaluepaths-criterion-key and contains(., '$display_name')]";
        $criteria_nodes = $this->find_all('xpath', $xpath, false, $criteria_group_node);

        $nth = (int)$criterion_idx;
        if ($nth <= 0) {
            throw new ExpectationException(
                'Invalid criterion index "' . $criterion_idx . '"',
                $this->getSession()
            );
        }

        if (count($criteria_nodes) < $nth) {
            $msg = '"' . $criterion_type . '" "' . $criterion_idx . '" could not be found in criteria_group "' .
                $group_idx . '" in scalevalue "' . $scale_value_name . '"';
            throw new ExpectationException($msg, $this->getSession());
        }

        return $criteria_nodes[$nth - 1];
    }

    /**
     * @param string $item
     * @param string $pathway_type
     * @param string|null $pathway_idx
     * @param string|null $position
     * @throws ExpectationException
     */
    private function find_item_in_criterion(string $item, string $criterion_type, string $criterion_idx,
        string $group_idx, string $scale_value_name
    ) {
        /** @var NodeElement $criterion_node */
        $criterion_node = $this->find_nth_criterion_in_nth_criteria_group($criterion_type, $criterion_idx,
            $group_idx, $scale_value_name
        );

        $item_type = $this->get_criterion_item_type($criterion_type);
        $xpath = './/li[@data-tw-' . $item_type . '-item-value and contains(./span, "' . $item . '")]';
        return $this->find('xpath', $xpath);
    }


    /**
     * Return the Add criterion type button
     * @param NodeElement $ancestor_node
     * @return NodeElement
     */
    private function find_add_criteria_button(NodeElement $ancestor_node): NodeElement {
        return $ancestor_node->find('css', '.tw-editScaleValuePaths__addButton');
    }


    /**
     * Return the Option selector for the specific criterion type in the ancestor
     * @param NodeElement $ancestor_node
     * @param string $criterion_type
     * @return NodeElement
     */
    private function find_criterion_type_option(NodeElement $ancestor_node, string $criterion_type): NodeElement {
        return $ancestor_node->find('css', "[data-tw-editscalevaluepaths-dropdown-item-type='$criterion_type']");
    }

    /**
     * Open the criteria type list and click the applicable type button
     * @param NodeElement $ancestor_node
     * @param string $criterion_type
     * @throws ExpectationException
     */
    private function select_criterion_type(NodeElement $ancestor_node, string $criterion_type) {
        /** @var NodeElement $add_button */
        $add_button = null;

        try {
            $add_button = $this->find_add_criteria_button($ancestor_node);
        } catch (Exception $e) {
            throw new ExpectationException(
                'Add criterion type button could not be found',
                $this->getSession()
            );
        }

        // Open the dropdown
        $add_button->click();

        /** @var NodeElement $criterion_type_button */
        $criterion_type_button = null;

        try {
            $criterion_type_button = $this->find_criterion_type_option($ancestor_node, $criterion_type);
        } catch (Exception $e) {
            throw new ExpectationException(
                'Criterion type option "' . $criterion_type . '" could not be found',
                $this->getSession()
            );
        }

        $criterion_type_button->click();
    }

    /**
     * @param string $criterion_type
     * @param string $criterion_idx
     * @param string $group_idx
     * @param string $scale_value_name
     * @return NodeElement[] Aggregation nodes
     */
    private function get_criterion_aggregation_nodes(string $criterion_type, string $criterion_idx,
        string $group_idx, string $scale_value_name
    ): array {
        $results = [];

        /** @var NodeElement $criterion_node */
        $criterion_node = $this->find_nth_criterion_in_nth_criteria_group($criterion_type, $criterion_idx,
            $group_idx, $scale_value_name
        );
        $method_all_locator = './/div[@data-tw-criterion' . $criterion_type . '-aggregationmethod="' .
            criterion::AGGREGATE_ALL . '"]//input[@type="radio"]';
        $method_any_locator = './/div[@data-tw-criterion' . $criterion_type . '-aggregationmethod="' .
            criterion::AGGREGATE_ANY_N . '"]//input[@type="radio"]';
        $req_items_locator = './/div[@data-tw-criterion' . $criterion_type . '-aggregationcount]/input[@type="number"]';

        try {
            $results['method_all'] = $this->find('xpath', $method_all_locator, false, $criterion_node);
            $results['method_any'] = $this->find('xpath', $method_any_locator, false, $criterion_node);
            $results['req_items'] = $this->find('xpath', $req_items_locator, false, $criterion_node);
        } catch (Exception $e) {
            $msg = 'Could not find aggregation nodes for ' .
                $this->get_criterion_description($criterion_type, $criterion_idx, $group_idx, $scale_value_name);
            throw new ExpectationException($msg, $this->getSession());
        }

        return $results;
    }

    /**
     * @param string $group_idx
     * @param string $scale_value_name
     * @return string
     */
    private function get_nth_criteria_group_description(string $group_idx, string $scale_value_name): string {
        return 'criteria_group "' . $group_idx . '" in "' . $scale_value_name . '" scalevalue';
    }

    /**
     * @param string $pathway_type
     * @param string|null $pathway_idx
     * @param string|null $position
     * @return string
     */
    private function get_pathway_description(string $pathway_type, ?string $pathway_idx = null, ?string $position = null): string {
        return '"' . $pathway_type . '" pathway' .
            ($pathway_idx === null ? '' : ' "' . $pathway_idx . '"') .
            ($position === null ? '' : ' "' . $position . '" criteria groups');
    }

    /**
     * Return the pathway display name for the pathway_type
     *
     * @param string $pathway_type
     * @return string Pathway name displayed in UI
     */
    private function get_pathway_display_name(string $pathway_type): string {
        if (!isset($this->pathway_display_names[$pathway_type])) {
            $pw = pathway_factory::create($pathway_type);
            $this->pathway_display_names[$pathway_type] = $pw->get_title();
        }

        return $this->pathway_display_names[$pathway_type];
    }

    /**
     * @param string $criterion_type
     * @param string $criterion_idx
     * @param string $group_idx
     * @param string $scale_value_name
     * @return string
     */
    private function get_criterion_description(string $criterion_type, string $criterion_idx,
        string $group_idx, string $scale_value_name
    ): string {
        return '"' . $criterion_type . '" criterion "' . $criterion_idx .
            '" in ' . $this->get_nth_criteria_group_description($group_idx, $scale_value_name);
    }

    /**
     * Return the criterion display name for the criterion type
     *
     * @param string $criterion_type
     * @return string Criterion name displayed in UI
     */
    private function get_criterion_display_name(string $criterion_type): string {
        if (!isset($this->criterion_display_names[$criterion_type])) {
            $criterion = criterion_factory::create($criterion_type);
            $this->criteria_display_names[$criterion_type] = $criterion->get_title();
        }

        return $this->criteria_display_names[$criterion_type];
    }

    /**
     * Return the criterion item type for the criterion type
     *
     * @param string $criterion_type
     * @return string item_type of items in this criterion
     */
    private function get_criterion_item_type(string $criterion_type): string {
        if (!isset($this->criterion_item_types[$criterion_type])) {
            $criterion = criterion_factory::create($criterion_type);
            $this->criteria_item_types[$criterion_type] = $criterion->get_items_type();
        }

        return $this->criteria_item_types[$criterion_type];
    }

    /**
     * @Given /^I should see the following manual achievements:$/
     * @param TableNode $table
     * @throws Exception
     */
    public function i_should_see_the_following_manual_achievements(TableNode $table) {
        \behat_hooks::set_step_readonly(true);

        $expected = $this->parse_table(
            $table,
            ['role', 'rating'],
            ['rater', 'date', 'actions']
        );
        $expected = array_map(function ($row) {
            $row['role'] = strtolower($row['role']) === 'self' ? 'Your rating' : $row['role'];
            return $row;
        }, $expected);

        $columns = array_keys($expected[0]);
        $actual = [];
        /** @var NodeElement[] $manual_achievements */
        $manual_achievements = $this->find_all('css', self::COMPETENCY_PROFILE_MANUAL_ACHIEVEMENT_LOCATOR);
        foreach ($manual_achievements as $rater_row) {
            $element = [];

            foreach ($columns as $column) {
                switch ($column) {
                    case 'role':
                        /** @var NodeElement $node */
                        $node = $this->find('css', self::COMPETENCY_PROFILE_MANUAL_ACHIEVEMENT_ROLE_LOCATOR, false, $rater_row);
                        if ($node !== null) {
                            $element['role'] = $node->getText();
                        }
                        break;

                    case 'rating':
                    case 'date':
                    case 'rater':
                        $xpath = '//div[contains(@class, "tui-dataTableCell") and ' .
                            './/span[@class="tui-dataTableCell__label" and contains(., "' . ucfirst($column) . '")]]';

                        try {
                            /** @var NodeElement $node */
                            $node = $this->find('xpath', $xpath, false, $rater_row);
                            /** @var NodeElement $element_node */
                            $element_node = $this->find('css',
                                self::COMPETENCY_PROFILE_MANUAL_ACHIEVEMENT_CONTENT_LOCATOR,
                                false,
                                $node
                            );
                            if ($element_node !== null) {
                                $element[$column] = $element_node->getText();
                            }
                        } catch (Exception $e) {
                            if ($column === 'rating') {
                                // When there is no rating, there is no column heading
                                /** @var NodeElement[] $nodes */
                                $nodes = $this->find_all('css',
                                    self::COMPETENCY_PROFILE_MANUAL_ACHIEVEMENT_CONTENT_LOCATOR,
                                    false,
                                    $rater_row
                                );
                                if (!empty($nodes)) {
                                    $element[$column] = $nodes[0]->getText();
                                }
                            } else {
                                $element[$column] = '';
                            }
                        }
                        break;

                    case 'actions':
                        // No heading and always the last column
                        /** @var NodeElement[] $nodes */
                        $nodes = $this->find_all('css',
                            self::COMPETENCY_PROFILE_MANUAL_ACHIEVEMENT_CONTENT_LOCATOR,
                            false,
                            $rater_row
                        );
                        if (!empty($nodes)) {
                            $element['actions'] = end($nodes)->getText();
                        }
                        break;
                }
            }

            $actual[] = $element;
        }

        $diff = array_udiff_assoc($expected, $actual, function ($a, $b): int {
            return (int)!empty(array_diff_assoc($a, $b));
        });

        if (!empty($diff)) {
            throw new ExpectationException("Could not find all expected manual achievements", $this->getSession());
        }
    }

    /**
     * @Given /^I click on "([^"]*)" "([^"]*)" in the manual achievement of "([^"]*)"$/
     * @Given /^I click on "([^"]*)" "([^"]*)" in the manual achievement of "([^"]*)" "([^"]*)"$/
     * @param string $element Element we look for
     * @param string $selector_type The type of what we look for
     * @param string $role The role of the rater
     * @param string|null $rater The rater
     * @throws Exception
     */
    public function i_click_on_in_the_manual_achievement_of(
        string $element,
        string $selector_type,
        string $role,
        ?string $rater = null
    ) {
        \behat_hooks::set_step_readonly(false);

        $role = (strtolower($role) === 'self') ? 'Your rating' : $role;
        $xpath = "//div[contains(concat(' ', @class, ' '), ' tui-pathwayManualAchievementRater ') and " .
            ".//div[contains(@class, 'tui-pathwayManualAchievementRater__overview-role') and contains(., '{$role}')]]" .
            "//div[contains(concat(' ', @class, ' '), ' tui-dataTableRow ')";
        if ($rater !== null) {
            $xpath .= " and .//div[contains(concat(' ', @class, ' '), ' tui-dataTableCell ') " .
                "and ./span[@class='tui-dataTableCell__label' and contains(., 'Rater')] " .
                "and ./div[@class = 'tui-dataTableCell__content' and contains(., '{$rater}')]]";
        }
        $xpath .= ']';

        /** @var NodeElement $row */
        $row = $this->find('xpath', $xpath);

        list($selector, $locator) = $this->transform_selector($selector_type, $element);
        /** @var NodeElement $element_node */
        $element_node = $this->find($selector, $locator, false, $row);
        try {
            $this->ensure_node_is_visible($element_node);
            $element_node->click();
        } catch (Exception $e) {
            $msg = '"' . $element . '" "' . $selector_type . '" in the manual achievement of "' . $role . '"' .
                ($rater !== null ? "\"{$rater}\"" : '') . ' is not clickable';
            throw new ExpectationException($msg, $this->getSession());
        }
    }

    /**
     * @return behat_totara_tui|behat_base
     */
    private function get_behat_tui(): behat_totara_tui {
        return behat_context_helper::get('behat_totara_tui');
    }

}
