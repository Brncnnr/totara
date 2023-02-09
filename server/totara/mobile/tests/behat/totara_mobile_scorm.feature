@totara @totara_mobile @mod_scorm @javascript
Feature: Test various aspects of the totara_mobile_scorm query

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | C1        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
    When I log in as "admin"
    And I navigate to "Plugins > Mobile > Mobile settings" in site administration
    And I set the following fields to these values:
      | Enable mobile app | 1  |
    And I click on "Save changes" "button"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "SCORM package" to section "1"
    And I set the following fields to these values:
      | Name | Awesome SCORM package |
      | Description | Description |
      | Allow offline attempts in mobile app | Yes |
    And I upload "mod/scorm/tests/packages/singlescobasic.zip" file to "Package file" filemanager
    And I click on "Save and display" "button"

  Scenario: Test basic query and download of package file
    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Making login_setup request"
    And I set the field "username" to "student1"
    And I set the field "password" to "student1"
    When I click on "Submit Credentials 1" "button"
    Then I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    When I set the field "jsondata2" to "{\"operationName\": \"totara_mobile_scorm\",\"variables\": {\"scormid\": 1}}"
    And I click on "Submit Request 2" "button"
    Then I should not see "Coding error detected" in the "#response2" "css_element"
    And I should see "\"name\": \"Awesome SCORM package\"" in the "#response2" "css_element"
    And I click on "link0" "link" in the "#response2" "css_element"
    Then I should see "26) File request HTTP ok."
    And I should see "27) File received application/x-forcedownload"
    And I should see the mobile file response on line "28"

  Scenario: Test current status query after an attempt is made
    Given I log out
    And I log in as "student1"
    # From mod/scorm/tests/behat/scorm_score_report.feature
    And I am on "Course 1" course homepage
    And I follow "Awesome SCORM package"
    And I should see "Normal"
    And I press "Enter"
    And I switch to "scorm_object" iframe
    And I switch to "contentFrame" iframe
    And I should see "Play of the game"
    And I switch to the main frame
    And I switch to "scorm_object" iframe
    #Par
    And I press "Next ->"
    #Scoring
    And I press "Next ->"
    #Other Scoring Systems
    And I press "Next ->"
    #The Rules of Golf
    And I press "Next ->"
    #Etiquette - Care For the Course
    And I press "Next ->"
    #Etiquette - Avoiding Distraction
    And I press "Next ->"
    #Etiquette - Playing the Game
    And I press "Next ->"
    #Handicapping
    And I press "Next ->"
    #Calculating a Handicap
    And I press "Next ->"
    #Calculating a Score
    And I press "Next ->"
    #Handicaping Example
    And I press "Next ->"
    #How to Have Fun Golfing
    And I press "Next ->"
    #How to Make Friends on the Golf Course
    And I press "Next ->"
    #How to Be Stylish on the Golf Course
    And I press "Next ->"
    #Knowledge Check
    And I press "Next ->"
    And I switch to "contentFrame" iframe
    # Playing
    And I click on "question_com.scorm.golfsamples.interactions.playing_1_1" "radio"
    And I click on "question_com.scorm.golfsamples.interactions.playing_2_3" "radio"
    And I set the field "question_com.scorm.golfsamples.interactions.playing_3_Text" to "18"
    And I click on "question_com.scorm.golfsamples.interactions.playing_4_True" "radio"
    And I set the field "question_com.scorm.golfsamples.interactions.playing_5_Text" to "3"
    # Etiquette
    And I click on "question_com.scorm.golfsamples.interactions.etiquette_1_2" "radio"
    And I click on "question_com.scorm.golfsamples.interactions.etiquette_2_True" "radio"
    And I click on "question_com.scorm.golfsamples.interactions.etiquette_3_0" "radio"
    # Handicap
    And I click on "question_com.scorm.golfsamples.interactions.handicap_1_2" "radio"
    And I set the field "question_com.scorm.golfsamples.interactions.handicap_2_Text" to "1"
    And I set the field "question_com.scorm.golfsamples.interactions.handicap_3_Text" to "0"
    And I set the field "question_com.scorm.golfsamples.interactions.handicap_4_Text" to "2"
    # Fun
    And I click on "question_com.scorm.golfsamples.interactions.fun_1_False" "radio"
    And I click on "question_com.scorm.golfsamples.interactions.fun_2_False" "radio"
    And I click on "question_com.scorm.golfsamples.interactions.fun_3_False" "radio"
    # Submit and exit
    And I click on "Submit Answers" "button"
    And I switch to the main frame
    And I follow "Exit activity"
    And I log out
    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Making login_setup request"
    And I set the field "username" to "student1"
    And I set the field "password" to "student1"
    When I click on "Submit Credentials 1" "button"
    Then I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    When I set the field "jsondata2" to "{\"operationName\": \"totara_mobile_scorm_current_status\",\"variables\": {\"scormid\": 1}}"
    And I click on "Submit Request 2" "button"
    And I should not see "Coding error detected" in the "#response2" "css_element"
    Then I should see "\"attemptsCurrent\": 1" in the "#response2" "css_element"
    And I should see "\"completionstatus\": \"complete\"" in the "#response2" "css_element"
    And I should see "\"gradefinal\": 100" in the "#response2" "css_element"
    And I should see "\"grademax\": 100" in the "#response2" "css_element"
    And I should see "\"gradepercentage\": 100" in the "#response2" "css_element"

  Scenario: Test webview settings override for SCORM player
    When I follow "Edit settings"
    And I set the following fields to these values:
      | Display package | 1 |
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
    When I set the field "jsondata2" to "{\"operationName\": \"totara_mobile_create_webview\",\"variables\": {\"url\": \"/mod/scorm/player.php?mode=normal&newattempt=on&cm=2&scoid=0\"}}"
    And I click on "Submit Request 2" "button"
    And I switch to "WebView" iframe
    Then I should not see "click here to return to the course"
    And I should not see "Awesome SCORM package"

  Scenario: Test webview settings override for SCORM player, new window (simple)
    When I follow "Edit settings"
    And I set the following fields to these values:
      | Display package | 2 |
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
    When I set the field "jsondata2" to "{\"operationName\": \"totara_mobile_create_webview\",\"variables\": {\"url\": \"/mod/scorm/player.php?mode=normal&newattempt=on&cm=2&scoid=0\"}}"
    And I click on "Submit Request 2" "button"
    When I switch to the main window
    And I switch to "WebView" iframe
    And I should not see "Awesome SCORM package"
    And I switch to "scorm_object" iframe
    Then I should not see "Your content is playing in another window."
    And I switch to "contentFrame" iframe
    And I should see "Play of the game"
