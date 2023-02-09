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
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 * @module totara_topic
 */

import { mount } from '@vue/test-utils';
import TopicsSelector from 'totara_topic/components/form/TopicsSelector';

describe('TopicsSelector', () => {
  let wrapper = null;
  let refetch = jest.fn();
  let changeEvent = jest.fn();
  const item1 = { id: '5', value: 'Topic 1' };
  const item2 = { id: '6', value: 'Topic 4' };

  beforeEach(() => {
    refetch = jest.fn();
    changeEvent = jest.fn();

    wrapper = mount(TopicsSelector, {
      listeners: {
        change: changeEvent,
      },
      propsData: {
        selectedTopics: [],
      },
      data() {
        return {
          topics: [item1, item2],
        };
      },
      mocks: {
        $apollo: {
          queries: {
            topics: {
              refetch,
            },
          },
        },
      },
      stubs: {
        TagList: {
          props: {
            item: { value: 'x' },
          },
          render() {
            return '';
          },
        },
      },
    });
  });

  it('displayTopics works as expected', async () => {
    expect(wrapper.vm.displayTopics).toEqual([item1, item2]);
    wrapper.setProps({ selectedTopics: [item1] });
    await wrapper.vm.$nextTick();
    expect(wrapper.vm.displayTopics).toEqual([item2]);
    wrapper.setProps({ selectedTopics: [item1, item2] });
    await wrapper.vm.$nextTick();
    expect(wrapper.vm.displayTopics).toEqual([]);
  });

  it('selectTopic works as expected', async () => {
    let term = 'Top';

    wrapper.vm.searchTerm = term;
    await wrapper.vm.selectTopic(item1);
    expect(changeEvent).toHaveBeenCalled();
    wrapper.setProps({ selectedTopics: [item1] });

    expect(wrapper.vm.searchTerm).toBe(term);
    expect(refetch).toHaveBeenCalledTimes(0);

    wrapper.vm.selectedTopics.push(item2);
    await wrapper.vm.selectTopic(item2);
    expect(changeEvent).toHaveBeenCalled();
    await wrapper.vm.$nextTick();
    expect(wrapper.vm.searchTerm).toBe('');
    expect(refetch).toHaveBeenCalledTimes(1);
  });
});
