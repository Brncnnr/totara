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
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @module tui
 */

import { mount } from '@vue/test-utils';
import { plainWrapperArray } from '../../util';
import PropsProvider from 'tui/components/util/PropsProvider';

const SecondLevel = {
  props: ['j'],
  render(h) {
    return h('span', {}, [`j: ${this.j}`]);
  },
};

const TestComp = {
  props: ['i', 'j'],
  render(h) {
    return h('p', {}, [`${this.i}, ${this.j}`, h(SecondLevel)]);
  },
};

const PropsProviderWrap = {
  functional: true,
  render(h, { props, children }) {
    return h('div', [h(PropsProvider, { props }, children)]);
  },
};

describe('PropsProvider', () => {
  it('passes props and events to direct children', () => {
    const handler = jest.fn();
    const wrapper = mount(PropsProviderWrap, {
      context: {
        children: [
          h => h(TestComp, { props: { i: 1 } }),
          'hi',
          h => h(TestComp, { props: { i: 2 } }),
          h => h(TestComp, { props: { i: 3, j: 3 } }),
        ],
      },
      propsData: {
        provide({ props }) {
          return {
            props: { j: (props.i || 0) + 1000 },
            listeners: { click: handler },
          };
        },
      },
    });

    expect(
      plainWrapperArray(wrapper.findAll(TestComp)).map(w => w.props())
    ).toMatchObject([
      { i: 1, j: 1001 },
      { i: 2, j: 1002 },
      { i: 3, j: 3 },
    ]);
    expect(
      plainWrapperArray(wrapper.findAll(SecondLevel)).map(w => w.props())
    ).toMatchObject([{ j: undefined }, { j: undefined }, { j: undefined }]);

    expect(handler).toHaveBeenCalledTimes(0);
    wrapper.find(TestComp).vm.$emit('click', 'foo');
    expect(handler).toHaveBeenCalledWith('foo');

    expect(wrapper.element).toMatchSnapshot();
  });

  it('merges multiple event handlers', () => {
    const handlerInline = jest.fn();
    const handlerProvided = jest.fn();
    const wrapper = mount(PropsProviderWrap, {
      context: {
        children: [
          h => h(TestComp, { props: { i: 1 }, on: { click: handlerInline } }),
          h => h(TestComp, { props: { i: 2 }, on: { click: handlerInline } }),
          h => h(TestComp, { props: { i: 3 } }),
        ],
      },
      propsData: {
        provide({ props }) {
          return {
            props: { j: (props.i || 0) + 1000 },
            listeners: props.i != 2 ? { click: handlerProvided } : {},
          };
        },
      },
    });

    const comps = wrapper.findAll(TestComp);

    comps.at(0).vm.$emit('click', 'first');
    expect(handlerInline).toHaveBeenCalledWith('first');
    expect(handlerProvided).toHaveBeenCalledWith('first');

    comps.at(1).vm.$emit('click', 'second');
    expect(handlerInline).toHaveBeenCalledWith('second');
    expect(handlerProvided).not.toHaveBeenCalledWith('second');

    comps.at(2).vm.$emit('click', 'third');
    expect(handlerInline).not.toHaveBeenCalledWith('third');
    expect(handlerProvided).toHaveBeenCalledWith('third');
  });
});
