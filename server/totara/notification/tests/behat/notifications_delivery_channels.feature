@totara @totara_notification @javascript @vuejs
Feature: Notifications delivery channels
  As a notifications administrator
  I can change the default delivery channels set on a notifiable event
  so they can be available to be overridden by users.

  Background:
    Given I log in as "admin"
    And I navigate to "Manage message outputs" node in "Site administration > Plugins > Message outputs"
    And I click on "//table[contains(@class, 'admintable')]/tbody/tr/td[contains(text(), 'Email')]/following-sibling::td[1]/a" "xpath_element"
    And I navigate to "Plugins > Message outputs > Totara AirNotifier" in site administration
    And I set the following fields to these values:
      | AirNotifier App Code | 0123456789abcdef |
    And I click on "Save changes" "button"
    And I log out

  Scenario: Delivery channels are visible and configurable at the admin level
    Given I log in as "admin"
    And I navigate to system notifications page
    Then I should see "Certification"

    When I click on "Certification" "button"
    Then I should see "Learner assigned in certification"
    And I should see "Default delivery channels"
    When I click on "Actions for Learner assigned in certification event" "button"
    And I click on "Edit delivery channels" "link"
    Then I should see "Edit delivery channels" in the ".tui-modalContent__header-title" "css_element"
    And I should see "Notification trigger: Learner assigned in certification" in the ".tui-modalContent__content" "css_element"
    And the field "Site notifications" matches value "1"
    And the field "Mobile app notifications" matches value "0"
    And the field "Tasks" matches value "0"
    And the field "Alerts" matches value "0"
    And the field "Email" matches value "1"
    And the field "Microsoft Teams" matches value "0"

    # Check that the parent/child toggle works
    When I click on the "default_popup" tui checkbox
    Then the field "Site notifications" matches value "0"
    And "Mobile app notifications" "checkbox" should not exist
    And "Tasks" "checkbox" should not exist
    And "Alerts" "checkbox" should not exist
    And the field "Microsoft Teams" matches value "0"

    # Save the changes
    When I click on "Save" "button"
    And I wait for the next second
    And I click on "Actions for Learner assigned in certification event" "button"
    And I click on "Edit delivery channels" "link"
    Then the field "Site notifications" matches value "0"
    And "Mobile app notifications" "checkbox" should not exist
    And "Tasks" "checkbox" should not exist
    And "Alerts" "checkbox" should not exist
    And the field "Microsoft Teams" matches value "0"

  Scenario: Delivery channels are visible but not configurable at the certification level
    Given I log in as "admin"
    And the following "certifications" exist in "totara_program" plugin:
      | fullname          | shortname | activeperiod | windowperiod | recertifydatetype |
      | Certification One | cert1     | 1 month      | 1 month      | 1                 |
    And I am on "Certification One" certification homepage
    And I press "Edit certification details"
    And I switch to "Notifications" tab
    Then I should see "Notifications"

    Then I should see "Learner assigned in certification"
    And I should see "Default delivery channels"
    When I click on "Actions for Learner assigned in certification event" "button"
    Then I should not see "Delivery preferences"

  Scenario: Delivery channels are visible and overridable at the user preference level
    Given I log in as "admin"
    And I follow "Preferences" in the user menu
    And I follow "Notification preferences"
    Then I should see "Notification preferences"
    And I should see "Certification"

    When I click on "Certification" "button"
    Then I should see "Learner assigned in certification"
    And I should see "Default delivery channels"
    And I should see "Site notifications; Email"

    When I click on "Actions for Learner assigned in certification event" "button"
    And I click on "Edit delivery channels" "link"
    Then I should see "Delivery preferences" in the ".tui-modalContent__header-title" "css_element"
    And I should see "Notification trigger: Learner assigned in certification" in the ".tui-modalContent__content" "css_element"
    And the field "Override" matches value "0"
    And the "Site notifications" "checkbox" should be disabled
    And the "Mobile app notifications" "checkbox" should be disabled
    And the "Tasks" "checkbox" should be disabled
    And the "Alerts" "checkbox" should be disabled
    And the "Email" "checkbox" should be disabled
    And the "Microsoft Teams" "checkbox" should be disabled

    When I click on the "override_delivery_preferences" tui checkbox
    Then the "Site notifications" "checkbox" should be enabled
    And the "Mobile app notifications" "checkbox" should be enabled
    And the "Tasks" "checkbox" should be enabled
    And the "Alerts" "checkbox" should be enabled
    And the "Email" "checkbox" should be enabled
    And the "Microsoft Teams" "checkbox" should be enabled

    # Turn the override back off
    When I click on the "override_delivery_preferences" tui checkbox
    Then the "Site notifications" "checkbox" should be disabled
    And the "Email" "checkbox" should be disabled
    And the "Microsoft Teams" "checkbox" should be disabled

  Scenario: Delivery channels for disabled outputs are not visible at all
    Given I log in as "admin"
    And I navigate to "Plugins > Message outputs > Totara AirNotifier" in site administration
    And I set the following fields to these values:
      | AirNotifier App Code |  |
    And I click on "Save changes" "button"
    And I navigate to system notifications page
    Then I should see "Certification"

    When I click on "Certification" "button"
    And I click on "Actions for Learner assigned in certification event" "button"
    And I click on "Edit delivery channels" "link"
    Then "Mobile app notifications" "checkbox" should not exist

    When I click on "Cancel" "button"
    And I follow "Preferences" in the user menu
    And I follow "Notification preferences"
    And I click on "Certification" "button"
    And I click on "Actions for Learner assigned in certification event" "button"
    And I click on "Edit delivery channels" "link"
    Then "Mobile app notifications" "checkbox" should not exist