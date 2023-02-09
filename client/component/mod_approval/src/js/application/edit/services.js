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
import apollo from 'tui/apollo_client';
import { get } from 'tui/util';
import { APPLICATION_FORM_SCHEMA } from 'mod_approval/constants';
import applicationSubmit from 'mod_approval/graphql/application_submit';
import applicationPublish from 'mod_approval/graphql/application_publish';
import applicationSaveAsDraft from 'mod_approval/graphql/application_save_as_draft';
import applicationFormSchemaQuery from 'mod_approval/graphql/application_form_schema';
import loadApplicationQuery from 'mod_approval/graphql/load_application';
import { prepareValuesForSave } from 'mod_approval/schema_form';
import { getApplicationId } from 'mod_approval/graphql_selectors/load_application';
export * from 'mod_approval/common/services';

export function loadApplication(context) {
  return {
    query: loadApplicationQuery,
    variables: {
      input: {
        application_id: getApplicationId(context),
      },
    },
  };
}

export function applicationFormSchema(context) {
  return {
    query: applicationFormSchemaQuery,
    variables: {
      input: {
        application_id: getApplicationId(context),
      },
    },
  };
}
applicationFormSchema.updateContext = (context, { data }) => {
  const formSchema =
    get(data, ['mod_approval_application_form_schema', 'form_schema']) || '{}';

  const formData =
    get(data, ['mod_approval_application_form_schema', 'form_data']) || '{}';

  return Object.assign({}, context, {
    [APPLICATION_FORM_SCHEMA]: data,
    formData: JSON.parse(formData),
    parsedFormSchema: JSON.parse(formSchema),
    parsedFormData: JSON.parse(formData),
  });
};

export const saveApplication = context => {
  const { parsedFormSchema, formData, keepApprovals } = context;
  return apollo.mutate({
    mutation: applicationPublish,
    variables: {
      input: {
        application_id: getApplicationId(context),
        form_data: JSON.stringify(
          prepareValuesForSave(parsedFormSchema, formData)
        ),
        keep_approvals: keepApprovals,
      },
    },
  });
};

export const saveAsDraftApplication = context => {
  const { parsedFormSchema, formData } = context;
  return apollo.mutate({
    mutation: applicationSaveAsDraft,
    variables: {
      input: {
        application_id: getApplicationId(context),
        form_data: JSON.stringify(
          prepareValuesForSave(parsedFormSchema, formData)
        ),
      },
    },
  });
};

export const submitApplication = context => {
  const { parsedFormSchema, formData } = context;
  return apollo.mutate({
    mutation: applicationSubmit,
    variables: {
      input: {
        application_id: getApplicationId(context),
        form_data: JSON.stringify(
          prepareValuesForSave(parsedFormSchema, formData)
        ),
      },
    },
  });
};
