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
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @module mod_approval
 */

/**
 * Create a field wrapper for a Uniform field component.
 *
 * @param {import('vue').Component} component Field component
 * @param {(field: object) => object} [additionalMapper]
 *  Map field definition to vue component attributes
 * @returns {import('vue').Component}
 */
export function uniformFieldWrapper(component, additionalMapper) {
  return {
    functional: true,
    components: { inner: component },
    props: {
      field: { type: Object, required: true },
      readonly: { type: Boolean },
      view: { type: String },
    },
    render(h, { props }) {
      const { field } = props;
      const attrs = {
        name: field.key,
        disabled: field.disabled,
      };
      if (additionalMapper) {
        Object.assign(attrs, additionalMapper(field));
      }
      return h(component, {
        attrs,
        props: {
          charLength: field.char_length,
          readonly: props.readonly,
        },
      });
    },
  };
}
