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
import Field from 'tui/components/reform/Field';
import {
  ReformScopeProvider,
  ReformFieldContextProvider,
  createMockScope,
} from './util';
import { fieldValidator } from 'tui/validation';

jest.mock('tui/validation', () => {
  const fieldValidator = jest.fn(fn => fn);
  return { fieldValidator };
});

function create({ scope, fieldContext, name, props }) {
  const result = {};
  const outerWrapper = mount(ReformScopeProvider, {
    propsData: { scope },
    stubs: {
      Scope: true,
    },
    scopedSlots: {
      default() {
        const h = this.$createElement;
        return h(ReformFieldContextProvider, {
          props: { fieldContext },
          scopedSlots: {
            default() {
              return h(Field, {
                props: { name, ...props },
                scopedSlots: {
                  default(props) {
                    result.props = props;
                    return h('div');
                  },
                },
              });
            },
          },
        });
      },
    },
  });

  result.wrapper = outerWrapper.find(Field);

  return result;
}

describe('Field', () => {
  it('provides slot props to interact with Reform state', () => {
    const scope = createMockScope();
    const fieldContext = {
      getId: () => 4,
      getLabelId: () => 5,
      getAriaDescribedby: () => 6,
    };

    scope.getValue.mockImplementation(path => (path == 'foo' ? 3 : null));
    scope.getError.mockImplementation(path => (path == 'foo' ? 'err' : null));
    scope.getInputName.mockImplementation(x => 'input-' + x);

    const opt = create({ scope, fieldContext, name: 'foo' });

    expect(opt.props.ariaDescribedby).toBe(6);
    expect(opt.props.labelId).toBe(5);
    expect(opt.props.id).toBe(4);
    expect(opt.props.value).toBe(3);
    expect(opt.props.name).toBe('foo');
    expect(opt.props.inputName).toBe('input-foo');
    opt.props.update('val');
    expect(scope.update).toHaveBeenCalledWith('foo', 'val');
    opt.props.blur();
    expect(scope.blur).toHaveBeenCalledWith('foo');
    expect(opt.props.error).toBe('err');
    expect(scope.getError).toHaveBeenCalledWith('foo');
  });

  it('registers validator for field', async () => {
    const scope = createMockScope();
    const fieldContext = {
      getId: () => 4,
      getLabelId: () => 5,
      getAriaDescribedby: () => 6,
    };
    const validate = jest.fn();

    const opt = create({
      scope,
      fieldContext,
      name: 'foo',
      props: {
        validate,
      },
    });

    expect(scope.updateRegistration).toHaveBeenCalledWith(
      'validator',
      'foo',
      validate,
      undefined,
      undefined
    );

    opt.wrapper.setProps({ validate: null });
    await Vue.nextTick();

    expect(scope.updateRegistration).toHaveBeenCalledWith(
      'validator',
      'foo',
      null,
      'foo',
      validate
    );

    const validationsFn = jest.fn();

    opt.wrapper.setProps({ validations: validationsFn });
    await Vue.nextTick();

    expect(fieldValidator).toHaveBeenCalledWith(validationsFn);
    expect(scope.updateRegistration).toHaveBeenCalledWith(
      'validator',
      'foo',
      validationsFn,
      'foo',
      null
    );
  });
});
