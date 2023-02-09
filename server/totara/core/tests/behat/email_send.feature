@totara @totara_core @javascript
Feature: Test email capture in Behat
  Testing the new Behat email capture facility.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   |  One     | student1@example.com |
      | student2 | Student   |  Two     | student2@example.com |
      | student3 | Student   |  Three   | student3@example.com |
      | student4 | Student   |  Four    | student4@example.com |
      | student5 | Student   |  Five    | student5@example.com |

  Scenario: capture emails only when the email sink is explicitly enabled
    # The email sink is not enabled by default; so no emails should be captured
    # at this point. This is an important check to ensure existing Behat tests
    # are not affected.
    When I send a reset password email to "student1"
    Then the following emails should not have been sent:
      | To                   |
      | student1@example.com |

    # Emails only get captured after enabling the email sink.
    When I reset the email sink
    And I send a reset password email to "student2"
    Then the following emails should have been sent:
      | To                   | Subject                      | Body                                      |
      | student2@example.com | Change password confirmation | requested a new password for your account |

    # Captured emails are retained until the sink is reset.
    When I send a reset password email to "student3"
    Then the following emails should have been sent:
      | To                   | Subject                      | Body                                      |
      | student2@example.com | Change password confirmation | requested a new password for your account |
      | student3@example.com | Change password confirmation | requested a new password for your account |

    # Resetting the sink clears captured emails - as if they were never sent.
    When I reset the email sink
    And I send a reset password email to "student4"
    Then the following emails should have been sent:
      | To                   | Subject                      | Body                                      |
      | student4@example.com | Change password confirmation | requested a new password for your account |
    And the following emails should not have been sent:
      | To                   |
      | student2@example.com |
      | student3@example.com |

    # Email capture stops when the email sink is closed. This also clears all
    # previously captured emails as well.
    When I close the email sink
    And I send a reset password email to "student5"
    Then the following emails should not have been sent:
      | To                   |
      | student4@example.com |
      | student5@example.com |
