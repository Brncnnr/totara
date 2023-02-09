@totara @totara_mobile @mod_scorm @javascript
Feature: Test various aspects of the mod_scorm_save_offline_attempts mutation via mobile device emulator

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
      | Name                                 | Golf |
      | Allow offline attempts in mobile app | Yes  |
      | Completion tracking                  | 2    |
      | completionscoredisabled              | 0    |
      | completionscorerequired              | 80   |
    And I upload "mod/scorm/tests/packages/singlescobasic.zip" file to "Package file" filemanager
    And I click on "Save and display" "button"

  Scenario: Test ability to save a single offline attempt
    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Making login_setup request"
    And I set the field "username" to "student1"
    And I set the field "password" to "student1"
    When I click on "Submit Credentials 1" "button"
    Then I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    When I set the field "jsondata2" to multiline:
    """
    {
      "operationName": "mod_scorm_save_offline_attempts",
      "variables": {
        "scormid": 1, "attempts": [
          {
            "timestarted": 1587084075,
            "tracks": [
              { "identifier": "item_1", "element": "cmi.core.lesson_status", "value": "failed", "timemodified": 1587084095 },
              { "identifier": "item_1", "element": "cmi.core.score.raw", "value": "25.0", "timemodified": 1587084095 }
            ]
          }
        ]
      }
    }
    """
    And I click on "Submit Request 2" "button"
    Then I should not see "Coding error detected" in the "#response2" "css_element"
    And I should see "\"attempts_current\": 2" in the "#response2" "css_element"
    And I should see "\"completionstatus\": \"incomplete\"" in the "#response2" "css_element"
    And I should see "\"gradepercentage\": 25" in the "#response2" "css_element"

  Scenario: Test ability to save multiple offline attempts at once
    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Making login_setup request"
    And I set the field "username" to "student1"
    And I set the field "password" to "student1"
    When I click on "Submit Credentials 1" "button"
    Then I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    When I set the field "jsondata2" to multiline:
    """
    {
      "operationName": "mod_scorm_save_offline_attempts",
      "variables": {
        "scormid": 1,
        "attempts": [
          {
            "timestarted": 1587084075,
            "tracks": [
              { "identifier": "item_1", "element": "cmi.core.lesson_status", "value": "failed", "timemodified": 1587084095 },
              { "identifier": "item_1", "element": "cmi.core.score.raw", "value": "25.0", "timemodified": 1587084095 }
            ]
          },
          {
            "timestarted": 1587084096,
            "tracks": [
              { "identifier": "item_1", "element": "cmi.core.lesson_status", "value": "passed", "timemodified": 1587084126 },
              { "identifier": "item_1", "element": "cmi.core.score.raw", "value": "95.0", "timemodified": 1587084126 }
            ]
          }
        ]
      }
    }
    """
    And I click on "Submit Request 2" "button"
    Then I should not see "Coding error detected" in the "#response2" "css_element"
    And I should see "\"attempts_current\": 3" in the "#response2" "css_element"
    And I should see "\"completionstatus\": \"complete\"" in the "#response2" "css_element"
    And I should see "\"gradepercentage\": 95" in the "#response2" "css_element"

  Scenario: Test ability to save attempts when grading method is set to Learning objects
    And I am on "Course 1" course homepage
    And I add a "SCORM package" to section "2"
    And I set the following fields to these values:
      | Name                                 | Golf2 |
      | Grading method                       | 0     |
      | Allow offline attempts in mobile app | Yes   |
      | Completion tracking                  | 2     |
      | completionscoredisabled              | 0     |
      | completionscorerequired              | 4     |
    And I upload "mod/scorm/tests/packages/multisco_w_status_no_raw_score.zip" file to "Package file" filemanager
    And I click on "Save and display" "button"
    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Making login_setup request"
    And I set the field "username" to "student1"
    And I set the field "password" to "student1"
    When I click on "Submit Credentials 1" "button"
    Then I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    When I set the field "jsondata2" to multiline:
    """
    {
      "operationName": "mod_scorm_save_offline_attempts",
      "variables": {
        "scormid": 2,
        "attempts": [
          {
            "timestarted": 1587084075,
            "tracks": [
              {
                "identifier": "playing_quiz_item",
                "element": "cmi.core.lesson_status",
                "value": "completed",
                "timemodified": 1587084095
              },
              {
                "identifier": "etiquette_quiz_item",
                "element": "cmi.core.lesson_status",
                "value": "completed",
                "timemodified": 1587084095
              },
              {
                "identifier": "handicapping_quiz_item",
                "element": "cmi.core.lesson_status",
                "value": "completed",
                "timemodified": 1587084095
              }
            ]
          }
        ]
      }
    }
    """
    And I click on "Submit Request 2" "button"
    Then I should not see "Coding error detected" in the "#response2" "css_element"
    And I should see "true" in the "#response2" "css_element"
    And I should see "\"attempts_current\": 2" in the "#response2" "css_element"
    And I should see "\"completionstatus\": \"incomplete\"" in the "#response2" "css_element"
    And I should see "\"gradefinal\": 3" in the "#response2" "css_element"
    When I set the field "jsondata3" to multiline:
    """
    {
      "operationName": "mod_scorm_save_offline_attempts",
      "variables": {
        "scormid": 2,
        "attempts": [
          {
            "timestarted": 1587084096,
            "tracks": [
              {
                "identifier": "playing_quiz_item",
                "element": "cmi.core.lesson_status",
                "value": "completed",
                "timemodified": 1587084095
              },
              {
                "identifier": "etiquette_quiz_item",
                "element": "cmi.core.lesson_status",
                "value": "completed",
                "timemodified": 1587084095
              },
              {
                "identifier": "handicapping_quiz_item",
                "element": "cmi.core.lesson_status",
                "value": "completed",
                "timemodified": 1587084095
              },
              {
                "identifier": "havingfun_quiz_item",
                "element": "cmi.core.lesson_status",
                "value": "completed",
                "timemodified": 1587084125
              }
            ]
          }
        ]
      }
    }
    """
    And I click on "Submit Request 3" "button"
    Then I should not see "Coding error detected" in the "#response3" "css_element"
    And I should see "\"completionstatus\": \"complete\"" in the "#response3" "css_element"
    And I should see "\"gradefinal\": 4" in the "#response3" "css_element"
