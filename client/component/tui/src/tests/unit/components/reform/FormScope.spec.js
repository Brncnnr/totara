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

import Vue from 'vue';
import { mount } from '@vue/test-utils';
import FormScope from 'tui/components/reform/FormScope';
import {
  ReformScopeProvider,
  ReformScopeReceiver,
  pathMethods,
  createMockScope,
} from './util';

function create({ scope, path, validate }) {
  const outerWrapper = mount(ReformScopeProvider, {
    propsData: { scope },
    scopedSlots: {
      default() {
        const h = this.$createElement;
        return h(FormScope, {
          props: { path, validate },
          scopedSlots: {
            default() {
              return h(ReformScopeReceiver);
            },
          },
        });
      },
    },
  });

  const wrapper = outerWrapper.find(FormScope);
  const receiver = outerWrapper.find(ReformScopeReceiver).vm;

  return { wrapper, receiver };
}

describe('Form scope', () => {
  it('proxies reformScope calls, prefixing path', () => {
    const scope = createMockScope();
    const { receiver } = create({ scope, path: 'foo' });

    pathMethods.forEach(name => {
      receiver.reformScope[name]('bar', name + ' arg');
      expect(scope[name]).toHaveBeenCalledWith(['foo', 'bar'], name + ' arg');
    });

    receiver.reformScope.register('validator', 'bar', 1);
    expect(scope.register).toHaveBeenCalledWith('validator', ['foo', 'bar'], 1);

    receiver.reformScope.unregister('validator', 'bar', 1, 'baz', 2);
    expect(scope.unregister).toHaveBeenCalledWith(
      'validator',
      ['foo', 'bar'],
      1
    );

    receiver.reformScope.updateRegistration('validator', 'bar', 1, 'baz', 2);
    expect(scope.updateRegistration).toHaveBeenCalledWith(
      'validator',
      ['foo', 'bar'],
      1,
      ['foo', 'baz'],
      2
    );
  });

  it('registers supplied validator', async () => {
    const scope = createMockScope();
    const validate = () => {};
    const { wrapper } = create({ scope, path: 'foo', validate });
    expect(scope.updateRegistration).toHaveBeenCalledWith(
      'validator',
      'foo',
      validate,
      undefined,
      undefined
    );
    scope.updateRegistration.mockReset();

    wrapper.setProps({ path: 'bar' });
    await Vue.nextTick();
    expect(scope.updateRegistration).toHaveBeenCalledWith(
      'validator',
      'bar',
      validate,
      'foo',
      validate
    );
    scope.updateRegistration.mockReset();

    const newValidate = () => {};
    wrapper.setProps({ validate: newValidate });
    await Vue.nextTick();
    expect(scope.updateRegistration).toHaveBeenCalledWith(
      'validator',
      'bar',
      newValidate,
      'bar',
      validate
    );

    wrapper.destroy();

    expect(scope.unregister).toHaveBeenCalledWith(
      'validator',
      'bar',
      newValidate
    );
  });
});
