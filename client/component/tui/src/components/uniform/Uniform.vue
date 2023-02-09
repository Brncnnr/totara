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

<template>
  <Reform
    ref="reform"
    v-slot="slotProps"
    :initial-values="initialValues"
    :state="state"
    :errors="errors"
    :validate="validate"
    :validation-mode="validationMode"
    @change="$emit('change', $event)"
    @update:state="$emit('update:state', $event)"
    @submit="$emit('submit', $event)"
    @validation-changed="$emit('validation-changed', $event)"
  >
    <Form
      :vertical="vertical"
      :input-width="inputWidth"
      novalidate
      :autocomplete="autocomplete"
      @submit="slotProps.handleSubmit"
    >
      <slot v-bind="slotProps" />
    </Form>
  </Reform>
</template>

<script>
import Reform from 'tui/components/reform/Reform';
import Form from 'tui/components/form/Form';

export default {
  components: {
    Reform,
    Form,
  },

  model: {
    prop: 'state',
    event: 'update:state',
  },

  props: {
    /*
     * Initial values for form fields.
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

    vertical: Boolean,

    // default input size
    inputWidth: {
      type: String,
      validator: x => ['full', 'limited'].includes(x),
      default: 'limited',
    },

    autocomplete: {
      type: String,
      default: 'off',
    },
  },

  methods: {
    /**
     * Trigger submit of form, firing submit event if valid..
     *
     * Returns form values if valid or null otherwise.
     *
     * @public
     * @returns {Promise<object|null>}
     */
    submit() {
      return this.$refs.reform.submit();
    },

    /**
     * Attempt to submit form, returning form values if valid or null otherwise.
     *
     * @public
     * @returns {Promise<object|null>}
     */
    trySubmit() {
      return this.$refs.reform.trySubmit();
    },

    /**
     * Focus the first invalid field.
     *
     * @public
     */
    focusFirstInvalid() {
      return this.$refs.reform.focusFirstInvalid();
    },

    /**
     * Reset form to initial state.
     *
     * @public
     */
    reset() {
      this.$refs.reform.reset();
    },

    /**
     * Get value of field at path.
     *
     * @public
     * @param {?(string|number|array)} path Path. Omit to return all values.
     * @returns {*}
     */
    get(path) {
      return this.$refs.reform.get(path);
    },

    /**
     * Set value of field at path.
     *
     * @public
     * @param {(string|number|array)} path
     */
    update(path, value) {
      this.$refs.reform.update(path, value);
    },

    /**
     * Record that input has been touched.
     *
     * @public
     * @param {(string|number|array)} path
     */
    touch(path) {
      this.$refs.reform.touch(path);
    },

    /**
     * Focus the form.
     *
     * @public
     */
    focus() {
      this.$refs.reform.focus();
    },
  },
};
</script>
