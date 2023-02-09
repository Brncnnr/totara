<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Murali Nair <murali.nair@totaralearning.com>
 */

// Unfortunately, this file may be loaded by behat before including config.php;
// hence the unwieldy relative path instead of using $CFG->dirroot.
require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode;

/**
 * Behat email sink related steps.
 */
final class behat_email extends behat_base {
    // Recognized Behat table column constants.
    private const COL_FROM = 'From';
    private const COL_TO = 'To';
    private const COL_SUBJECT = 'Subject';
    private const COL_BODY = 'Body';

    /**
     * Enables the email sink. This must be done before the actual sending or
     * reading of emails. This can be called multiple times in a scenario; each
     * time, it clears previously captured emails.
     *
     * @Given /^I reset the email sink$/
     */
    public function i_reset_the_email_sink() {
        if (!\behat_util::reset_email_sink()) {
            throw new InvalidArgumentException('cannot create email sink');
        }
    }

    /**
     * Closes the email sink. After this, all previously captured emails will be
     * cleared and no new emails will be captured.
     *
     * @Given /^I close the email sink$/
     */
    public function i_close_the_email_sink() {
        if (\behat_util::email_sink_enabled()
            && !\behat_util::close_email_sink()
        ) {
            throw new InvalidArgumentException('cannot close email sink');
        }
    }

    /**
     * Checks whether the given emails were "sent".
     *
     * These are the fields in the Behat table this function recognizes:
     * - self::COL_FROM: [OPTIONAL] sender email address.
     * - self::COL_TO: [OPTIONAL] recipient email address.
     * - self::COL_SUBJECT: [OPTIONAL] email subject. Note: as long as the real
     *   subject contains this value, it is a match.
     * - self::COL_BODY: [OPTIONAL] email body. Note: as long as the real body
     *   (without whitespace) contains this value (also without whitespace), it
     *   is a match.
     *
     * Matching is done on all _non empty_ values that occur in the Behat table.
     * Thus a row that has just a "to" value will pass if any email is sent to
     * that address. A row that has "to", "from" and "subject" will only pass if
     * at least one email matches these values.
     *
     * @Given /^the following emails should have been sent:$/
     *
     * @param Behat\Gherkin\Node\TableNode $rows emails to check.
     */
    public function the_following_emails_should_have_been_sent(
        TableNode $rows
    ) {
        $this->check_emails(
            $rows,
            function (array $row, array $matches): ?string {
                return !empty($matches)
                       ? null
                       : "no sent email matching " . print_r($row, 1);
            }
        );
    }

    /**
     * Checks whether the given emails were NOT "sent".
     *
     * This is opposite of self::the_following_emails_should_have_been_sent()
     * and all the comparison caveats for that method apply.
     *
     * @Given /^the following emails should not have been sent:$/
     *
     * @param Behat\Gherkin\Node\TableNode $rows emails to check.
     */
    public function the_following_emails_should_not_have_been_sent(
        TableNode $rows
    ) {
        $this->check_emails(
            $rows,
            function (array $row, array $matches): ?string {
                return empty($matches)
                       ? null
                       : "found sent email matching " . print_r($row, 1);
            }
        );
    }

    /**
     * Checks whether emails specified in a Behat table meets some validation
     * criteria.
     *
     * @param Behat\Gherkin\Node\TableNode $rows emails to check.
     * @param callable $validate (array, \stdClass[])=>?str method that takes a
     *        behat table row and a list of matching emails and returns an error
     *        if the matching emails are not correct.
     *
     * @return null if emails are valid or the error message if they are not.
     */
    private function check_emails(
        TableNode $rows,
        callable $validate
    ): ?string {
        if (empty($rows)) {
            throw new InvalidArgumentException('emails not specified');
        }

        $sent = array_map(
            function (array $email): array {
                [$from, $to, $subject, $body] = $email;
                return [
                    self::COL_FROM => trim($from),
                    self::COL_TO => trim($to),
                    self::COL_SUBJECT => trim($subject),
                    self::COL_BODY => trim($body)
                ];
            },
            // This returns an empty array if the email sink is disabled.
            \behat_util::get_emails()
        );

        // Uncomment this for debugging behat tests.
        //debugging("Sent emails = " . print_r($sent, 1));

        array_map(
            function (array $row) use ($sent, $validate): void {
                $matches = $this->find_matching_emails($sent, $row);

                $error = $validate($row, $matches);
                if ($error) {
                    if (!\behat_util::email_sink_enabled()) {
                        $error = "(warning: email sink is DISABLED) $error";
                    }
                    throw new Exception($error);
                }
            },
            $rows->getHash()
        );

        return null;
    }

    /**
     * Returns "sent" emails that match the given email values.
     *
     * @param array $sent sent emails as a list of \stdClass which have the same
     *        fields as the $values associative array.
     * @param array $values associative array of values for a single email to be
     *        checked.
     *
     * @return array a list of matching emails in the same format as $sent.
     */
    private function find_matching_emails(
        array $sent,
        array $values
    ): array {
        return array_filter(
            $sent,
            function (array $email) use ($values): bool {
                $value = trim($values[self::COL_FROM] ?? '');
                if ($value && $value !== $email[self::COL_FROM]) {
                    return false;
                }

                $value = trim($values[self::COL_TO] ?? '');
                if ($value && $value !== $email[self::COL_TO]) {
                    return false;
                }

                $value = trim($values[self::COL_SUBJECT] ?? '');
                if ($value
                    && strpos($email[self::COL_SUBJECT], $value) === false) {
                    return false;
                }

                $value = trim($values[self::COL_BODY] ?? '');
                if ($value) {
                    $value_no_ws = preg_replace('/\s+/', '', $value);
                    $body_no_ws = preg_replace(
                        '/\s+/', '', $email[self::COL_BODY]
                    );

                    if (strpos($body_no_ws, $value_no_ws) === false) {
                        return false;
                    }
                }

                return true;
            }
        );
    }

    /**
     * Sends out an email via the forgot password facility.
     *
     * THIS IS FOR TESTING IN email_send.feature.
     *
     * @Given /^I send a reset password email to "(?P<user_name_string>(?:[^"]|\\")*)"$/
     *
     * @param string $user username of the person to which to send the forgot
     *        password email.
     */
    public function i_send_a_reset_password_email_to(
        string $user
    ) {
        $this->execute("behat_general::i_am_on_homepage");
        $this->execute("behat_navigation::get_expand_navbar_step");
        $this->execute("behat_general::i_click_on_in_the", ["Log in", "link", ".login", "css_element"]);
        $this->execute("behat_general::click_link", ["Forgot username or password"]);
        $this->execute("behat_forms::i_set_the_field_to", ["Username", $this->escape($user)]);
        $this->execute("behat_forms::press_button", ["Search"]);
        $this->execute("behat_forms::press_button", ["Continue"]);
    }
}
