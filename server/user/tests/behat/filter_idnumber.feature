@core @core_user
Feature: Filter users by idnumber
  As a system administrator
  I need to be able to filter users by their ID number
  So that I can quickly find users based on an external key.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email | idnumber |
      | teacher1 | Teacher  | 1 | teacher@example.com  | 0000002 |
      | student1 | Student1 | 1 | student1@example.com | 0000003 |
      | student2 | Student2 | 1 | student2@example.com | 2000000 |
      | student3 | Student3 | 1 | student3@example.com | 3000000 |
    And I log in as "admin"
    And I navigate to "Users > Manage users" in site administration
    And I press "Edit this report"
    And I switch to "Filters" tab
    And I select "User ID Number" from the "newstandardfilter" singleselect
    And I press "Add"
    And I press "Save changes"
    And I follow "View This Report"

  @javascript
  Scenario: Filtering id numbers - with case "is empty"
    # We should see see admin on the user list, the following e-mail is admin's e-mail.
    Then I should see "moodle@example.com" in the "system_browse_users" "table"
    And I should see "Teacher" in the "system_browse_users" "table"
    And I should see "Student1" in the "system_browse_users" "table"
    And I should see "Student2" in the "system_browse_users" "table"
    And I should see "Student3" in the "system_browse_users" "table"
    And I set the field "id_user-idnumber_op" to "is empty"
    When I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    # We should see admin on the user list, the following e-mail is admin's e-mail.
    Then I should see "moodle@example.com" in the "system_browse_users" "table"
    And I should not see "Teacher" in the "system_browse_users" "table"
    And I should not see "Student1" in the "system_browse_users" "table"
    And I should not see "Student2" in the "system_browse_users" "table"
    And I should not see "Student3" in the "system_browse_users" "table"

  @javascript
  Scenario Outline: Filtering id numbers - with all other cases
    # We should see see admin on the user list, the following e-mail is admin's e-mail.
    Then I should see "moodle@example.com" in the "system_browse_users" "table"
    And I should see "Teacher" in the "system_browse_users" "table"
    And I should see "Student1" in the "system_browse_users" "table"
    And I should see "Student2" in the "system_browse_users" "table"
    And I should see "Student3" in the "system_browse_users" "table"
    And I set the field "id_user-idnumber_op" to "<Category>"
    And I set the field "id_user-idnumber" to "<Argument>"
    When I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should <Admin's Visibility> "moodle@example.com" in the "system_browse_users" "table"
    And I should <Teacher's Vis> "Teacher" in the "system_browse_users" "table"
    And I should <S1's Vis> "Student1" in the "system_browse_users" "table"
    And I should <S2's Vis> "Student2" in the "system_browse_users" "table"
    And I should <S3's Vis> "Student3" in the "system_browse_users" "table"

    Examples:
      | Category        | Argument | Admin's Visibility | Teacher's Vis | S1's Vis | S2's Vis | S3's Vis |
      | contains        | 0        | not see            | see           | see      | see      | see      |
      | doesn't contain | 2        | see                | not see       | see      | not see  | see      |
      | is equal to     | 2000000  | not see            | not see       | not see  | see      | not see  |
      | starts with     | 0        | not see            | see           | see      | not see  | not see  |
      | ends with       | 0        | not see            | not see       | not see  | see      | see      |
