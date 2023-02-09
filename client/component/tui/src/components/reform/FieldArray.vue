<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2020 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Simon Chester <simon.chester@totaralearning.com>
  @module tui
-->

<script>
import Vue from 'vue';
import FormScope from 'tui/components/reform/FormScope';

const isFn = fn => typeof fn == 'function';

export default {
  inject: ['reformScope'],

  components: {
    FormScope,
  },

  provide() {
    return {
      // prevent field context from being passed down
      reformFieldContext: null,
    };
  },

  props: {
    /**
     * Path at which array is located.
     *
     * Array will be created at the path upon mutation if one does not exist.
     */
    path: {
      type: [String, Array],
      required: true,
    },
    validate: Function,
  },

  created() {
    this.arrayHelpers = {
      push: this.push,
      pop: this.pop,
      swap: this.swap,
      move: this.move,
      insert: this.insert,
      unshift: this.unshift,
      shift: this.shift,
      remove: this.remove,
      replace: this.replace,
    };
  },

  methods: {
    /**
     * Update slice state using the provided functions
     *
     * @internal
     * @param {function} valueFn
     * @param {(function|boolean)} [touchFn=true]
     * @returns {*} Result of valueFn
     */
    $_mutate(valueFn, touchFn = true) {
      let result;
      this.reformScope.$_internalUpdateSliceState(this.path, state => {
        if (!state.values) Vue.set(state, 'values', []);
        if (!state.touched) Vue.set(state, 'touched', []);
        result = valueFn(state.values);
        if (touchFn) {
          (isFn(touchFn) ? touchFn : valueFn)(state.touched);
        }
        return state;
      });
      return result;
    },

    /**
     * Add an item to the end of the array.
     *
     * @param {*} item
     */
    push(item) {
      this.$_mutate(
        v => v.push(item),
        t => t.push(null)
      );
    },

    /**
     * Remove an item from the end of the array and return it.
     *
     * @returns {*}
     */
    pop() {
      return this.$_mutate(v => v.pop());
    },

    /**
     * Swap the positions of two items.
     *
     * @param {number} ia
     * @param {number} ib
     */
    swap(ia, ib) {
      this.$_mutate(v => {
        const tmp = v[ia];
        v[ia] = v[ib];
        v[ib] = tmp;
      });
    },

    /**
     * Swap the positions of two items.
     *
     * @param {number} ia
     * @param {number} ib
     */
    move(from, to) {
      this.$_mutate(v => {
        const [item] = v.splice(from, 1);
        v.splice(to, 0, item);
      });
    },

    /**
     * Insert an item at the specified position.
     *
     * @param {number} i
     * @param {*} item
     */
    insert(i, item) {
      this.$_mutate(
        v => v.splice(i, 0, item),
        t => t.splice(i, 0, null)
      );
    },

    /**
     * Add an item to the beginning of the array.
     *
     * @param {*} item
     */
    unshift(item) {
      this.$_mutate(
        v => v.unshift(item),
        t => t.unshift(null)
      );
    },

    /**
     * Remove an item from the end of the array.
     *
     * @param {*} item
     */
    shift(item) {
      this.$_mutate(v => v.shift(item));
    },

    /**
     * Remove the item at the specified index.
     *
     * @param {number} i
     * @returns {*} Removed item.
     */
    remove(i) {
      return this.$_mutate(v => v.splice(i, 1)[0]);
    },

    /**
     * Replace the item at the specified index with the provided item
     */
    replace(i, item) {
      this.$_mutate(v => (v[i] = item), false);
    },
  },

  render(h) {
    const items = this.reformScope.getValue(this.path) || [];

    return h(
      'FormScope',
      { props: { path: this.path, validate: this.validate } },
      this.$scopedSlots.default(Object.assign({ items }, this.arrayHelpers))
    );
  },
};
</script>
