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

import { pick } from 'tui/util';
import { getPropDefs, getModelDef } from 'tui/vue_util';
import FormField from 'tui/components/uniform/FormField';
import { mergeListeners } from '../../js/internal/vnode';
import { charLengthProp } from '../form/form_common';

/**
 * Create a wrapper component for an input.
 *
 * The only hard requirement is that the input component works with v-model.
 *
 * @param {(object|Vue)} input Input component to wrap.
 * @param {object} [options]
 * @param {boolean} [options.passAriaLabelledby] Pass the label ID as aria-labelledby.
 * @param {boolean} [options.functional] Generate a functional component.
 * @param {boolean} [options.touchOnChange] Mark input as touched whenever it changes.
 * @returns {(object|Vue)} Vue component.
 */
export function createUniformInputWrapper(input, options = {}) {
  const functional =
    options.functional !== undefined ? options.functional : true;
  const inputProps = Object.assign({}, getPropDefs(input));
  delete inputProps.name;
  delete inputProps.validate;
  delete inputProps.validations;
  delete inputProps.id;
  delete inputProps.ariaDescribedby;
  delete inputProps.ariaInvalid;
  delete inputProps.ariaLabelledby;

  const props = Object.assign({}, inputProps, {
    id: {},

    name: {
      type: [String, Number, Array],
      required: true,
    },

    validate: Function,
    validations: [Function, Array],
    charLength: charLengthProp,
  });

  const model = getModelDef(input);
  const modelProp = (model && model.prop) || 'value';
  const modelEvent = (model && model.event) || 'input';

  return {
    functional,

    // needed for lang string loader support
    components: {
      FormField,
      Input: input,
    },

    inheritAttrs: false,

    props: props,

    render(h, context) {
      const props = functional ? context.props : this;
      const listeners = functional ? context.listeners : this.$listeners;
      const propsForInput = pick(props, Object.keys(inputProps));
      propsForInput.charLength = 'full';
      return h(FormField, {
        props: {
          name: props.name,
          validate: props.validate,
          validations: props.validations,
          charLength: props.charLength,
        },
        scopedSlots: {
          default: ({ value, update, blur, touch, labelId, attrs }) => {
            const finalProps = Object.assign({}, propsForInput);
            finalProps[modelProp] = value;
            // attrs that are actually props will automatically be moved to
            // props by Vue
            const inputAttrs = Object.assign({}, attrs);
            if (options.passAriaLabelledby) {
              inputAttrs['aria-labelledby'] = labelId;
            }
            if (props.id) {
              inputAttrs.id = props.id;
            }
            return h(
              input,
              {
                props: finalProps,
                attrs: inputAttrs,
                on: mergeListeners(listeners, {
                  [modelEvent]: value => {
                    update(value);
                    if (options.touchOnChange) touch();
                  },
                  blur,
                }),
                scopedSlots: functional ? undefined : this.$scopedSlots,
              },
              functional ? context.children : undefined
            );
          },
        },
      });
    },
  };
}
