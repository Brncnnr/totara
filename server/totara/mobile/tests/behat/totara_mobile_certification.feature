@totara @totara_mobile @totara_certification @_file_upload @javascript
Feature: Test the totara_mobile_certification query

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
    And the following "certifications" exist in "totara_program" plugin:
      | fullname        | shortname | summary | endnote |
      | Certification 1 | cert1     | HTML    | LMTH    |
    And the following "program assignments" exist in "totara_program" plugin:
      | program | user     |
      | cert1   | student1 |
    When I log in as "admin"
    And I navigate to "Plugins > Mobile > Mobile settings" in site administration
    And I set the following fields to these values:
      | Enable mobile app | 1  |
    And I click on "Save changes" "button"

  Scenario: Test the query with a basic certification
    And I log out
    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Making login_setup request"
    And I set the field "username" to "student1"
    And I set the field "password" to "student1"
    When I click on "Submit Credentials 1" "button"
    Then I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    When I set the field "jsondata2" to "{\"operationName\": \"totara_mobile_certification\",\"variables\": {\"certificationid\": 1}}"
    And I click on "Submit Request 2" "button"
    Then I should not see "Coding error detected" in the "#response2" "css_element"
    And I should see "\"fullname\": \"Certification 1\"" in the "#response2" "css_element"
    And I should see "\"shortname\": \"cert1\"" in the "#response2" "css_element"
    And I should see "\"duedate\": null" in the "#response2" "css_element"
    And I should see "\"duedateState\": null" in the "#response2" "css_element"
    And I should see "\"summary\": \"HTML\"" in the "#response2" "css_element"
    And I should see "\"summaryformat\": \"HTML\"" in the "#response2" "css_element"
    And I should see "\"endnote\": \"LMTH\"" in the "#response2" "css_element"
    And I should see "\"endnoteformat\": \"HTML\"" in the "#response2" "css_element"
    And I should see "\"completion\": {" in the "#response2" "css_element"
    And I should see "\"currentCourseSets\": []" in the "#response2" "css_element"
    And I should see "\"countUnavailableSets\": 0" in the "#response2" "css_element"
    And I should see "\"courseSetHeader\": \"\"" in the "#response2" "css_element"
    And I should see "\"imageSrc\": \"\"" in the "#response2" "css_element"

  Scenario: Test the query with a certification that has JSON summary and endnote
    When I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I click on "Miscellaneous" "link"
    And I click on "Certification 1" "link"
    And I click on "Edit certification details" "button"
    And I click on "Details" "link"
    And I set the field with xpath "//select[@name='summary_editor[format]']" to "5"
    And I set the field with xpath "//select[@name='endnote_editor[format]']" to "5"
    And I click on "Save changes" "button"
    And I activate the weka editor with css "#uid-1"
    And I select the text "HTML" in the weka editor
    And I replace the selection with "JSON" in the weka editor
    Then I should not see "HTML" in the "#uid-1" "css_element"
    And I activate the weka editor with css "#uid-2"
    And I select the text "LMTH" in the weka editor
    And I replace the selection with "NOSJ" in the weka editor
    Then I should not see "LMTH" in the "#uid-2" "css_element"
    And I click on "Save changes" "button"
    And I log out
    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Making login_setup request"
    And I set the field "username" to "student1"
    And I set the field "password" to "student1"
    When I click on "Submit Credentials 1" "button"
    Then I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    When I set the field "jsondata2" to "{\"operationName\": \"totara_mobile_certification\",\"variables\": {\"certificationid\": 1}}"
    And I click on "Submit Request 2" "button"
    Then I should not see "Coding error detected" in the "#response2" "css_element"
    And I should see "\"fullname\": \"Certification 1\"" in the "#response2" "css_element"
    And I should see "\"summary\": \"{\\"type" in the "#response2" "css_element"
    And I should see "\"summaryformat\": \"JSON_EDITOR\"" in the "#response2" "css_element"
    And I should see "\"endnote\": \"{\\"type" in the "#response2" "css_element"
    And I should see "\"endnoteformat\": \"JSON_EDITOR\"" in the "#response2" "css_element"

  Scenario: Test the query with a certification that has an image
    When I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I click on "Miscellaneous" "link"
    And I click on "Certification 1" "link"
    And I click on "Edit certification details" "button"
    And I switch to "Details" tab
    And I expand all fieldsets
    And I upload "totara/program/tests/fixtures/leaves-blue.png" file to "Image" filemanager
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
    When I set the field "jsondata2" to "{\"operationName\": \"totara_mobile_certification\",\"variables\": {\"certificationid\": 1}}"
    And I click on "Submit Request 2" "button"
    And I should not see "Coding error detected" in the "#response2" "css_element"
    Then I should see "\"fullname\": \"Certification 1\"" in the "#response2" "css_element"
    And I should see "\"imageSrc\"" in the "#response2" "css_element"
    And I click on "link0" "link" in the "#response2" "css_element"
    Then I should see "26) File request HTTP ok."
    And I should see "27) File received image/png"
    And I should see the mobile file response on line "28"

  Scenario: Test the query with a certification that has a custom default image
    When I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I follow "Set default image for all certifications"
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
    When I set the field "jsondata2" to "{\"operationName\": \"totara_mobile_certification\",\"variables\": {\"certificationid\": 1}}"
    And I click on "Submit Request 2" "button"
    Then I should not see "Coding error detected" in the "#response2" "css_element"
    And I should see "\"fullname\": \"Certification 1\"" in the "#response2" "css_element"
    And I should see "\"imageSrc\": \"\"" in the "#response2" "css_element"

