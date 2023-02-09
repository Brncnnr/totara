@core
Feature: Reset password

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | testuser | Test | User | moodle@example.com |

  Scenario: Invalid username password reset does not show any error
    Given I use magic for persistent login to open the login page
    And I follow "Forgot username or password"
    And I set the field "Username" to "xyz"
    When I press "submitbuttonusername"
    Then I should see "If you supplied a correct username or email address then an email should have been sent to you."

  Scenario: Invalid email password reset does not show any error
    Given I use magic for persistent login to open the login page
    And I follow "Forgot username or password"
    And I set the field "Email address" to "xyz@example.com"
    When I press "submitbuttonemail"
    Then I should see "If you supplied a correct username or email address then an email should have been sent to you."

