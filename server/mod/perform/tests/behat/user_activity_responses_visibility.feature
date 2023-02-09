@totara @perform @mod_perform @javascript @vuejs
Feature: Visibility of my responses

  Background:
    Given the following "users" exist:
      | username | firstname | lastname  | email             |
      | sean     | Sean      | Subject   | sean@example.com  |
      | manny    | Manny     | Manager   | manny@example.com |
      | ava      | Ava       | Appraiser | ava@example.com   |
    And the following "subject instances" exist in "mod_perform" plugin:
      | activity_name                                   | subject_username | subject_is_participating | other_participant_username | third_participant_username | relationships_can_view      | relationships_can_answer    | anonymous_responses |
      | View-only Subject only can view responses       | sean             | true                     | manny                      | ava                        | subject                     | manager, appraiser          | false               |
      | View-only Subject only can view anon responses  | sean             | true                     | manny                      | ava                        | subject                     | manager, appraiser          | true                |
      | No one can view responses                       | sean             | true                     | manny                      | ava                        |                             | manager, appraiser, subject | false               |
      | No one can view anon responses                  | sean             | true                     | manny                      | ava                        |                             | manager, appraiser, subject | true                |
      | Everyone can view responses                     | sean             | true                     | manny                      | ava                        | manager, appraiser, subject | manager, appraiser, subject | false               |
      | Everyone can view anon responses                | sean             | true                     | manny                      | ava                        | manager, appraiser, subject | manager, appraiser, subject | true                |
      | Subject only can view responses                 | sean             | true                     | manny                      | ava                        | subject                     | manager, appraiser, subject | false               |
      | Subject only can view anon responses            | sean             | true                     | manny                      | ava                        | subject                     | manager, appraiser, subject | true                |
      | Managers only can view responses                | sean             | true                     | manny                      | ava                        | manager                     | manager, appraiser, subject | false               |
      | Managers only can view anon responses           | sean             | true                     | manny                      | ava                        | manager                     | manager, appraiser, subject | true                |
      | Appraisers only can view responses              | sean             | true                     | manny                      | ava                        | appraiser                   | manager, appraiser, subject | false               |
      | Appraisers only can view anon responses         | sean             | true                     | manny                      | ava                        | appraiser                   | manager, appraiser, subject | true                |
      | Managers and Appraisers can view responses      | sean             | true                     | manny                      | ava                        | manager, appraiser          | manager, appraiser, subject | false               |
      | Managers and Appraisers can view anon responses | sean             | true                     | manny                      | ava                        | manager, appraiser          | manager, appraiser, subject | true                |
      | Single subject participant section              | sean             | true                     |                            |                            | subject                     | manager, appraiser, subject | false               |
      | Single manager participant section              | sean             | false                    | manny                      |                            | manager                     | manager, appraiser, subject | false               |

  Scenario Outline: Viewing visibility of my responses
    Given I log in as "sean"

    When I navigate to the outstanding perform activities list page
    And I click on "<activity>" "link"
    Then I should see "<activity>" in the ".tui-performUserActivity h2" "css_element"
    And I should see "<banner_label>" in the perform activity response visibility description
    And I should see "<banner_content>" in the perform activity response visibility description
    And I should see "<banner_end>" in the perform activity response visibility description
    And <toggle_step>

    Examples:
      | activity                                        | banner_label                                                                                                | banner_content                                                 | banner_end                                                            | toggle_step                                              |
      | View-only Subject only can view responses       | View-only                                                                                                   | Responses are displayed as soon as a participant has submitted |                                                                       | I should not see the show others responses toggle        |
      | View-only Subject only can view anon responses  | View-only                                                                                                   | Responses are displayed as soon as a participant has submitted |                                                                       | I should not see the show others responses toggle        |
      | No one can view responses                       | Your responses are visible only to those granted access to performance reports, but not other participants. |                                                                |                                                                       | I should not see the show others responses toggle        |
      | No one can view anon responses                  | Your responses are visible only to those granted access to performance reports, but not other participants. |                                                                |                                                                       | I should not see the show others responses toggle        |
      | Everyone can view responses                     | Your responses (once submitted) are visible to:                                                             | your Managers, your Appraisers                                 | They are also visible to those granted access to performance reports. | I should see that show others responses is toggled "off" |
      | Everyone can view anon responses                | Your anonymised responses (once submitted) are visible to:                                                  | your Managers, your Appraisers                                 | They are also visible to those granted access to performance reports. | I should see that show others responses is toggled "off" |
      | Subject only can view responses                 | Your responses are visible only to those granted access to performance reports, but not other participants. |                                                                |                                                                       | I should see that show others responses is toggled "off" |
      | Subject only can view anon responses            | Your responses are visible only to those granted access to performance reports, but not other participants. |                                                                |                                                                       | I should see that show others responses is toggled "off" |
      | Managers only can view responses                | Your responses (once submitted) are visible to:                                                             | your Managers                                                  | They are also visible to those granted access to performance reports. | I should not see the show others responses toggle        |
      | Managers only can view anon responses           | Your anonymised responses (once submitted) are visible to:                                                  | your Managers                                                  | They are also visible to those granted access to performance reports. | I should not see the show others responses toggle        |
      | Appraisers only can view responses              | Your responses (once submitted) are visible to:                                                             | your Appraisers                                                | They are also visible to those granted access to performance reports. | I should not see the show others responses toggle        |
      | Appraisers only can view anon responses         | Your anonymised responses (once submitted) are visible to:                                                  | your Appraisers                                                | They are also visible to those granted access to performance reports. | I should not see the show others responses toggle        |
      | Managers and Appraisers can view responses      | Your responses (once submitted) are visible to:                                                             | your Managers, your Appraisers                                 | They are also visible to those granted access to performance reports. | I should not see the show others responses toggle        |
      | Managers and Appraisers can view anon responses | Your anonymised responses (once submitted) are visible to:                                                  | your Managers, your Appraisers                                 | They are also visible to those granted access to performance reports. | I should not see the show others responses toggle        |
      | Single subject participant section              | Your responses are visible only to those granted access to performance reports, but not other participants. |                                                                |                                                                       | I should not see the show others responses toggle        |

  Scenario Outline: Viewing responses as a non subject participant
    Given I log in as "manny"

    When I navigate to the outstanding perform activities list page
    And I click on "As Manager" "link"
    And I click on "<activity>" "link"
    Then I should see "<activity>" in the ".tui-performUserActivity h2" "css_element"
    And I should see "<banner_label>" in the perform activity response visibility description
    And I should see "<banner_content>" in the perform activity response visibility description
    And I should see "<banner_end>" in the perform activity response visibility description
    And <toggle_step>

    Examples:
      | activity                                        | banner_label                                                                                               | banner_content                                     | banner_end                                                            | toggle_step                                              |
      | No one can view responses                       | Your responses are visible only to those granted access to performance reports, but not other participants |                                                    |                                                                       | I should not see the show others responses toggle        |
      | No one can view anon responses                  | Your responses are visible only to those granted access to performance reports, but not other participants |                                                    |                                                                       | I should not see the show others responses toggle        |
      | Everyone can view responses                     | Your responses (once submitted) are visible to:                                                            | the employee's Managers, the employee's Appraisers | They are also visible to those granted access to performance reports. | I should see that show others responses is toggled "off" |
      | Everyone can view anon responses                | Your anonymised responses (once submitted) are visible to:                                                 | the employee's Managers, the employee's Appraisers | They are also visible to those granted access to performance reports. | I should see that show others responses is toggled "off" |
      | Subject only can view responses                 | Your responses (once submitted) are visible to:                                                            | the Employee                                       | They are also visible to those granted access to performance reports. | I should see that show others responses is toggled "off" |
      | Subject only can view anon responses            | Your anonymised responses (once submitted) are visible to:                                                 | the Employee                                       | They are also visible to those granted access to performance reports. | I should see that show others responses is toggled "off" |
      | Managers only can view responses                | Your responses (once submitted) are visible to:                                                            | the employee's Managers                            | They are also visible to those granted access to performance reports. | I should see that show others responses is toggled "off" |
      | Managers only can view anon responses           | Your anonymised responses (once submitted) are visible to:                                                 | the employee's Managers                            | They are also visible to those granted access to performance reports. | I should see that show others responses is toggled "off" |
      | Appraisers only can view responses              | Your responses (once submitted) are visible to:                                                            | the employee's Appraisers                          | They are also visible to those granted access to performance reports. | I should not see the show others responses toggle        |
      | Appraisers only can view anon responses         | Your anonymised responses (once submitted) are visible to:                                                 | the employee's Appraisers                          | They are also visible to those granted access to performance reports. | I should not see the show others responses toggle        |
      | Managers and Appraisers can view responses      | Your responses (once submitted) are visible to:                                                            | the employee's Managers, the employee's Appraisers | They are also visible to those granted access to performance reports. | I should see that show others responses is toggled "off" |
      | Managers and Appraisers can view anon responses | Your anonymised responses (once submitted) are visible to:                                                 | the employee's Managers, the employee's Appraisers | They are also visible to those granted access to performance reports. | I should see that show others responses is toggled "off" |
      | Single manager participant section              | Your responses (once submitted) are visible to:                                                            | the employee's Managers                            | They are also visible to those granted access to performance reports. | I should not see the show others responses toggle        |
