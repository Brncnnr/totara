@core @core_backup @core_block @core_admin_roles @javascript @totara
Feature: Backup and restore blocks
  Scenario: Restore course with block permissions
    Given the following "users" exist:
      | username | firstname | lastname | email               |
      | student  | Stu       | Dent     | student@example.com |
      | teacher  | Tea       | Cher     | teacher@example.com |
    And the following "courses" exist:
      | fullname          | shortname | format |
      | Course with block | block     | topics |
      | Course empty      | empty     | topics |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | student | block  | student        |
      | teacher | block  | editingteacher |
      | teacher | empty  | editingteacher |
    And I log in as "admin"
    And I am on "Course with block" course homepage with editing mode on
    And I add the "Comments" block
    And I set the field "Add a comment..." to "Kia ora! Ma te wa!"
    When I click on "Save comment" "link"
    Then I should see "Kia ora! Ma te wa!" in the "Comments" "block"

    And I open the "Comments" blocks action menu
    And I click on "Permissions" "link" in the "Comments" "block"
    And I click on ".preventlink" "css_element" in the "//span[contains(@class,'allowed') and contains(.,'Authenticated User')]" "xpath_element"
    And I press "Remove"
    And I click on ".preventlink" "css_element" in the "//span[contains(@class,'allowed') and contains(.,'Guest')]" "xpath_element"
    And I press "Remove"
    And I click on ".preventlink" "css_element" in the "//span[contains(@class,'allowed') and contains(.,'Student')]" "xpath_element"
    And I press "Remove"

    And I am on "Course with block" course homepage
    When I navigate to "Backup" node in "Course administration"
    And I click on "Jump to final step" "button"
    Then I should see "The backup file was successfully created"
    And I click on "Continue" "button"

    When I click on "Restore" "button" in the "backup" "table"
    And I click on "Next" "button"
    And I click on "Miscellaneous" "radio"
    And I click on "Next" "button"
    And I click on "Next" "button"
    And I set the field "Course name" to "Course restored 1"
    And I set the field "Course short name" to "restored1"
    And I click on "Next" "button"
    And I click on "Perform restore" "button"
    Then I should see "The course was restored successfully"
    And I click on "Continue" "button"

    Then I should see "Kia ora! Ma te wa!" in the "Comments" "block"
    When I open the "Comments" blocks action menu
    And I click on "Permissions" "link" in the "Comments" "block"
    Then I should not see "Student" in the "View block" "table_row"
    And I should not see "Authenticated User" in the "View block" "table_row"
    And I should not see "Guest" in the "View block" "table_row"

    And I press the "back" button in the browser
    When I navigate to "Enrolled users" node in "Course administration > Users"
    Then I should see "Tea Cher"
    And I should see "Stu Dent"
    And I log out

    When I log in as "teacher"
    And I am on "Course with block" course homepage
    Then I should see "Kia ora! Ma te wa!" in the "Comments" "block"
    And I am on "Course restored 1" course homepage
    Then I should see "Kia ora! Ma te wa!" in the "Comments" "block"

    And I am on "Course with block" course homepage
    When I navigate to "Restore" node in "Course administration"
    When I click on "Restore" "button" in the "backup" "table"
    And I click on "Restore into an existing course" "radio"
    And I click on "Next" "button"
    And I click on "empty" "radio"
    And I set the field "Delete the contents of the existing course and then restore" to "1"
    And I click on "Next" "button"
    And I click on "Next" "button"
    And I set the field "Course name" to "Course restored 2"
    And I set the field "Course short name" to "restored2"
    And I click on "Next" "button"
    And I click on "Perform restore" "button"
    Then I should see "The Authenticated User role in the backup file cannot be mapped to any of the roles that you are allowed to assign"
    But I should not see "The Guest role in the backup file cannot be mapped to any of the roles that you are allowed to assign"
    But I should not see "The  role in the backup file cannot be mapped to any of the roles that you are allowed to assign"
    And I click on "Continue" "button"
    Then I should see "The course was restored successfully"
    And I click on "Continue" "button"
    # The admin's comment is gone because editingteacher is not able to restore it
    Then I should not see "Kia ora! Ma te wa!" in the "Comments" "block"
    And I log out

    And I log in as "admin"
    # Course name is not restored
    And I am on "Course empty" course homepage with editing mode on
    When I open the "Comments" blocks action menu
    And I click on "Permissions" "link" in the "Comments" "block"
    Then I should not see "Student" in the "View block" "table_row"
    # Guest is restored because editingteacher is able to change his permission
    And I should not see "Guest" in the "View block" "table_row"
    # Authenticated user is not restored because editingteacher is not able to change his permission
    But I should see "Authenticated User" in the "View block" "table_row"

    And I press the "back" button in the browser
    When I navigate to "Enrolled users" node in "Course administration > Users"
    # Student is not enrolled because editingteacher is not able to restore enrolment
    Then I should see "Tea Cher"
    But I should not see "Stu Dent"
    And I log out

    When I log in as "student"
    And I am on "Course with block" course homepage
    Then I should not see the "Comments" block
    And I am on "Course restored 1" course homepage
    Then I should not see the "Comments" block
    And I am on "Course empty" course homepage
    Then I should see "You can not enrol yourself in this course"
    And I log out
