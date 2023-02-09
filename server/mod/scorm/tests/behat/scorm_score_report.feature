@mod @mod_scorm @totara_reportbuilder
Feature: View scorm reportbuilder report
  In order to let managers view a scorm report
  As a learner
  I need to complete the scorm activity in a course

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email |
      | manager1 | Manager r | 1 | manager1@example.com |
      | learner1 | Learner   | 1 | learner1@example.com |
      | learner2 | Learner   | 2 | learner2@example.com |
      | learner3 | Learner   | 3 | learner3@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | manager1 | C1 | editingteacher |
      | learner1 | C1 | student |
      | learner2 | C1 | student |
      | learner3 | C1 | student |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname          | shortname  | source |
      | Test Scorm Scores | scormscore | scorm  |
    And I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I follow "Test Scorm Scores"
    And I switch to "Columns" tab
    And I add the "SCO Min Score" column to the report
    And I add the "SCO Max Score" column to the report
    And I log out

  @javascript
  Scenario: View scorm grades in report
    Given I log in as "manager1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "SCORM package" to section "1"
    And I set the following fields to these values:
      | Name | Awesome SCORM package |
      | Description | Description |
    And I upload "mod/scorm/tests/packages/singlescobasic.zip" file to "Package file" filemanager
    And I click on "Save and display" "button"
    Then I should see "Awesome SCORM package"
    And I should see "Normal"
    And I should see "Preview"
    And I log out
    And I log in as "learner1"
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

    And I log in as "learner2"
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
    And I set the field "question_com.scorm.golfsamples.interactions.handicap_2_Text" to "11"
    And I set the field "question_com.scorm.golfsamples.interactions.handicap_3_Text" to "5"
    And I set the field "question_com.scorm.golfsamples.interactions.handicap_4_Text" to "2"
    # Fun
    And I click on "question_com.scorm.golfsamples.interactions.fun_1_True" "radio"
    And I click on "question_com.scorm.golfsamples.interactions.fun_2_True" "radio"
    And I click on "question_com.scorm.golfsamples.interactions.fun_3_False" "radio"
    # Submit and exit
    And I click on "Submit Answers" "button"
    And I switch to the main frame
    And I follow "Exit activity"
    And I log out

    And I log in as "learner3"
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
    And I click on "question_com.scorm.golfsamples.interactions.playing_1_0" "radio"
    And I click on "question_com.scorm.golfsamples.interactions.playing_2_2" "radio"
    And I set the field "question_com.scorm.golfsamples.interactions.playing_3_Text" to "16"
    And I click on "question_com.scorm.golfsamples.interactions.playing_4_False" "radio"
    And I set the field "question_com.scorm.golfsamples.interactions.playing_5_Text" to "53"
    # Etiquette
    And I click on "question_com.scorm.golfsamples.interactions.etiquette_1_1" "radio"
    And I click on "question_com.scorm.golfsamples.interactions.etiquette_2_False" "radio"
    And I click on "question_com.scorm.golfsamples.interactions.etiquette_3_1" "radio"
    # Handicap
    And I click on "question_com.scorm.golfsamples.interactions.handicap_1_1" "radio"
    And I set the field "question_com.scorm.golfsamples.interactions.handicap_2_Text" to "2"
    And I set the field "question_com.scorm.golfsamples.interactions.handicap_3_Text" to "2"
    And I set the field "question_com.scorm.golfsamples.interactions.handicap_4_Text" to "5"
    # Fun
    And I click on "question_com.scorm.golfsamples.interactions.fun_1_True" "radio"
    And I click on "question_com.scorm.golfsamples.interactions.fun_2_True" "radio"
    And I click on "question_com.scorm.golfsamples.interactions.fun_3_True" "radio"
    # Submit and exit
    And I click on "Submit Answers" "button"
    And I switch to the main frame
    And I follow "Exit activity"
    And I log out

    And I log in as "admin"

    When I navigate to my "Test Scorm Scores" report
    Then I should see "100.0" in the "sco_scoreraw" report column for "Learner 1"
    And I should see "0.0" in the "sco_scoremin" report column for "Learner 1"
    And I should see "100.0" in the "sco_scoremax" report column for "Learner 1"

    And I should see "73.0" in the "sco_scoreraw" report column for "Learner 2"
    And I should see "0.0" in the "sco_scoremin" report column for "Learner 2"
    And I should see "100.0" in the "sco_scoremax" report column for "Learner 2"

    And I should see "0.0" in the "sco_scoreraw" report column for "Learner 3"
    And I should see "0.0" in the "sco_scoremin" report column for "Learner 3"
    And I should see "100.0" in the "sco_scoremax" report column for "Learner 3"

