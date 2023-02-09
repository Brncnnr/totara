/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @module engage_survey
 */

import SurveyCard from 'engage_survey/components/card/SurveyCard';
import { shallowMount } from '@vue/test-utils';
import { AccessManager } from 'totara_engage/index';

jest.mock('tui/apollo_client', function() {
  return null;
});

describe('Engage SurveyCard', function() {
  let wrapper = null;

  beforeAll(function() {
    wrapper = shallowMount(SurveyCard, {
      mocks: {
        $str(id, component) {
          return `${id}, ${component}`;
        },

        $id(random) {
          return `some-random-${random}`;
        },
      },
      propsData: {
        instanceId: 42,
        userId: 42,
        userFullName: 'Bolobala',
        userProfileImageUrl: 'http://example.com',

        access: AccessManager.PRIVATE,
        timeCreated: '5h September 2019',
        rating: 5,
        totalComments: 15,
        totalReactions: 12,
        sharedbycount: 1,
        owned: false,
        name: 'sss',
        extra: JSON.stringify({
          questions: [{ id: 15, value: 1, answertype: 1, options: [10] }],
        }),
        labelId: 'survey-1',
        showBookmark: true,
      },
    });
  });

  it('Checks snapshot', function() {
    expect(wrapper.element).toMatchSnapshot();
  });
});
