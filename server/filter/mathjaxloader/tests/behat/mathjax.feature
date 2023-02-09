@filter @filter_mathjax @javascript
Feature: Confirm mathjax filter is working
  In order to use mathematcial formulae
  The mathjax filter needs to be working

  Scenario: Confirm mathjax works through the site home page
    Given I log in as "admin"
    And I am on site homepage
    And I navigate to "Plugins > Filters > Manage filters" in site administration
    And I set the field with xpath "//table//tr[contains(.,'MathJax')]//*[@name='newstate']" to "On"
    And I am on site homepage
    And I navigate to "Edit settings" node in "Front page settings"
    And I set the field "Front page summary" to "\( \alpha \beta \Delta \)"
    And I press "Save changes"
    And I am on site homepage
    Then I should see "αβΔ"
