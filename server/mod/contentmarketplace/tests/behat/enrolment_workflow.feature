@mod @mod_contentmarketplace @totara @javascript @contentmarketplace_linkedin @totara_contentmarketplace
Feature: General enrolment workflow mod contentmarketplace

  Background:
    Given I set up the "linkedin" content marketplace plugin
    And the following "learning objects" exist in "contentmarketplace_linkedin" plugin:
      | urn          | title     |
      | urn:course:1 | Spring |
    And the following "categories" exist:
      | name       | category | idnumber |
      | Category A | 0        | A        |
    And the following "users" exist:
      | username   | firstname | lastname | email             |
      | user_one   | User      | One      | one@example.com   |
      | user_two   | User      | Two      | two@example.com   |

    And I log in as "admin"
    And I navigate to the catalog import page for the "linkedin" content marketplace
    And I should see "Spring"
    And I toggle the selection of row "1" of the tui select table
    And I set the field "Select category" to "Category A"
    And I click on "Next: Review" "button"
    And I click on "Create course(s)" "button"
    And I log out

  Scenario: Enrolment workflow for an admin
    Given I am on a totara site
    And I log in as "admin"
    And I am on "Spring" course homepage
    And I click on "Administration" "button"
    And I press "Course administration"
    And I press "Users"
    And I click on "Enrolment methods" "link"
    And I click on "Enable" "link" in the "Self enrolment (Learner)" "table_row"
    When I am on "Spring" course homepage
    Then I should see "You’re accessing this course as an administrator. You must enrol in the course for your learning to be recorded."
    And I should see "Enrol"
    And the "Launch (opens in new window)" "button" should be disabled
    When I click on "Enrol to course Spring" "button"
    Then I should see "You've been enrolled successfully"
    And the "Launch (opens in new window)" "button" should be enabled
    And I should not see "You’re accessing this course as an administrator. You must enrol in the course for your learning to be recorded."

  Scenario: Check enrol button should be configurable by self completion setting
    Given I am on a totara site
    And I log in as "admin"
    When I am on "Spring" course homepage
    Then I should not see "Enrol"
    And I should see "You’re accessing this course as an administrator."
    And I click on "Administration" "button"
    And I press "Course administration"
    And I press "Users"
    And I click on "Enrolment methods" "link"
    And I click on "Enable" "link" in the "Guest access" "table_row"
    And I log out

    And I log in as "user_one"
    When I am on "Spring" course homepage
    Then I should not see "Enrol"
    And I should see "You are viewing as a ‘Guest’. Your progress will not be recorded."

  Scenario: Enrolment workflow for a site guest
    Given I am on a totara site
    And I log in as "admin"
    And I set the following administration settings values:
      | Guest login button | Show |
    Then I log out

    And I log in as "guest"
    When I am on "Spring" course homepage
    Then I should see "Guests cannot access this course."
    And I log out

    # Disabled guest access
    And I log in as "admin"
    And I am on "Spring" course homepage
    And I click on "Administration" "button"
    And I press "Course administration"
    And I press "Users"
    And I click on "Enrolment methods" "link"
    And I click on "Edit" "link" in the "Guest access" "table_row"
    And I set the following fields to these values:
      | Allow guest access | Yes |
    And I press "Save changes"
    And I log out

    And I log in as "guest"
    When I am on "Spring" course homepage
    Then I should not see "Enrol"
    And I should see "You are viewing as a ‘Guest’. Your progress will not be recorded."

  Scenario: Enrolment workflow for a system user
    Given I am on a totara site
    And I log in as "admin"
    And I am on "Spring" course homepage
    And I click on "Administration" "button"
    And I press "Course administration"
    And I press "Users"
    And I click on "Enrolment methods" "link"
    And I click on "Enable" "link" in the "Self enrolment (Learner)" "table_row"
    And I click on "Edit" "link" in the "Guest access" "table_row"
    And I set the following fields to these values:
      | Allow guest access | Yes   |
      | Password           | guest |
    And I press "Save changes"
    And I log out

    # Guest access is enabled with required key, a learner needs to enter key to do self enrolment
    And I log in as "user_one"
    And I am on course index
    And I click on "div[title=\"Spring\"]" "css_element"
    And I set the field "Password" to "guest"
    When I press "Go to course"
    Then I should see "Enrol"
    And I should see "You’re viewing this course as a ‘Guest’. You must enrol in the course for your learning to be recorded."
    When I click on "Enrol to course Spring" "button"
    Then I should see "You've been enrolled successfully"
    And the "Launch (opens in new window)" "button" should be enabled
    And I should not see "You’re viewing this course as a ‘Guest’. You must enrol in the course for your learning to be recorded."
    And I log out

    # Guest access is disabled, a learner can do self enrolment through enrol page
    And I log in as "admin"
    And I am on "Spring" course homepage
    And I click on "Administration" "button"
    And I press "Course administration"
    And I press "Users"
    And I click on "Enrolment methods" "link"
    And I click on "Disable" "link" in the "Guest access" "table_row"
    And I log out

    And I log in as "user_two"
    And I am on course index
    And I click on "div[title=\"Spring\"]" "css_element"
    When I press "Enrol"
    Then I should see "You've been enrolled successfully"
    And I should not see "You’re viewing this course as a ‘Guest’. You must enrol in the course for your learning to be recorded."