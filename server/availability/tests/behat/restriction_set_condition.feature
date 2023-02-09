@core @core_availability @availability @availability_restriction @javascript
Feature: Restriction set of course's restriction is appearing when user editing it
  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | c101     | c101      | 0        | 1                |
    And the following "cohorts" exist:
      | name  | idnumber |
      | Hunga | hunga    |
    And the following "position frameworks" exist in "totara_hierarchy" plugin:
      | fullname               | idnumber |
      | Position Framework 001 | PFW001   |
    And the following "positions" exist in "totara_hierarchy" plugin:
      | pos_framework | fullname | idnumber |
      | PFW001        | Tunga1   | POS001   |
      | PFW001        | Tunga2   | POS002   |
    And the following "organisation" frameworks exist:
      | fullname                 | idnumber |
      | Organisation Framework 1 | OF1      |
    And the following "organisation" hierarchy exists:
      | framework | fullname | idnumber | description |
      | OF1       | Ropu 1   | O1       | Ropu #1     |
      | OF1       | Ropu 2   | O2       | Ropu #2     |
    And I am on a totara site
    And I log in as "admin"

  Scenario: User is adding the restriction and going to edit restriction set afterward
    Given I am on "c101" course homepage with editing mode on
    And I edit the section "1"
    And I follow "Restrict access"

    And I click on "Add restriction..." "button"
    And I click on "Restriction set" "button"

    And I click on "Add restriction..." "button"
    And I click on "Assigned to Organisation" "button"
    And I set the field "Assigned to Organisation" to "Ropu 1"
    And I press key "13" in the field "Assigned to Organisation"

    And I click on "Add restriction..." "button"
    And I click on "Assigned to Position" "button"
    And I set the field "Assigned to Position" to "Tunga1"
    And I press key "13" in the field "Assigned to Position"

    And I click on "Add restriction..." "button"
    And I click on "Member of Audience" "button"
    And I set the field "Member of Audience" to "Hunga"
    And I press key "13" in the field "Member of Audience"

    And I click on "Save changes" "button"
    When I edit the section "1"
    And I follow "Restrict access"
    Then I should see "Ropu 1"
    And I should see "Tunga1"
    And I should see "Hunga"

  @mod @mod_survey
  Scenario: User is adding the restriction to survey and going to edit restriction set afterward
    Given I navigate to "Manage activities" node in "Site administration > Plugins > Activity modules"
    And I click on "Show Survey" "link" in the "Survey" "table_row"
    And I am on "c101" course homepage with editing mode on
    And I follow "Add an activity or resource"
    When I click on "Survey" "radio" in the "Add an activity or resource" "dialogue"
    And I click on "Add" "button" in the "Add an activity or resource" "dialogue"
    And I set the field "Name" to "Pukapuka Uiui"
    And I set the field "Survey type" to "COLLES (Actual)"
    And I follow "Restrict access"

    And I click on "Add restriction..." "button"
    And I click on "Restriction set" "button"

    And I click on "Add restriction..." "button"
    And I click on "Assigned to Organisation" "button"
    And I set the field "Assigned to Organisation" to "Ropu 1"
    And I press key "13" in the field "Assigned to Organisation"

    And I click on "Add restriction..." "button"
    And I click on "Assigned to Position" "button"
    And I set the field "Assigned to Position" to "Tunga1"
    And I press key "13" in the field "Assigned to Position"

    And I click on "Add restriction..." "button"
    And I click on "Member of Audience" "button"
    And I set the field "Member of Audience" to "Hunga"
    And I press key "13" in the field "Member of Audience"

    And I click on "Add restriction..." "button"
    And I click on "Grade" "button"

    When I click on "Save and display" "button"
    Then I should see "You must select a grade item for the grade condition."
    And I should see "Ropu 1"
    And I should see "Tunga1"
    And I should see "Hunga"

    And I click on "Delete" "link" in the ".availability-item:last-child" "css_element"
    When I click on "Save and display" "button"
    Then I should not see "Adding a new Survey"
    And I should see "Pukapuka Uiui"

    When I navigate to "Edit settings" node in "Survey administration"
    And I follow "Restrict access"
    Then I should see "Ropu 1"
    And I should see "Tunga1"
    And I should see "Hunga"

  @mod @mod_quiz
  Scenario: User is adding the restriction to quiz and going to edit restriction set afterward
    Given I am on "c101" course homepage with editing mode on
    And I follow "Add an activity or resource"
    When I click on "Quiz" "radio" in the "Add an activity or resource" "dialogue"
    And I click on "Add" "button" in the "Add an activity or resource" "dialogue"
    And I set the field "Name" to "Pataitaitanga"
    And I follow "Restrict access"

    And I click on "Add restriction..." "button"
    And I click on "Restriction set" "button"

    And I click on "Add restriction..." "button"
    And I click on "Restriction set" "button"

    And I click on "Add restriction..." "button"
    And I click on "Restriction set" "button"

    And I click on "Add restriction..." "button" in the ".availability-children .availability-children .availability-children .availability-button" "css_element"
    And I click on "Date" "button"

    And I click on "Add restriction..." "button" in the ".availability-field > .availability-list > .availability-inner > .availability-children > .availability-list > .availability-inner > .availability-children > .availability-list > .availability-inner > .availability-button" "css_element"
    And I click on "Date" "button"

    And I click on "Add restriction..." "button" in the ".availability-field > .availability-list > .availability-inner > .availability-children > .availability-list > .availability-inner > .availability-button" "css_element"
    And I click on "Date" "button"

    And I click on "Add restriction..." "button" in the ".availability-field > .availability-list > .availability-inner > .availability-button" "css_element"
    And I click on "Date" "button"

    When I click on "Save and return to course" "button"
    Then I should not see "please select"
    And I click on ".toggle-display" "css_element" in the "Pataitaitanga" activity
    And I click on "Edit settings" "link" in the "Pataitaitanga" activity
    And I follow "Restrict access"
    Then I should see "Add restriction..." in the ".availability-children .availability-children .availability-children .availability-button" "css_element"
    And I should see "Date" in the ".availability-children .availability-children .availability-children .availability-children .availability_date" "css_element"
    And I should see "Date" in the ".availability-field > .availability-list > .availability-inner > .availability-children > .availability-list > .availability-inner > .availability-children > .availability-list > .availability-inner > .availability-children > .availability-item > .availability_date" "css_element"
    And I should see "Date" in the ".availability-field > .availability-list > .availability-inner > .availability-children > .availability-list > .availability-inner > .availability-children > .availability-item > .availability_date" "css_element"
    And I should see "Date" in the ".availability-field > .availability-list > .availability-inner > .availability-children > .availability-item > .availability_date" "css_element"

  @mod @mod_assign
  Scenario: User is adding the restriction to assignment and going to remove audience, position and organisation afterward
    Given I am on "c101" course homepage with editing mode on
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Tuhinga 1 |
      | Completion tracking | Show activity as complete when conditions are met |
      | Learner must submit to this activity to complete it | 1 |

    And I follow "Add an activity or resource"
    And I click on "Assignment" "radio" in the "Add an activity or resource" "dialogue"
    And I click on "Add" "button" in the "Add an activity or resource" "dialogue"
    And I set the field "Assignment name" to "Tuhinga 2"
    And I follow "Restrict access"

    And I click on "Add restriction..." "button"
    And I click on "Restriction set" "button"

    And I click on "Add restriction..." "button"
    And I click on "Restriction set" "button"

    And I click on "Add restriction..." "button"
    And I click on "Restriction set" "button"

    And I click on "Add restriction..." "button" in the ".availability-children .availability-children .availability-children .availability-button" "css_element"
    And I click on "Assigned to Organisation" "button"
    And I set the field "Assigned to Organisation" to "Ropu 1"
    And I press key "13" in the field "Assigned to Organisation"

    And I click on "Add restriction..." "button" in the ".availability-field > .availability-list > .availability-inner > .availability-children > .availability-list > .availability-inner > .availability-children > .availability-list > .availability-inner > .availability-button" "css_element"
    And I click on "Assigned to Position" "button"
    And I set the field "Assigned to Position" to "Tunga1"
    And I press key "13" in the field "Assigned to Position"

    And I click on "Add restriction..." "button" in the ".availability-field > .availability-list > .availability-inner > .availability-children > .availability-list > .availability-inner > .availability-button" "css_element"
    And I click on "Member of Audience" "button"
    And I set the field "Member of Audience" to "Hunga"
    And I press key "13" in the field "Member of Audience"

    And I click on "Add restriction..." "button" in the ".availability-field > .availability-list > .availability-inner > .availability-button" "css_element"
    And I click on "Activity completion" "button" in the "Add restriction..." "dialogue"
    And I set the field "Activity or resource" to "Tuhinga 1"

    When I click on "Save and return to course" "button"
    Then I should see "Tuhinga 2"
    And I should see "You are assigned to the Organisation: Ropu 1"
    And I should see "You are assigned to the Position: Tunga1"
    And I should see "You are a member of the Audience: Hunga"
    And I should see "The activity Tuhinga 1 is marked complete"

    And I navigate to "Audiences > Audiences" in site administration
    And I click on "Delete" "link" in the "Hunga" "table_row"
    And I press "Delete"
    And I navigate to "Organisations >  Manage organisations" in site administration
    And I click on "Delete" "link" in the "Organisation Framework 1" "table_row"
    And I press "Yes"
    And I navigate to "Positions > Manage positions" in site administration
    And I click on "Delete" "link" in the "Position Framework 001" "table_row"
    And I press "Yes"

    When I am on "c101" course homepage
    Then I should not see "Hunga" in the ".availabilityinfo" "css_element"
    And I should not see "Tunga1" in the ".availabilityinfo" "css_element"
    And I should not see "Ropu 1" in the ".availabilityinfo" "css_element"
    And I should see "(Missing audience)" in the ".availabilityinfo" "css_element"
    And I should see "(Missing position)" in the ".availabilityinfo" "css_element"
    And I should see "(Missing organisation)" in the ".availabilityinfo" "css_element"
    But I should see "The activity Tuhinga 1 is marked complete" in the ".availabilityinfo" "css_element"

    When I click on ".toggle-display" "css_element" in the "Tuhinga 2" activity
    And I click on "Edit settings" "link" in the "Tuhinga 2" activity
    And I expand all fieldsets
    Then I should not see "Hunga" in the "#fitem_id_availabilityconditionsjson" "css_element"
    And I should not see "Tunga1" in the "#fitem_id_availabilityconditionsjson" "css_element"
    And I should not see "Ropu 1" in the "#fitem_id_availabilityconditionsjson" "css_element"
    And I should see "Please set" in the "//*[contains(@class, 'availability-item') and contains(.,'Member of Audience')]" "xpath_element"
    And I should see "Please set" in the "//*[contains(@class, 'availability-item') and contains(.,'Assigned to Position')]" "xpath_element"
    And I should see "Please set" in the "//*[contains(@class, 'availability-item') and contains(.,'Assigned to Organisation')]" "xpath_element"
    But the field "Activity or resource" matches value "Tuhinga 1"
