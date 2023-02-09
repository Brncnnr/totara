@totara @totara_mobile @javascript
Feature: Confirm basic mobile plugin functionality

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
    When I log in as "admin"
    And I navigate to "Plugins > Mobile > Mobile settings" in site administration

  Scenario: Check that mobile API does not work with mobile disabled
    And I log out
    And I log in as "student1"
    When I am using the mobile emulator
    Then I should see "Mobile support unavailable"

  Scenario: Check webview authentication request and register functionality, make a GraphQL request, ensure user is
    logged out and we are operating using just the API key
    And I set the following fields to these values:
      | Enable mobile app | 1  |
    And I click on "Save changes" "button"
    And I navigate to "Plugins > Mobile > Mobile authentication" in site administration
    And I set the following fields to these values:
      | Type of login | Webview  |
    And I click on "Save changes" "button"
    And I log out
    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Initialised"
    And I should see "Authentication method: webview"
    And I should see "...logout complete."
    And I should see "Emulating webview login."
    When I switch to "WebView" iframe
    Then I should see "Log in"
    And I set the field "username" to "student1"
    And I set the field "password" to "student1"
    When I click on "Log in" "button"
    Then I should see "Registration request created"
    And I switch to the main frame
    And I should see "Capture setup secret"
    And I follow "Capture setup secret"
    Then I should see "Secret is"
    And I should see "Device registration HTTP ok"
    And I should see "API key"
    And I should see "API URL"
    And I should see "Mobile API Version"
    And I should see "Setting up new GraphQL browser"
    When I click on "Submit Request 1" "button"
    Then I should see "GraphQL response 1"
    And I should see "\"me\":" in the "#response1" "css_element"
    And I should see "Student 1" in the "#response1" "css_element"
    When I am on site homepage
    Then I should see "Log in"

  Scenario: Check native authentication functionality
    And I set the following fields to these values:
      | Enable mobile app | 1  |
    And I click on "Save changes" "button"
    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Initialised"
    And I should see "\"auth\": \"native\"" in the "#site_info_response" "css_element"
    And I should see "Making login_setup request"
    And I should see "Login secret:"
    And I set the field "username" to "student1"
    And I set the field "password" to "wrongpassword"
    When I click on "Submit Credentials 1" "button"
    Then I should see "Login request authentication error: 401"
    And I set the field "username" to "wronguser"
    And I set the field "password" to "student1"
    When I click on "Submit Credentials 2" "button"
    Then I should see "Login request authentication error: 401"
    And I set the field "username" to "student1"
    And I set the field "password" to "student1"
    When I click on "Submit Credentials 3" "button"
    Then I should see "Native login OK"
    And I should see "Setup secret is"
    And I should see "API key"
    And I should see "API URL"
    And I should see "Mobile API Version"
    And I should see "Setting up new GraphQL browser"
    When I click on "Submit Request 4" "button"
    Then I should see "GraphQL response 4"
    And I should see "\"me\":" in the "#response4" "css_element"
    And I should see "Student 1" in the "#response4" "css_element"
    When I am on site homepage
    Then I should see "Admin User"

  Scenario: Check authentication time-out period set to '1 day'
    And I set the following fields to these values:
      | Enable mobile app | 1  |
    And I click on "Save changes" "button"
    And I navigate to "Plugins > Mobile > Mobile authentication" in site administration
    And I set the following fields to these values:
      | Type of login   | Native |
      | Time-out period | 1 day  |
    And I click on "Save changes" "button"
    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Initialised"
    And I should see "\"auth\": \"native\"" in the "#site_info_response" "css_element"
    And I should see "Making login_setup request"
    And I should see "Login secret:"
    And I set the field "username" to "student1"
    And I set the field "password" to "student1"
    And I click on "Submit Credentials 1" "button"
    And I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    And I click on "Submit Request 2" "button"
    And I should see "GraphQL response 2"
    And I should not see "Coding error detected" in the "#response2" "css_element"
    And I should see "\"me\":" in the "#response2" "css_element"
    And I should see "Student 1" in the "#response2" "css_element"
    # DAYSECS + 1
    When I age the "student1" "devices" in the "totara_mobile" plugin "86401" seconds
    And I click on "Submit Request 3" "button"
    Then I should see "Network response was not ok: 401"

  Scenario: Check authentication time-out period set to '30 days'
    And I set the following fields to these values:
      | Enable mobile app | 1  |
    And I click on "Save changes" "button"
    And I navigate to "Plugins > Mobile > Mobile authentication" in site administration
    And I set the following fields to these values:
      | Type of login   | Native  |
      | Time-out period | 30 days |
    And I click on "Save changes" "button"
    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Initialised"
    And I should see "\"auth\": \"native\"" in the "#site_info_response" "css_element"
    And I should see "Making login_setup request"
    And I should see "Login secret:"
    And I set the field "username" to "student1"
    And I set the field "password" to "student1"
    And I click on "Submit Credentials 1" "button"
    And I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    And I click on "Submit Request 2" "button"
    And I should see "GraphQL response 2"
    And I should not see "Coding error detected" in the "#response2" "css_element"
    And I should see "\"me\":" in the "#response2" "css_element"
    And I should see "Student 1" in the "#response2" "css_element"
    # DAYSECS * 30 = 2592000
    When I age the "student1" "devices" in the "totara_mobile" plugin "2592001" seconds
    And I click on "Submit Request 3" "button"
    Then I should see "Network response was not ok: 401"

  Scenario: Check authentication time-out period set to 'Never'
    And I set the following fields to these values:
      | Enable mobile app | 1  |
    And I click on "Save changes" "button"
    And I navigate to "Plugins > Mobile > Mobile authentication" in site administration
    And I set the following fields to these values:
      | Type of login   | Native  |
      | Time-out period | Never   |
    And I click on "Save changes" "button"
    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Initialised"
    And I should see "\"auth\": \"native\"" in the "#site_info_response" "css_element"
    And I should see "Making login_setup request"
    And I should see "Login secret:"
    And I set the field "username" to "student1"
    And I set the field "password" to "student1"
    And I click on "Submit Credentials 1" "button"
    And I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    And I click on "Submit Request 2" "button"
    And I should see "GraphQL response 2"
    And I should not see "Coding error detected" in the "#response2" "css_element"
    And I should see "\"me\":" in the "#response2" "css_element"
    And I should see "Student 1" in the "#response2" "css_element"
    # DAYSECS * 30 = 2592000
    When I age the "student1" "devices" in the "totara_mobile" plugin "2592001" seconds
    And I click on "Submit Request 3" "button"
    Then I should see "GraphQL response 3"
    And I should see "\"me\":" in the "#response3" "css_element"
    And I should see "Student 1" in the "#response3" "css_element"

  Scenario: Check that the mobile api endpoint handles errors correctly, by creating a force password change condition
    And I set the following fields to these values:
      | Enable mobile app | 1  |
    And I click on "Save changes" "button"
    And I navigate to "Manage users" node in "Site administration > Users"
    And I click on "Student 1" "link"
    And I click on "Edit profile" "link"
    And I set the following fields to these values:
      | Force password change | 1 |
    And I press "Update profile"
    And I should see "User details"
    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Initialised"
    And I should see "\"auth\": \"native\"" in the "#site_info_response" "css_element"
    And I should see "Making login_setup request"
    And I should see "Login secret:"
    And I set the field "username" to "student1"
    And I set the field "password" to "student1"
    When I click on "Submit Credentials 1" "button"
    Then I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    When I set the field "jsondata2" to "{\"operationName\": \"totara_mobile_my_current_learning\",\"variables\": {}}"
    And I click on "Submit Request 2" "button"
    Then I should see "HTTP error: 500 Internal Server Error"
