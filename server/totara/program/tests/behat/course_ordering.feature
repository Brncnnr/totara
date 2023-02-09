@totara @totara_program @javascript
Feature: Program courses can be ordered within a courseset

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | user001  | fn_001    | ln_001   | user001@example.com |
      | user002  | fn_002    | ln_002   | user002@example.com |
      | user003  | fn_003    | ln_003   | user003@example.com |
    And the following "courses" exist:
      | fullname      | shortname     | format  | enablecompletion |
      | Test Course 1 | TestC1        | topics  | 1                |
      | Test Course 2 | TestC2        | topics  | 1                |
      | Test Course 3 | TestC3        | topics  | 1                |
      | Test Course 4 | TestC4        | topics  | 1                |
    And the following "programs" exist in "totara_program" plugin:
      | fullname       | shortname  |
      | Test Program 1 | program1   |
    And the following "program assignments" exist in "totara_program" plugin:
      | program  | user    |
      | program1 | user001 |

  Scenario: Order courses within a program
    Given I log in as "admin"
    When I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Test Program 1" "link"
    And I click on "Edit program details" "button"
    And I switch to "Content" tab
    And I click on "addcontent_ce" "button" in the "#edit-program-content" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Test Course 1" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Test Course 2" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Test Course 3" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Test Course 4" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    Then "Test Course 1" "text" should appear before "Test Course 2" "text"
    And "Test Course 2" "text" should appear before "Test Course 3" "text"
    And "Test Course 2" "text" should appear after "Test Course 1" "text"
    And "Test Course 3" "text" should appear before "Test Course 4" "text"
    And "Test Course 3" "text" should appear after "Test Course 2" "text"
    And "Test Course 4" "text" should appear after "Test Course 3" "text"
    # Move course 1 down.
    When I click on "//*[text()='Test Course 1']//a[contains(@class, 'coursedownlink')]" "xpath_element"
    # Move course 4 up.
    And I click on "//*[text()='Test Course 4']//a[contains(@class, 'courseuplink')]" "xpath_element"
    Then "Test Course 2" "text" should appear before "Test Course 1" "text"
    And "Test Course 1" "text" should appear before "Test Course 4" "text"
    And "Test Course 1" "text" should appear after "Test Course 2" "text"
    And "Test Course 4" "text" should appear before "Test Course 3" "text"
    And "Test Course 4" "text" should appear after "Test Course 1" "text"
    And "Test Course 3" "text" should appear after "Test Course 4" "text"
    When I press "Save changes"
    And I click on "Save all changes" "button"
    Then "Test Course 2" "text" should appear before "Test Course 1" "text"
    And "Test Course 1" "text" should appear before "Test Course 4" "text"
    And "Test Course 1" "text" should appear after "Test Course 2" "text"
    And "Test Course 4" "text" should appear before "Test Course 3" "text"
    And "Test Course 4" "text" should appear after "Test Course 1" "text"
    And "Test Course 3" "text" should appear after "Test Course 4" "text"
    And I log out
    # Ensure the courses display correctly for the learner.
    When I log in as "user001"
    And I am on "Dashboard" page
    And I should see "Test Program 1" in the "Current Learning" "block"
    And I toggle "Test Program 1" in the current learning block
    Then "Test Course 2" "text" should appear before "Test Course 1" "text"
    And "Test Course 1" "text" should appear before "Test Course 4" "text"
    And "Test Course 1" "text" should appear after "Test Course 2" "text"
    And "Test Course 4" "text" should appear before "Test Course 3" "text"
    And "Test Course 4" "text" should appear after "Test Course 1" "text"
    And "Test Course 3" "text" should appear after "Test Course 4" "text"
    When I click on "Test Program 1" "link"
    Then "Test Course 2" "text" should appear before "Test Course 1" "text"
    And "Test Course 1" "text" should appear before "Test Course 4" "text"
    And "Test Course 1" "text" should appear after "Test Course 2" "text"
    And "Test Course 4" "text" should appear before "Test Course 3" "text"
    And "Test Course 4" "text" should appear after "Test Course 1" "text"
    And "Test Course 3" "text" should appear after "Test Course 4" "text"

  Scenario: Ensure program course move up and move down icons are displayed correctly
    Given I log in as "admin"
    When I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Test Program 1" "link"
    And I click on "Edit program details" "button"
    And I switch to "Content" tab
    And I click on "addcontent_ce" "button" in the "#edit-program-content" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Test Course 1" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Test Course 2" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Test Course 3" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Test Course 4" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    Then I should see "Test Course 1"
    And I should see "Test Course 2"
    And I should see "Test Course 3"
    And I should see "Test Course 4"
    # Course 1 should only display a down icon.
    And "//*[text()='Test Course 1']//a[contains(@class, 'courseuplink')]" "xpath_element" in the "//body" "xpath_element" should not be visible
    And "//*[text()='Test Course 1']//a[contains(@class, 'coursedownlink')]" "xpath_element" in the "//body" "xpath_element" should be visible
    # Course 2 should display an up and down icon.
    And "//*[text()='Test Course 2']//a[contains(@class, 'courseuplink')]" "xpath_element" in the "//body" "xpath_element" should be visible
    And "//*[text()='Test Course 2']//a[contains(@class, 'coursedownlink')]" "xpath_element" in the "//body" "xpath_element" should be visible
    # Course 3 should display an up and down icon.
    And "//*[text()='Test Course 3']//a[contains(@class, 'courseuplink')]" "xpath_element" in the "//body" "xpath_element" should be visible
    And "//*[text()='Test Course 3']//a[contains(@class, 'coursedownlink')]" "xpath_element" in the "//body" "xpath_element" should be visible
    # Course 4 should only display a up icon.
    And "//*[text()='Test Course 4']//a[contains(@class, 'courseuplink')]" "xpath_element" in the "//body" "xpath_element" should be visible
    And "//*[text()='Test Course 4']//a[contains(@class, 'coursedownlink')]" "xpath_element" in the "//body" "xpath_element" should not be visible

    # Delete course 1.
    When I click on "//*[text()='Test Course 1']//a[contains(@class, 'coursedeletelink')]" "xpath_element"
    Then I should not see "Test Course 1"
    # Course 2 should only display a down icon.
    And "//*[text()='Test Course 2']//a[contains(@class, 'courseuplink')]" "xpath_element" in the "//body" "xpath_element" should not be visible
    And "//*[text()='Test Course 2']//a[contains(@class, 'coursedownlink')]" "xpath_element" in the "//body" "xpath_element" should be visible
    # Course 3 should display an up and down icon.
    And "//*[text()='Test Course 3']//a[contains(@class, 'courseuplink')]" "xpath_element" in the "//body" "xpath_element" should be visible
    And "//*[text()='Test Course 3']//a[contains(@class, 'coursedownlink')]" "xpath_element" in the "//body" "xpath_element" should be visible
    # Course 4 should only display a up icon.
    And "//*[text()='Test Course 4']//a[contains(@class, 'courseuplink')]" "xpath_element" in the "//body" "xpath_element" should be visible
    And "//*[text()='Test Course 4']//a[contains(@class, 'coursedownlink')]" "xpath_element" in the "//body" "xpath_element" should not be visible

    # Delete course 4.
    When I click on "//*[text()='Test Course 4']//a[contains(@class, 'coursedeletelink')]" "xpath_element"
    Then I should not see "Test Course 1"
    Then I should not see "Test Course 4"
    # Course 2 should only display a down icon.
    And "//*[text()='Test Course 2']//a[contains(@class, 'courseuplink')]" "xpath_element" in the "//body" "xpath_element" should not be visible
    And "//*[text()='Test Course 2']//a[contains(@class, 'coursedownlink')]" "xpath_element" in the "//body" "xpath_element" should be visible
    # Course 3 should only display an up icon.
    And "//*[text()='Test Course 3']//a[contains(@class, 'courseuplink')]" "xpath_element" in the "//body" "xpath_element" should be visible
    And "//*[text()='Test Course 3']//a[contains(@class, 'coursedownlink')]" "xpath_element" in the "//body" "xpath_element" should not be visible

  Scenario: Ensure ordered courses within a program appear correctly in the Program Overview report builder report
    Given the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname         | shortname | source           |
      | Program Overview | report_po | program_overview |
    And I log in as "admin"
    When I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Test Program 1" "link"
    And I click on "Edit program details" "button"
    And I switch to "Content" tab
    And I click on "addcontent_ce" "button" in the "#edit-program-content" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Test Course 1" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Test Course 2" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Test Course 3" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Test Course 4" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    # Move course 1 down.
    And I click on "//*[text()='Test Course 1']//a[contains(@class, 'coursedownlink')]" "xpath_element"
    # Move course 4 up.
    And I click on "//*[text()='Test Course 4']//a[contains(@class, 'courseuplink')]" "xpath_element"
    When I press "Save changes"
    And I click on "Save all changes" "button"
    Then "Test Course 2" "text" should appear before "Test Course 1" "text"
    And "Test Course 1" "text" should appear before "Test Course 4" "text"
    And "Test Course 1" "text" should appear after "Test Course 2" "text"
    And "Test Course 4" "text" should appear before "Test Course 3" "text"
    And "Test Course 4" "text" should appear after "Test Course 1" "text"
    And "Test Course 3" "text" should appear after "Test Course 4" "text"

    When I navigate to my "Program Overview" report
    And I press "Edit this report"
    Then I should see "Edit Report 'Program Overview'"
    When I switch to "Access" tab
    And I set the following fields to these values:
      | Authenticated User | 1 |
    And I press "Save changes"
    Then I should see "Report Updated"
    And I log out

    When I log in as "user001"
    And I click on "Reports" in the totara menu
    And I follow "Program Overview"
    Then "TestC2" "text" should appear before "TestC1" "text"
    And "TestC1" "text" should appear before "TestC4" "text"
    And "TestC1" "text" should appear after "TestC2" "text"
    And "TestC4" "text" should appear before "TestC3" "text"
    And "TestC4" "text" should appear after "TestC1" "text"
    And "TestC3" "text" should appear after "TestC4" "text"

  Scenario: Ensure ordered courses within a certification appear correctly in the Certification Overview report builder report
    Given the following "certifications" exist in "totara_program" plugin:
      | fullname             | shortname        |
      | Test Certification 1 | certification1   |
    And the following "program assignments" exist in "totara_program" plugin:
      | program        | user    |
      | certification1 | user001 |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname               | shortname | source                 |
      | Certification Overview | report_co | certification_overview |
    When I log in as "admin"
    And I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I click on "Miscellaneous" "link"
    And I click on "Test Certification 1" "link"
    And I click on "Edit certification details" "button"
    And I switch to "Content" tab
    And I click on "addcontent_ce" "button" in the "#edit-program-content" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Test Course 1" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Test Course 2" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Test Course 3" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Test Course 4" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    # Move course 1 down.
    And I click on "//*[text()='Test Course 1']//a[contains(@class, 'coursedownlink')]" "xpath_element"
    # Move course 4 up.
    And I click on "//*[text()='Test Course 4']//a[contains(@class, 'courseuplink')]" "xpath_element"
    When I press "Save changes"
    And I click on "Save all changes" "button"
    Then "Test Course 2" "text" should appear before "Test Course 1" "text"
    And "Test Course 1" "text" should appear before "Test Course 4" "text"
    And "Test Course 1" "text" should appear after "Test Course 2" "text"
    And "Test Course 4" "text" should appear before "Test Course 3" "text"
    And "Test Course 4" "text" should appear after "Test Course 1" "text"
    And "Test Course 3" "text" should appear after "Test Course 4" "text"

    When I navigate to my "Certification Overview" report
    And I press "Edit this report"
    Then I should see "Edit Report 'Certification Overview'"
    When I switch to "Access" tab
    And I set the following fields to these values:
      | Authenticated User | 1 |
    And I press "Save changes"
    Then I should see "Report Updated"
    And I log out

    When I log in as "user001"
    And I click on "Reports" in the totara menu
    And I follow "Certification Overview"
    Then "TestC2" "text" should appear before "TestC1" "text"
    And "TestC1" "text" should appear before "TestC4" "text"
    And "TestC1" "text" should appear after "TestC2" "text"
    And "TestC4" "text" should appear before "TestC3" "text"
    And "TestC4" "text" should appear after "TestC1" "text"
    And "TestC3" "text" should appear after "TestC4" "text"
