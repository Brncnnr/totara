@totara @totara_engage @engage @totara_reportedcontent @javascript
Feature: Admin can create a user report with the reportedcontent source

  Scenario: The default column options with the reportedcontent report are correct.
    Given I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the field "search_input" to "Inappropriate content"
    And I click on "button.tw-selectSearchText__btn" "css_element"
    And I click on "div[data-tw-grid-item-id=\"reportedcontent-source\"]" "css_element"
    And I wait for pending js
    And I press "Create and edit"
    And I follow "Columns"
    # We should only see 5 default columns (can't really check the dropdown values easily)
    Then "//div[@class='reportbuilderform']/table/tbody[count(tr[@data-colid])=5]" "xpath_element" should exist

    When I press "Save changes"
    Then I should not see "You cannot include the same column more than once"
    And I should see "Columns updated"