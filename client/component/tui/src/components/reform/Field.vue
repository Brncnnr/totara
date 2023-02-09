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
import { fieldValidator } from 'tui/validation';

export default {
  inject: {
    reformScope: {},
    reformFieldContext: { default: undefined },
  },

  props: {
    name: {
      type: [String, Number, Array],
      required: true,
    },

    validate: Function,
    validations: [Function, Array],
  },

  computed: {
    /**
     * HTML ID for field.
     *
     * Used to support accessibility.
     *
     * @returns {string}
     */
    ariaDescribedby() {
      return (
        (this.reformFieldContext &&
          this.reformFieldContext.getAriaDescribedby()) ||
        undefined
      );
    },

    /**
     * HTML ID for field.
     *
     * Used to support accessibility.
     *
     * @returns {string}
     */
    id() {
      return (
        (this.reformFieldContext && this.reformFieldContext.getId()) || this.uid
      );
    },

    /**
     * HTML ID for label.
     *
     * Used to suppot accessibility.
     *
     * @returns {?string}
     */
    labelId() {
      return (
        (this.reformFieldContext && this.reformFieldContext.getLabelId()) ||
        undefined
      );
    },

    /**
     * Field value.
     *
     * @returns {*}
     */
    value() {
      return this.reformScope && this.reformScope.getValue(this.name);
    },

    /**
     * Error string if field has an error, or falsy value if it does not.
     *
     * @returns {?string}
     */
    error() {
      return this.reformScope && this.reformScope.getError(this.name);
    },

    /**
     * Validator function.
     *
     * @returns {?function}
     */
    validator() {
      if (typeof this.validate == 'function') {
        return this.validate;
      }
      if (this.validations) {
        return fieldValidator(this.validations);
      }
      return null;
    },

    /**
     * Helper computed property for updating registration
     */
    validatorInfo() {
      return [this.name, this.validator];
    },

    /**
     * Name to use for the input in HTML.
     *
     * @returns {string}
     */
    inputName() {
      return this.reformScope
        ? this.reformScope.getInputName(this.name)
        : this.name.toString();
    },
  },

  watch: {
    validatorInfo: {
      immediate: true,
      handler(val, old) {
        // re-register validator if it changes or the path changes
        this.reformScope.updateRegistration(
          'validator',
          val && val[0],
          val && val[1],
          old && old[0],
          old && old[1]
        );
      },
    },

    name: {
      immediate: true,
      handler(val, old) {
        // re-register element at new path
        this.reformScope.updateRegistration(
          'element',
          val,
          this.$_getEl,
          old,
          this.$_getEl
        );
      },
    },
  },

  mounted() {
    if (!this.reformScope) {
      console.warn(
        '[Reform] Field must be contained within a <Reform> component.'
      );
    }
  },

  beforeDestroy() {
    if (this.reformScope) {
      this.reformScope.unregister('validator', this.name, this.validator);
      this.reformScope.unregister('element', this.name, this.$_getEl);
    }
  },

  methods: {
    /**
     * Update form with new value for field.
     *
     * @param {*} value
     */
    update(value) {
      this.reformScope.update(this.name, value);
    },

    /**
     * Mark field as having been unfocused.
     */
    blur() {
      this.reformScope.blur(this.name);
    },

    /**
     * Mark field as touched.
     */
    touch() {
      this.reformScope.touch(this.name);
    },

    /**
     * Get the element associated with this field.
     *
     * @internal
     */
    $_getEl() {
      return this.$el;
    },
  },

  render() {
    return this.$scopedSlots.default({
      ariaDescribedby: this.ariaDescribedby,
      id: this.id,
      labelId: this.labelId,
      value: this.value,
      name: this.name,
      update: this.update,
      blur: this.blur,
      touch: this.touch,
      error: this.error,
      inputName: this.inputName,
    });
  },
};
</script>
