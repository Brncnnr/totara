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
 * @module totara_engage
 */

import { shallowMount } from '@vue/test-utils';
import ArticleCard from 'engage_article/components/card/ArticleCard';

jest.mock('tui/apollo_client', function() {
  return null;
});

describe('Engage ArticleCard', () => {
  let wrapper = null;

  beforeAll(() => {
    wrapper = shallowMount(ArticleCard, {
      mocks: {
        $str(identifier, component, param) {
          return `[${identifier}, ${component} - ${param}]`;
        },
        $url(str) {
          return str;
        },
      },

      propsData: {
        instanceId: 1,
        name: 'Hello world resource',
        totalReactions: 15,
        sharedbycount: 10,
        totalComments: 1,
        extra: JSON.stringify({
          image: 'https://example.com/image.jpg',
          alt: 'Example image',
          usage: 5,
          timeview: null,
        }),
        userId: 12,
        userFullName: 'Bolo bala',
        userProfileImageUrl: 'http://example.com',
        access: 'PUBLIC',
        timeCreated: 'Monday 18th, September, 2019',
        rating: 0,
        labelId: 'article-1',
        showBookmark: true,
      },
    });
  });

  afterEach(() => {
    wrapper.setProps({ visibility: 'public' });
  });

  it('Checks snapshot', () => {
    expect(wrapper.element).toMatchSnapshot();
  });
});
