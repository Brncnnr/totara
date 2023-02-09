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
 * @author Simon Tegg <simon.tegg@totaralearning.com>
 * @module mod_approval
 */
import { assign } from 'tui_xstate/xstate';
export const setIdNumber = assign({
  idNumber: (context, { idNumber }) => idNumber,
});
export const setDetails = assign({
  details: (context, { details }) => details,
});
export const setFormSearch = assign({
  formSearch: (context, { formSearch }) => formSearch,
  formPage: 1,
});
export const setSelectedFormId = assign({
  selectedFormId: (context, { selectedFormId }) => selectedFormId,
});
export const setFormPage = assign({
  formPage: (context, { formPage }) => formPage,
});
export const setFormLimit = assign({
  formLimit: (context, { formLimit }) => formLimit,
});
export const setSelectedTarget = assign((context, event) => {
  return {
    selectedIdentifier: event.identifier,
    selectedAssignmentType: event.assignmentType,
  };
});
