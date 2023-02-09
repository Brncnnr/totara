@totara @totara_completioneditor @core_grades @mod @javascript
Feature: Activity completed via RPL
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | student  | Stu       | Dent     | student@example.com |
      | teacher  | Tea       | Cher     | teacher@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | enablecompletion |
      | Course 1 | C1        | topics | 1                |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | student | C1     | student        |
      | teacher | C1     | editingteacher |

  @mod_assign @_file_upload
  Scenario: Ensure assignments completed via RPL are excluded from cascade update
    # Assignment  Criteria  RPL     Cascade
    # ----------  --------  ------  -------
    # 1           Yes       Yes/CC  No
    # 2           Yes       Yes/CE  No
    # 3           Yes       No      Yes
    # 4           No        -       Yes
    # 5           Yes       No      -

    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name     | Ass 1                                             |
      | Completion tracking | Show activity as complete when conditions are met |
      | completionusegrade  | 1                                                 |
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name     | Ass 2                                             |
      | Completion tracking | Show activity as complete when conditions are met |
      | completionusegrade  | 1                                                 |
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name     | Ass 3                                             |
      | Completion tracking | Show activity as complete when conditions are met |
      | completionusegrade  | 1                                                 |
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name     | Ass 4                                             |
      | Completion tracking | Show activity as complete when conditions are met |
      | completionusegrade  | 1                                                 |
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name     | Ass 5                                             |
      | Completion tracking | Show activity as complete when conditions are met |
      | completionusegrade  | 1                                                 |

    # Execute the adhoc tasks, as the grades need to be regraded.
    And I run all adhoc tasks
    And I am on "Course 1" course homepage with editing mode on

    When I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I set the field "Completion requirements" to "Course is complete when ALL conditions are met"
    And I set the field "Ass 1" to "1"
    And I set the field "Ass 2" to "1"
    And I set the field "Ass 3" to "1"
    And I set the field "Ass 5" to "1"
    And I press "Save changes"
    Then I should see "Course completion criteria changes have been saved"
    And I log out

    Given I log in as "teacher"
    And I am on "Course 1" course homepage
    And I navigate to "Course completion" node in "Course administration > Reports"

    # Complete via RPL with course completion report
    And I click on "//tr[contains(.,'Stu Dent')]/td[2]//a[@class='rpledit' and contains(.,'Not completed')]" "xpath_element"
    And I set the field "rplinput" to "lorem"
    And I press key "13" in the field "rplinput"

    And I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Course completion" node in "Course administration > Reports"

    # Complete via RPL with completion editor
    And I click on "Edit" "link" in the "Stu Dent" "table_row"
    And I click on "Edit" "link" in the "Ass 2" "table_row"
    And I set the field "Activity status" to "Completed"
    And I wait "2" seconds
    And I set the field "RPL" to "ipsum"
    And I set the field "Activity time completed" to "2019-08-01 00:00"
    And I click on "Done" "button" in the ".ui-datepicker" "css_element"
    And I wait "1" seconds
    And I click on "Save changes" "button"
    And I click on "Yes" "button"

    # Complete with completion editor
    And I click on "Edit" "link" in the "Ass 3" "table_row"
    And I set the field "Activity status" to "Completed"
    And I wait "2" seconds
    And I set the field "Activity time completed" to "2019-08-02 00:00"
    And I click on "Done" "button" in the ".ui-datepicker" "css_element"
    And I wait "1" seconds
    And I click on "Save changes" "button"
    And I click on "Yes" "button"

    # Complete with completion editor
    And I click on "Edit" "link" in the "Ass 4" "table_row"
    And I set the field "Activity status" to "Completed"
    And I wait "2" seconds
    And I set the field "Activity time completed" to "2019-08-03 00:00"
    And I click on "Done" "button" in the ".ui-datepicker" "css_element"
    And I wait "1" seconds
    And I click on "Save changes" "button"
    And I click on "Yes" "button"

    When I navigate to "Course completion" node in "Course administration > Reports"
    Then I should see "Completed" in the "//tr[contains(.,'Stu Dent')]/td[2]/span[@class='sr-only'][1]" "xpath_element"
    And I should see "Completed" in the "//tr[contains(.,'Stu Dent')]/td[2]/span[@class='sr-only'][2]" "xpath_element"
    And I should see "Completed" in the "//tr[contains(.,'Stu Dent')]/td[3]/span[@class='sr-only'][1]" "xpath_element"
    And I should see "Completed" in the "//tr[contains(.,'Stu Dent')]/td[3]/span[@class='sr-only'][2]" "xpath_element"
    And I should see "Completed" in the "//tr[contains(.,'Stu Dent')]/td[4]/span[@class='sr-only'][1]" "xpath_element"
    But I should see "Not completed" in the "//tr[contains(.,'Stu Dent')]/td[5]/span[@class='sr-only'][1]" "xpath_element"
    But I should see "Not completed" in the "//tr[contains(.,'Stu Dent')]/td[5]//a[@class='rpledit']" "xpath_element"
    When I click on "//tr[contains(.,'Stu Dent')]/td[2]//a[@title='Show RPL']" "xpath_element"
    Then I should see "lorem" in the "//tr[contains(.,'Stu Dent')]/td[2]" "xpath_element"
    When I click on "//tr[contains(.,'Stu Dent')]/td[3]//a[@title='Show RPL']" "xpath_element"
    Then I should see "ipsum" in the "//tr[contains(.,'Stu Dent')]/td[3]" "xpath_element"
    But "//tr[contains(.,'Stu Dent')]/td[4]//a[@title='Show RPL']" "xpath_element" should not exist
    But "//tr[contains(.,'Stu Dent')]/td[5]//a[@title='Show RPL']" "xpath_element" should not exist
    And I log out

    And I log in as "student"
    And I am on "Course 1" course homepage
    And I should see "Completed: Ass 1"
    And I should see "Completed: Ass 2"
    And I should see "Completed: Ass 3"
    And I should see "Completed: Ass 4"
    But I should see "Not completed: Ass 5"
    And I follow "Ass 5"
    When I press "Add submission"
    And I upload "lib/tests/fixtures/empty.txt" file to "File submissions" filemanager
    And I press "Save changes"
    Then I should see "Submitted"
    And I am on "Course 1" course homepage
    And I should see "Completed: Ass 1"
    And I should see "Completed: Ass 2"
    But I should see "Not completed: Ass 3"
    But I should see "Not completed: Ass 4"
    But I should see "Not completed: Ass 5"
    And I log out

    And I log in as "teacher"
    And I am on "Course 1" course homepage
    And I navigate to "Course completion" node in "Course administration > Reports"
    Then I should see "Completed" in the "//tr[contains(.,'Stu Dent')]/td[2]/span[@class='sr-only'][1]" "xpath_element"
    And I should see "Completed" in the "//tr[contains(.,'Stu Dent')]/td[2]/span[@class='sr-only'][2]" "xpath_element"
    And I should see "Completed" in the "//tr[contains(.,'Stu Dent')]/td[3]/span[@class='sr-only'][1]" "xpath_element"
    And I should see "Completed" in the "//tr[contains(.,'Stu Dent')]/td[3]/span[@class='sr-only'][2]" "xpath_element"
    But I should see "Not completed" in the "//tr[contains(.,'Stu Dent')]/td[4]/span[@class='sr-only'][1]" "xpath_element"
    But I should see "Not completed" in the "//tr[contains(.,'Stu Dent')]/td[5]/span[@class='sr-only'][1]" "xpath_element"
    But I should see "Not completed" in the "//tr[contains(.,'Stu Dent')]/td[5]//a[@class='rpledit']" "xpath_element"
    When I click on "//tr[contains(.,'Stu Dent')]/td[2]//a[@title='Show RPL']" "xpath_element"
    Then I should see "lorem" in the "//tr[contains(.,'Stu Dent')]/td[2]" "xpath_element"
    When I click on "//tr[contains(.,'Stu Dent')]/td[3]//a[@title='Show RPL']" "xpath_element"
    Then I should see "ipsum" in the "//tr[contains(.,'Stu Dent')]/td[3]" "xpath_element"
    But "//tr[contains(.,'Stu Dent')]/td[4]//a[@title='Show RPL']" "xpath_element" should not exist
    But "//tr[contains(.,'Stu Dent')]/td[5]//a[@title='Show RPL']" "xpath_element" should not exist
    And I log out

  @mod_facetoface
  Scenario: Ensure seminar completed via RPL does not update its activity completion status
    # Seminar  Criteria  RPL     Cascade
    # -------  --------  ------  -------
    # 1        Yes       Yes/CC  No
    # 2        Yes       Yes/CE  No
    # 3        Yes       No      Yes
    # 4        No        -       Yes
    # 5        Yes       No      -

    Given the following "seminars" exist in "mod_facetoface" plugin:
      | name  | course |
      | Sem 1 | C1     |
      | Sem 2 | C1     |
      | Sem 3 | C1     |
      | Sem 4 | C1     |
      | Sem 5 | C1     |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface | details |
      | Sem 1      | Event 1 |
      | Sem 2      | Event 2 |
      | Sem 3      | Event 3 |
      | Sem 4      | Event 4 |
      | Sem 5      | Event 5 |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start  | finish            |
      | Event 1      | -1 day | -1 day +30 minute |
      | Event 2      | -2 day | -2 day +30 minute |
      | Event 3      | -3 day | -3 day +30 minute |
      | Event 4      | -4 day | -4 day +30 minute |
      | Event 5      | -5 day | -5 day +30 minute |
    # To test cascade update, let's take attendance before partying on completion status
    And the following "seminar signups" exist in "mod_facetoface" plugin:
      | user    | eventdetails | status             |
      | student | Event 1      | partially_attended |
      | student | Event 2      | partially_attended |
      | student | Event 3      | partially_attended |
      | student | Event 4      | partially_attended |
      | student | Event 5      | partially_attended |

    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I click on ".toggle-display" "css_element" in the "Sem 1" activity
    And I click on "Edit settings" "link" in the "Sem 1" activity
    And I set the following fields to these values:
      | Completion tracking           | Show activity as complete when conditions are met |
      | completionstatusrequired[100] | 0                                                 |
      | Require grade                 | Yes, passing grade                                |
      | Passing grade                 | 100                                               |
    And I click on "Save and return to course" "button"
    And I click on ".toggle-display" "css_element" in the "Sem 2" activity
    And I click on "Edit settings" "link" in the "Sem 2" activity
    And I set the following fields to these values:
      | Completion tracking           | Show activity as complete when conditions are met |
      | completionstatusrequired[100] | 0                                                 |
      | Require grade                 | Yes, passing grade                                |
      | Passing grade                 | 100                                               |
    And I click on "Save and return to course" "button"
    And I click on ".toggle-display" "css_element" in the "Sem 3" activity
    And I click on "Edit settings" "link" in the "Sem 3" activity
    And I set the following fields to these values:
      | Completion tracking           | Show activity as complete when conditions are met |
      | completionstatusrequired[100] | 0                                                 |
      | Require grade                 | Yes, passing grade                                |
      | Passing grade                 | 100                                               |
    And I click on "Save and return to course" "button"
    And I click on ".toggle-display" "css_element" in the "Sem 4" activity
    And I click on "Edit settings" "link" in the "Sem 4" activity
    And I set the following fields to these values:
      | Completion tracking           | Show activity as complete when conditions are met |
      | completionstatusrequired[100] | 0                                                 |
      | Require grade                 | Yes, passing grade                                |
      | Passing grade                 | 100                                               |
    And I click on "Save and return to course" "button"
    And I click on ".toggle-display" "css_element" in the "Sem 5" activity
    And I click on "Edit settings" "link" in the "Sem 5" activity
    And I set the following fields to these values:
      | Completion tracking           | Show activity as complete when conditions are met |
      | completionstatusrequired[100] | 0                                                 |
      | Require grade                 | Yes, passing grade                                |
      | Passing grade                 | 100                                               |
    And I click on "Save and return to course" "button"

    # Execute the adhoc tasks, as the grades need to be regraded.
    And I run all adhoc tasks
    And I am on "Course 1" course homepage with editing mode on

    When I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I set the field "Completion requirements" to "Course is complete when ALL conditions are met"
    And I set the field "Sem 1" to "1"
    And I set the field "Sem 2" to "1"
    And I set the field "Sem 3" to "1"
    And I set the field "Sem 5" to "1"
    And I press "Save changes"
    Then I should see "Course completion criteria changes have been saved"

    And I navigate to "Course completion" node in "Course administration > Reports"

    # Complete via RPL with course completion report
    And I click on "//tr[contains(.,'Stu Dent')]/td[2]//a[@class='rpledit' and contains(.,'Not completed')]" "xpath_element"
    And I set the field "rplinput" to "lorem"
    And I press key "13" in the field "rplinput"

    # Complete via RPL with completion editor
    And I click on "Edit" "link" in the "Stu Dent" "table_row"
    And I click on "Edit" "link" in the "Sem 2" "table_row"
    And I set the field "Activity status" to "Completed"
    And I wait "2" seconds
    And I set the field "RPL" to "ipsum"
    And I set the field "Activity time completed" to "2019-08-01 00:00"
    And I click on "Done" "button" in the ".ui-datepicker" "css_element"
    And I wait "1" seconds
    And I click on "Save changes" "button"
    And I click on "Yes" "button"

    # Complete with completion editor
    And I click on "Edit" "link" in the "Sem 3" "table_row"
    And I set the field "Activity status" to "Completed"
    And I wait "2" seconds
    And I set the field "Activity time completed" to "2019-08-02 00:00"
    And I click on "Done" "button" in the ".ui-datepicker" "css_element"
    And I wait "1" seconds
    And I click on "Save changes" "button"
    And I click on "Yes" "button"

    # Complete with completion editor
    And I click on "Edit" "link" in the "Sem 4" "table_row"
    And I set the field "Activity status" to "Completed"
    And I wait "2" seconds
    And I set the field "Activity time completed" to "2019-08-03 00:00"
    And I click on "Done" "button" in the ".ui-datepicker" "css_element"
    And I wait "1" seconds
    And I click on "Save changes" "button"
    And I click on "Yes" "button"

    When I navigate to "Course completion" node in "Course administration > Reports"
    Then I should see "Completed" in the "//tr[contains(.,'Stu Dent')]/td[2]/span[@class='sr-only'][1]" "xpath_element"
    And I should see "Completed" in the "//tr[contains(.,'Stu Dent')]/td[2]/span[@class='sr-only'][2]" "xpath_element"
    And I should see "Completed" in the "//tr[contains(.,'Stu Dent')]/td[3]/span[@class='sr-only'][1]" "xpath_element"
    And I should see "Completed" in the "//tr[contains(.,'Stu Dent')]/td[3]/span[@class='sr-only'][2]" "xpath_element"
    And I should see "Completed" in the "//tr[contains(.,'Stu Dent')]/td[4]/span[@class='sr-only'][1]" "xpath_element"
    When I click on "//tr[contains(.,'Stu Dent')]/td[2]//a[@title='Show RPL']" "xpath_element"
    Then I should see "lorem" in the "//tr[contains(.,'Stu Dent')]/td[2]" "xpath_element"
    When I click on "//tr[contains(.,'Stu Dent')]/td[3]//a[@title='Show RPL']" "xpath_element"
    Then I should see "ipsum" in the "//tr[contains(.,'Stu Dent')]/td[3]" "xpath_element"
    But "//tr[contains(.,'Stu Dent')]/td[4]//a[@title='Show RPL']" "xpath_element" should not exist
    And I log out

    And I log in as "student"
    And I am on "Course 1" course homepage
    And I should see "Completed: Sem 1"
    And I should see "Completed: Sem 2"
    And I should see "Completed: Sem 3"
    And I should see "Completed: Sem 4"
    But I should see "Not completed: Sem 5"
    And I log out

    And I log in as "teacher"
    And I am on "Course 1" course homepage

    # Make sure update to seminar #5 does not cascade update activities completed via RPL
    When I follow "Sem 5"
    And I click on the seminar event action "Attendees" in row "#1"
    And I switch to "Take attendance" tab
    And I set the field "Stu Dent's attendance" to "No show"
    And I press "Save attendance"
    Then I should see "Successfully updated attendance"

    When I navigate to "Course completion" node in "Course administration > Reports"
    Then I should see "Completed" in the "//tr[contains(.,'Stu Dent')]/td[2]/span[@class='sr-only'][1]" "xpath_element"
    And I should see "Completed" in the "//tr[contains(.,'Stu Dent')]/td[2]/span[@class='sr-only'][2]" "xpath_element"
    And I should see "Completed" in the "//tr[contains(.,'Stu Dent')]/td[3]/span[@class='sr-only'][1]" "xpath_element"
    And I should see "Completed" in the "//tr[contains(.,'Stu Dent')]/td[3]/span[@class='sr-only'][2]" "xpath_element"
    But I should see "Not completed" in the "//tr[contains(.,'Stu Dent')]/td[4]/span[@class='sr-only'][1]" "xpath_element"
    When I click on "//tr[contains(.,'Stu Dent')]/td[2]//a[@title='Show RPL']" "xpath_element"
    Then I should see "lorem" in the "//tr[contains(.,'Stu Dent')]/td[2]" "xpath_element"
    When I click on "//tr[contains(.,'Stu Dent')]/td[3]//a[@title='Show RPL']" "xpath_element"
    Then I should see "ipsum" in the "//tr[contains(.,'Stu Dent')]/td[3]" "xpath_element"
    But "//tr[contains(.,'Stu Dent')]/td[4]//a[@title='Show RPL']" "xpath_element" should not exist
    And I press the "back" button in the browser

    When I follow "Sem 4"
    And I click on the seminar event action "Attendees" in row "#1"
    And I switch to "Take attendance" tab
    And I set the field "Stu Dent's attendance" to "No show"
    And I press "Save attendance"
    Then I should see "Successfully updated attendance"

    When I follow "Sem 3"
    And I click on the seminar event action "Attendees" in row "#1"
    And I switch to "Take attendance" tab
    And I set the field "Stu Dent's attendance" to "No show"
    And I press "Save attendance"
    Then I should see "Successfully updated attendance"

    When I follow "Sem 2"
    And I click on the seminar event action "Attendees" in row "#1"
    And I switch to "Take attendance" tab
    And I set the field "Stu Dent's attendance" to "No show"
    And I press "Save attendance"
    Then I should see "Successfully updated attendance"

    When I follow "Sem 1"
    And I click on the seminar event action "Attendees" in row "#1"
    And I switch to "Take attendance" tab
    And I set the field "Stu Dent's attendance" to "No show"
    And I press "Save attendance"
    Then I should see "Successfully updated attendance"

    And I log out
    And I log in as "student"
    And I am on "Course 1" course homepage
    And I should see "Completed: Sem 1"
    And I should see "Completed: Sem 2"
    But I should see "Not completed: Sem 3"
    But I should see "Not completed: Sem 4"
    But I should see "Not completed: Sem 5"

    And I log out
    And I log in as "teacher"
    And I am on "Course 1" course homepage

    When I navigate to "Course completion" node in "Course administration > Reports"
    Then I should see "Completed" in the "//tr[contains(.,'Stu Dent')]/td[2]/span[@class='sr-only'][1]" "xpath_element"
    And I should see "Completed" in the "//tr[contains(.,'Stu Dent')]/td[2]/span[@class='sr-only'][2]" "xpath_element"
    And I should see "Completed" in the "//tr[contains(.,'Stu Dent')]/td[3]/span[@class='sr-only'][1]" "xpath_element"
    And I should see "Completed" in the "//tr[contains(.,'Stu Dent')]/td[3]/span[@class='sr-only'][2]" "xpath_element"
    But I should see "Not completed" in the "//tr[contains(.,'Stu Dent')]/td[4]/span[@class='sr-only'][1]" "xpath_element"
    When I click on "//tr[contains(.,'Stu Dent')]/td[2]//a[@title='Show RPL']" "xpath_element"
    Then I should see "lorem" in the "//tr[contains(.,'Stu Dent')]/td[2]" "xpath_element"
    When I click on "//tr[contains(.,'Stu Dent')]/td[3]//a[@title='Show RPL']" "xpath_element"
    Then I should see "ipsum" in the "//tr[contains(.,'Stu Dent')]/td[3]" "xpath_element"
    But "//tr[contains(.,'Stu Dent')]/td[4]//a[@title='Show RPL']" "xpath_element" should not exist
