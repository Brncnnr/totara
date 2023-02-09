@core @core_grades @availability @availability_restriction @mod @mod_assign @javascript
Feature: Grade visibility with audience restriction set
  Background:
    Given the "reports" user profile block exists
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Tea       | Cher     | tea.cher@example.com |
      | student1 | Stu       | Dent     | stu.dent@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "cohorts" exist:
      | name      | idnumber |
      | Audience1 | aud1     |
    And the following "cohort members" exist:
      | user     | cohort |
      | teacher1 | aud1   |
    And the following "activities" exist:
      | activity | course | idnumber | name     | intro    |
      | assign   | C1     | assign1  | Homework | Homework |

    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Homework"
    And I click on "Grade" "link" in the ".submissionlinks" "css_element"
    And I set the field "Grade out of 100" to "42"
    And I press "Save changes"
    And I click on "Ok" "button" in the "Changes saved" "dialogue"
    And I am on "Course 1" course homepage
    And I log out

  Scenario: Student and teacher can see student's grade when activity has no access restriction
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Grades" node in "Course administration"
    And I follow "User report"
    And I set the field "Select all or one user" to "Stu Dent"
    Then I should see "42.00" in the "Homework" "table_row"
    And I log out

    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Grades" node in "Course administration"
    And I follow "User report"
    And I set the field "Select all or one user" to "Stu Dent"
    Then I should see "42.00" in the "Homework" "table_row"
    And I log out

    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I navigate to "Grades" node in "Course administration"
    And I follow "User report"
    Then I should see "42.00" in the "Homework" "table_row"

  Scenario: Student and teacher can see student's grade when activity has access restriction
    Given I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I follow "Homework"
    And I navigate to "Edit settings" node in "Assignment administration"
    And I follow "Restrict access"
    And I click on "Add restriction..." "button"
    And I click on "Member of Audience" "button" in the "Add restriction..." "dialogue"
    And I set the field "Member of Audience" to "Audience1"
    And I press key "13" in the field "Member of Audience"
    When I press "Save and return to course"
    Then I should see "Not available unless: You are a member of the Audience: Audience1"
    And I should not see "Not available unless: You are a member of the Audience: Audience1 (hidden otherwise)"

    And I navigate to "Grades" node in "Course administration"
    And I follow "User report"
    And I set the field "Select all or one user" to "Stu Dent"
    Then I should see "42.00" in the "Homework" "table_row"
    And I log out

    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Grades" node in "Course administration"
    And I follow "User report"
    And I set the field "Select all or one user" to "Stu Dent"
    Then I should see "42.00" in the "Homework" "table_row"
    And I log out

    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I navigate to "Grades" node in "Course administration"
    And I follow "User report"
    Then I should see "42.00" in the "Homework" "table_row"

  Scenario: Only teacher can see student's grade when activity has invisible access restriction
    Given I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I follow "Homework"
    And I navigate to "Edit settings" node in "Assignment administration"
    And I follow "Restrict access"
    And I click on "Add restriction..." "button"
    And I click on "Member of Audience" "button" in the "Add restriction..." "dialogue"
    And I set the field "Member of Audience" to "Audience1"
    And I press key "13" in the field "Member of Audience"
    And I click on "Displayed greyed-out if user does not meet this condition" "link"
    When I press "Save and return to course"
    Then I should see "Not available unless: You are a member of the Audience: Audience1 (hidden otherwise)"

    And I navigate to "Grades" node in "Course administration"
    And I follow "User report"
    And I set the field "Select all or one user" to "Stu Dent"
    Then I should see "42.00" in the "Homework" "table_row"
    And I log out

    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Grades" node in "Course administration"
    And I follow "User report"
    And I set the field "Select all or one user" to "Stu Dent"
    Then I should see "42.00" in the "Homework" "table_row"
    And I log out

    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I navigate to "Grades" node in "Course administration"
    And I follow "User report"
    Then I should not see "Homework" in the ".user-grade" "css_element"

  Scenario: Only teacher and user with capability can see student's grade when activity has invisible access restriction
    Given the following "users" exist:
      | username | firstname | lastname | email               |
      | manager  | Mana      | Ger      | manager@example.com |
      | staff    | Sta       | FF       | staff@example.com   |
      | tom      | Tom       | Peeping  | tom@example.com     |
      | mate     | Ma        | Te       | mate@example.com    |
    And the following "course enrolments" exist:
      | user | course | role    |
      | mate | C1     | student |
    And the following job assignments exist:
      | user     | fullname | idnumber | manager |
      | student1 | jajaja1  | 1        | manager |
      | student1 | jajaja2  | 2        | staff   |
      | student1 | jajaja3  | 3        | tom     |
    And the following "roles" exist:
      | name | shortname | contextlevel |
      | Peep | peep      | System       |
    And the following "role assigns" exist:
      | user     | role         | contextlevel | reference |
      | manager  | manager      | System       |           |
      | staff    | staffmanager | System       |           |
      | tom      | peep         | System       |           |

    Given I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I follow "Homework"
    And I navigate to "Edit settings" node in "Assignment administration"
    And I follow "Restrict access"
    And I click on "Add restriction..." "button"
    And I click on "Member of Audience" "button" in the "Add restriction..." "dialogue"
    And I set the field "Member of Audience" to "Audience1"
    And I press key "13" in the field "Member of Audience"
    And I click on "Displayed greyed-out if user does not meet this condition" "link"
    When I press "Save and return to course"
    Then I should see "Not available unless: You are a member of the Audience: Audience1 (hidden otherwise)"

    # Only admin, site manager and teacher can see student's grade
    And I navigate to "Grades" node in "Course administration"
    And I follow "User report"
    And I set the field "Select all or one user" to "Stu Dent"
    Then I should see "42.00" in the "Homework" "table_row"
    And I log out

    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Grades" node in "Course administration"
    And I follow "User report"
    And I set the field "Select all or one user" to "Stu Dent"
    Then I should see "42.00" in the "Homework" "table_row"
    And I log out

    When I log in as "manager"
    And I am on "Course 1" course homepage
    And I navigate to "Grades" node in "Course administration"
    And I follow "User report"
    And I set the field "Select all or one user" to "Stu Dent"
    Then I should see "42.00" in the "Homework" "table_row"
    And I log out

    When I log in as "staff"
    And I am on "Team" page
    And I click on "Profile" "link" in the "Stu Dent" "table_row"
    And I click on "Grades overview" "link" in the ".block_totara_user_profile_category_reports" "css_element"
    And I follow "Course 1"
    Then I should not see "Homework" in the ".user-grade" "css_element"
    And I log out

    When I log in as "tom"
    And I am on "Team" page
    And I click on "Profile" "link" in the "Stu Dent" "table_row"
    And I follow "Grades overview"
    And I follow "Course 1"
    Then I should not see "Homework" in the ".user-grade" "css_element"
    And I log out

    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I navigate to "Grades" node in "Course administration"
    And I follow "User report"
    Then I should not see "Homework" in the ".user-grade" "css_element"
    And I log out

    # Add capabilities: staff manager and tom can now see student's grade
    And the following "permission overrides" exist:
      | capability                         | permission | role         | contextlevel | reference |
      | moodle/course:view                 | Allow      | staffmanager | System       |           |
      | moodle/course:view                 | Allow      | peep         | System       |           |
      | moodle/course:viewhiddenactivities | Allow      | staffmanager | System       |           |
      | moodle/course:viewhiddenactivities | Allow      | peep         | System       |           |
      | mod/assign:view                    | Allow      | staffmanager | System       |           |
      | mod/assign:view                    | Allow      | peep         | System       |           |

    When I log in as "staff"
    And I am on "Team" page
    And I click on "Profile" "link" in the "Stu Dent" "table_row"
    And I follow "Grades overview"
    And I follow "Course 1"
    Then I should see "42.00" in the "Homework" "table_row"
    And I log out

    When I log in as "tom"
    And I am on "Team" page
    And I click on "Profile" "link" in the "Stu Dent" "table_row"
    And I follow "Grades overview"
    And I follow "Course 1"
    Then I should see "42.00" in the "Homework" "table_row"
    And I log out

    # Remove permission overrides: staff manager and tom can't see student's grade
    And the following "permission overrides" exist:
      | capability                         | permission | role         | contextlevel | reference |
      | moodle/course:view                 | Prevent    | staffmanager | System       |           |
      | moodle/course:view                 | Prevent    | peep         | System       |           |
      | moodle/course:viewhiddenactivities | Prevent    | staffmanager | System       |           |
      | moodle/course:viewhiddenactivities | Prevent    | peep         | System       |           |
      | mod/assign:view                    | Prevent    | staffmanager | System       |           |
      | mod/assign:view                    | Prevent    | peep         | System       |           |

    When I log in as "staff"
    And I am on "Team" page
    And I click on "Profile" "link" in the "Stu Dent" "table_row"
    And I follow "Grades overview"
    And I follow "Course 1"
    Then I should not see "Homework" in the ".user-grade" "css_element"
    And I log out

    When I log in as "tom"
    And I am on "Team" page
    And I click on "Profile" "link" in the "Stu Dent" "table_row"
    And I follow "Grades overview"
    And I follow "Course 1"
    Then I should not see "Homework" in the ".user-grade" "css_element"
    And I log out

    # Add student to the audience: student, staff manager and tom can see student's grade
    And the following "cohort members" exist:
      | user     | cohort |
      | student1 | aud1   |

    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I navigate to "Grades" node in "Course administration"
    And I follow "User report"
    Then I should see "42.00" in the "Homework" "table_row"
    And I log out

    When I log in as "staff"
    And I am on "Team" page
    And I click on "Profile" "link" in the "Stu Dent" "table_row"
    And I follow "Grades overview"
    And I follow "Course 1"
    Then I should see "42.00" in the "Homework" "table_row"
    And I log out

    When I log in as "tom"
    And I am on "Team" page
    And I click on "Profile" "link" in the "Stu Dent" "table_row"
    And I follow "Grades overview"
    And I follow "Course 1"
    Then I should see "42.00" in the "Homework" "table_row"
    And I log out

    When I log in as "mate"
    Then I should not see "Team"
    # .. The other students cannot see student1's grade even if they directly goes to the page
    And I log out
