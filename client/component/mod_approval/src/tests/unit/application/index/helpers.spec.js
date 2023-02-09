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
import { produce } from 'tui/immutable';
import {
  mapContextToQueryParams,
  mapQueryParamsToContext,
} from 'mod_approval/application/index/table/helpers';
import baseContext from 'mod_approval/application/index/table/context';
import { MY_APPLICATIONS, OverallProgressState } from 'mod_approval/constants';

describe('mapContextToQueryParams', () => {
  test('it transforms variables to params', () => {
    const prevContext = produce(baseContext, x => x);
    const context = produce(baseContext, draft => {
      draft.variables[MY_APPLICATIONS] = {
        query_options: {
          pagination: {
            page: 2,
            limit: 20,
          },
          filters: {
            overall_progress: OverallProgressState.DRAFT,
          },
        },
      };
    });

    const params = mapContextToQueryParams(context, prevContext);
    expect(params).toHaveProperty(
      ['my.filters.overall_progress'],
      OverallProgressState.DRAFT
    );
    expect(params).toHaveProperty(['my.pagination.page'], 2);
  });
});

describe('mapQueryParamsToContext', () => {
  test('it updates context', () => {
    const params = { notify: 'clone_application', notify_type: 'success' };
    const context = mapQueryParamsToContext(params);

    expect(context).toHaveProperty('notify', params.notify);
    expect(context).toHaveProperty('notifyType', params.notify_type);
  });

  test('it updates context with filters', () => {
    const params = {
      ['my.filters.overall_progress']: OverallProgressState.DRAFT,
    };
    const context = mapQueryParamsToContext(params);
    expect(context).toHaveProperty(
      `variables.${MY_APPLICATIONS}.query_options.filters.overall_progress`,
      OverallProgressState.DRAFT
    );
  });
});
