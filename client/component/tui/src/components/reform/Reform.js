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
import { get, set, structuralDeepClone, result } from 'tui/util';
import { set as vueSet } from 'tui/vue_util';
import { loadLangStrings, isRtl } from 'tui/i18n';
import { getDocumentPosition } from 'tui/dom/position';
import { getTabbableElements } from 'tui/dom/focus';
import BatchingLoadQueue from '../../js/internal/BatchingLoadQueue';
import {
  arrayEqual,
  arrayStartsWith,
  toPath,
  pathFunctionEqual,
  sortEntriesByPath,
  collectErrorValues,
  mergeErrors,
  onlyTouched,
  makeAllTouch,
  collectLangStrings,
} from '../../js/internal/reform/data_structure_utils';
import ValidationResults from '../../js/internal/reform/ValidationResults';
import { TOUCHED } from '../../js/internal/reform/constants';
import { produce } from '../../js/immutable';

export default {
  provide() {
    return {
      reformScope: {
        getValue: this.get,
        getError: name => get(this.displayedErrors, name),
        getTouched: name => !!get(this.$_state(TOUCHED), name),
        update: this.update,
        blur: this.blur,
        touch: this.touch,
        register: this.register,
        unregister: this.unregister,
        updateRegistration: this.updateRegistration,
        getInputName: this.getInputName,
        $_internalUpdateSliceState: this.$_internalUpdateSliceState,
      },
    };
  },

  model: {
    prop: 'state',
    event: 'update:state',
  },

  props: {
    /**
     * Initial values for form fields.
     *
     * Ignored if `state` is passed.
     */
    initialValues: {
      type: [Object, Function],
      default: () => ({}),
    },

    /**
     * Form state, when controlled externally.
     *
     * Updates are emitted through `update:state`
     */
    state: Object,

    /**
     * External errors to display in form.
     */
    errors: Object,

    /**
     * Root-level validator function.
     */
    validate: Function,

    /**
     * Validation mode.
     *
     * 'auto': smart validation
     * 'submit': only validate on submit
     */
    validationMode: {
      type: String,
      default: 'auto',
      validator: x => ['auto', 'submit'].includes(x),
    },
  },

  data() {
    return {
      // structural state
      validators: [],
      registrations: {
        processor: [],
        submitHandler: [],
        element: [],
        changeListener: [],
      },

      // form state
      submitting: false,

      // form content state
      formState: {
        values: structuralDeepClone(result(this.initialValues)),
        [TOUCHED]: {},
      },

      // generated
      mergedErrors: {},
      validationResults: new ValidationResults({}),
      validatorsErrors: [],
      tempTouched: null,
    };
  },

  computed: {
    /**
     * Get displayed errors.
     *
     * @returns {object}
     */
    displayedErrors() {
      return onlyTouched(
        this.mergedErrors,
        this.tempTouched || this.$_state(TOUCHED),
        this.$_computedTouched
      );
    },

    /**
     * Work out if we have any errors.
     *
     * @returns {boolean}
     */
    isValid() {
      return collectErrorValues(this.mergedErrors).every(x => !x);
    },

    isControlled() {
      return this.state != null;
    },
  },

  watch: {
    // revalidate when external errors change
    errors: {
      handler() {
        this.$_validate();
      },
      deep: true,
    },

    state: {
      handler(val, old) {
        if (old == null || val == null) {
          throw new Error(
            'Reform may not be shifted between controlled and uncontrolled. ' +
              'You must either not pass :value or always pass :value.'
          );
        }

        // Run validators and listeners.
        this.$_afterChange([]);
      },
    },
  },

  created() {
    this.validationQueue = new BatchingLoadQueue({
      handler: this.$_validateInternal,
      wait: 10,
      equal: arrayEqual,
      serial: true,
    });

    this.$_stateUpdateTask = false;
    this.$_pendingState = null;
  },

  methods: {
    /**
     * Get the part of state identified by field
     *
     * @param {string|Symbol} field
     * @returns {object}
     */
    $_state(field) {
      const state = this.$_pendingState || this.state || this.formState;

      if (field) {
        return state[field] || {};
      }
      return state;
    },

    /**
     * Record an update to the state.
     *
     * Calls recipe with the state as an argument.
     *
     * When in controlled mode, it uses produce() from tui/immutable to apply
     * an immutable update.
     *
     * @param {(draft: object)} recipe
     * @param {string|number|array} pathHint
     */
    $_recordUpdate(recipe, pathHint) {
      if (this.isControlled) {
        const state = this.$_pendingState || this.state;
        const dummy = {
          values: state.values || {},
          [TOUCHED]: state[TOUCHED] || {},
        };
        const newState = produce(dummy, recipe);
        if (newState != dummy) {
          this.$_pendingState = {
            values: newState.values,
            [TOUCHED]: newState[TOUCHED],
          };
          // batch up changes and send in the next tick, that way we won't lose
          // multiple mutations made in the same tick
          if (!this.$_stateUpdateTask) {
            this.$_stateUpdateTask = true;
            Vue.nextTick(() => {
              this.$emit('update:state', this.$_pendingState);
              this.$_pendingState = null;
              this.$_stateUpdateTask = false;
            });
          }
        }
      } else {
        recipe(this.formState);
        this.$emit('change', this.formState.values);
        this.$_afterChange(toPath(pathHint));
      }
    },

    /**
     * Update recorded value for input.
     *
     * @param {(string|number|array)} path
     * @param {*} value
     */
    update(path, value) {
      this.$_recordUpdate(state => {
        if (path == null) {
          state.values = value;
        } else {
          path = toPath(path);
          vueSet(state.values, path, value);
        }
      }, path);
    },

    /**
     * Get current value of input.
     *
     * @param {?(string|number|array)} path Path. Omit to return all values.
     * @returns {*}
     */
    get(path) {
      if (path == null) {
        return this.$_state('values');
      }
      return get(this.$_state('values'), path);
    },

    /**
     * Record that input has blurred (been unfocused).
     *
     * @param {(string|number|array)} path
     */
    blur(path) {
      this.touch(path);
    },

    /**
     * Record that input has been touched.
     *
     * @param {(string|number|array)} path
     */
    touch(path) {
      if (!this.tempTouched) {
        const state = this.$_pendingState || this.state || this.formState;
        this.tempTouched = structuralDeepClone(state[TOUCHED] || {});
      }

      this.$_recordUpdate(draft => {
        vueSet(draft[TOUCHED], path, true);
      }, path);
      if (!this.isControlled && this.validationMode != 'submit') {
        this.$_validate(path);
      }
    },

    /**
     * Register (path, function) of specified type.
     *
     * @param {('validator'|'processor'|'submitHandler'|'changeListener')} type
     * @param {(string|number|array|null)} path
     * @param {function} fn
     */
    register(type, path, fn) {
      switch (type) {
        case 'validator':
          this.$_register(this.validators, path, fn);
          if (this.validationMode != 'submit') {
            this.$_validateIfTouched(path);
          }
          return;
        default:
          if (!this.registrations[type]) {
            this.registrations[type] = [];
          }
          return this.$_register(this.registrations[type], path, fn);
      }
    },

    /**
     * Unregister (path, function) of specified type.
     *
     * @param {('validator'|'processor'|'submitHandler'|'changeListener')} type
     * @param {(string|number|array|null)} path
     * @param {function} fn
     */
    unregister(type, path, fn) {
      switch (type) {
        case 'validator':
          this.$_unregister(this.validators, path, fn);
          if (this.validationMode != 'submit') {
            this.$_validateIfTouched(path);
          }
          return;
        default:
          if (!this.registrations[type]) {
            return;
          }
          return this.$_unregister(this.registrations[type], path, fn);
      }
    },

    /**
     * Helper for updating registration when it changes.
     *
     * Unregisters the old function and registers the new one.
     * Does nothing if they haven't changed.
     *
     * @param {string} type
     * @param {(string|number|array)} path
     * @param {function} fn
     * @param {(string|number|array)} oldPath
     * @param {function} oldFn
     */
    updateRegistration(type, path, fn, oldPath, oldFn) {
      if (
        fn == oldFn &&
        (path == oldPath || arrayEqual(toPath(path), toPath(oldPath)))
      ) {
        // nothing has changed
        return;
      }

      if (oldFn) {
        this.unregister(type, oldPath, oldFn);
      }

      if (fn) {
        this.register(type, path, fn);
      }
    },

    $_register(array, path, fn) {
      const entry = [path, fn];
      if (!array.some(x => pathFunctionEqual(x, entry))) {
        array.push(entry);
      }
    },

    $_unregister(array, path, fn) {
      const entry = [path, fn];
      const index = array.findIndex(x => pathFunctionEqual(x, entry));
      if (index !== -1) {
        array.splice(index, 1);
      }
    },

    /**
     * Get name to use for HTML input.
     *
     * This is mostly for autocomplete support.
     *
     * e.g.:
     * ['name'] => 'name'
     * ['a', 'b', 'c'] => 'a[b][c]'
     *
     * This syntax was chosen as it is the one used by PHP for nested params.
     *
     * @param {(array|string)} path
     * @returns {string}
     */
    getInputName(path) {
      return toPath(path)
        .map((part, i) => (i == 0 ? part : '[' + part + ']'))
        .join('');
    },

    /**
     * Reset form to initial state.
     */
    reset() {
      const newState = {
        values: structuralDeepClone(result(this.initialValues)),
        [TOUCHED]: {},
      };
      if (this.isControlled) {
        this.$emit('update:state', newState);
      } else {
        this.formState = newState;
      }
      this.mergedErrors = {};
      this.validatorsErrors = [];
    },

    /**
     * Handle submit event on form.
     *
     * @param {Event} e
     */
    async handleSubmit(e) {
      e.preventDefault();

      return this.submit();
    },

    /**
     * Attempt to submit form, returning form values if valid or null otherwise.
     *
     * @public
     * @returns {Promise<object|null>}
     */
    async trySubmit() {
      this.submitting = true;

      // wait for rerender
      await Vue.nextTick();

      // validate
      await this.$_validate();

      this.submitting = false;
      this.$_recordUpdate(draft => {
        mergeErrors(draft[TOUCHED], makeAllTouch(this.mergedErrors));
      });

      // emit
      if (this.isValid) {
        const processors = sortEntriesByPath(
          this.registrations.processor
        ).reverse();
        const submitHandlers = sortEntriesByPath(
          this.registrations.submitHandler
        ).reverse();
        let values = structuralDeepClone(this.$_state('values'));

        // process values
        for (let i = 0; i < processors.length; i++) {
          const [path, processor] = processors[i];
          if (path === null) {
            // eslint false positive (`values` is not modifiable from outside
            // this function while we are awaiting):
            // eslint-disable-next-line require-atomic-updates
            values = await processor(values);
          } else {
            const result = await processor(get(values, path));
            set(values, path, result);
          }
        }

        // call registered submit handlers
        submitHandlers.forEach(([path, handler]) =>
          handler(path === null ? values : get(values, path))
        );

        return values;
      } else {
        return null;
      }
    },

    /**
     * Trigger submit of form, firing submit event if valid..
     *
     * Returns form values if valid or null otherwise.
     *
     * @public
     * @returns {Promise<object|null>}
     */
    async submit() {
      const values = await this.trySubmit();
      if (values) {
        this.$emit('submit', values);
        return values;
      } else {
        this.focusFirstInvalid();
        return null;
      }
    },

    /**
     * Focus the first invalid field.
     *
     * @public
     * @returns {boolean} success
     */
    focusFirstInvalid() {
      return this.$_focusFirstEl(path => get(this.mergedErrors, path));
    },

    /**
     * Focus the first field.
     *
     * @public
     */
    focus() {
      // find first field (by screen-space position in doucment)
      this.$_focusFirstEl();
    },

    /**
     * Focus first field meeting condition.
     *
     * @internal
     * @param {((path: (string|number|array), getEl: Function) => boolean} [filter]
     * @returns {boolean} success
     */
    $_focusFirstEl(filter) {
      const rtl = isRtl();
      const isLeftBefore = rtl ? (a, b) => a > b : (a, b) => a < b;

      // find first field (by screen-space position in document)
      let firstEl = null;
      let firstPos = null;
      this.registrations.element.forEach(([path, getEl]) => {
        const el = getEl();
        if (el && (!filter || filter(path, getEl))) {
          const pos = getDocumentPosition(el);
          if (
            firstEl == null ||
            pos.top < firstPos.top ||
            (pos.top === firstPos.top && isLeftBefore(pos.left, firstPos.left))
          ) {
            firstEl = el;
            firstPos = pos;
          }
        }
      });

      if (firstEl) {
        const tabbable = getTabbableElements(firstEl);
        if (tabbable.length > 0) {
          tabbable[0].focus();
        } else {
          firstEl.scrollIntoView({ behavior: 'smooth' });
        }
        return true;
      }
      return false;
    },

    /**
     * INTERNAL method for updating a slice of form state.
     *
     * Do not use this method outside of Tui core forms code.
     *
     * @internal
     * @param {(string|number|array)} path
     * @param {({ values, touched }) => { values, touched }} fn Callback. Called with { values, touched }, should return the same shaped object.
     */
    $_internalUpdateSliceState(path, fn) {
      this.$_recordUpdate(draft => {
        const slice = path
          ? {
              values: get(draft.values, path),
              touched: get(draft[TOUCHED], path),
            }
          : { values: draft.values, touched: draft[TOUCHED] };

        const result = fn(slice);

        if (path) {
          vueSet(draft.values, path, result.values);
          vueSet(draft[TOUCHED], path, result.touched);
        } else {
          draft.values = result.values;
          draft[TOUCHED] = result.touched;
        }
      }, path);
    },

    /**
     * Handle after-change actions.
     *
     * @internal
     * @param {array} path
     */
    $_afterChange(path) {
      // run listeners for any parents, self, or children matching
      const listeners = this.registrations.changeListener.filter(
        ([listenerPath]) =>
          listenerPath == null ||
          arrayStartsWith(toPath(listenerPath), path) ||
          arrayStartsWith(path, toPath(listenerPath))
      );

      listeners.forEach(([, handler]) => handler());

      if (this.validationMode != 'submit') {
        this.$_validate(path);
      }
    },

    /**
     * Run validators and update error status.
     *
     * @internal
     * @param {(array|null)} [validatePath]
     * @returns {Promise}
     */
    $_validate(path = null) {
      return this.validationQueue.enqueue(path);
    },

    /**
     * Run validators if path touched.
     *
     * @internal
     * @param {(array|null)} [validatePath]
     */
    $_validateIfTouched(path = null) {
      if (path == null) {
        this.$_validate();
      } else if (get(this.$_state('touched'), path)) {
        this.$_validate(path);
      }
    },

    /**
     * Run validators for specified paths (called by queue).
     *
     * @internal
     * @param {Array<(string|number|array)>} validatePaths
     */
    async $_validateInternal(validatePaths) {
      validatePaths = validatePaths.map(toPath);
      // figure out what validators to run
      let validators = sortEntriesByPath(this.validators);
      if (this.validate) {
        validators.unshift([null, this.validate]);
      }
      const validateRoot = validatePaths.some(
        x => x.length == 1 && x[0] == null
      );
      let validatorMatcher;
      if (!validateRoot) {
        // run validators for any parents, self, or children matching
        // root validators always run
        const validatorMatcherInner = (path, reqPath) =>
          path == null ||
          arrayStartsWith(toPath(path), reqPath) ||
          arrayStartsWith(reqPath, toPath(path));

        validatorMatcher = path =>
          validatePaths.some(reqPath => validatorMatcherInner(path, reqPath));

        validators = validators.filter(([path]) => validatorMatcher(path));
      }

      // run validators (async)
      let validatorResults = await Promise.all(
        validators.map(([path, validator]) => {
          if (path === null) {
            return Promise.resolve(
              validator(this.$_state('values'))
            ).then(x => [path, x]);
          } else {
            const values = get(this.$_state('values'), path);
            return Promise.resolve(validator(values)).then(validatorResult => {
              let validatorErrors = {};
              vueSet(validatorErrors, path, validatorResult);
              return [path, validatorErrors];
            });
          }
        })
      );

      if (validatorMatcher) {
        // filter out errors from validators matching our path, then replace
        // with new validation results
        validatorResults = sortEntriesByPath(
          this.validatorsErrors
            .filter(([path]) => !validatorMatcher(path))
            .concat(validatorResults)
        );
      }

      // combine errors into a single object
      const mergedErrors = validatorResults.reduce(
        (acc, [, errors]) => mergeErrors(acc, errors),
        this.errors ? structuralDeepClone(this.errors) : {}
      );

      // load strings for errors
      const langStrings = collectLangStrings(mergedErrors);
      if (langStrings.length > 0) {
        await loadLangStrings(langStrings);
      }

      // finally, assign result
      this.validatorsErrors = validatorResults;
      this.mergedErrors = mergedErrors;
      this.validationResults = new ValidationResults(mergedErrors);

      if (this.validationQueue.size == 0) {
        this.tempTouched = null;
      }

      this.$emit('validation-changed', this.validationResults);
    },

    /**
     * Get whether the form is submitting.
     *
     * @returns {boolean}
     */
    getSubmitting() {
      return this.submitting;
    },

    /**
     * Compute touched.
     *
     * @param {string} field
     * @returns {boolean}
     */
    $_computedTouched(field) {
      return this.errors && !!get(this.errors, field);
    },
  },

  render() {
    // do not return any state directly here, return getter functions instead.
    // otherwise if this component is wrapped, Vue will think the state is being
    // accessed when we pass along all slot props.
    return this.$scopedSlots.default({
      getSubmitting: this.getSubmitting,
      handleSubmit: this.handleSubmit,
      submit: this.submit,
      reset: this.reset,
    });
  },
};
