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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @module mod_approval
 */

// GraphQL
import apollo from 'tui/apollo_client';
import applicationClone from 'mod_approval/graphql/application_clone';
import applicationAction from 'mod_approval/graphql/application_action';
import applicationDelete from 'mod_approval/graphql/application_delete';
import { applicationSelectors } from 'mod_approval/graphql_selectors';
import { ApplicationAction } from 'mod_approval/constants';
import { loadSchemaData } from 'mod_approval/schema_form';
import { loadApplicationOptions } from './query_options';

export const prepare = context => () => {
  return loadSchemaData(applicationSelectors.getParsedFormSchema(context));
};

export const approveApplication = context => {
  const applicationId = applicationSelectors.getApplicationId(context);
  return apollo.mutate({
    mutation: applicationAction,
    variables: {
      input: {
        application_id: applicationId,
        action: ApplicationAction.APPROVE,
      },
    },
    refetchQueries: [loadApplicationOptions(applicationId)],
    awaitRefetchQueries: true,
  });
};

export const cloneApplication = context => {
  const applicationId = applicationSelectors.getApplicationId(context);
  return apollo.mutate({
    mutation: applicationClone,
    variables: {
      input: {
        application_id: applicationId,
      },
    },
  });
};

export const withdrawApplication = context => {
  const applicationId = applicationSelectors.getApplicationId(context);
  return apollo.mutate({
    mutation: applicationAction,
    variables: {
      input: {
        application_id: applicationId,
        action: ApplicationAction.WITHDRAW_IN_APPROVALS,
      },
    },
    refetchQueries: [loadApplicationOptions(applicationId)],
    awaitRefetchQueries: true,
  });
};

export const deleteApplication = context => {
  const applicationId = applicationSelectors.getApplicationId(context);
  return apollo.mutate({
    mutation: applicationDelete,
    variables: {
      input: {
        application_id: applicationId,
      },
    },
  });
};

export const rejectApplication = context => {
  const applicationId = applicationSelectors.getApplicationId(context);
  return apollo.mutate({
    mutation: applicationAction,
    variables: {
      input: {
        application_id: applicationId,
        action: ApplicationAction.REJECT,
      },
    },
    refetchQueries: [loadApplicationOptions(applicationId)],
    awaitRefetchQueries: true,
  });
};
