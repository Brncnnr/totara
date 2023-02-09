@mod @mod_contentmarketplace @totara @javascript  @totara_contentmarketplace
Feature: General behaviour with mod contentmarketplace

  Background:
    Given the following "courses" exist:
      | fullname   | shortname | format |
      | Course 101 | c101      | topics |
    And the following "content marketplace" exist in "mod_contentmarketplace" plugin:
      | name       | course | marketplace_component       |
      | Learning 1 | c101   | contentmarketplace_linkedin |

  Scenario: View the content marketplace within and without listing
    Given I am on a totara site
    And I log in as "admin"
    When I am on "Course 101" course homepage
    Then I should see "Learning 1"
    When I am on content marketplace index page of course "c101"
    Then I should see "Learning 1"

  Scenario: View the content marketplace from multi activities course
    Given I am on a totara site
    And I log in as "admin"
    When I am on "Course 101" course homepage
    Then I should see "Learning 1"
    When I follow "Learning 1"
    Then I should see "Course 101"

  Scenario: Launch new course created from learning object
    Given I am on a totara site
    And I log in as "admin"
    And I set up the "linkedin" content marketplace plugin
    And the following "learning objects" exist in "contentmarketplace_linkedin" plugin:
      | urn          | title     |
      | urn:course:1 | Spring    |
    And the following "categories" exist:
      | name       | category | idnumber |
      | Category A | 0        | A        |
    And I navigate to the catalog import page for the "linkedin" content marketplace
    And I should see "Spring"
    And I toggle the selection of row "1" of the tui select table
    And I set the field "Select category" to "Category A"
    And I click on "Next: Review" "button"
    And I click on "Create course(s)" "button"
    And I am on "Spring" course homepage
    And I click on "Administration" "button"
    And I press "Course administration"
    And I press "Users"
    And I click on "Enrolment methods" "link"
    And I click on "Enable" "link" in the "Self enrolment (Learner)" "table_row"
    When I am on "Spring" course homepage
    And I click on "Enrol to course Spring" "button"
    Then I should see "You've been enrolled successfully"
    And the "Launch (opens in new window)" "button" should be enabled
    And I click on "Launch (opens in new window)" "button"
    When I switch to "linkedIn_course_window" window
    Then I should see "Learning object launched from activity page"

  Scenario: Use the admin settings popover
    Given I am on a totara site
    And I log in as "admin"
    When I am on "Course 101" course homepage
    And I click on "Learning 1" "link"
    Then I should see "Course 101"
    When I click on "Administration" "button"
    Then I should see "Content marketplace activity administration"
    And I should not see "Turn editing on"
    When I click on "Course administration" "button"
    And I click on "Turn editing on" "link"
    Then I should see "Course 101"
    When I click on "Administration" "button"
    And I click on "Course administration" "button"
    And I click on "Turn editing off" "link"
    Then I should see "Course 101"