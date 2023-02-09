@totara @totara_mobile @core_user @_file_upload @javascript
Feature: Test the totara_mobile_user_own_profile query

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Learner   | One      | learner1@example.com |
    When I log in as "admin"
    And I navigate to "Manage users" node in "Site administration > Users"
    And I click on "Learner One" "link" in the "Learner One" "table_row"
    And I follow "Edit profile"
    And I upload "totara/mobile/tests/fixtures/fruit.jpg" file to "New picture" filemanager
    And I click on "Update profile" "button"
    And I navigate to "Plugins > Mobile > Mobile settings" in site administration
    And I set the following fields to these values:
      | Enable mobile app | 1 |
    And I click on "Save changes" "button"

  Scenario: Test the query with a user that has a profile image
    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Making login_setup request"
    And I set the field "username" to "learner1"
    And I set the field "password" to "learner1"
    When I click on "Submit Credentials 1" "button"
    Then I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    When I set the field "jsondata2" to "{\"operationName\": \"totara_mobile_user_own_profile\",\"variables\": {}}"
    And I click on "Submit Request 2" "button"
    And I should not see "Coding error detected" in the "#response2" "css_element"
    Then I should see "\"username\": \"learner1\"" in the "#response2" "css_element"
    And I should see "\"profileimage\": " in the "#response2" "css_element"
    And I should see "totara/mobile/pluginfile.php" in the "#response2" "css_element"
    When I click on "link0" "link" in the "#response2" "css_element"
    Then I should see "26) File request HTTP ok."
    And I should see "27) File received image/jpeg"
    And I should see the mobile file response on line "28"
