@totara @totara_mobile @javascript
Feature: Confirm that the mobile my current learning query works as expected

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
      | Course 2 | C2        | 0        | 1                |
      | Course 3 | C3        | 0        | 1                |
      | Course 4 | C4        | 0        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
      | student1 | C2     | student |
    And the following "programs" exist in "totara_program" plugin:
      | fullname  | shortname | summary      |
      | Program 1 | program1  | Program HTML |
    And the following "program assignments" exist in "totara_program" plugin:
      | user     | program  |
      | student1 | program1 |
    And the following "certifications" exist in "totara_program" plugin:
      | fullname | shortname | summary   |
      | Cert 1   | cert1     | Cert HTML |
    And the following "program assignments" exist in "totara_program" plugin:
      | user     | program |
      | student1 | cert1   |
    When I log in as "admin"
    And I navigate to "Plugins > Mobile > Mobile settings" in site administration
    And I set the following fields to these values:
      | Enable mobile app | 1 |
    And I click on "Save changes" "button"

  Scenario: Check totara_mobile_current_learning query works for courses
    And I log out
    And I log in as "student1"
    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Initialised"
    And I set the field "username" to "student1"
    And I set the field "password" to "student1"
    When I click on "Submit Credentials 1" "button"
    Then I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    When I set the field "jsondata2" to "{\"operationName\": \"totara_mobile_current_learning\",\"variables\": {}}"
    And I click on "Submit Request 2" "button"
    Then I should not see "Coding error detected" in the "#response2" "css_element"
    And I should see "\"currentLearning\": [" in the "#response2" "css_element"
    And I should see "\"id\": \"course_" in the "#response2" "css_element"
    And I should see "\"itemtype\": \"course\"" in the "#response2" "css_element"
    And I should see "\"itemcomponent\": \"core_course\"" in the "#response2" "css_element"
    And I should see "\"shortname\": \"C1\"" in the "#response2" "css_element"
    And I should see "\"fullname\": \"Course 1\"," in the "#response2" "css_element"
    And I should see "\"summary\": \"Test course 1" in the "#response2" "css_element"
    And I should see "\"summaryFormat\": \"HTML\"" in the "#response2" "css_element"
    And I should see "\"progress\": 0" in the "#response2" "css_element"
    And I should see "\"urlView\": \"http" in the "#response2" "css_element"
    And I should see "\"duedate\": null" in the "#response2" "css_element"
    And I should see "\"duedateState\": null" in the "#response2" "css_element"
    And I should see "\"native\": false" in the "#response2" "css_element"
    And I should see "\"imageSrc\": \"http" in the "#response2" "css_element"
    And I should not see "totara/mobile/pluginfile.php" in the "#response2" "css_element"
    And I should see "\"__typename\": \"mobile_currentlearning_item\"" in the "#response2" "css_element"
    And I should see "\"fullname\": \"Course 2\"," in the "#response2" "css_element"
    And I should see "/course_defaultimage" in the "#response2" "css_element"
    And I should see "\"native\": false," in the "#response2" "css_element"
    And I should not see "Program 1" in the "#response2" "css_element"
    And I should not see "Cert 1" in the "#response2" "css_element"

  Scenario: Check totara_mobile_current_learning query works for programs and certifications
    Given I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Program 1" "link"
    And I click on "Edit program details" "button"
    And I switch to "Content" tab
    And I click on "addcontent_ce" "button" in the "#edit-program-content" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 2" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 3" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I wait "1" seconds
    And I press "Save changes"
    And I click on "Save all changes" "button"
    And I switch to "Assignments" tab
    And I click on "Set due date" "link" in the "Student 1" "table_row"
    And I set the following fields to these values:
      | completiontime | 12/10/2030 |
    And I click on "Set fixed completion date" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds
    Then I should see "12 Oct 2030" in the "Student 1" "table_row"
    And I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I click on "Miscellaneous" "link"
    And I click on "Cert 1" "link"
    And I click on "Edit certification details" "button"
    And I switch to "Content" tab
    And I click on "addcontent_ce" "button" in the "#programcontent_ce" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 4" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I wait "2" seconds
    And I click on "Save changes" "button"
    And I click on "Save all changes" "button"
    And I log out
    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Initialised"
    And I set the field "username" to "student1"
    And I set the field "password" to "student1"
    When I click on "Submit Credentials 1" "button"
    Then I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    When I set the field "jsondata2" to "{\"operationName\": \"totara_mobile_current_learning\",\"variables\": {}}"
    And I click on "Submit Request 2" "button"
    Then I should not see "Coding error detected" in the "#response2" "css_element"
    And I should see "\"currentLearning\": [" in the "#response2" "css_element"
    And I should see "\"fullname\": \"Course 1\"," in the "#response2" "css_element"
    And I should not see "totara/mobile/pluginfile.php" in the "#response2" "css_element"
    And I should see "\"__typename\": \"mobile_currentlearning_item\"" in the "#response2" "css_element"
    And I should not see "\"fullname\": \"Course 2\"," in the "#response2" "css_element"
    And I should see "/course_defaultimage" in the "#response2" "css_element"
    And I should see "\"id\": \"program_" in the "#response2" "css_element"
    And I should see "\"itemtype\": \"program\"" in the "#response2" "css_element"
    And I should see "\"itemcomponent\": \"totara_program\"" in the "#response2" "css_element"
    And I should see "\"shortname\": \"program1\"" in the "#response2" "css_element"
    And I should see "\"fullname\": \"Program 1\"," in the "#response2" "css_element"
    And I should see "\"summary\": \"Program HTML\"" in the "#response2" "css_element"
    And I should see "\"summaryFormat\": \"HTML\"" in the "#response2" "css_element"
    And I should see "\"id\": \"certification_" in the "#response2" "css_element"
    And I should see "\"itemtype\": \"certification\"" in the "#response2" "css_element"
    And I should see "\"itemcomponent\": \"totara_certification\"" in the "#response2" "css_element"
    And I should see "\"shortname\": \"cert1\"" in the "#response2" "css_element"
    And I should see "\"fullname\": \"Cert 1\"," in the "#response2" "css_element"
    And I should see "\"summary\": \"Cert HTML\"" in the "#response2" "css_element"
    And I should see "/defaultimage" in the "#response2" "css_element"
    And I should see "\"duedate\": \"2030-10-12" in the "#response2" "css_element"
    And I should see "\"duedateState\": \"info\"," in the "#response2" "css_element"
    And I should see "\"native\": false," in the "#response2" "css_element"
    And I should see "\"native\": true," in the "#response2" "css_element"

  Scenario: Check totara_mobile_current_learning query works for items with JSON content
    Given I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Program 1" "link"
    And I click on "Edit program details" "button"
    And I switch to "Details" tab
    And I set the field with xpath "//select[@name='summary_editor[format]']" to "5"
    And I click on "Save changes" "button"
    And I click on "Save changes" "button"
    And I switch to "Content" tab
    And I click on "addcontent_ce" "button" in the "#edit-program-content" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 2" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I wait "1" seconds
    And I press "Save changes"
    And I click on "Save all changes" "button"
    And I switch to "Assignments" tab
    And I click on "Set due date" "link" in the "Student 1" "table_row"
    And I set the following fields to these values:
      | completiontime | 12/10/2030 |
    And I click on "Set fixed completion date" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds
    Then I should see "12 Oct 2030" in the "Student 1" "table_row"
    And I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I click on "Miscellaneous" "link"
    And I click on "Cert 1" "link"
    And I click on "Edit certification details" "button"
    And I switch to "Details" tab
    And I set the field with xpath "//select[@name='summary_editor[format]']" to "5"
    And I click on "Save changes" "button"
    And I click on "Save changes" "button"
    And I switch to "Content" tab
    And I click on "addcontent_ce" "button" in the "#programcontent_ce" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 4" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I wait "2" seconds
    And I click on "Save changes" "button"
    And I click on "Save all changes" "button"
    And I am on "Course 1" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the field with xpath "//select[@name='summary_editor[format]']" to "5"
    And I click on "Save and display" "button"
    And I click on "Save and display" "button"
    And I log out
    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Initialised"
    And I set the field "username" to "student1"
    And I set the field "password" to "student1"
    When I click on "Submit Credentials 1" "button"
    Then I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    When I set the field "jsondata2" to "{\"operationName\": \"totara_mobile_current_learning\",\"variables\": {}}"
    And I click on "Submit Request 2" "button"
    Then I should not see "Coding error detected" in the "#response2" "css_element"
    And I should see "\"summaryFormat\": \"JSON_EDITOR\"" in the "#response2" "css_element"
    And I should not see "\"summaryFormat\": \"HTML\"" in the "#response2" "css_element"
    And I should see "\"summary\": \"{\\"type" in the "#response2" "css_element"
    And I should not see "\"summary\": \"Test course 1" in the "#response2" "css_element"
    And I should not see "\"summary\": \"Program HTML\"" in the "#response2" "css_element"
    And I should not see "\"summary\": \"Cert HTML\"" in the "#response2" "css_element"

  Scenario: Check totara_mobile_current_learning query works as expected for learning items with custom default and custom images
    And I navigate to "Course default settings" node in "Site administration >  Courses"
    And I upload "totara/program/tests/fixtures/leaves-blue.png" file to "" filemanager
    And I press "Save changes"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I follow "Set default image for all programs"
    And I upload "totara/program/tests/fixtures/leaves-green.png" file to "" filemanager
    And I press "Save changes"
    And I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I follow "Set default image for all certifications"
    And I upload "totara/program/tests/fixtures/leaves-green.png" file to "" filemanager
    And I press "Save changes"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I follow "Miscellaneous"
    And I click on "Settings" "link" in the "Program 1" "table_row"
    And I switch to "Content" tab
    And I click on "addcontent_ce" "button" in the "#edit-program-content" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 2" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 3" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I wait "1" seconds
    And I press "Save changes"
    And I click on "Save all changes" "button"
    And I switch to "Assignments" tab
    And I click on "Set due date" "link" in the "Student 1" "table_row"
    And I set the following fields to these values:
      | completiontime | 12/10/2030 |
    And I click on "Set fixed completion date" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds
    Then I should see "12 Oct 2030" in the "Student 1" "table_row"
    And I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I follow "Miscellaneous"
    And I click on "Settings" "link" in the "Cert 1" "table_row"
    And I switch to "Content" tab
    And I click on "addcontent_ce" "button" in the "#programcontent_ce" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 4" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I wait "2" seconds
    And I click on "Save changes" "button"
    And I click on "Save all changes" "button"
    And I log out
    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Initialised"
    And I set the field "username" to "student1"
    And I set the field "password" to "student1"
    When I click on "Submit Credentials 1" "button"
    Then I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    When I set the field "jsondata2" to "{\"operationName\": \"totara_mobile_current_learning\",\"variables\": {}}"
    And I click on "Submit Request 2" "button"
    Then I should not see "Coding error detected" in the "#response2" "css_element"
    And I should see "pluginfile.php" in the "#response2" "css_element"
    And I should see "defaultimage" in the "#response2" "css_element"
    And I should see "\"imageSrc\": \"http" in the "#response2" "css_element"
    # Now add custom images
    When I am on site homepage
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Edit settings"
    And I expand all fieldsets
    And I upload "totara/mobile/tests/fixtures/fruit.jpg" file to "Image" filemanager
    And I click on "Save and display" "button"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I follow "Miscellaneous"
    And I click on "Settings" "link" in the "Program 1" "table_row"
    And I switch to "Details" tab
    And I upload "totara/program/tests/fixtures/leaves-blue.png" file to "Image" filemanager
    And I press "Save changes"
    And I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I follow "Miscellaneous"
    And I click on "Settings" "link" in the "Cert 1" "table_row"
    And I switch to "Details" tab
    And I upload "totara/program/tests/fixtures/leaves-green.png" file to "Image" filemanager
    And I press "Save changes"
    And I log out
    And I log in as "student1"
    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Initialised"
    And I set the field "username" to "student1"
    And I set the field "password" to "student1"
    When I click on "Submit Credentials 1" "button"
    Then I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    When I set the field "jsondata2" to "{\"operationName\": \"totara_mobile_current_learning\",\"variables\": {}}"
    And I click on "Submit Request 2" "button"
    Then I should not see "Coding error detected" in the "#response2" "css_element"
    And I click on "link0" "link" in the "#response2" "css_element"
    Then I should see "26) File request HTTP ok."
    And I should see "27) File received image/png"
    And I should see the mobile file response on line "28"
    And I click on "link1" "link" in the "#response2" "css_element"
    Then I should see "30) File request HTTP ok."
    And I should see "31) File received image/jpeg"
    And I should see the mobile file response on line "32"
    And I click on "link2" "link" in the "#response2" "css_element"
    Then I should see "34) File request HTTP ok."
    And I should see "35) File received image/png"
    And I should see the mobile file response on line "36"

  Scenario: Check totara_mobile_current_learning query works as expected with mobile native courses
    When I am on "Course 1" course homepage
    And I follow "Edit settings"
    And I expand all fieldsets
    And I set the following fields to these values:
      | Mobile-friendly course | Yes |
    And I click on "Save and display" "button"
    And I log out
    And I log in as "student1"
    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Initialised"
    And I set the field "username" to "student1"
    And I set the field "password" to "student1"
    When I click on "Submit Credentials 1" "button"
    Then I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    When I set the field "jsondata2" to "{\"operationName\": \"totara_mobile_current_learning\",\"variables\": {}}"
    And I click on "Submit Request 2" "button"
    Then I should not see "Coding error detected" in the "#response2" "css_element"
    And I should see "\"currentLearning\": [" in the "#response2" "css_element"
    And I should see "\"fullname\": \"Course 1\"," in the "#response2" "css_element"
    And I should see "\"native\": true," in the "#response2" "css_element"
