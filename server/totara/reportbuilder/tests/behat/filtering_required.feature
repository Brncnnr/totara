@totara @totara_reportbuilder @javascript
Feature: Filter option for filtering required

  Background:
    Given I am on a totara site

  Scenario: Adding new Report builder filter with Filtering required
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
      | user3    | User      | Three    | user3@example.com |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname    | shortname          | source |
      | User report | report_user_report | user   |
    And I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I follow "User report"
    And I switch to "Filters" tab
    When I set the field "newstandardfilter" to "User Last Name"
    And I set the field "newstandardfilteringrequired" to "1"
    And I press "Add"
    And I press "Save changes"
    And I click on "View This Report" "link"
    Then I should see "Following filters are required to be applied in order to run this report: User Last Name"
    And I should see "Please apply all required filters to view this report"
    And I should see "Required search"
    And I should not see "User One"
    And I should not see "User Two"
    And I should not see "User Three"

    When I set the field "user-lastname" to "One"
    And I press "id_submitgroupstandard_addfilter"
    Then I should not see "Following filters are required to be applied in order to run this report: User Last Name"
    And I should not see "Please apply all required filters to view this report"
    And I should see "User One"
    And I should not see "User Two"
    And I should not see "User Three"

    When I press "Edit this report"
    And I switch to "Filters" tab
    When I set the field "newstandardfilter" to "User First Name"
    And I set the field "newstandardfilteringrequired" to "1"
    And I press "Save changes"
    And I click on "View This Report" "link"
    And I should see "Following filters are required to be applied in order to run this report: User First Name"
    And I should see "Please apply all required filters to view this report"
    And I should not see "User One"
    And I should not see "User Two"
    And I should not see "User Three"

  Scenario: Updating Filtering required of existing Report builder filter
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
      | user3    | User      | Three    | user3@example.com |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname    | shortname          | source |
      | User report | report_user_report | user   |
    And I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I follow "User report"
    And I switch to "Filters" tab
    # This is a nasty hack, let's hope the "Users's Fullname" is the only default filter with id 1.
    When I set the field "filteringrequired1" to "1"
    And I press "Save changes"
    And I click on "View This Report" "link"
    Then I should see "Following filters are required to be applied in order to run this report: User's Fullname"
    And I should see "Please apply all required filters to view this report"
    And I should see "Required search"
    And I should not see "User One"
    And I should not see "User Two"
    And I should not see "User Three"

    When I press "Edit this report"
    And I switch to "Filters" tab
    And I set the field "filteringrequired1" to "0"
    And I press "Save changes"
    And I click on "View This Report" "link"
    Then I should not see "Following filters are required to be applied in order to run this report: User's Fullname"
    And I should not see "Please apply all required filters to view this report"
    And I should see "User One"
    And I should see "User Two"
    And I should see "User Three"

  Scenario: Advanced option disables Filtering required in Report builder
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
      | user3    | User      | Three    | user3@example.com |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname    | shortname          | source |
      | User report | report_user_report | user   |
    And I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I follow "User report"
    And I switch to "Filters" tab
    # This is a nasty hack, let's hope the "Users's Fullname" is the only default filter with id 1.
    When I set the field "filteringrequired1" to "1"
    And I set the field "advanced1" to "1"
    And I set the field "newstandardfilter" to "User Last Name"
    And I set the field "newstandardfilteringrequired" to "1"
    And I set the field "newstandardadvanced" to "1"
    And I press "Add"
    And I set the field "newstandardfilter" to "User First Name"
    And I set the field "newstandardfilteringrequired" to "1"
    And I set the field "newstandardadvanced" to "1"
    And I press "Save changes"
    And I click on "View This Report" "link"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"
    And I should see "User One"
    And I should see "User Two"
    And I should see "User Three"

  Scenario: Filtering required works in rb_filter_badge
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
      | user3    | User      | Three    | user3@example.com |
      | user4    | User      | Four     | user4@example.com |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname     | shortname           | source       |
      | Badge report | report_badge_report | badge_issued |
    And I log in as "admin"
    And I navigate to "Manage badges" node in "Site administration > Badges"
    And I press "Add a new badge"
    And I set the following fields to these values:
      | Name          | Test Badge 1           |
      | Description   | Test badge description |
    And I upload "badges/tests/behat/badge.png" file to "Image" filemanager
    And I press "Create badge"
    And I set the field "Add badge criteria" to "Manual issue by role"
    And I set the field "Site Manager" to "1"
    And I click on "Save" "button"
    And I click on "Enable access" "button"
    And I click on "Continue" "button"
    And I switch to "Recipients (0)" tab
    And I click on "Award badge" "button"
    And I set the field "potentialrecipients" to "User One (user1@example.com),User Two (user2@example.com),User Three (user3@example.com)"
    And I click on "Award badge" "button"
    # Add a second badge.
    And I navigate to "Manage badges" node in "Site administration > Badges"
    And I press "Add a new badge"
    And I set the following fields to these values:
      | Name          | Test Badge 2           |
      | Description   | Test badge description |
    And I upload "badges/tests/behat/badge.png" file to "Image" filemanager
    And I press "Create badge"
    And I set the field "Add badge criteria" to "Manual issue by role"
    And I set the field "Site Manager" to "1"
    And I click on "Save" "button"
    And I click on "Enable access" "button"
    And I click on "Continue" "button"
    And I switch to "Recipients (0)" tab
    And I click on "Award badge" "button"
    And I set the field "potentialrecipients" to "User One (user1@example.com)"
    And I click on "Award badge" "button"

    When I navigate to my "Badge report" report
    And I press "Edit this report"
    And I switch to "Filters" tab
    And I set the field "filteringrequired1" to "1"
    And I press "Save changes"
    And I click on "View This Report" "link"
    Then I should see "Following filters are required to be applied in order to run this report: Badges"
    And I should see "Please apply all required filters to view this report"

    # Now do some filtering.
    When I click on "Add badges" "link"
    And I click on "Test Badge 2" "link" in the "Choose badges" "totaradialogue"
    And I click on "Save" "button" in the "Choose badges" "totaradialogue"
    And I wait "1" seconds
    # This needs to be limited as otherwise it clicks the legend ...
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"
    And I should see "User One"
    And I should not see "User Two"
    And I should not see "User Three"
    And I should not see "User Four"

  Scenario: Filtering required works in rb_filter_category
    Given the following "categories" exist:
      | category | name   | idnumber |
      | 0        | cat 1  | cat1     |
      | cat1     | cat 1a | cat1a    |
      | cat1     | cat 1b | cat1b    |
      | 0        | cat 2  | cat2     |
    And the following "courses" exist:
      | fullname  | shortname | category |
      | Course 0  | c0        | 0        |
      | Course 1z | c1        | cat1     |
      | Course 1a | c1a       | cat1a    |
      | Course 1b | c1b       | cat1b    |
      | Course 2  | c2        | cat2     |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname      | shortname            | source  |
      | Course report | report_course_report | courses |
    And I log in as "admin"

    When I navigate to my "Course report" report
    And I press "Edit this report"
    And I switch to "Filters" tab
    And I set the field "filteringrequired2" to "1"
    And I press "Save changes"
    And I click on "View This Report" "link"
    Then I should see "Following filters are required to be applied in order to run this report: Course Category (multichoice)"
    And I should see "Please apply all required filters to view this report"

    When I set the field "course_category-path_op" to "is equal to"
    And I click on "Choose Categories" "button"
    And I click on "cat 1" "link" in the "Choose Categories" "totaradialogue"
    And I click on "Save" "button" in the "Choose Categories" "totaradialogue"
    And I wait "1" seconds
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"
    And I should not see "Course 0"
    And I should see "Course 1z"
    And I should not see "Course 1a"
    And I should not see "Course 1b"
    And I should not see "Course 2"

    When I set the field "course_category-path_op" to "isn't equal to"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"
    And I should see "Course 0"
    And I should not see "Course 1z"
    And I should see "Course 1a"
    And I should see "Course 1b"
    And I should see "Course 2"

  Scenario: Filtering required works in rb_filter_cohort
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
      | user3    | User      | Three    | user3@example.com |
    And the following "cohorts" exist:
      | name     | idnumber |
      | Cohort 1 | CH1      |
      | Cohort 2 | CH2      |
    And the following "cohort members" exist:
      | user  | cohort |
      | user1 | CH1    |
      | user2 | CH1    |
      | user1 | CH2    |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname    | shortname          | source |
      | User report | report_user_report | user   |
    And I log in as "admin"

    When I navigate to my "User report" report
    And I press "Edit this report"
    And I switch to "Filters" tab
    And I set the field "newstandardfilter" to "User is a member of audience"
    And I set the field "newstandardfilteringrequired" to "1"
    And I press "Save changes"
    And I click on "View This Report" "link"
    Then I should see "Following filters are required to be applied in order to run this report: User is a member of audience"
    And I should see "Please apply all required filters to view this report"

    When I click on "Add audiences" "link"
    And I click on "Cohort 2" "link" in the "Choose audiences" "totaradialogue"
    And I click on "Save" "button" in the "Choose audiences" "totaradialogue"
    And I wait "1" seconds
    # This needs to be limited as otherwise it clicks the legend ...
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"
    And I should see "User One"
    And I should not see "User Two"
    And I should not see "User Three"

  Scenario: Filtering required works in rb_filter_course_multi
    Given the following "courses" exist:
      | fullname    | shortname | audiencevisible |
      | CourseOne   | Course1   | 3               |
      | CourseTwo   | Course2   | 2               |
      | CourseThree | Course3   | 2               |
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | user      | one      | user1@example.com |
    And I log in as "admin"
    And the following config values are set as admin:
      | audiencevisibility | 1 |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname | shortname | source  |
      | Courses  | courses   | courses |

    When I navigate to my "Courses" report
    And I press "Edit this report"
    And I switch to "Access" tab
    And I set the field "Authenticated User" to "1"
    And I press "Save changes"
    And I switch to "Filters" tab
    And I set the field "newstandardfilter" to "Course (multi-item)"
    And I set the field "newstandardfilteringrequired" to "1"
    And I press "Save changes"
    And I click on "View This Report" "link"
    Then I should see "Following filters are required to be applied in order to run this report: Course (multi-item)"
    And I should see "Please apply all required filters to view this report"

    When I select "is equal to" from the "Course (multi-item)" singleselect
    And I press "Choose Courses"
    And I click on "Miscellaneous" "link" in the "Choose Courses" "totaradialogue"
    And I wait "1" seconds
    And I click on "CourseOne" "link" in the "Choose Courses" "totaradialogue"
    And I click on "Save" "button" in the "Choose Courses" "totaradialogue"
    And I wait "1" seconds
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"
    And I should see "CourseOne"
    And I should not see "CourseTwo"
    And I should not see "CourseThree"

    When I select "isn't equal to" from the "Course (multi-item)" singleselect
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"
    And I should see "CourseTwo"
    And I should see "CourseThree"

  Scenario: Filtering required works in rb_filter_date
    Given the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname         | shortname               | source |
      | Test User Report | report_test_user_report | user   |
    And I log in as "admin"

    When I navigate to my "Test User Report" report
    And I press "Edit this report"
    And I switch to "Filters" tab
    And I set the field "newstandardfilter" to "User Last Login"
    And I set the field "newstandardfilteringrequired" to "1"
    And I press "Save changes"
    And I click on "View This Report" "link"
    Then I should see "Following filters are required to be applied in order to run this report: User Last Login"
    And I should see "Please apply all required filters to view this report"

    When I set the field "user-lastlogin_sck" to "1"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"

    When I set the field "user-lastlogin_sck" to "0"
    And I set the field "user-lastlogin_eck" to "1"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"

    When I set the field "user-lastlogin_eck" to "0"
    And I set the field "user-lastlogindaysbeforechkbox" to "1"
    And I set the field "user-lastlogindaysbefore" to "2"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"

    When I set the field "user-lastlogindaysbeforechkbox" to "0"
    And I set the field "user-lastlogindaysafterchkbox" to "1"
    And I set the field "user-lastlogindaysafter" to "2"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"

  Scenario: Filtering required works in rb_filter_enrol
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
      | user3    | User      | Three    | user3@example.com |
    And the following "courses" exist:
      | fullname    | shortname |
      | CourseOne   | Course1   |
      | CourseTwo   | Course2   |
    And the following "course enrolments" exist:
      | user     | course  | role           |
      | user1    | Course1 | editingteacher |
      | user2    | Course2 | student        |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname          | shortname                | source            |
      | Course Completion | report_course_completion | course_completion |
    And I log in as "admin"

    When I navigate to my "Course Completion" report
    And I press "Edit this report"
    And I switch to "Filters" tab
    And I set the field "newstandardfilter" to "Is enrolled"
    And I set the field "newstandardfilteringrequired" to "1"
    And I press "Save changes"
    And I click on "View This Report" "link"
    Then I should see "Following filters are required to be applied in order to run this report: Is enrolled"
    And I should see "Please apply all required filters to view this report"

    When I set the field "course_completion-enrolled" to "Yes"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"

    When I set the field "course_completion-enrolled" to "No"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"

  Scenario: Filtering required works in rb_filter_hierarchy
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
      | user3    | User      | Three    | user3@example.com |
      | user4    | User      | Four     | user4@example.com |
      | user5    | User      | Five     | user5@example.com |
    And the following "organisation frameworks" exist in "totara_hierarchy" plugin:
      | fullname | idnumber |
      | Org Fram | orgfw    |
    And the following "organisations" exist in "totara_hierarchy" plugin:
      | fullname        | idnumber | org_framework |
      | Organisation 1z | org1z    | orgfw         |
      | Organisation 1a | org1a    | orgfw         |
      | Organisation 1b | org1b    | orgfw         |
      | Organisation 2z | org2z    | orgfw         |
    And the following job assignments exist:
      | user  | organisation |
      | user1 | org1z        |
      | user2 | org1a        |
      | user3 | org1b        |
      | user4 | org2z        |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname    | shortname          | source |
      | User report | report_user_report | user   |
    And I log in as "admin"

    When I navigate to my "User report" report
    And I press "Edit this report"
    And I switch to "Filters" tab
    And I set the field "newstandardfilter" to "User's Organisation(s)"
    And I set the field "newstandardfilteringrequired" to "1"
    And I press "Save changes"
    And I click on "View This Report" "link"
    Then I should see "Following filters are required to be applied in order to run this report: User's Organisation(s)"
    And I should see "Please apply all required filters to view this report"

    When I set the field "job_assignment-allorganisations_op" to "Any of the selected"
    And I click on "Choose Organisations" "link" in the "Required search" "fieldset"
    And I click on "Organisation 1z" "link" in the "Choose Organisations" "totaradialogue"
    And I click on "Save" "button" in the "Choose Organisations" "totaradialogue"
    And I wait "1" seconds
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"

    When I set the field "job_assignment-allorganisations_op" to "Not any of the selected"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"

    When I set the field "job_assignment-allorganisations_op" to "All of the selected"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"

    When I set the field "job_assignment-allorganisations_op" to "Not all of the selected"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"

  Scenario: Filtering required works in rb_filter_hierarchy_multi
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
      | user3    | User      | Three    | user3@example.com |
      | user4    | User      | Four     | user4@example.com |
      | user5    | User      | Five     | user5@example.com |
    And the following "organisation frameworks" exist in "totara_hierarchy" plugin:
      | fullname | idnumber |
      | Org Fram | orgfw    |
    And the following "organisations" exist in "totara_hierarchy" plugin:
      | fullname        | idnumber | org_framework |
      | Organisation 1z | org1z    | orgfw         |
      | Organisation 1a | org1a    | orgfw         |
      | Organisation 1b | org1b    | orgfw         |
      | Organisation 2z | org2z    | orgfw         |
    And the following job assignments exist:
      | user  | organisation |
      | user1 | org1z        |
      | user2 | org1a        |
      | user3 | org1b        |
      | user4 | org2z        |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname          | shortname                | source            |
      | Course Completion | report_course_completion | course_completion |
    And I log in as "admin"

    When I navigate to my "Course Completion" report
    And I press "Edit this report"
    And I switch to "Filters" tab
    And I set the field "advanced3" to "0"
    And I set the field "filteringrequired3" to "1"
    And I press "Save changes"
    And I click on "View This Report" "link"
    Then I should see "Following filters are required to be applied in order to run this report: The organisation when completed"
    And I should see "Please apply all required filters to view this report"

    When I set the field "course_completion-organisationpath_op" to "is equal to"
    And I click on "Choose Organisation..." "button" in the "Required search" "fieldset"
    And I click on "Organisation 1z" "link" in the "Choose organisation" "totaradialogue"
    And I click on "OK" "button" in the "Choose organisation" "totaradialogue"
    And I wait "1" seconds
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"

    When I set the field "course_completion-organisationpath_op" to "isn't equal to"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"

  Scenario: Filtering required works in rb_filter_menuofchoices
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
      | Course 2 | C2        |
      | Course 3 | C3        |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname            | shortname                  | source  |
      | Test courses report | report_test_courses_report | courses |
    And I log in as "admin"
    And I navigate to "Custom fields" node in "Site administration > Courses"
    And I set the field "Create a new custom field" to "Menu of choices"
    And I set the following fields to these values:
      | Full name                   | Course menu   |
      | Short name (must be unique) | menuofchoices |
    And I set the field "Menu options (one per line)" to multiline:
      """
      Option 1
      Option 2
      Option 3
      """
    And I press "Save changes"
    And I am on "Course 1" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I expand all fieldsets
    And I set the field "Course menu" to "Option 1"
    And I press "Save and display"
    And I am on "Course 2" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I expand all fieldsets
    And I set the field "Course menu" to "Option 2"
    And I press "Save and display"
    And I am on "Course 3" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I expand all fieldsets
    And I set the field "Course menu" to "Option 3"
    And I press "Save and display"

    When I navigate to my "Test courses report" report
    And I press "Edit this report"
    And I switch to "Filters" tab
    And I set the field "newstandardfilter" to "Course menu"
    And I set the field "newstandardfilteringrequired" to "1"
    And I press "Save changes"
    And I click on "View This Report" "link"
    Then I should see "Following filters are required to be applied in order to run this report: Course menu"
    And I should see "Please apply all required filters to view this report"

    When I set the field "course-custom_field_1" to "Option 1"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"

    When I set the field "course-custom_field_1" to "Option 2"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"

  Scenario: Filtering required works in rb_filter_multicheck not simplemode
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
      | user3    | User      | Three    | user3@example.com |
    And the following "courses" exist:
      | fullname    | shortname |
      | CourseOne   | Course1   |
      | CourseTwo   | Course2   |
    And the following "course enrolments" exist:
      | user     | course  | role           |
      | user1    | Course1 | editingteacher |
      | user2    | Course2 | student        |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname          | shortname                | source            |
      | Course Completion | report_course_completion | course_completion |
    And I log in as "admin"

    When I navigate to my "Course Completion" report
    And I press "Edit this report"
    And I switch to "Filters" tab
    And I set the field "advanced9" to "0"
    And I set the field "filteringrequired9" to "1"
    And I press "Save changes"
    And I click on "View This Report" "link"
    Then I should see "Following filters are required to be applied in order to run this report: Completion Status"
    And I should see "Please apply all required filters to view this report"

    When I set the field "course_completion-status_op" to "Any of the selected"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "Following filters are required to be applied in order to run this report: Completion Status"
    And I should see "Please apply all required filters to view this report"

    When I set the field "course_completion-status_op" to "Any of the selected"
    And I set the field "course_completion-status[10]" to "1"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"

    When I set the field "course_completion-status_op" to "All of the selected"
    And I set the field "course_completion-status[10]" to "1"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"

    When I set the field "course_completion-status_op" to "Not any of the selected"
    And I set the field "course_completion-status[10]" to "1"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"

    When I set the field "course_completion-status_op" to "Not all of the selected"
    And I set the field "course_completion-status[10]" to "1"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"

  Scenario: Filtering required works in rb_filter_multicheck simplemode
    Given the following "courses" exist:
      | fullname    | shortname |
      | CourseOne   | Course1   |
      | CourseTwo   | Course2   |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname     | shortname      | source  |
      | Some Courses | report_courses | courses |
    And I log in as "admin"

    When I navigate to my "Some Courses" report
    And I press "Edit this report"
    And I switch to "Filters" tab
    And I set the field "newstandardfilter" to "Course Type"
    And I set the field "newstandardfilteringrequired" to "1"
    And I press "Save changes"
    And I click on "View This Report" "link"
    Then I should see "Following filters are required to be applied in order to run this report: Course Type"
    And I should see "Please apply all required filters to view this report"

    When I set the field "course-coursetype[1]" to "1"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"

  Scenario: Filtering required works in rb_filter_number
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
      | user3    | User      | Three    | user3@example.com |
    And the following "courses" exist:
      | fullname    | shortname |
      | CourseOne   | Course1   |
      | CourseTwo   | Course2   |
    And the following "course enrolments" exist:
      | user     | course  | role           |
      | user1    | Course1 | editingteacher |
      | user2    | Course2 | student        |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname          | shortname                | source            |
      | Course Completion | report_course_completion | course_completion |
    And I log in as "admin"

    When I navigate to my "Course Completion" report
    And I press "Edit this report"
    And I switch to "Filters" tab
    And I set the field "newstandardfilter" to "Grade"
    And I set the field "newstandardfilteringrequired" to "1"
    And I press "Save changes"
    And I click on "View This Report" "link"
    Then I should see "Following filters are required to be applied in order to run this report: Grade"
    And I should see "Please apply all required filters to view this report"

    When I set the field "id_course_completion-grade_op" to "is equal to"
    And I set the field "course_completion-grade" to "0"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"

    When I set the field "id_course_completion-grade_op" to "is equal to"
    And I set the field "course_completion-grade" to "1"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"

  Scenario: Filtering required works in rb_filter_select simplemode
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
      | user3    | User      | Three    | user3@example.com |
    And the following "courses" exist:
      | fullname    | shortname |
      | CourseOne   | Course1   |
      | CourseTwo   | Course2   |
    And the following "course enrolments" exist:
      | user     | course  | role           |
      | user1    | Course1 | editingteacher |
      | user2    | Course2 | student        |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname    | shortname          | source |
      | User report | report_user_report | user   |
    And I log in as "admin"

    When I navigate to my "User report" report
    And I press "Edit this report"
    And I switch to "Filters" tab
    And I set the field "newstandardfilter" to "User's Country"
    And I set the field "newstandardfilteringrequired" to "1"
    And I press "Save changes"
    And I click on "View This Report" "link"
    Then I should see "Following filters are required to be applied in order to run this report: User's Country"
    And I should see "Please apply all required filters to view this report"

    When I set the field "user-country" to "Czechia"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"

  Scenario: Filtering required works in rb_filter_select not simplemode
    And the following "courses" exist:
      | fullname    | shortname |
      | CourseOne   | Course1   |
      | CourseTwo   | Course2   |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname     | shortname      | source  |
      | Some Courses | report_courses | courses |
    And I log in as "admin"

    When I navigate to my "Some Courses" report
    And I press "Edit this report"
    And I switch to "Filters" tab
    And I set the field "newstandardfilter" to "Course Category"
    And I set the field "newstandardfilteringrequired" to "1"
    And I press "Save changes"
    And I click on "View This Report" "link"
    Then I should see "Following filters are required to be applied in order to run this report: Course Category"
    And I should see "Please apply all required filters to view this report"

    When I set the field "course_category-id_op" to "is equal to"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"

    When I set the field "course_category-id_op" to "isn't equal to"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"

  Scenario: Filtering required works in rb_filter_system_role
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
      | user3    | User      | Three    | user3@example.com |
    And the following "system role assigns" exist:
      | user  | role         | contextlevel | reference |
      | user1 | manager      | System       |           |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname    | shortname          | source |
      | User report | report_user_report | user   |
    And I log in as "admin"

    When I navigate to my "User report" report
    And I press "Edit this report"
    And I switch to "Filters" tab
    And I set the field "newstandardfilter" to "User System Role"
    And I set the field "newstandardfilteringrequired" to "1"
    And I press "Save changes"
    And I click on "View This Report" "link"
    Then I should see "Following filters are required to be applied in order to run this report: User System Role"
    And I should see "Please apply all required filters to view this report"

    When I set the field "user-roleid" to "Any role"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"

    When I set the field "user-roleid" to "Site Manager"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"

    When I click on "Not assigned" "radio"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"

  Scenario: Filtering required works in rb_filter_text
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
      | user3    | User      | Three    | user3@example.com |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname    | shortname          | source |
      | User report | report_user_report | user   |
    And I log in as "admin"

    When I navigate to my "User report" report
    And I press "Edit this report"
    And I switch to "Filters" tab
    And I set the field "newstandardfilter" to "User First Name"
    And I set the field "newstandardfilteringrequired" to "1"
    And I press "Save changes"
    And I click on "View This Report" "link"
    Then I should see "Following filters are required to be applied in order to run this report: User First Name"
    And I should see "Please apply all required filters to view this report"

    When I set the field "user-firstname_op" to "is empty"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"

    When I set the field "user-firstname_op" to "is not empty (NOT NULL)"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"

    When I set the field "user-firstname_op" to "contains"
    And I set the field "user-firstname" to ""
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "Following filters are required to be applied in order to run this report: User First Name"
    And I should see "Please apply all required filters to view this report"

    When I set the field "user-firstname_op" to "contains"
    And I set the field "user-firstname" to "a"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"

    When I set the field "user-firstname_op" to "doesn't contain"
    And I set the field "user-firstname" to ""
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "Following filters are required to be applied in order to run this report: User First Name"
    And I should see "Please apply all required filters to view this report"

    When I set the field "user-firstname_op" to "doesn't contain"
    And I set the field "user-firstname" to "a"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"

    When I set the field "user-firstname_op" to "is equal to"
    And I set the field "user-firstname" to ""
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "Following filters are required to be applied in order to run this report: User First Name"
    And I should see "Please apply all required filters to view this report"

    When I set the field "user-firstname_op" to "is equal to"
    And I set the field "user-firstname" to "a"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"

    When I set the field "user-firstname_op" to "starts with"
    And I set the field "user-firstname" to ""
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "Following filters are required to be applied in order to run this report: User First Name"
    And I should see "Please apply all required filters to view this report"

    When I set the field "user-firstname_op" to "starts with"
    And I set the field "user-firstname" to "a"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"

    When I set the field "user-firstname_op" to "ends with"
    And I set the field "user-firstname" to ""
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "Following filters are required to be applied in order to run this report: User First Name"
    And I should see "Please apply all required filters to view this report"

    When I set the field "user-firstname_op" to "ends with"
    And I set the field "user-firstname" to "a"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"

  Scenario: Filtering required works in rb_filter_textarea
    Given the following "courses" exist:
      | fullname  | shortname |
      | Course 0  | c0        |
      | Course 1z | c1        |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname      | shortname            | source  |
      | Course report | report_course_report | courses |
    And I log in as "admin"

    When I navigate to my "Course report" report
    And I press "Edit this report"
    And I switch to "Filters" tab
    And I set the field "newstandardfilter" to "Course Name and Summary"
    And I set the field "newstandardfilteringrequired" to "1"
    And I press "Save changes"
    And I click on "View This Report" "link"
    Then I should see "Following filters are required to be applied in order to run this report: Course Name and Summary"
    And I should see "Please apply all required filters to view this report"

    When I set the field "course-name_and_summary_op" to "contains"
    And I set the field "course-name_and_summary" to ""
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "Following filters are required to be applied in order to run this report: Course Name and Summary"
    And I should see "Please apply all required filters to view this report"

    When I set the field "course-name_and_summary_op" to "contains"
    And I set the field "course-name_and_summary" to "a"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"

    When I set the field "course-name_and_summary_op" to "doesn't contain"
    And I set the field "course-name_and_summary" to ""
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "Following filters are required to be applied in order to run this report: Course Name and Summary"
    And I should see "Please apply all required filters to view this report"

    When I set the field "course-name_and_summary_op" to "doesn't contain"
    And I set the field "course-name_and_summary" to "a"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Following filters are required to be applied in order to run this report:"
    And I should not see "Please apply all required filters to view this report"
