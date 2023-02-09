@totara @totara_engage @engage
Feature: Display contributions block in user's profile
  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"

  @javascript
  Scenario: Edit contributions block within the profile
    Given I am on a totara site
    And I log in as "admin"
    When I navigate to "Users > Default profile page" in site administration
    Then I should see "Admin User's library"
    And I click on "Blocks editing on" "button"
    # Change the default user profile block to something else
    And I configure the "User Profile" block
    And I set the field "Override default block title" to "1"
    And I set the field "Block title" to "User details"
    And I click on "Save changes" "button"
    # The second user profile block will definitely a contributions block
    And I configure the "User Profile" block
    When I follow "Custom block settings"
    And "Contributions" "option" should exist
    And I set the field "Display User Profile category" to "Contributions"
    When I click on "Save changes" "button"
    Then I should see "Admin User's library"

  @javascript
  Scenario: contributions block is not available when engage is off
    Given I am on a totara site
    And I log in as "admin"
    And I disable the "engage_resources" advanced feature
    When I navigate to "Users > Default profile page" in site administration
    Then I should not see "Admin User's library"
    And I click on "Blocks editing on" "button"
    And I open the "User Profile" blocks action menu
    And I click on ".editing_delete" "css_element" in the "User Profile" "block"
    And I press "Yes"
    And "User Profile" "block" should exist
    And I configure the "User Profile" block
    When I follow "Custom block settings"
    Then "Contributions" "option" should not exist

  @javascript
  Scenario: contributions block disable when engage turn off
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Users > Default profile page" in site administration
    And I click on "Blocks editing on" "button"
    # Change the default user profile block to something else
    And I configure the "User Profile" block
    And I set the field "Override default block title" to "1"
    And I set the field "Block title" to "User details"
    And I click on "Save changes" "button"
    # Second block is definitely a contributions block
    And I should see "Admin User's library"
    And I should see "Contributions"
    And I disable the "engage_resources" advanced feature
    When I navigate to "Users > Default profile page" in site administration
    Then I should not see "Admin User's library"
    And I should not see "Contributions"
