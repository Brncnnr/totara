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
import idNumberUniqueQuery from 'mod_approval/graphql/workflow_id_number_is_unique';
import getActiveFormsQuery from 'mod_approval/graphql/get_active_forms';

export function workflowIdNumberIsUnique(context) {
  return {
    query: idNumberUniqueQuery,
    variables: {
      input: {
        id_number: context.idNumber,
      },
    },
    fetchPolicy: 'network-only',
  };
}

export function getActiveForms(context) {
  return {
    query: getActiveFormsQuery,
    variables: {
      query_options: {
        pagination: {
          page: context.formPage,
          limit: context.formLimit,
        },
        filters: {
          title: context.formSearch,
        },
      },
    },
  };
}
