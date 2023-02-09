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

import CardsGrid from 'totara_engage/components/contribution/CardsGrid';
import { mount } from '@vue/test-utils';
import { AccessManager } from 'totara_engage/index';

// Mock tui.import
jest.mock('tui/tui', function() {
  return {
    asyncComponent: function() {
      return {
        render(h) {
          return h('span');
        },
      };
    },
  };
});

describe('Engage CardsGrid', function() {
  let wrapper = null,
    cards = [
      {
        instanceid: 1,
        name: 'Hello world resource',
        totalReactions: 15,
        sharedbycount: 5,
        totalComments: 1,
        extra: JSON.stringify({
          image: '/',
          usage: 5,
        }),
        user: {
          id: 12,
          fullname: 'Bolobala',
          profileimageurl: 'http://example.com',
        },
        access: AccessManager.PRIVATE,
        timeCreated: 'Monday 18th, September, 2019',
        rating: 0,
        component: 'ArticleCard',
        tuicomponent: 'engage_article/components/card/ArticleCard',
      },
      {
        instanceid: 2,
        name: 'Hello world resource',
        totalReactions: 15,
        sharedbycount: 10,
        totalComments: 1,
        extra: JSON.stringify({
          image: '/',
          usage: 5,
        }),
        user: {
          id: 12,
          fullname: 'Bolobala',
          profileimageurl: 'http://example.com',
        },
        access: AccessManager.PRIVATE,
        timeCreated: 'Monday 18th, September, 2019',
        rating: 0,
        component: 'ArticleCard',
        tuicomponent: 'engage_article/components/card/ArticleCard',
      },
      {
        instanceid: 3,
        name: 'Hello world resource',
        totalReactions: 15,
        sharedbycount: 3,
        totalComments: 1,
        extra: JSON.stringify({
          image: '/',
          usage: 5,
        }),
        user: {
          id: 12,
          fullname: 'Bolobala',
          profileimageurl: 'http://example.com',
        },
        access: AccessManager.PRIVATE,
        timeCreated: 'Monday 18th, September, 2019',
        rating: 0,
        component: 'ArticleCard',
        tuicomponent: 'engage_article/components/card/ArticleCard',
      },
      {
        instanceid: 4,
        name: 'Hello world resource',
        totalReactions: 15,
        sharedbycount: 9,
        totalComments: 1,
        extra: JSON.stringify({
          image: '/',
          usage: 5,
        }),
        user: {
          id: 12,
          fullname: 'Bolobala',
          profileimageurl: 'http://example.com',
        },
        access: AccessManager.PRIVATE,
        timeCreated: 'Monday 18th, September, 2019',
        rating: 0,
        component: 'ArticleCard',
        tuicomponent: 'engage_article/components/card/ArticleCard',
      },
    ];

  beforeAll(function() {
    wrapper = mount(CardsGrid, {
      propsData: {
        cards,
        maxUnits: 5,
        isLoading: true,
      },
      mocks: {
        $str: (x, y) => `[[${x}, ${y}]]`,
        $id: x => 'id' + x,
      },
    });
  });

  it('Checks snapshot', function() {
    expect(wrapper.element).toMatchSnapshot();
  });

  it('Checks rows', function() {
    expect(wrapper.vm.rows.length).toEqual(2);

    let row = wrapper.vm.rows[0];
    expect(row.items.length).toEqual(2);
  });
});
