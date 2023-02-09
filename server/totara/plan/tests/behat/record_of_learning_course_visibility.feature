@totara @totara_plan @totara_rol @javascript
Feature: Verify standard and audience-based visibility in record of learning courses.

  # Courses are set up to verify the record of learning courses as follows:
  #
  # Course 1 - Completed and enrolled
  # Course 2 - In progress and enrolled
  # Course 3 - In progress but now un-enrolled
  # Course 4 - Not started and enrolled
  # Course 5 - Not started and un-enrolled
  # Course 6 - Not started and assigned via learning plan
  # Course 7 - Not started and assigned via audience availability
  # Course 8 - not started and never enrolled

  Background:
    Given the "mylearning" user profile block exists
    And I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Bob1      | Learner1 | learner1@example.com |
      | learner2 | Bob2      | Learner2 | learner2@example.com |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | C1        | 1                |
      | Course 2 | C2        | 1                |
      | Course 3 | C3        | 1                |
      | Course 4 | C4        | 1                |
      | Course 5 | C5        | 1                |
      | Course 6 | C6        | 0                |
      | Course 7 | C7        | 1                |
      | Course 8 | C8        | 1                |
    And the following "activities" exist:
      | course | idnumber | activity | completion | intro              |
      | C1     | label1   | label    | 1          | Completion label 1 |
      | C1     | label2   | label    | 1          | Completion label 2 |
      | C2     | label1   | label    | 1          | Completion label 1 |
      | C2     | label2   | label    | 1          | Completion label 2 |
      | C3     | label1   | label    | 1          | Completion label 1 |
      | C3     | label2   | label    | 1          | Completion label 2 |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | learner1 | C1     | student |
      | learner1 | C2     | student |
      | learner1 | C3     | student |
      | learner1 | C4     | student |
      | learner1 | C5     | student |
    And the following "plans" exist in "totara_plan" plugin:
      | user     | name            |
      | learner1 | Learning Plan 1 |
    And the following "cohorts" exist:
      | name       | idnumber |
      | Audience 1 | A1       |
    And the following "cohort members" exist:
      | user     | cohort |
      | learner1 | A1     |

    When I log in as "admin"
    # Set completion for course 1.
    And I am on "Course 1" course homepage
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I set the field "Label - Completion label 1" to "1"
    And I set the field "Label - Completion label 2" to "1"
    And I press "Save changes"
    Then I should see "Course completion criteria changes have been saved"

    # Set completion for course 2.
    When I am on "Course 2" course homepage
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I set the field "Label - Completion label 1" to "1"
    And I set the field "Label - Completion label 2" to "1"
    And I press "Save changes"
    Then I should see "Course completion criteria changes have been saved"

    # Set completion for course 3.
    When I am on "Course 3" course homepage
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I set the field "Label - Completion label 1" to "1"
    And I set the field "Label - Completion label 2" to "1"
    And I press "Save changes"
    Then I should see "Course completion criteria changes have been saved"

    # Create a learning plan for the learner and allocate Course 5 to it.
    When I navigate to "Manage users" node in "Site administration > Users"
    And I follow "Bob1 Learner1"
    And I click on "Learning Plans" "link" in the ".block_totara_user_profile_category_mylearning" "css_element"
    And I follow "Learning Plan 1"
    And I switch to "Courses" tab
    And I press "Add course"
    And I follow "Miscellaneous"
    And I follow "Course 6"
    And I click on "Save" "button" in the "Add courses" "totaradialogue"
    Then I should see "Course 6" in the ".dp-plan-component-items" "css_element"

    When I press "Approve"
    Then I should see "Plan \"Learning Plan 1\" has been approved by Admin User"

    # Login as the learner and progress some of the courses.
    When I log out
    And I log in as "learner1"
    # Complete Course 1.
    And I click on "Record of Learning" in the totara menu
    And I follow "Course 1"
    And I set the field "Manual completion of Completion label 1" to "1"
    And I set the field "Manual completion of Completion label 2" to "1"
    # Only start Course 2 but don't complete it.
    And I click on "Record of Learning" in the totara menu
    And I follow "Course 2"
    And I set the field "Manual completion of Completion label 1" to "1"
    # Only start Course 3 but don't complete it.
    And I click on "Record of Learning" in the totara menu
    And I follow "Course 3"
    And I set the field "Manual completion of Completion label 1" to "1"
    And I log out
    Then I should see "Log in"

    # Unenrol learner1 from course 3
    When I log in as "admin"
    And I am on "Course 3" course homepage
    And I navigate to "Enrolled users" node in "Course administration > Users"
    And I click on "Unenrol" "link" in the "Bob1 Learner1" "table_row"
    Then I should see "Do you really want to unenrol user \"Bob1 Learner1\""
    When I press "Continue"
    Then I should not see "Bob1 Learner1"

    When I am on "Course 5" course homepage
    And I navigate to "Enrolled users" node in "Course administration > Users"
    And I click on "Unenrol" "link" in the "Bob1 Learner1" "table_row"
    Then I should see "Do you really want to unenrol user \"Bob1 Learner1\""
    When I press "Continue"
    Then I should not see "Bob1 Learner1"
    And I log out

  Scenario: Verify standard visibility for courses set to 'show' in the record of learning.

    Given I log in as "learner2"
    When I click on "Find Learning" in the totara menu
    Then I should see "Course 1"
    And I should see "Course 2"
    And I should see "Course 3"
    And I should see "Course 4"
    And I should see "Course 5"
    And I should see "Course 6"
    And I should see "Course 7"
    And I should see "Course 8"
    And I am on homepage
    And I log out

    When I log in as "learner1"
    And I click on "Record of Learning" in the totara menu

    When I follow "All Learning"
    Then the "reportbuilder-table" table should contain the following:
      | Course Title | Plan            | Progress    |
      | Course 1     |                 | 100%        |
      | Course 2     |                 | 50%         |
      | Course 3     |                 | 50%         |
      | Course 4     |                 | No criteria |
      | Course 6     | Learning Plan 1 | Not tracked |
    And the "reportbuilder-table" table should not contain the following:
      | Course Title |
      | Course 5     |
      | Course 7     |
      | Course 8     |

  Scenario: Verify standard visibility for courses set to 'hide' in the record of learning.

    Given I log in as "admin"
    When I navigate to "Courses and categories" node in "Site administration > Courses"
    And I follow "Course 1"
    And I click on "Hide" "link" in the ".course-detail-listing-actions" "css_element"
    And I follow "Course 2"
    And I click on "Hide" "link" in the ".course-detail-listing-actions" "css_element"
    And I follow "Course 3"
    And I click on "Hide" "link" in the ".course-detail-listing-actions" "css_element"
    And I follow "Course 4"
    And I click on "Hide" "link" in the ".course-detail-listing-actions" "css_element"
    And I follow "Course 5"
    And I click on "Hide" "link" in the ".course-detail-listing-actions" "css_element"
    And I follow "Course 6"
    And I click on "Hide" "link" in the ".course-detail-listing-actions" "css_element"
    And I log out
    Then I should see "Log in"

    # The first five courses should be hidden and not available to learner2.
    When I log in as "learner2"
    And I click on "Find Learning" in the totara menu
    Then I should see "Course 7"
    And I should see "Course 8"
    And I should not see "Course 1"
    And I should not see "Course 2"
    And I should not see "Course 3"
    And I should not see "Course 4"
    And I should not see "Course 5"
    And I should not see "Course 6"
    And I am on homepage
    And I log out

    When I log in as "learner1"
    And I click on "Record of Learning" in the totara menu
    # We only need to check the visibility of courses so just check the All Learning report.
    And I follow "All Learning"
    Then the "reportbuilder-table" table should contain the following:
      | Course Title | Plan | Progress |
      | Course 1     |      | 100%     |
      | Course 2     |      | 50%      |
      | Course 3     |      | 50%      |
    And the "reportbuilder-table" table should not contain the following:
      | Course Title |
      | Course 4     |
      | Course 5     |
      | Course 6     |
      | Course 7     |
      | Course 8     |

  Scenario: Verify audience visibility for courses set to 'no users' in the record of learning.

    Given I log in as "admin"
    And the following config values are set as admin:
      | audiencevisibility | 1 |

    When I am on "Course 1" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the field "Visibility" to "No users"
    And I press "Save and display"
    Then I should see "Topic 1"

    When I am on "Course 2" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the field "Visibility" to "No users"
    And I press "Save and display"
    Then I should see "Topic 1"

    When I am on "Course 3" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the field "Visibility" to "No users"
    And I press "Save and display"
    Then I should see "Topic 1"

    When I am on "Course 4" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the field "Visibility" to "No users"
    And I press "Save and display"
    Then I should see "Topic 1"

    When I am on "Course 5" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the field "Visibility" to "No users"
    And I press "Save and display"
    Then I should see "Topic 1"

    When I am on "Course 6" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the field "Visibility" to "No users"
    And I press "Save and display"
    Then I should see "Topic 1"

    When I am on "Course 7" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the field "Visibility" to "No users"
    And I press "Save and display"
    Then I should see "Topic 1"

    When I log out
    Then I should see "Log in"

    # The first 7 courses should be hidden and not available to learner2.
    When I log in as "learner2"
    And I click on "Find Learning" in the totara menu
    Then I should see "Course 8"
    And I should not see "Course 1"
    And I should not see "Course 2"
    And I should not see "Course 3"
    And I should not see "Course 4"
    And I should not see "Course 5"
    And I should not see "Course 6"
    And I should not see "Course 7"
    And I am on homepage
    And I log out

    When I log in as "learner1"
    And I click on "Record of Learning" in the totara menu
    # We only need to check the visibility of courses so just check the All Learning report.
    And I follow "All Learning"
    Then the "reportbuilder-table" table should contain the following:
      | Course Title | Plan | Progress |
      | Course 1     |      | 100%     |
      | Course 2     |      | 50%      |
      | Course 3     |      | 50%      |
    And the "reportbuilder-table" table should not contain the following:
      | Course Title |
      | Course 4     |
      | Course 5     |
      | Course 6     |
      | Course 7     |
      | Course 8     |

  Scenario: Verify audience visibility for courses set to 'all users' in the record of learning.

    Given I log in as "admin"
    And the following config values are set as admin:
      | audiencevisibility | 1 |

    When I am on "Course 1" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the field "Visibility" to "All users"
    And I press "Save and display"
    Then I should see "Topic 1"

    When I am on "Course 2" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the field "Visibility" to "All users"
    And I press "Save and display"
    Then I should see "Topic 1"

    When I am on "Course 3" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the field "Visibility" to "All users"
    And I press "Save and display"
    Then I should see "Topic 1"

    When I am on "Course 4" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the field "Visibility" to "All users"
    And I press "Save and display"
    Then I should see "Topic 1"

    When I am on "Course 5" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the field "Visibility" to "All users"
    And I press "Save and display"
    Then I should see "Topic 1"

    When I am on "Course 7" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the field "Visibility" to "All users"
    And I press "Save and display"
    Then I should see "Topic 1"

    When I log out
    Then I should see "Log in"

    # The first five courses should be hidden and not available to learner2.
    When I log in as "learner2"
    And I click on "Find Learning" in the totara menu
    Then I should see "Course 1"
    And I should see "Course 2"
    And I should see "Course 3"
    And I should see "Course 4"
    And I should see "Course 5"
    And I should see "Course 6"
    And I should see "Course 7"
    And I should see "Course 8"
    And I am on homepage
    And I log out

    When I log in as "learner1"
    And I click on "Record of Learning" in the totara menu
    # We only need to check the visibility of courses so just check the All Learning report.
    And I follow "All Learning"
    Then the "reportbuilder-table" table should contain the following:
      | Course Title | Plan            | Progress    |
      | Course 1     |                 | 100%        |
      | Course 2     |                 | 50%         |
      | Course 3     |                 | 50%         |
      | Course 4     |                 | No criteria |
      | Course 6     | Learning Plan 1 | Not tracked |
    And the "reportbuilder-table" table should not contain the following:
      | Course Title |
      | Course 5     |
      | Course 7     |
      | Course 8     |

  Scenario: Verify audience visibility for courses set to 'enrolled users' in the record of learning.

    Given I log in as "admin"
    And the following config values are set as admin:
      | audiencevisibility | 1 |

    When I am on "Course 1" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the field "Visibility" to "Enrolled users only"
    And I press "Save and display"
    Then I should see "Topic 1"

    When I am on "Course 2" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the field "Visibility" to "Enrolled users only"
    And I press "Save and display"
    Then I should see "Topic 1"

    When I am on "Course 3" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the field "Visibility" to "Enrolled users only"
    And I press "Save and display"
    Then I should see "Topic 1"

    When I am on "Course 4" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the field "Visibility" to "Enrolled users only"
    And I press "Save and display"
    Then I should see "Topic 1"

    When I am on "Course 5" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the field "Visibility" to "Enrolled users only"
    And I press "Save and display"
    Then I should see "Topic 1"

    When I am on "Course 7" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the field "Visibility" to "Enrolled users only"
    And I press "Save and display"
    Then I should see "Topic 1"

    When I log out
    Then I should see "Log in"

    # The first five courses should be hidden and not available to learner2.
    When I log in as "learner2"
    And I click on "Find Learning" in the totara menu
    # Only 6 and 8 should be visible as they don't have enrollment as a requirement for visibility.
    Then I should see "Course 6"
    And I should see "Course 8"
    And I should not see "Course 1"
    And I should not see "Course 2"
    And I should not see "Course 3"
    And I should not see "Course 4"
    And I should not see "Course 5"
    And I should not see "Course 7"
    And I am on homepage
    And I log out

    When I log in as "learner1"
    And I click on "Record of Learning" in the totara menu
    # We only need to check the visibility of courses so just check the All Learning report.
    And I follow "All Learning"
    Then the "reportbuilder-table" table should contain the following:
      | Course Title | Plan            | Progress    |
      | Course 1     |                 | 100%        |
      | Course 2     |                 | 50%         |
      | Course 3     |                 | 50%         |
      | Course 4     |                 | No criteria |
      | Course 6     | Learning Plan 1 | Not tracked |
    And the "reportbuilder-table" table should not contain the following:
      | Course Title |
      | Course 5     |
      | Course 7     |
      | Course 8     |

  Scenario: Verify audience visibility for courses set to 'enrolled users and audience members' in the record of learning.

    Given I log in as "admin"
    And the following config values are set as admin:
      | audiencevisibility | 1 |

    When I am on "Course 1" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the field "Visibility" to "Enrolled users and members of the selected audiences"
    And I press "Add visible audiences"
    And I follow "Audience 1"
    And I click on "OK" "button" in the "Course audiences (visible)" "totaradialogue"
    And I press "Save and display"
    Then I should see "Topic 1"

    When I am on "Course 2" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the field "Visibility" to "Enrolled users and members of the selected audiences"
    And I press "Add visible audiences"
    And I follow "Audience 1"
    And I click on "OK" "button" in the "Course audiences (visible)" "totaradialogue"
    And I press "Save and display"
    Then I should see "Topic 1"

    When I am on "Course 3" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the field "Visibility" to "Enrolled users and members of the selected audiences"
    And I press "Add visible audiences"
    And I follow "Audience 1"
    And I click on "OK" "button" in the "Course audiences (visible)" "totaradialogue"
    And I press "Save and display"
    Then I should see "Topic 1"

    When I am on "Course 4" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the field "Visibility" to "Enrolled users and members of the selected audiences"
    And I press "Add visible audiences"
    And I follow "Audience 1"
    And I click on "OK" "button" in the "Course audiences (visible)" "totaradialogue"
    And I press "Save and display"
    Then I should see "Topic 1"

    When I am on "Course 5" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the field "Visibility" to "Enrolled users and members of the selected audiences"
    And I press "Add visible audiences"
    And I follow "Audience 1"
    And I click on "OK" "button" in the "Course audiences (visible)" "totaradialogue"
    And I press "Save and display"
    Then I should see "Topic 1"

    When I am on "Course 7" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the field "Visibility" to "Enrolled users and members of the selected audiences"
    And I press "Add visible audiences"
    And I follow "Audience 1"
    And I click on "OK" "button" in the "Course audiences (visible)" "totaradialogue"
    And I press "Save and display"
    Then I should see "Topic 1"

    When I log out
    Then I should see "Log in"

    # The first five courses should be hidden and not available to learner2.
    When I log in as "learner2"
    And I click on "Find Learning" in the totara menu
    # Only 6 and 8 should be visible as they don't have enrollment as a requirement for visibility.
    Then I should see "Course 6"
    And I should see "Course 8"
    And I should not see "Course 1"
    And I should not see "Course 2"
    And I should not see "Course 3"
    And I should not see "Course 4"
    And I should not see "Course 5"
    And I should not see "Course 7"
    And I am on homepage
    And I log out

    When I log in as "learner1"
    And I click on "Record of Learning" in the totara menu
    # We only need to check the visibility of courses so just check the All Learning report.
    And I follow "All Learning"
    Then the "reportbuilder-table" table should contain the following:
      | Course Title | Plan            | Progress    |
      | Course 1     |                 | 100%        |
      | Course 2     |                 | 50%         |
      | Course 3     |                 | 50%         |
      | Course 4     |                 | No criteria |
      | Course 6     | Learning Plan 1 | Not tracked |
    And the "reportbuilder-table" table should not contain the following:
      | Course Title |
      | Course 5     |
      | Course 7     |
      | Course 8     |
