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

import { parseISO } from 'tui/date';
import { getModelDef, getPropDefs } from 'tui/vue_util';
import { uniformFieldWrapper } from '../wrappers';
import * as formFields from 'tui/components/uniform';
import { createUniformInputWrapper } from 'tui/components/uniform/util';
import DateSelector from 'tui/components/form/DateSelector';
import CurrencyTotal from 'mod_approval/components/schema_form/fields/CurrencyTotal';
import EditorView from 'mod_approval/components/schema_form/fields/EditorView';
import LabelRow from 'mod_approval/components/schema_form/LabelRow';
import { calculateTotal } from '../fields/total';
import { pick } from 'tui/util';
import Editor from 'tui/components/editor/Editor';
import { EditorContent } from 'tui/editor';
import { formatDate } from '../common';
import { config } from 'tui/config';
import { CurrencyFormat } from 'tui/currency';
import { getString } from 'tui/i18n';

const getAttr = (field, name) =>
  field.attrs != null ? field.attrs[name] : null;

function mapVModel(mapIn, mapOut, comp) {
  const model = getModelDef(comp);
  const modelProp = (model && model.prop) || 'value';
  const modelEvent = (model && model.event) || 'input';
  const propDefs = getPropDefs(comp);
  propDefs['value'] = {};
  return {
    name: 'VModelMapper',
    components: { inner: comp },
    inheritAttrs: false,
    props: propDefs,
    computed: {
      mappedValue() {
        return mapIn(this.value);
      },
    },
    render(h) {
      const props = pick(this, Object.keys(propDefs));
      props[modelProp] = this.mappedValue;
      return h(comp, {
        attrs: this.$attrs,
        props,
        on: {
          [modelEvent]: value => {
            this.$emit('input', mapOut(value));
          },
        },
      });
    },
  };
}

const Text = uniformFieldWrapper(formFields.FormText, field => ({
  maxlength: getAttr(field, 'maxlength'),
}));

const Textarea = uniformFieldWrapper(formFields.FormTextarea, field => ({
  rows: getAttr(field, 'rows'),
}));

const SelectOne = uniformFieldWrapper(formFields.FormSelect, field => ({
  options:
    field.attrs && field.attrs.choices
      ? field.attrs.choices.map(({ key, label }) => ({ id: key, label }))
      : [],
}));

const DateField = uniformFieldWrapper(
  createUniformInputWrapper(
    mapVModel(
      iso => (iso ? { iso } : null),
      obj => (obj ? obj.iso : null),
      DateSelector
    )
  ),
  field => ({
    yearsBeforeMidrange: field.meta && field.meta.years_before_midrange,
    yearsAfterMidrange: field.meta && field.meta.years_after_midrange,
  })
);

const NumberField = uniformFieldWrapper(formFields.FormNumber, field => ({
  min: getAttr(field, 'min'),
}));

const CurrencyField = uniformFieldWrapper(formFields.FormCurrency, field => {
  const currency = getAttr(field, 'currency');
  const label = field.label;

  return {
    min: getAttr(field, 'min'),
    currency,
    ariaLabel:
      label && currency
        ? getString('input_label_in_currency', 'totara_core', {
            label,
            currency,
          })
        : null,
  };
});
CurrencyField.langStrings = {
  totara_core: ['input_label_in_currency'],
};

const EditorField = uniformFieldWrapper(
  createUniformInputWrapper(Editor),
  field => ({
    usageIdentifier: field.meta && field.meta.usage_identifier,
    variant: field.meta && field.meta.variant,
    extraExtensions: field.meta && field.meta.extraExtensions,
  })
);

/** @type {Object<string, import('../defs').FieldDef>} */
const fields = {
  // non editable
  label: {
    supports: {
      edit: false,
      disable: false,
    },
    rowComponent: LabelRow,
  },
  currency_total: {
    supports: {
      edit: false,
    },
    fieldComponent: CurrencyTotal,
    calculatedValue(value, field, { values }) {
      return calculateTotal(getAttr(field, 'sources'), key => values[key]);
    },
    displayText(value, field, context) {
      const total = fields['currency_total'].calculatedValue(
        value,
        field,
        context
      );
      const currency = getAttr(field, 'currency');
      return new CurrencyFormat(currency).format(total);
    },
    displayClassModifiers() {
      return { bold: true };
    },
  },

  // generic fields
  text: {
    fieldComponent: Text,
  },
  select_one: {
    fieldComponent: SelectOne,
    displayText(value, field) {
      if (value == null) {
        return '';
      }
      const choices = getAttr(field, 'choices');
      const result =
        Array.isArray(choices) && choices.find(choice => choice.key === value);
      return result ? result.label : '';
    },
    prepareForEdit(value, field) {
      const choices = getAttr(field, 'choices');
      const allChoices = choices ? choices.slice() : [];
      if (field.rules) {
        field.rules.forEach(rule => {
          if (rule.set && rule.set.attrs && rule.set.attrs.choices) {
            allChoices.push(...rule.set.attrs.choices);
          }
        });
      }
      return Array.isArray(allChoices) &&
        allChoices.find(choice => choice.key === value)
        ? value
        : null;
    },
  },
  date: {
    fieldComponent: DateField,
    displayText(value, field) {
      if (!value) {
        return value;
      }
      const date = parseISO(value);
      if (isNaN(date)) return value;
      if (getAttr(field, 'format')) {
        return formatDate(date, getAttr(field, 'format'));
      } else {
        return new Intl.DateTimeFormat(config.locale.language).format(date);
      }
    },
  },
  email: {
    fieldComponent: uniformFieldWrapper(formFields.FormEmail),
  },
  number: {
    fieldComponent: NumberField,
  },
  phone: {
    fieldComponent: uniformFieldWrapper(formFields.FormTel),
  },
  textarea: {
    fieldComponent: Textarea,
  },
  url: {
    fieldComponent: uniformFieldWrapper(formFields.FormUrl),
  },
  editor: {
    fieldComponent: EditorField,
    viewFieldComponent: EditorView,
    printFieldComponent: EditorView,
    prepareForEdit(value, field) {
      if (!value || !value.editor) {
        return new EditorContent({
          fileItemId: field.meta && field.meta.fileItemId,
        });
      }
      try {
        const data = JSON.parse(value.editor);
        return new EditorContent({
          format: data.format,
          content: data.content,
          fileItemId: field.meta && field.meta.fileItemId,
        });
      } catch (e) {
        return new EditorContent({
          fileItemId: field.meta && field.meta.fileItemId,
        });
      }
    },
    prepareForSave(value) {
      if (!value) {
        return null;
      }
      return JSON.stringify({
        draft_id: value.fileItemId,
        format: value.format,
        content: value.getContent(),
      });
    },
  },

  // specialised fields
  signature: {
    fieldComponent: Text,
  },
  address: {
    fieldComponent: Textarea,
  },
  currency: {
    fieldComponent: CurrencyField,
    displayText(value, field) {
      value = Number(value);
      if (isNaN(value)) {
        return null;
      }
      const currency = getAttr(field, 'currency');
      return new CurrencyFormat(currency).format(value);
    },
  },
  fullname: {
    fieldComponent: Text,
  },
  usssn: {
    fieldComponent: Text,
  },
};

export default fields;
