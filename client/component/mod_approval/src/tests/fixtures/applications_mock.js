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
 * @author Simon Tegg <simon.teggfe@totaralearning.com>
 * @module mod_approval
 */
//import VueApollo from 'vue-apollo';
import { createMockClient } from 'mock-apollo-client';

// queries
import myApplicationsQuery from 'mod_approval/graphql/my_applications';
import applicationsQuery from 'mod_approval/graphql/applications';
import applicationClone from 'mod_approval/graphql/application_clone';

// fixtures
import applications from './applications';
import myApplications from './my_applications';

export const mockClient = createMockClient();
export const mocks = [
  {
    request: {
      query: applicationClone,
    },
    result: {
      data: {
        mod_approval_application_clone: {
          application_id: 45,
        },
      },
    },
  },
  {
    request: {
      query: myApplicationsQuery,
    },
    result: {
      data: myApplications,
    },
  },
  {
    request: {
      query: applicationsQuery,
    },
    result: {
      data: applications,
      // data: {
      // mod_approval_applications: { items: [], total: 0, next_cursor: {} },
      // interactor: {
      // __typename: 'user',
      // type: 'APPROVER',
      // id: 2,
      // name: 'Florence N',
      // profileUrl: 'url'
      // }
      // }
    },
  },
];

console.log({ mocks });

mocks.forEach(({ request, result }) => {
  mockClient.setRequestHandler(request.query, variables => {
    const operation = request.query.definitions[0].name.value;

    if (
      operation === 'mod_approval_my_applications' &&
      variables.filters &&
      variables.filters.overall_progress
    ) {
      const { overall_progress } = variables.filters;

      const update = result.data.mod_approval_my_applications.items.filter(
        item => {
          return item.overall_progress === overall_progress;
        }
      );

      console.log({ result, overall_progress, update });

      return new Promise(resolve => {
        setTimeout(
          () =>
            resolve({
              data: {
                mod_approval_my_applications: {
                  total: 2,
                  next_cursor: {},
                  items: update,
                  interactor:
                    result.data.mod_approval_my_applications.interactor,
                },
              },
            }),
          2000
        );
      });
    }

    if (operation === 'mod_approval_applications' && variables.filters) {
      const {
        application_type,
        your_progress,
        overall_progress,
        user,
      } = variables.filters;
      let items = result.data.mod_approval_applications.items;

      if (application_type) {
        items = items.filter(item => {
          return item.workflow_type === application_type;
        });
      }

      if (your_progress) {
        items = items.filter(item => {
          return item.your_progress === your_progress;
        });
      }

      if (overall_progress) {
        items = items.filter(item => {
          return item.overall_progress === overall_progress;
        });
      }

      if (user) {
        items = items.filter(item => {
          return item.submitted_by.fullname.contains(user);
        });
      }

      // return Promise.reject(Error('test error'))

      return new Promise(resolve => {
        console.log({
          data: {
            mod_approval_applications: {
              total: 123,
              items: items,
              next_cursor: {},
            },
          },
        });
        setTimeout(
          () =>
            resolve({
              data: {
                mod_approval_applications: {
                  total: 123,
                  items: items,
                  next_cursor: {},
                },
              },
            }),
          2000
        );
      });
    }

    return new Promise(resolve => {
      setTimeout(function() {
        resolve(result);
      }, 2000);
    });
  });
});

// export const apolloProvider = new VueApollo({
// defaultClient: mockClient,
// });

// Storybook usage
// export const decorator = () => {
// return { apolloProvider, template: '<story />' }
// }
