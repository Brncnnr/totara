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
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 * @module totara_engage
 */

import { shallowMount } from '@vue/test-utils';
import Vue from 'vue';
import Share from 'totara_engage/components/sidepanel/media/Share';

describe('Engage sidepanel share', () => {
  let mocks = {};
  let propsData = {};

  beforeEach(() => {
    mocks = {
      $str(id, component) {
        return `${id}, ${component}`;
      },
      sharedBy: ['Brian Barnes'],
      sharedToCount: 5,
      sharedTo: ['Alvin Smith', 'Joe Smith'],
    };
    propsData = {
      owned: false,
      accessValue: 'PUBLIC', // values: 'PUBLIC', 'PRIVATE', 'RESTRICTED'
      instanceId: 4,
      component: 'engage_article',
      buttonAriaLabel: 'Share resource "unicorn"',
      sharedByCount: 5,
      sharedTo: [],
    };
  });

  it('matches snapshot', () => {
    let wrapper = shallowMount(Share, {
      mocks,
      propsData,
    });

    expect(wrapper.element).toMatchSnapshot();
  });

  it('Adding a new share works as expected', async () => {
    let mutateFunction = jest.fn(() => {
      return {
        data: {
          shares: {
            sharedbycount: 9,
          },
        },
      };
    });
    let refetch = jest.fn();

    let localMocks = Object.assign({}, mocks, {
      $apollo: {
        mutate: mutateFunction,
        queries: {
          sharedTo: {
            refetch,
          },
        },
      },
    });
    let wrapper = shallowMount(Share, {
      mocks: localMocks,
      propsData,
    });

    wrapper.vm.submit();
    expect(wrapper.vm.sharedByCountDisplay).toEqual(5);

    await Vue.nextTick();
    expect(mutateFunction).toHaveBeenCalled();
    expect(wrapper.vm.sharedByCountLocal).toEqual(9);
    expect(wrapper.vm.sharedByCountDisplay).toEqual(9);
    expect(refetch).toHaveBeenCalled();
  });

  it('Adding and removing users from sharebox works', () => {
    let wrapper = shallowMount(Share, {
      mocks,
      propsData,
    });

    expect(wrapper.vm.newShares.length).toBe(0);
    wrapper.vm.addNewShare({
      instanceid: 5,
      component: 'enagage_article',
      area: 'comment',
    });
    expect(wrapper.vm.newShares.length).toBe(1);
    expect(wrapper.vm.newShares[0].instanceid).toBe(5);
    expect(wrapper.vm.newShares[0].component).toBe('enagage_article');
    expect(wrapper.vm.newShares[0].area).toBe('comment');

    wrapper.vm.addNewShare({
      instanceid: 8,
      component: 'enagage_survey',
      area: 'comments',
    });
    expect(wrapper.vm.newShares.length).toBe(2);

    wrapper.vm.removeNewShare({
      instanceid: 5,
      component: 'enagage_article',
      area: 'comment',
    });
    expect(wrapper.vm.newShares.length).toBe(1);
    expect(wrapper.vm.newShares[0].instanceid).toBe(8);
    expect(wrapper.vm.newShares[0].component).toBe('enagage_survey');
    expect(wrapper.vm.newShares[0].area).toBe('comments');
  });
});
