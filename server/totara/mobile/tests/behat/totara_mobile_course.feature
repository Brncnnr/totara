@totara @totara_mobile @core_course @_file_upload @javascript
Feature: Test the totara_mobile_course query

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | C1        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    When I log in as "admin"
    And I navigate to "Plugins > Mobile > Mobile settings" in site administration
    And I set the following fields to these values:
      | Enable mobile app | 1 |
    And I click on "Save changes" "button"
    And I am on "Course 1" course homepage

  Scenario: Test the query with a basic course
    And I log out
    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Making login_setup request"
    And I set the field "username" to "student1"
    And I set the field "password" to "student1"
    When I click on "Submit Credentials 1" "button"
    Then I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    When I set the field "jsondata2" to "{\"operationName\": \"totara_mobile_course\",\"variables\": {\"courseid\": 2}}"
    And I click on "Submit Request 2" "button"
    Then I should not see "Coding error detected" in the "#response2" "css_element"
    And I should see "\"fullname\": \"Course 1\"" in the "#response2" "css_element"
    And I should see "\"summary\": \"Test course 1" in the "#response2" "css_element"
    And I should see "\"summaryformat\": \"HTML\"" in the "#response2" "css_element"
    And I should see "\"format\": \"topics\"" in the "#response2" "css_element"
    And I should see "\"criteria\": []" in the "#response2" "css_element"
    And I should see "\"statuskey\": \"notyetstarted\"" in the "#response2" "css_element"
    And I should see "\"native\": false" in the "#response2" "css_element"
    And I should see "\"imageSrc\": \"\"" in the "#response2" "css_element"

  Scenario: Test the query with a course that has JSON summary
    When I follow "Edit settings"
    And I set the field with xpath "//select[@name='summary_editor[format]']" to "5"
    And I click on "Save and display" "button"
    And I click on "Save and display" "button"
    And I log out
    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Making login_setup request"
    And I set the field "username" to "student1"
    And I set the field "password" to "student1"
    When I click on "Submit Credentials 1" "button"
    Then I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    When I set the field "jsondata2" to "{\"operationName\": \"totara_mobile_course\",\"variables\": {\"courseid\": 2}}"
    And I click on "Submit Request 2" "button"
    Then I should not see "Coding error detected" in the "#response2" "css_element"
    And I should see "\"fullname\": \"Course 1\"" in the "#response2" "css_element"
    And I should see "\"summary\": \"{\\"type" in the "#response2" "css_element"
    And I should see "\"summaryformat\": \"JSON_EDITOR\"" in the "#response2" "css_element"

  Scenario: Test the query with a course that has an image
    When I follow "Edit settings"
    And I expand all fieldsets
    And I upload "totara/program/tests/fixtures/leaves-blue.png" file to "Image" filemanager
    And I press "Save and display"
    And I log out
    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Making login_setup request"
    And I set the field "username" to "student1"
    And I set the field "password" to "student1"
    When I click on "Submit Credentials 1" "button"
    Then I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    When I set the field "jsondata2" to "{\"operationName\": \"totara_mobile_course\",\"variables\": {\"courseid\": 2}}"
    And I click on "Submit Request 2" "button"
    And I should not see "Coding error detected" in the "#response2" "css_element"
    Then I should see "\"fullname\": \"Course 1\"" in the "#response2" "css_element"
    And I should see "\"imageSrc\"" in the "#response2" "css_element"
    And I click on "link1" "link" in the "#response2" "css_element"
    Then I should see "26) File request HTTP ok."
    And I should see "27) File received image/png"
    And I should see the mobile file response on line "28"

  Scenario: Test the query with a course that has a custom default image
    When I navigate to "Course default settings" node in "Site administration >  Courses"
    And I upload "totara/program/tests/fixtures/leaves-blue.png" file to "" filemanager
    And I press "Save changes"
    And I log out
    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Making login_setup request"
    And I set the field "username" to "student1"
    And I set the field "password" to "student1"
    When I click on "Submit Credentials 1" "button"
    Then I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    When I set the field "jsondata2" to "{\"operationName\": \"totara_mobile_course\",\"variables\": {\"courseid\": 2}}"
    And I click on "Submit Request 2" "button"
    Then I should not see "Coding error detected" in the "#response2" "css_element"
    And I should see "\"fullname\": \"Course 1\"" in the "#response2" "css_element"
    And I should see "\"imageSrc\": \"\"" in the "#response2" "css_element"

  Scenario: Test the native property, which maps to mobile_coursecompat
    When I follow "Edit settings"
    And I expand all fieldsets
    And I set the following fields to these values:
      | Mobile-friendly course | Yes |
    And I click on "Save and display" "button"
    And I log out
    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Making login_setup request"
    And I set the field "username" to "student1"
    And I set the field "password" to "student1"
    When I click on "Submit Credentials 1" "button"
    Then I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    When I set the field "jsondata2" to "{\"operationName\": \"totara_mobile_course\",\"variables\": {\"courseid\": 2}}"
    And I click on "Submit Request 2" "button"
    Then I should not see "Coding error detected" in the "#response2" "css_element"
    And I should see "\"fullname\": \"Course 1\"" in the "#response2" "css_element"
    And I should see "\"native\": true" in the "#response2" "css_element"

  Scenario: Test the query with an orphaned category
    Given I am on "Course 1" course homepage with editing mode on
    And I follow "Reduce the number of sections"
    And I follow "Reduce the number of sections"
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name | First seminar |
    And I add a "Seminar" to section "2" and I fill the form with:
      | Name | Second seminar |
    And I add a "Seminar" to section "3" and I fill the form with:
      | Name | Third seminar |
    And I follow "Reduce the number of sections"
    Then I should see "Orphaned activities"
    And I log out
    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Making login_setup request"
    And I set the field "username" to "student1"
    And I set the field "password" to "student1"
    When I click on "Submit Credentials 1" "button"
    Then I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    When I set the field "jsondata2" to "{\"operationName\": \"totara_mobile_course\",\"variables\": {\"courseid\": 2}}"
    And I click on "Submit Request 2" "button"
    Then I should not see "Coding error detected" in the "#response2" "css_element"
    And I should see "\"title\": \"Topic 1\"" in the "#response2" "css_element"
    And I should see "\"title\": \"Topic 2\"" in the "#response2" "css_element"
    And I should not see "\"title\": \"Topic 3\"" in the "#response2" "css_element"
    And I should not see "Orphaned" in the "#response2" "css_element"

  Scenario: Test that urls are not escaped within a course description for mobile queries
    Given I follow "Edit settings"
    And I set the field "Course summary" to "https://www.example.com"
    And I set the field with xpath "//select[@name='summary_editor[format]']" to "5"
    And I click on "Save and display" "button"
    Then I should see "Please check mobile-friendly content and save when ready"
    And I click on "Save and display" "button"
    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Making login_setup request"
    And I set the field "username" to "student1"
    And I set the field "password" to "student1"
    When I click on "Submit Credentials 1" "button"
    Then I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    When I set the field "jsondata2" to "{\"operationName\": \"totara_mobile_course\",\"variables\": {\"courseid\": 2}}"
    And I click on "Submit Request 2" "button"
    Then I should not see "Coding error detected" in the "#response2" "css_element"
    And I should see "\"fullname\": \"Course 1\"" in the "#response2" "css_element"
    And I should see "https://www.example.com" in the "#response2" "css_element"

  Scenario: Test the availability of course items based off user language in mobile
    Given remote langimport tests are enabled
    And I navigate to "Language packs" node in "Site administration > Localisation"
    Given language pack installation succeeds
    And I set the field "Available language packs" to "de"
    And I press "Install selected language pack(s)"
    Given I am on "Course 1" course homepage with editing mode on

    And I follow "Add an activity or resource"
    When I click on "label" "radio" in the "Add an activity or resource" "dialogue"
    And I click on "Add" "button" in the "Add an activity or resource" "dialogue"
    And I set the field "Label text" to "Label (en)"
    And I follow "Restrict access"
    And I click on "Add restriction..." "button"
    And I click on "User language" "button"
    And I set the field "User language" to "en"
    And I click on "Save and return to course" "button"

    And I follow "Add an activity or resource"
    When I click on "label" "radio" in the "Add an activity or resource" "dialogue"
    And I click on "Add" "button" in the "Add an activity or resource" "dialogue"
    And I set the field "Label text" to "Label (de)"
    And I follow "Restrict access"
    And I click on "Add restriction..." "button"
    And I click on "User language" "button"
    And I set the field "User language" to "de"
    And I click on "Save and return to course" "button"

    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Making login_setup request"
    And I set the field "username" to "student1"
    And I set the field "password" to "student1"
    When I click on "Submit Credentials 1" "button"
    Then I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    When I set the field "jsondata2" to "{\"operationName\": \"totara_mobile_course\",\"variables\": {\"courseid\": 2}}"
    And I click on "Submit Request 2" "button"
    And I should not see "Coding error detected" in the "#response2" "css_element"
    Then I should see "\"fullname\": \"Course 1\"" in the "#response2" "css_element"
    And I should see "Not available unless: Your language is Deutsch" in the "#response2" "css_element"
    And I should not see "Not available unless: Your language is English (en)" in the "#response2" "css_element"

    Given I am on profile page for user "student1"
    And I click on "Preferences" "link" in the ".block_totara_user_profile_category_administration" "css_element"
    And I click on "Preferred language" "link"
    And I set the field "Preferred language" to "de"
    And I click on "Save changes" "button"

    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Making login_setup request"
    And I set the field "username" to "student1"
    And I set the field "password" to "student1"
    When I click on "Submit Credentials 1" "button"
    Then I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    When I set the field "jsondata2" to "{\"operationName\": \"totara_mobile_course\",\"variables\": {\"courseid\": 2}}"
    And I click on "Submit Request 2" "button"
    And I should not see "Coding error detected" in the "#response2" "css_element"
    Then I should see "\"fullname\": \"Course 1\"" in the "#response2" "css_element"
    And I should see "Nicht verfügbar, es sei denn: Ihre Sprache ist English" in the "#response2" "css_element"
    And I should not see "Nicht verfügbar, es sei denn: Ihre Sprache ist Deutsch" in the "#response2" "css_element"
