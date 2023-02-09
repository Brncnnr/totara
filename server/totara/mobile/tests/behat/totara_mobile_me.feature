@totara @totara_mobile @javascript
Feature: Test various aspects of the totara_mobile_me query

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | C1        | 1                |
      | Course 2 | C2        | 0                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | student1 | C2     | student        |
    When I log in as "admin"
    And I navigate to "Plugins > Mobile > Mobile settings" in site administration
    And I set the following fields to these values:
      | Enable mobile app | 1  |
    And I click on "Save changes" "button"

  Scenario: Test user and system blocks with no site policies or user fields
    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Making login_setup request"
    And I set the field "username" to "student1"
    And I set the field "password" to "student1"
    When I click on "Submit Credentials 1" "button"
    Then I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    When I click on "Submit Request 2" "button"
    Then I should see "GraphQL response 2"
    And I should see "\"me\":" in the "#response2" "css_element"
    And I should see "\"firstname\": \"Student\"" in the "#response2" "css_element"
    And I should see "\"lastname\": \"1\"" in the "#response2" "css_element"
    And I should see "\"fullname\": \"Student 1\"" in the "#response2" "css_element"
    And I should see "\"email\": \"student1@example.com\"" in the "#response2" "css_element"
    And I should see "\"__typename\": \"totara_mobile_user\"" in the "#response2" "css_element"
    And I should see "\"wwwroot\": \"http" in the "#response2" "css_element"
    And I should see "\"apiurl\": \"http" in the "#response2" "css_element"
    And I should see "\"release\": \"" in the "#response2" "css_element"
    And I should see "\"request_policy_agreement\": false," in the "#response2" "css_element"
    And I should see "\"request_user_consent\": false," in the "#response2" "css_element"
    And I should see "\"request_user_fields\": false," in the "#response2" "css_element"
    And I should see "\"__typename\": \"totara_mobile_system\"" in the "#response2" "css_element"

  Scenario: Test system block with moodle site policy
    And I navigate to "Security > Security settings" in site administration
    And I set the field "Site policy URL" to "https://www.totaralearning.com/"
    And I click on "Save changes" "button"
    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Making login_setup request"
    And I set the field "username" to "student1"
    And I set the field "password" to "student1"
    When I click on "Submit Credentials 1" "button"
    Then I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    When I click on "Submit Request 2" "button"
    Then I should see "GraphQL response 2"
    And I should see "\"me\":" in the "#response2" "css_element"
    And I should see "\"request_policy_agreement\": true," in the "#response2" "css_element"
    And I should see "\"request_user_consent\": false," in the "#response2" "css_element"
    And I should see "\"request_user_fields\": false," in the "#response2" "css_element"
    And I am on site homepage
    And I follow "Continue in browser"
    And I log out
    And I log in as "student1"
    And I click on "Yes" "button"
    Then I should see "Current Learning"
    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Making login_setup request"
    And I set the field "username" to "student1"
    And I set the field "password" to "student1"
    When I click on "Submit Credentials 1" "button"
    Then I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    When I click on "Submit Request 2" "button"
    Then I should see "GraphQL response 2"
    And I should see "\"me\":" in the "#response2" "css_element"
    And I should see "\"request_policy_agreement\": false," in the "#response2" "css_element"

  Scenario: Test system block with Totara site policy
    And I set the following administration settings values:
      | Enable site policies | 1 |
    Given the following "multiversionpolicies" exist in "tool_sitepolicy" plugin:
      | hasdraft | numpublished | allarchived | title    | languages |statement          | numoptions | consentstatement       | providetext | withholdtext | mandatory |
      | 0        | 1            | 0           | Policy 1 | en        |Policy 1 statement | 1          | P1 - Consent statement | Yes         | No           | none      |
    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Making login_setup request"
    And I set the field "username" to "student1"
    And I set the field "password" to "student1"
    When I click on "Submit Credentials 1" "button"
    Then I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    When I click on "Submit Request 2" "button"
    Then I should see "GraphQL response 2"
    And I should see "\"me\":" in the "#response2" "css_element"
    And I should see "\"request_policy_agreement\": false," in the "#response2" "css_element"
    And I should see "\"request_user_consent\": true," in the "#response2" "css_element"
    And I should see "\"request_user_fields\": false," in the "#response2" "css_element"
    And I am on site homepage
    And I follow "Continue in browser"
    And I log out
    And I log in as "student1"
    And I set the "P1 - Consent statement 1" Totara form field to "0"
    And I press "Submit"
    Then I should see "Current Learning"
    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Making login_setup request"
    And I set the field "username" to "student1"
    And I set the field "password" to "student1"
    When I click on "Submit Credentials 1" "button"
    Then I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    When I click on "Submit Request 2" "button"
    Then I should see "GraphQL response 2"
    And I should see "\"me\":" in the "#response2" "css_element"
    And I should see "\"request_user_consent\": false," in the "#response2" "css_element"

  Scenario: Test system block with user field required
    And I navigate to "User profile fields" node in "Site administration > Users"
    And I set the following fields to these values:
      | datatype | text |
    And I set the following fields to these values:
      | Short name | textinput         |
      | Name       | User text profile |
      | required   | 1                 |
    And I press "Save changes"
    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Making login_setup request"
    And I set the field "username" to "student1"
    And I set the field "password" to "student1"
    When I click on "Submit Credentials 1" "button"
    Then I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    When I click on "Submit Request 2" "button"
    Then I should see "GraphQL response 2"
    And I should see "\"me\":" in the "#response2" "css_element"
    And I should see "\"request_policy_agreement\": false," in the "#response2" "css_element"
    And I should see "\"request_user_consent\": false," in the "#response2" "css_element"
    And I should see "\"request_user_fields\": true," in the "#response2" "css_element"
    And I am on site homepage
    And I follow "Continue in browser"
    And I log out
    And I log in as "student1"
    And I set the field "User text profile" to "Kia ora"
    And I press "Update profile"
    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Making login_setup request"
    And I set the field "username" to "student1"
    And I set the field "password" to "student1"
    When I click on "Submit Credentials 1" "button"
    Then I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    When I click on "Submit Request 2" "button"
    Then I should see "GraphQL response 2"
    And I should see "\"me\":" in the "#response2" "css_element"
    And I should see "\"request_user_fields\": false," in the "#response2" "css_element"
