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
import baseContext from 'mod_approval/application/index/table/context';
import {
  // MY_APPLICATIONS,
  APPLICATIONS_FROM_OTHERS,
  // OverallProgressState
} from 'mod_approval/constants';
import { setQueryOptions } from 'mod_approval/application/index/table/actions';

describe('setQueryOptions', () => {
  it('sets value at arbitrary path', () => {
    const context = produce(baseContext, x => x);
    const event = {
      path: [
        APPLICATIONS_FROM_OTHERS,
        'query_options',
        'filters',
        'applicant_name',
      ],
      value: 'harry',
    };

    const updatedContext = setQueryOptions.assignment(context, event);
    expect(
      updatedContext.variables[APPLICATIONS_FROM_OTHERS].query_options
    ).toHaveProperty('filters');
    expect(
      updatedContext.variables[APPLICATIONS_FROM_OTHERS].query_options.filters
    ).toHaveProperty('applicant_name', event.value);
  });
});
