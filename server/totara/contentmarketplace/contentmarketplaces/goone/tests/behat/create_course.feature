@totara @totara_contentmarketplace @contentmarketplace_goone @javascript @_switch_window
Feature: Create a course from the Go1 content marketplace
  As an admin
  I should be able to navigate the content marketplace and create a course

  Background:
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Plugins > Content marketplace > Manage content marketplaces" in site administration
    And I should see "Disabled" in the ".contentmarketplace_goone" "css_element"
    When I click on "Set up" "link" in the ".contentmarketplace_goone" "css_element"
    And I switch to "setup" window
    And the following should exist in the "state" table:
      | full_name       | Admin User         |
      | email           | moodle@example.com |
      | users_total     | 1                  |
    And I click on "Authorize Totara" "button"
    And I switch to the main window
    And I click on "Continue" "button"
    And I click on "Save and explore Go1" "button"

  Scenario: Multiactivity go1 course can be created with a single Go1 course
    When I click on "[for='selection-1868492']" "css_element"
    Then I should see "1 item selected"
    When I click on "Create course" "link"
    Then I should see "1 item selected"
    And I should see "Basic First Aid"
    Then I should see the following Totara form fields having these values:
      | Course full name  | Basic First Aid |
      | Course short name | Basic First Aid |
    When I set the following Totara form fields to these values:
      | Course short name | bsr |
    And I click on "Create and view course" "button"
    Then I should see "bsr" in the ".breadcrumb" "css_element"
    And I should see "Basic First Aid" in the "#section-1" "css_element"

    # Course catalogue Go1 provider logo
    When I click on "Find Learning" in the totara menu
    Then I should see "1 items"
    And I should see "Basic First Aid"
    And I should see image with alt text "Go1's logo"

  Scenario: Single activity go1 course can be created
    When I click on "[for='selection-1868492']" "css_element"
    Then I should see "1 item selected"
    When I click on "Create course" "link"
    Then I should see "1 item selected"
    And I should see "Basic First Aid"
    Then I should see the following Totara form fields having these values:
      | Course full name  | Basic First Aid |
      | Course short name | Basic First Aid |
    When I set the following Totara form fields to these values:
      | Course short name                                                 | bsr |
      | Create a new single activity course for the selected content item | 2   |
    And I click on "Create and view course" "button"
    Then I should see "bsr" in the ".breadcrumb" "css_element"
    And I should see "Basic First Aid"

    # Course catalogue Go1 provider logo
    When I click on "Find Learning" in the totara menu
    Then I should see "1 items"
    And I should see "Basic First Aid"
    And I should see image with alt text "Go1's logo"

  Scenario: Multiactivity go1 course can be created with multiple Go1 courses
    # Select assortment of courses
    When I click on "[for='selection-1868492']" "css_element"
    And I click on "[for='selection-1873868']" "css_element"
    And I click on "[for='selection-29271']" "css_element"
    And I click on "[for='selection-1916572']" "css_element"
    And I click on "[for='selection-1881379']" "css_element"
    Then I should see "5 items selected"

    When I click on "Create course" "link"
    Then I should see "5 items selected"
    And I should see "Basic First Aid"
    And I should see "How to Master Public Speaking"
    And I should see "Seizure First Aid"
    And I should see "Interviewing 101"
    And I should see "Epilepsy and the Older Person"

    When I click on "Remove" "link"
    Then I should see "4 items selected"
    And I should not see "Basic First Aid"

    When I set the following Totara form fields to these values:
      | Course full name  | Go1 test course |
      | Course short name | g1tc            |
    And I click on "Create and view course" "button"
    Then I should see "g1tc" in the ".breadcrumb" "css_element"
    And I should see "How to Master Public Speaking" in the "#section-1" "css_element"
    And I should see "Seizure First Aid" in the "#section-1" "css_element"
    And I should see "Interviewing 101" in the "#section-1" "css_element"
    And I should see "Epilepsy and the Older Person" in the "#section-1" "css_element"

    # Course catalogue Go1 provider logo
    When I click on "Find Learning" in the totara menu
    Then I should see "1 items"
    And I should see "Go1 test course"
    And I should see image with alt text "Go1's logo"

    # Course catalogue provider filter
    When I click on "Configure catalogue" "link"
    And I switch to "Filters" tab
    And I set the field "Add another..." to "Provider"
    And I click on "Save" "button"
    And I click on "View catalogue" "link"
    Then I should see "Provider" in the ".tw-selectRegionPanel" "css_element"
    When I click on "Internal" "link" in the "[aria-labelledby='contentmarketplace_provider_panel']" "css_element"
    # Multi activity go1 courses also have a forum module, so they count as internal as well as go1
    Then I should see "1 items"
    When I click on "Go1" "link" in the "[aria-labelledby='contentmarketplace_provider_panel']" "css_element"
    Then I should see "1 items"

  Scenario: Multiple single activity go1 courses can be created
    # Select assortment of courses
    When I click on "[for='selection-1868492']" "css_element"
    And I click on "[for='selection-1873868']" "css_element"
    And I click on "[for='selection-29271']" "css_element"
    And I click on "[for='selection-1916572']" "css_element"
    And I click on "[for='selection-1881379']" "css_element"
    Then I should see "5 items selected"

    When I click on "Create course" "link"
    Then I should see "5 items selected"
    And I should see "Basic First Aid"
    And I should see "How to Master Public Speaking"
    And I should see "Seizure First Aid"
    And I should see "Interviewing 101"
    And I should see "Epilepsy and the Older Person"

    When I set the following Totara form fields to these values:
      | Create a new single activity course for each selected content item | 2 |
    And I click on "Create 5 courses" "button"
    And I should see "5 new courses have been created"
    And I should see "Basic First Aid"
    And I should see "How to Master Public Speaking"
    And I should see "Seizure First Aid"
    And I should see "Interviewing 101"
    And I should see "Epilepsy and the Older Person"

    # Course catalogue Go1 provider logo
    When I click on "Find Learning" in the totara menu
    Then I should see "5 items"
    And I should see "Basic First Aid"
    And I should see "How to Master Public Speaking"
    And I should see "Seizure First Aid"
    And I should see "Interviewing 101"
    And I should see "Epilepsy and the Older Person"
    And I should see image with alt text "Go1's logo"
