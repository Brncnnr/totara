@totara @totara_notification @javascript @vuejs
Feature: Overridden notifications at lower context

  Scenario: Admin edit inherited notification at the system context
    Given I log in as "admin"
    And I navigate to "Manage message outputs" node in "Site administration > Plugins > Message outputs"
    And I click on "//table[contains(@class, 'admintable')]/tbody/tr/td[contains(text(), 'Email')]/following-sibling::td[1]/a" "xpath_element"
    And I navigate to system notifications page

    When I click on "Certification" "button"
    Then I should see "Course set completed"
    And "Course set completed details" "button" should exist
    And "Actions for Course set completed event" "button" should exist

    When I click on "Actions for Course set completed event" "button"
    Then I should see "Create notification"
    And "Create notification" "link" should exist

    When I click on "Course set completed details" "button"
    Then I should see "Certification course set completed"

    When I click on "Actions for Certification course set completed" "button"
    Then I should see "Edit"
    And "Edit" "link" should exist

    When I click on "//a[@title='Edit notification Certification course set completed'][contains(text(),'Edit')]" "xpath_element"
    Then I should not see "Overridden subject at system"
    And I should not see "Overridden body at system"
    And the "Enable customising field recipient" tui toggle switch should be "off"
    And the "Enable customising field subject" tui toggle switch should be "off"
    And the "Enable customising field schedule" tui toggle switch should be "off"
    And the "Enable customising field body" tui toggle switch should be "off"
    And the "Enable customising forced delivery channels" tui toggle switch should be "off"
    And the "Enable customising notification status" tui toggle switch should be "off"

    And the "Manager" "field" should be disabled
    And the "Name" "field" should be disabled
    And the "On notification trigger event" "field" should be disabled
    And the "Days after" "field" should be disabled
    And the "Enabled" "field" should be disabled
    And the "Force channel Email" "checkbox" should be disabled
    And the "Force channel Site notifications" "checkbox" should be disabled
    And the "Force channel Microsoft Teams" "checkbox" should be disabled
    And the "Force channel Alerts" "checkbox" should be disabled
    And the "Force channel Tasks" "checkbox" should be disabled

    When I click on the "Enable customising field recipient" tui toggle button
    And I click on the "Enable customising field subject" tui toggle button
    And I click on the "Enable customising field schedule" tui toggle button
    And I click on the "Enable customising field body" tui toggle button
    And I click on the "Enable customising forced delivery channels" tui toggle button
    And I click on the "Enable customising notification status" tui toggle button
    Then the "Manager" "field" should be enabled
    And the "On notification trigger event" "field" should be enabled
    And the "Days after" "field" should be enabled
    And the "Force channel Email" "checkbox" should be enabled
    And the "Force channel Site notifications" "checkbox" should be enabled
    And the "Force channel Microsoft Teams" "checkbox" should be enabled
    And the "Force channel Alerts" "checkbox" should be enabled
    And the "Force channel Tasks" "checkbox" should be enabled
    And the "Enabled" "field" should be enabled

    When the field "Manager" matches value "totara_notification\recipient\manager"
    And I set the weka editor with css ".tui-notificationPreferenceForm__subjectEditor" to "Overridden subject at system <hello>"
    And I set the weka editor with css ".tui-notificationPreferenceForm__bodyEditor" to "Overridden body at system <hello>"
    And I click on the "Days after" tui radio
    And I set the field "Number" to "3"
    And I click on the "force_email" tui checkbox
    And I click on the "force_popup" tui checkbox
    # The status field that handled by TUI form. At this point it does not understand the label associated with it.
    # Hence we are going to have to use the checkbox's field name.
    And I click on the "enabled[value]" tui checkbox
    And I click on "Save" "button"
    And I click on "Actions for Certification course set completed" "button"
    Then I click on "//a[@title='Edit notification Certification course set completed'][contains(text(),'Edit')]" "xpath_element"
    Then the "Manager" "field" should be enabled
    And the "On notification trigger event" "field" should be enabled
    And the "Days after" "field" should be enabled
    And the "Enabled" "field" should be enabled
    Then the field "Manager" matches value "totara_notification\recipient\manager"
    And I should see "Overridden subject at system <hello>"
    And I should see "Overridden body at system <hello>"
    And the field "Number" matches value "3"
    And the field "Force channel Email" matches value "email"
    And the field "Force channel Site notifications" matches value "popup"
    And the field "Force channel Microsoft Teams" does not match value "msteams"
    And the field "Force channel Alerts" does not match value "totara_alert"
    And the field "Force channel Tasks" does not match value "totara_task"
    # By default the notification preference is disabled, hence after click should result it to be enabled.
    And the field "Enabled" matches value "1"

    When I click on the "Enable customising field recipient" tui toggle button
    And I click on the "Enable customising field subject" tui toggle button
    And I click on the "Enable customising field schedule" tui toggle button
    And I click on the "Enable customising field body" tui toggle button
    And I click on the "Enable customising forced delivery channels" tui toggle button
    And I click on the "Enable customising notification status" tui toggle button
    Then I should not see "Overridden subject at system"
    And I should not see "Overridden body at system"
    And the "Force channel Email" "checkbox" should be disabled
    And the "Force channel Site notifications" "checkbox" should be disabled
    And the "Force channel Microsoft Teams" "checkbox" should be disabled
    And the "Force channel Alerts" "checkbox" should be disabled
    And the "Force channel Tasks" "checkbox" should be disabled
    And the field "Force channel Email" does not match value "email"
    And the field "Force channel Site notifications" does not match value "popup"
    And the field "Force channel Microsoft Teams" does not match value "msteams"
    And the field "Force channel Alerts" does not match value "totara_alert"
    And the field "Force channel Tasks" does not match value "totara_task"

    When I click on "Save" "button"
    And I click on "Actions for Certification course set completed" "button"
    And I click on "//a[@title='Edit notification Certification course set completed'][contains(text(),'Edit')]" "xpath_element"
    Then I should not see "Overridden subject at system"
    And I should not see "Overridden body at system"
    And the field "Number" does not match value "3"
    And the "Force channel Email" "checkbox" should be disabled
    And the "Force channel Site notifications" "checkbox" should be disabled
    And the "Force channel Microsoft Teams" "checkbox" should be disabled
    And the "Force channel Alerts" "checkbox" should be disabled
    And the "Force channel Tasks" "checkbox" should be disabled
    And the field "Force channel Email" does not match value "email"
    And the field "Force channel Site notifications" does not match value "popup"
    And the field "Force channel Microsoft Teams" does not match value "msteams"
    And the field "Force channel Alerts" does not match value "totara_alert"
    And the field "Force channel Tasks" does not match value "totara_task"
    # Default of status is disabled.
    And the field "Enabled" matches value "0"