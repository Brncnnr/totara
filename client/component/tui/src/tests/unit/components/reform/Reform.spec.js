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
import Reform from 'tui/components/reform/Reform';
import { ReformScopeReceiver } from './util';
import { mergeListeners } from '../../../../js/internal/vnode';

jest.mock('tui/dom/focus', () => ({
  getTabbableElements(el) {
    return [el];
  },
}));

const validateWait = async () => {
  await Vue.nextTick(); // wait for nextTick like handleSubmit
  jest.advanceTimersByTime(20); // drain queue
  await Vue.nextTick(); // need 2 more nextTicks for some reason
  await Vue.nextTick();
};

const domSubmit = async wrapper => {
  wrapper.find('form').trigger('submit');
  await validateWait();
};

function createSimple(initialValues, { localVue, props, listeners } = {}) {
  const wrapper = mount(Reform, {
    localVue,
    propsData: {
      initialValues: initialValues,
      ...props,
    },
    listeners: {
      ...listeners,
    },
    scopedSlots: {
      default({ handleSubmit }) {
        const h = this.$createElement;
        return h('form', { on: { submit: handleSubmit } }, [
          h(ReformScopeReceiver),
        ]);
      },
    },
  });

  const fsr = wrapper.find(ReformScopeReceiver).vm;
  const vm = wrapper.vm;

  return {
    wrapper,
    vm,
    scope: fsr.reformScope,
    submit: async () => {
      return Promise.all([vm.submit(), validateWait()]);
    },
  };
}

function createSimpleControlled(
  initialValues,
  { localVue, props, listeners } = {}
) {
  let state = { values: initialValues };
  const updateState = newState => {
    state = newState;
    result.wrapper.setProps({ state });
  };
  const result = createSimple(initialValues, {
    localVue,
    props: {
      ...props,
      state,
    },
    listeners: mergeListeners(listeners, {
      'update:state': updateState,
    }),
  });
  result.getState = () => state;
  result.setState = updateState;
  return result;
}

describe('Reform', () => {
  beforeAll(() => {
    jest.useFakeTimers();
  });

  describe('Reform with internal state', () => {
    it('holds form state', async () => {
      const submit = jest.fn();
      const expectedResult = { a: 1, b: 2 };
      const wrapper = mount(Reform, {
        propsData: {
          initialValues: { a: 1, b: 2 },
        },
        listeners: { submit },
        scopedSlots: {
          default({ handleSubmit }) {
            const h = this.$createElement;
            return h('form', { on: { submit: handleSubmit } }, [
              h(ReformScopeReceiver),
            ]);
          },
        },
      });

      expect(wrapper.vm.formState.values).toEqual(expectedResult);
      expect(submit).not.toHaveBeenCalled();
      await domSubmit(wrapper);
      expect(submit).toHaveBeenCalledWith(expectedResult);
    });

    it('allows updating form state via provide/inject callbacks', async () => {
      const { vm, scope } = createSimple({ a: 1 });

      expect(vm.formState.values).toEqual({ a: 1 });

      scope.update('a', 2);
      scope.update('b', 3);
      scope.update(['c', 'd', 1, 'e'], 3);

      expect(vm.formState.values).toEqual({
        a: 2,
        b: 3,
        c: { d: [undefined, { e: 3 }] },
      });

      expect(scope.getValue('a')).toBe(2);
      expect(scope.getValue('b')).toBe(3);
      expect(scope.getValue(['c', 'd', 1, 'e'])).toBe(3);
      expect(scope.getValue('c')).toEqual({ d: [undefined, { e: 3 }] });
    });
  });

  describe('Reform with controlled state', () => {
    it('holds form state', async () => {
      const submit = jest.fn();
      const expectedResult = { a: 1, b: 2 };
      const { wrapper, getState } = createSimpleControlled(
        { a: 1, b: 2 },
        { listeners: { submit } }
      );

      expect(getState().values).toEqual(expectedResult);
      expect(submit).not.toHaveBeenCalled();
      await domSubmit(wrapper);
      expect(submit).toHaveBeenCalledWith(expectedResult);
    });

    it('allows updating form state via provide/inject callbacks', async () => {
      const { scope, getState } = createSimpleControlled({ a: 1 });

      expect(getState().values).toEqual({ a: 1 });

      scope.update('a', 2);
      scope.update('b', 3);
      scope.update(['c', 'd', 1, 'e'], 3);

      await Vue.nextTick(); // need to wait for getState to get the latest value

      expect(getState().values).toEqual({
        a: 2,
        b: 3,
        c: { d: [undefined, { e: 3 }] },
      });

      expect(scope.getValue('a')).toBe(2);
      expect(scope.getValue('b')).toBe(3);
      expect(scope.getValue(['c', 'd', 1, 'e'])).toBe(3);
      expect(scope.getValue('c')).toEqual({ d: [undefined, { e: 3 }] });
    });

    it('updates validations when value changes', async () => {
      const val1 = { field: 'foo' };
      const { scope, setState } = createSimpleControlled(val1);

      scope.touch('field');
      scope.register('validator', 'field', x => (x == 'foo' ? 'err' : null));

      await validateWait();
      expect(scope.getError('field')).toBe('err');

      // replace entire value object
      setState({ values: { field: 'bar' } });
      await validateWait();
      expect(scope.getError('field')).not.toBe('err');
    });
  });

  describe.each([
    ['internal', createSimple],
    ['controlled', createSimpleControlled],
  ])('with %s state', (stateMode, createReform) => {
    it('does not lose multiple quick mutations', async () => {
      const { scope } = createReform();

      scope.update('a', 1);
      scope.update('b', 2);
      scope.update('c', 3);
      scope.touch('c');
      scope.touch('d');

      if (stateMode === 'controlled') {
        await Vue.nextTick();
      }

      expect(scope.getValue()).toEqual({ a: 1, b: 2, c: 3 });
      expect(scope.getTouched('c')).toBe(true);
      expect(scope.getTouched('d')).toBe(true);
    });

    it('marks fields as touched when they are blurred', () => {
      const { scope } = createReform({ a: 1 });

      expect(scope.getTouched('a')).toBe(false);
      expect(scope.getTouched('b')).toBe(false);
      expect(scope.getTouched('c')).toBe(false);
      expect(scope.getTouched(['d', 'e'])).toBe(false);
      scope.update('a', 1);
      scope.update('b', 2);
      scope.update(['d', 'e'], 3);
      expect(scope.getTouched('a')).toBe(false);
      expect(scope.getTouched('b')).toBe(false);
      expect(scope.getTouched('c')).toBe(false);
      expect(scope.getTouched(['d', 'e'])).toBe(false);
      scope.blur('a');
      scope.blur('b');
      scope.blur('c');
      scope.blur(['d', 'e']);
      expect(scope.getTouched('a')).toBe(true);
      expect(scope.getTouched('b')).toBe(true);
      expect(scope.getTouched('c')).toBe(true);
      expect(scope.getTouched(['d', 'e'])).toBe(true);
      scope.$_internalUpdateSliceState('f', state => {
        Vue.set(state, 'values', { a: null });
        Vue.set(state, 'touched', { a: null });
        return state;
      });
      scope.blur(['f', 'a', 'example']);
      expect(scope.getTouched(['f', 'a', 'example'])).toBe(true);
    });

    it('validates fields according to registered validators', async () => {
      const { scope, submit } = createReform({ a: 'no' });

      const validator1 = jest.fn(val => {
        const errors = {};
        if (val.a !== 'yes') errors.a = 'no 1';
        return errors;
      });

      const validator2 = jest.fn(val => {
        if (val !== 'yes') return 'no 2';
      });

      scope.register('validator', null, validator1);

      // errors only display if touched
      expect(scope.getError('a')).toBe(undefined);
      scope.blur('a');

      await validateWait();

      expect(scope.getError('a')).toBe('no 1');

      await submit();
      expect(scope.getError('a')).toBe('no 1');

      scope.register('validator', 'a', validator2);
      await submit();
      expect(scope.getError('a')).toBe('no 2'); // most specific wins

      scope.unregister('validator', null, validator1);
      scope.unregister('validator', 'a', validator2);
      scope.register('validator', 'a', validator2);
      scope.register('validator', null, validator1);
      await submit();
      expect(scope.getError('a')).toBe('no 2'); // registration order does not matter
    });

    it('allows passing root validator prop', async () => {
      const rootValidator = jest.fn(values => {
        const errors = {};
        if (values.a !== 2) errors.a = 'a must be 2';
        return errors;
      });

      const { scope, submit } = createReform(
        {},
        { props: { validate: rootValidator } }
      );

      scope.update('a', 1);

      await submit();

      expect(scope.getError('a')).toBe('a must be 2');
    });

    it('merges deep error paths', async () => {
      const { scope, submit } = createReform();

      scope.register('validator', 'a', () => ({ b: 'err1', c: { d: 'err2' } }));
      scope.register('validator', ['a', 'c'], () => ({ e: 'err3' }));

      await submit();

      expect(scope.getError(['a', 'b'])).toBe('err1');
      expect(scope.getError(['a', 'c', 'd'])).toBe('err2');
      expect(scope.getError(['a', 'c', 'e'])).toBe('err3');
    });

    it('can validate deeply nested paths', async () => {
      const { scope, submit } = createReform({ a: [undefined, { b: 'no' }] });

      const validator = jest.fn(val => {
        if (val !== 'yes') {
          return 'must be yes';
        }
      });

      scope.register('validator', ['a', 2, 'b'], validator);
      await submit();
      expect(scope.getError(['a', 2, 'b'])).toBe('must be yes');
    });

    if (stateMode != 'controlled') {
      it('only runs required validators', async () => {
        const { scope } = createReform({ a: 'no' });

        const validator1 = jest.fn(val => {
          const errors = {};
          if (val.a !== 'yes') errors.a = 'no 1';
          return errors;
        });

        const validator2 = jest.fn(val => {
          if (val !== 'yes') return 'no 2';
        });

        const validator3 = jest.fn(val => {
          if (val !== 'yes') return 'no 3';
        });

        scope.register('validator', null, validator1);
        scope.register('validator', 'a', validator2);
        scope.register('validator', 'b', validator3);

        await validateWait();

        expect(validator1).toHaveBeenCalled();
        expect(validator2).toHaveBeenCalled();
        expect(validator3).toHaveBeenCalled();

        [validator1, validator2, validator3].forEach(x => x.mockReset());

        scope.blur('a');

        await validateWait();

        expect(validator1).toHaveBeenCalled();
        expect(validator2).toHaveBeenCalled();
        expect(validator3).not.toHaveBeenCalled();
      });
    }

    it('allows passing external errors via errors object', async () => {
      const rootValidator = jest.fn(values => {
        const errors = {};
        if (values.a !== 2) errors.a = 'a must be 2';
        return errors;
      });

      const { wrapper, scope, submit } = createReform(
        {},
        { props: { validate: rootValidator, errors: { c: 'no c' } } }
      );

      scope.update('a', 1);
      scope.update('b', 1);

      await submit();

      expect(scope.getError('a')).toBe('a must be 2');
      expect(scope.getError('b')).toBe(undefined);
      expect(scope.getError('c')).toBe('no c');

      wrapper.setProps({ errors: { a: 'server error', b: 'b is required' } });
      await validateWait();

      expect(scope.getError('a')).toBe('a must be 2');
      expect(scope.getError('b')).toBe('b is required');
      expect(scope.getError('c')).toBe(undefined);

      wrapper.setProps({ errors: null });
      await validateWait();

      expect(scope.getError('a')).toBe('a must be 2');
      expect(scope.getError('b')).toBe(undefined);
      expect(scope.getError('c')).toBe(undefined);
    });

    it('allows adding hooks for processing submitted data', async () => {
      const handleSubmit = jest.fn();
      const { scope, submit } = createReform(
        { a: { b: 3 } },
        { listeners: { submit: handleSubmit } }
      );

      const procRoot = val => {
        val.root = 1;
        return val;
      };
      scope.register('processor', null, procRoot);

      const procA = val => {
        val.q = 1;
        return val;
      };
      scope.register('processor', 'a', procA);

      const procAB = val => {
        return val + 1;
      };
      scope.register('processor', ['a', 'b'], procAB);

      const submitRoot = jest.fn();
      scope.register('submitHandler', null, submitRoot);

      const submitA = jest.fn();
      scope.register('submitHandler', 'a', submitA);

      const submitAB = jest.fn();
      scope.register('submitHandler', ['a', 'b'], submitAB);

      await submit();

      expect(submitAB).toHaveBeenCalledWith(4);
      expect(submitAB).toHaveBeenCalledBefore(submitA);
      expect(submitA).toHaveBeenCalledWith({ b: 4, q: 1 });
      expect(submitA).toHaveBeenCalledBefore(submitRoot);
      expect(submitRoot).toHaveBeenCalledWith({ a: { b: 4, q: 1 }, root: 1 });
      expect(submitRoot).toHaveBeenCalledBefore(handleSubmit);
      expect(handleSubmit).toHaveBeenCalledWith({ a: { b: 4, q: 1 }, root: 1 });

      scope.unregister('processor', null, procRoot);
      scope.unregister('processor', 'a', procA);
      scope.unregister('processor', ['a', 'b'], procAB);

      await submit();

      expect(handleSubmit).toHaveBeenCalledWith({ a: { b: 3 } });
    });

    it('focuses invalid inputs', async () => {
      const { scope, submit } = createReform({ a: 'no' });

      const validator = jest.fn(val => {
        if (val !== 'yes') return 'no';
      });

      scope.register('validator', 'a', validator);

      const aEl = document.createElement('input');
      document.body.append(aEl);
      const focusHandler = jest.fn();
      aEl.addEventListener('focus', focusHandler);
      scope.register('element', 'a', () => aEl);

      await submit();

      expect(focusHandler).toHaveBeenCalled();
      aEl.remove();
    });

    it('always clones error result objects', async () => {
      // regression test for TL-29929:
      // error result objects sometimes did not get cloned before merging,
      // resulting in validation errors persisting after the validator stopped
      // returning them.

      const { scope, submit } = createReform({
        els: [{ value: null }, { value: null }],
      });

      scope.register('validator', ['els', 0, 'value'], () => null);
      scope.register('validator', ['els', 1, 'value'], () => null);

      scope.register('validator', 'els', items =>
        items.map((item, index) => {
          const isDuplicate =
            items.findIndex((x, i) => i != index && x.value === item.value) !==
            -1;
          return {
            value: isDuplicate ? 'dupe' : null,
          };
        })
      );

      scope.update(['els', 0, 'value'], 5);
      scope.update(['els', 1, 'value'], 5);

      await submit();

      expect(scope.getError([])).toEqual({
        els: [{ value: 'dupe' }, { value: 'dupe' }],
      });

      scope.update(['els', 1, 'value'], 9);

      await validateWait();

      expect(scope.getError([])).toEqual({
        els: [{ value: null }, { value: null }],
      });
    });

    it('handles undefined result in scope validator', async () => {
      // regression test for TL-30045:
      // scope validators that returned either undefined or an object would cause
      // an error on submit if the result was previously undefined.

      const { scope, submit } = createReform({ fields: [{}] });
      let draft = true;

      scope.register('validator', ['fields', 1], () =>
        draft ? undefined : { foo: 'error' }
      );

      await submit();

      draft = false;
      // submit will throw if merging doesn't work correctly
      await submit();
      expect(scope.getTouched(['fields', 1, 'foo'])).toBe(true);
    });

    it('handles direct touch with existing undefined validator result', async () => {
      // regression test for TL-30045:
      // scope validators that returned either undefined or an object would cause
      // an error on on real touch if the form was previously submitted with
      // undefined as the validator result.

      const { scope, submit } = createReform({ fields: [{}] });
      scope.register('validator', ['fields', 1], () => undefined);

      await submit();

      scope.touch(['fields', 1, 'foo']);
      expect(scope.getTouched(['fields', 1, 'foo'])).toBe(true);
    });

    it('allows attaching change listeners for paths', async () => {
      const { scope } = createReform();

      const listenerA = jest.fn();
      scope.register('changeListener', ['jack', 'foo'], listenerA);
      expect(listenerA).not.toHaveBeenCalled();
      scope.update(['jack', 'foo'], 'a');
      if (stateMode == 'controlled') await Vue.nextTick();
      expect(listenerA).toHaveBeenCalled();

      const listenerB = jest.fn();
      scope.register('changeListener', 'carl', listenerB);
      expect(listenerB).not.toHaveBeenCalled();
      scope.update(['carl', 'foo'], 'a');
      if (stateMode == 'controlled') await Vue.nextTick();
      expect(listenerB).toHaveBeenCalled();

      const listenerC = jest.fn();
      scope.register('changeListener', ['fred', 'foo'], listenerC);
      expect(listenerC).not.toHaveBeenCalled();
      scope.update(['fred'], { foo: 'a' });
      if (stateMode == 'controlled') await Vue.nextTick();
      expect(listenerC).toHaveBeenCalled();

      const listenerD = jest.fn();
      scope.register('changeListener', ['winston', 'foo'], listenerD);
      expect(listenerD).not.toHaveBeenCalled();
      scope.$_internalUpdateSliceState('winston', state => {
        state.values = { foo: 'a' };
        return state;
      });
      if (stateMode == 'controlled') await Vue.nextTick();
      expect(listenerD).toHaveBeenCalled();

      const listenerE = jest.fn();
      scope.register('changeListener', 'archibald', listenerE);
      expect(listenerE).not.toHaveBeenCalled();
      scope.$_internalUpdateSliceState(['archibald', 'foo'], state => state);
      if (stateMode == 'controlled') await Vue.nextTick();
      expect(listenerE).toHaveBeenCalled();
    });

    it('lets you submit the form externally', async () => {
      const submitHandler = jest.fn();

      const { scope, vm } = createReform(
        { foo: 'bar' },
        { listeners: { submit: submitHandler } }
      );

      scope.register('validator', 'foo', x =>
        x == 'invalid' ? 'invalid' : null
      );

      let result;
      [result] = await Promise.all([vm.trySubmit(), validateWait()]);
      expect(result).toEqual({ foo: 'bar' });
      expect(submitHandler).not.toHaveBeenCalled();

      scope.update('foo', 'invalid');

      [result] = await Promise.all([vm.trySubmit(), validateWait()]);
      expect(result).toBe(null);
      expect(submitHandler).not.toHaveBeenCalled();

      [result] = await Promise.all([vm.submit(), validateWait()]);
      expect(result).toBe(null);
      expect(submitHandler).not.toHaveBeenCalled();

      scope.update('foo', 'bar');

      [result] = await Promise.all([vm.submit(), validateWait()]);
      expect(result).toEqual({ foo: 'bar' });
      expect(submitHandler).toHaveBeenCalled();
    });

    it('exposes validation results', async () => {
      let last;
      const validationChanged = jest.fn();
      const { scope } = createReform(
        {},
        { listeners: { 'validation-changed': validationChanged } }
      );

      expect(validationChanged).not.toHaveBeenCalled();

      scope.register('validator', 'field', x => (x == 'f' ? 'error' : null));
      scope.touch('field');

      await validateWait();

      expect(validationChanged).toHaveBeenCalledTimes(1);
      last = validationChanged.mock.calls[0][0];
      expect(last.isValid).toBe(true);
      expect(last.getError('field')).not.toBeAnything();
      expect(last.getError('foo')).not.toBeAnything();

      scope.update('field', 'f');

      await validateWait();

      expect(validationChanged).toHaveBeenCalledTimes(2);
      last = validationChanged.mock.calls[1][0];
      expect(last.isValid).toBe(false);
      expect(last.isValid).toBe(false);
      expect(last.getError('field')).toBe('error');
      expect(last.getError()).toEqual({ field: 'error' });
      expect(last.getError('foo')).not.toBeAnything();
    });

    it('allows switching to validate-on-submit', async () => {
      const { scope, submit } = createReform(
        {},
        { props: { validationMode: 'submit' } }
      );

      scope.register('validator', 'field', x => (x ? null : 'required'));

      await validateWait();

      expect(scope.getError('field')).not.toBeAnything();

      await submit();

      expect(scope.getError('field')).toBe('required');
    });

    it('waits for validations to complete before displaying an error', async () => {
      const val1 = { field: 'foo' };
      const { scope } = createSimpleControlled(val1);

      scope.register('validator', 'field', x => (x == 'foo' ? 'err' : null));
      scope.touch('field');

      expect(scope.getError('field')).toBe(undefined);
      await validateWait();
      expect(scope.getError('field')).toBe('err');
    });
  });
});
