@totara @totara_program @totara_core_menu
Feature: Test Required Learning Main menu item
  In order to use Required Learning menu item
  As an admin
  I must be able to cofigure it

  Scenario: Make sure Required learning is available in totara menu but not used
    Given I am on a totara site
    And I log in as "admin"
    When I navigate to "Main menu" node in "Site administration > Navigation"
    Then "Required Learning" row "Visibility" column of "totaramenutable" table should contain "Unused"
    And I should not see "Required Learning" in the totara menu

  Scenario: Make sure Required learning is disabled in totara menu if both prorams and certifications are disabled
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Main menu" node in "Site administration > Navigation"
    And I click on "Edit" "link" in the "Required Learning" "table_row"
    And I set the following Totara form fields to these values:
      | Parent item | Top |
    And I press "Save changes"
    And "Required Learning" row "Visibility" column of "totaramenutable" table should contain "Show when accessible"

    When I disable the "certifications" advanced feature
    And I disable the "programs" advanced feature
    And I navigate to "Main menu" node in "Site administration > Navigation"
    Then "Required Learning" row "Visibility" column of "totaramenutable" table should contain "Feature disabled"

    When I disable the "certifications" advanced feature
    And I enable the "programs" advanced feature
    And I navigate to "Main menu" node in "Site administration > Navigation"
    Then "Required Learning" row "Visibility" column of "totaramenutable" table should contain "Show when accessible"

    When I enable the "certifications" advanced feature
    And I disable the "programs" advanced feature
    And I navigate to "Main menu" node in "Site administration > Navigation"
    Then "Required Learning" row "Visibility" column of "totaramenutable" table should contain "Show when accessible"
