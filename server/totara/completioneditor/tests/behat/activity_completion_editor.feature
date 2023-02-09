@totara @totara_completioneditor @javascript
Feature: Activity completion records can be edited
  In order to see that activity completion records can be edited
  I need to use the course completion editor

  Background:
    Given I am on a totara site
    And I log in as "admin"
    And the following "users" exist:
      | username | firstname  | lastname  | email               |
      | user001  | FirstName1 | LastName1 | user001@example.com |
    And the following "courses" exist:
      | fullname   | shortname | format | enablecompletion |
      | Course One | course1   | topics | 1                |
    And the following "course enrolments" exist:
      | user    | course  | role    |
      | user001 | course1 | student |

  Scenario: Activity completion records based on timemodified and grades (Quiz) can be edited
    # Completion editor list of users.
    When I am on "Course One" course homepage with editing mode on
    And I add a "Quiz" to section "1" and I fill the form with:
      | Name        | Test quiz 1           |
      | Description | Test quiz description |
      | Completion tracking | Show activity as complete when conditions are met |
      | Require view        | 1                                                 |

    # Check the completion indicator is incomplete, and setup the cache.
    When I log out
    And I log in as "user001"
    And I am on "Course One" course homepage

    Then I should see "Not completed: Test quiz 1" in the ".autocompletion" "css_element"

    When I log out
    And I log in as "admin"
    And I am on "Course One" course homepage

    And I navigate to "Completion editor" node in "Course administration"
    Then I should see "FirstName1 LastName1"

    # Completion editor criteria and activities tab/list.
    And I click on "Edit course completion" "link" in the "FirstName1 LastName1" "table_row"
    And I switch to "Criteria and Activities" tab
    Then I should see "Course completion criteria"
    And I should see "No completion criteria set for this course"
    And I should see "Activity completion"
    And I should see "Test quiz 1"

    # Default data was created and is loaded correctly.
    When I follow "Edit"
    Then the field "Viewed" matches value "0"
    And the field "Activity status" matches value "Not completed"
    And "Activity time completed" Totara form field should not exist

    # Create course_modules_completion record.
    When I set the field "Viewed" to "1"
    And I set the "Activity status" Totara form field to "Completed"
    And I wait for "Activity time completed" Totara form field to be ready
    And I set the "Activity time completed" Totara form field to "2011-02-03 04:56"
    And I press "Save changes"
    Then I should see "Changing the completion record may lead to changes in course completions"
    When I click on "Yes" "button"
    Then I should see "Completion changes have been saved"
    When I switch to "Transactions" tab
    Then I should see "February 2011"
    And I should see "Module completion manually created"
    And I should see "Completion state: Complete (1)"
    And I should see "Viewed: Yes (1)"
    And I should see "Time completed: Not set (null)"
    And I should not see "Crit compl manually"
    When I switch to "Criteria and Activities" tab
    And I follow "Edit"
    Then the field "Viewed" matches value "1"
    And the field "Activity status" matches value "Completed"
    And the field "Activity time completed" matches value "2011-02-03T04:56:00"

    # Check the completion indicator is now complete, i.e. we invalidated the cache.
    When I log out
    And I log in as "user001"
    And I am on "Course One" course homepage

    Then I should see "Completed: Test quiz 1" in the ".autocompletion" "css_element"

    # Navigate back as admin
    When I log out
    And I log in as "admin"
    And I am on "Course One" course homepage
    And I navigate to "Completion editor" node in "Course administration"
    And I click on "Edit course completion" "link" in the "FirstName1 LastName1" "table_row"
    When I switch to "Criteria and Activities" tab
    And I follow "Edit"

    # Update course_modules_completion record.
    When I set the field "Viewed" to "0"
    And I set the "Activity status" Totara form field to "Completed (did not achieve pass grade)"
    And I wait for "Activity time completed" Totara form field to be ready
    And I set the "Activity time completed" Totara form field to "2027-07-08 16:34"
    And I press "Save changes"
    Then I should see "Changing the completion record may lead to changes in course completions"
    When I click on "Yes" "button"
    Then I should see "Completion changes have been saved"
    When I switch to "Transactions" tab
    And I should see "July 2027"
    And I should see "Module completion manually updated"
    And I should see "Completion state: Complete with failing grade (3)"
    And I should see "Viewed: No (0)"
    When I switch to "Criteria and Activities" tab
    And I follow "Edit"
    Then the field "Viewed" matches value "0"
    And the field "Activity status" matches value "Completed (did not achieve pass grade)"
    And the field "Activity time completed" matches value "2027-07-08T16:34:00"

    # Check the completion indicator is incomplete again, i.e. the cache has reset.
    When I log out
    And I log in as "user001"
    And I am on "Course One" course homepage

    Then I should see "Completed: Test quiz 1 (did not achieve pass grade)" in the ".autocompletion" "css_element"

  Scenario: Activity completion records based on timecompleted and grades (Seminar) can be edited
    # Completion editor list of users.
    When I am on "Course One" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar 1           |
      | Description | Test seminar description |
    And I navigate to "Completion editor" node in "Course administration"
    Then I should see "FirstName1 LastName1"

    # Completion editor criteria and activities tab/list.
    When I click on "Edit course completion" "link" in the "FirstName1 LastName1" "table_row"
    And I switch to "Criteria and Activities" tab
    Then I should see "Course completion criteria"
    And I should see "No completion criteria set for this course"
    And I should see "Activity completion"
    And I should see "Test seminar 1"

    # Default data was created and is loaded correctly.
    When I follow "Edit"
    Then the field "Viewed" matches value "0"
    And the field "Activity status" matches value "Not completed"
    And "Activity time completed" Totara form field should not exist

    # Create course_modules_completion record.
    When I set the field "Viewed" to "1"
    And I set the "Activity status" Totara form field to "Completed (achieved pass grade)"
    And I wait for "Activity time completed" Totara form field to be ready
    And I set the "Activity time completed" Totara form field to "2011-02-03 04:56"
    And I press "Save changes"
    Then I should see "Changing the completion record may lead to changes in course completions"
    When I click on "Yes" "button"
    Then I should see "Completion changes have been saved"
    When I switch to "Transactions" tab
    And I should see "February 2011"
    And I should see "Module completion manually created"
    And I should see "Completion state: Complete with passing grade (2)"
    And I should see "Viewed: Yes (1)"
    When I switch to "Criteria and Activities" tab
    And I follow "Edit"
    Then the field "Viewed" matches value "1"
    And the field "Activity status" matches value "Completed (achieved pass grade)"
    And the field "Activity time completed" matches value "2011-02-03T04:56:00"

    # Edit course_modules_completion record.
    When I set the field "Viewed" to "0"
    And I set the "Activity status" Totara form field to "Completed (did not achieve pass grade)"
    And I wait for "Activity time completed" Totara form field to be ready
    And I set the "Activity time completed" Totara form field to "2027-07-08 16:34"
    And I press "Save changes"
    Then I should see "Changing the completion record may lead to changes in course completions"
    When I click on "Yes" "button"
    Then I should see "Completion changes have been saved"
    When I switch to "Transactions" tab
    And I should see "July 2027"
    And I should see "Module completion manually updated"
    And I should see "Completion state: Complete with failing grade (3)"
    And I should see "Viewed: No (0)"
    When I switch to "Criteria and Activities" tab
    And I follow "Edit"
    Then the field "Viewed" matches value "0"
    And the field "Activity status" matches value "Completed (did not achieve pass grade)"
    And the field "Activity time completed" matches value "2027-07-08T16:34:00"

  Scenario: Activity completion records based on activity without grades (Feedback) can be edited
    # Completion editor list of users.
    When I am on "Course One" course homepage with editing mode on
    And I add a "Feedback" to section "1" and I fill the form with:
      | Name        | Test feedback 1           |
      | Description | Test feedback description |
    And I navigate to "Completion editor" node in "Course administration"
    Then I should see "FirstName1 LastName1"

    # Completion editor criteria and activities tab/list.
    When I click on "Edit course completion" "link" in the "FirstName1 LastName1" "table_row"
    And I switch to "Criteria and Activities" tab
    Then I should see "Course completion criteria"
    And I should see "No completion criteria set for this course"
    And I should see "Activity completion"
    And I should see "Test feedback 1"

    # Default data was created and is loaded correctly.
    When I follow "Edit"
    Then the field "Viewed" matches value "0"
    And the field "Activity status" matches value "Not completed"
    And "Activity time completed" Totara form field should not exist

    # Create course_modules_completion record.
    When I set the field "Viewed" to "1"
    When I set the "Activity status" Totara form field to "Completed"
    And I wait for "Activity time completed" Totara form field to be ready
    And I set the "Activity time completed" Totara form field to "2011-02-03 04:56"
    And I press "Save changes"
    Then I should see "Changing the completion record may lead to changes in course completions"
    When I click on "Yes" "button"
    Then I should see "Completion changes have been saved"
    When I switch to "Transactions" tab
    And I should see "February 2011"
    And I should see "Module completion manually created"
    And I should see "Completion state: Complete (1)"
    And I should see "Viewed: Yes (1)"
    And I should see "Time completed: Not set (null)"
    When I switch to "Criteria and Activities" tab
    And I follow "Edit"
    Then the field "Viewed" matches value "1"
    And the field "Activity status" matches value "Completed"
    And the field "Activity time completed" matches value "2011-02-03T04:56:00"

    # Edit course_modules_completion record.
    When I set the field "Viewed" to "0"
    When I set the "Activity status" Totara form field to "Not completed"
    And I press "Save changes"
    Then I should see "Changing the completion record may lead to changes in course completions"
    When I click on "Yes" "button"
    Then I should see "Completion changes have been saved"
    When I switch to "Transactions" tab
    And I should see "Module completion manually updated"
    And I should see "Completion state: Not complete (0)"
    And I should see "Time completed: Not set (null)"
    And I should see "Viewed: No (0)"
    When I switch to "Criteria and Activities" tab
    And I follow "Edit"
    Then the field "Viewed" matches value "0"
    And the field "Activity status" matches value "Not completed"
    And "Activity time completed" Totara form field should not exist
