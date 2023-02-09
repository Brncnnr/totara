@mod @mod_contentmarketplace @contentmarketplace_linkedin @totara @totara_contentmarketplace @javascript
Feature: Content marketplace activity completion feature
  Background:
    Given I am on a totara site
    And the following "learning objects" exist in "contentmarketplace_linkedin" plugin:
      | urn | title    | level    | asset_type | locale_language | locale_country |
      | A   | Course A | BEGINNER | COURSE     | en              | US             |
    And the following "classifications" exist in "contentmarketplace_linkedin" plugin:
      | urn        | name  | type    | locale_language | locale_country |
      | category:1 | J2EE  | LIBRARY | en              | US             |
      | category:2 | JDBC  | SUBJECT | en              | US             |
    And the following "classification relationships" exist in "contentmarketplace_linkedin" plugin:
      | parent_urn | child_urn  |
      | category:1 | category:2 |
    And the following "learning object classifications" exist in "contentmarketplace_linkedin" plugin:
      | learning_object_urn | classification_urn |
      | A                   | category:2         |
    And the following "categories" exist:
      | name       | category | idnumber |
      | Category A | 0        | A        |
    And I set up the "linkedin" content marketplace plugin

  Scenario: Should not see the completion when completion is disabled
    When I log in as "admin"
    And I navigate to the catalog import page for the "linkedin" content marketplace
    And I toggle the selection of row "1" of the tui select table
    And I set the field "Select category" to "Category A"
    And I click on "Next: Review" "button"
    When I click on "Create course(s)" "button"
    Then I should see "Course A"
    When I am on "Course A" course homepage
    And I click on "Administration" "button"
    And I press "Course administration"
    And I press "Users"
    And I click on "Enrolment methods" "link"
    And I click on "Enable" "link" in the "Self enrolment (Learner)" "table_row"
    And I am on "Course A" course homepage
    When I click on "Enrol to course Course A" "button"
    And I wait for the next second
    Then I should see "Not completed"
    And I should see "0%" in the ".tui-progress" "css_element"
    And I should not see "I have completed this activity"
    When I click on "Administration" "button"
    And I click on "Edit settings" "link"
    And I follow "Activity completion"
    And I set the field "Completion tracking" to "0"
    And I click on "Save and display" "button"
    Then I should not see "Not completed"
    And ".tui-progress" "css_element" should not exist
    And I should not see "I have completed this activity"

  Scenario: Self completion is not enabled by default
    When I log in as "admin"
    And I navigate to the catalog import page for the "linkedin" content marketplace
    And I toggle the selection of row "1" of the tui select table
    And I set the field "Select category" to "Category A"
    And I click on "Next: Review" "button"
    When I click on "Create course(s)" "button"
    Then I should see "Course A"
    When I am on "Course A" course homepage
    Then ".tui-progress" "css_element" should not exist
    And I should not see "I have completed this activity"
    When I click on "Administration" "button"
    And I press "Course administration"
    And I press "Users"
    And I click on "Enrolment methods" "link"
    And I click on "Enable" "link" in the "Self enrolment (Learner)" "table_row"
    And I am on "Course A" course homepage
    And I click on "Enrol to course Course A" "button"
    Then I should not see "I have completed this activity"
    And I should see "0%" in the ".tui-progress" "css_element"
    When I click on "Administration" "button"
    And I click on "Edit settings" "link"
    And I follow "Activity completion"
    And I set the field "Completion tracking" to "1"
    And I click on "Save and display" "button"
    Then I should see "I have completed this activity"
    And I should see "Not completed"
    And I should not see "Completed"
    And ".tui-progress" "css_element" should not exist
    And the "I have completed this activity" tui toggle switch should be "off"
    When I click on the "I have completed this activity" tui toggle button
    Then I should see "Completed"
    And I should not see "Not completed"
    When I reload the page
    Then the "I have completed this activity" tui toggle switch should be "on"

  Scenario: Should not see the completion when not enrolled
    Given the following "users" exist:
      | username | firstname | lastname | email           |
      | user_one | User      | One      | one@example.com |
    When I log in as "admin"
    And I navigate to the catalog import page for the "linkedin" content marketplace
    And I toggle the selection of row "1" of the tui select table
    And I set the field "Select category" to "Category A"
    And I click on "Next: Review" "button"
    And I click on "Create course(s)" "button"
    And I am on "Course A" course homepage
    And I click on "Administration" "button"
    And I press "Course administration"
    And I press "Users"
    And I click on "Enrolment methods" "link"
    And I click on "Enable" "link" in the "Guest access" "table_row"
    And I log out
    And I log in as "user_one"
    And I am on "Course A" course homepage
    Then I should not see "Administration"
    When I log out
    And I log in as "admin"
    And I am on "Course A" course homepage
    And I click on "Administration" "button"
    And I press "Course administration"
    And I press "Users"
    And I click on "Enrolment methods" "link"
    And I click on "Enable" "link" in the "Self enrolment (Learner)" "table_row"
    And I log out
    And I log in as "user_one"
    And I am on "Course A" course homepage
    Then I should not see "Not completed"
    And I should see "You’re viewing this course as a ‘Guest’. You must enrol in the course for your learning to be recorded."
    And I should see "Administration"
    And ".tui-progress" "css_element" should not exist
    When I click on "Enrol" "button"
    And I wait for the next second
    Then I should see "Not completed"
    And I should not see "You’re viewing this course as a ‘Guest’. You must enrol in the course for your learning to be recorded."