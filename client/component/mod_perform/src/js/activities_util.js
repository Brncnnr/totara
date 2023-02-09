/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
 *
 * Totara Enterprise Extensions is provided only to Totara
 * Learning Solutions LTD's customers and partners, pursuant to
 * the terms and conditions of a separate agreement with Totara
 * Learning Solutions LTD or its affiliate.
 *
 * If you do not have an agreement with Totara Learning Solutions
 * LTD, you may not access, use, modify, or distribute this software.
 * Please contact [licensing@totaralearning.com] for more information.
 *
 * @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
 * @module mod_perform
 */

/**
 * Get the current users progress status from a particular subject instance.
 *
 * @param {Object} participantInstances
 * @param {String} role participant role id
 * @returns {string}
 */
export function getYourProgressStatusValue(participantInstances, role) {
  const currentStatus = participantInstances
    .filter(
      pi =>
        pi.is_for_current_user && pi.core_relationship.id === role.toString()
    )
    .map(instance => instance.progress_status);

  return currentStatus[0];
}

/**
 * Get the first section, if relationship id is supplied it will get the first section
 * for the user with the given relationship
 *
 * @param {Array} subjectSections
 * @param {String} role participant role id
 * @return {Object|Null} returns a participant_section object
 */
export function getFirstSectionToParticipate(subjectSections, role) {
  let foundSection = null;

  subjectSections.forEach(subjectSection => {
    // Check participant instance is for this user in a particular role
    let found = subjectSection.participant_sections.find(
      item =>
        item.participant_instance.is_for_current_user &&
        item.participant_instance.core_relationship.id === role.toString()
    );
    if (found && foundSection === null) {
      foundSection = found;
    }
  });

  return foundSection;
}

/**
 * Checks if participant instance for current role is closed.
 *
 * @param {Array} participantInstances
 * @return {Boolean}
 */
export function isRoleInstanceClosed(participantInstances, role) {
  return !participantInstances.find(pi => {
    return (
      pi.availability_status &&
      pi.availability_status !== 'CLOSED' &&
      pi.is_for_current_user &&
      pi.core_relationship.id === role.toString()
    );
  });
}

/**
 * Checks if participant instance for current role is overdue.
 *
 * @param {Array} participantInstances
 * @return {Boolean}
 */
export function isRoleInstanceOverdue(participantInstances, role) {
  return !participantInstances.find(
    pi =>
      !pi.is_overdue &&
      pi.is_for_current_user &&
      pi.core_relationship.id === role.toString()
  );
}
