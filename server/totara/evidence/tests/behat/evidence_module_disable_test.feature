@totara @perform @totara_evidence @javascript
Feature: Disable evidence module at site-level

  Background:
    When I log in as "admin"
    And I navigate to "System information > Configure features > Shared services settings" in site administration
    And I set the field "Enable Evidence" to "1"
    And I press "Save changes"

  Scenario: Evidence link in the admin menu
    When I toggle open the admin quick access menu
    Then I should see "Evidence" in the admin quick access menu

    # Disable it
    When I navigate to "System information > Configure features > Shared services settings" in site administration
    And I set the field "Enable Evidence" to "0"
    And I press "Save changes"

    When I toggle open the admin quick access menu
    Then I should not see "Evidence" in the admin quick access menu

  Scenario: Evidence link in users profile
    Given the "mylearning" user profile block exists
    When I am on profile page for user "admin"
    Then I should see "Evidence bank" in the ".block_totara_user_profile_category_mylearning" "css_element"

    # Disable it
    When I navigate to "System information > Configure features > Shared services settings" in site administration
    And I set the field "Enable Evidence" to "0"
    And I press "Save changes"

    When I am on profile page for user "admin"
    Then I should not see "Evidence bank" in the ".userprofile" "css_element"

  Scenario: Evidence link in learning plan navigation block
    When I click on "Record of Learning" in the totara menu
    Then I should see "Other Evidence" in the "#block-region-side-pre #dp-plans-menu" "css_element"
    When I click on "Evidence bank" "link" in the "#block-region-side-pre #dp-plans-menu" "css_element"
    Then I should see "Evidence bank" in the page title

      # Disable it
    When I navigate to "System information > Configure features > Shared services settings" in site administration
    And I set the field "Enable Evidence" to "0"
    And I press "Save changes"

    When I click on "Record of Learning" in the totara menu
    Then I should not see "Other Evidence" in the "#block-region-side-pre #dp-plans-menu" "css_element"
    And I should not see "Evidence bank" in the "#block-region-side-pre #dp-plans-menu" "css_element"
