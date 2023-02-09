<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2021 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Simon Chester <simon.chester@totaralearning.com>
  @module mod_approval
-->

<script>
import InputCurrency from 'tui/components/form/InputCurrency';
import { calculateTotal } from '../../../js/internal/schema_form/fields/total';
import { config } from 'tui/config';

export default {
  inject: ['reformScope', 'reformFieldContext'],

  components: { InputCurrency },

  props: {
    field: Object,
  },

  data() {
    return {
      total: 0, // sum of an empty set is 0
    };
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

    ariaLabel() {
      return (
        (this.field.label &&
          this.currency &&
          this.$str('input_label_in_currency', 'totara_core', {
            label: this.field.label,
            currency: this.currency,
          })) ||
        null
      );
    },

    currency() {
      return this.field.attrs && this.field.attrs.currency;
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

    fieldSources() {
      return this.field && this.field.attrs ? this.field.attrs.sources : [];
    },
  },

  watch: {
    fieldSources: {
      handler(sources, old) {
        if (old) {
          old.forEach(source => {
            this.reformScope.unregister(
              'changeListener',
              source,
              this.$_update
            );
          });
        }
        if (sources) {
          sources.forEach(source => {
            this.reformScope.register('changeListener', source, this.$_update);
          });
        }
      },
      immediate: true,
    },
  },

  mounted() {
    this.$_update();
  },

  beforeDestroy() {
    if (this.reformScope && this.fieldSources) {
      this.fieldSources.forEach(source => {
        this.reformScope.unregister('changeListener', source, this.$_update);
      });
    }
  },

  methods: {
    $_update() {
      this.total = calculateTotal(this.fieldSources, this.reformScope.getValue);
    },

    $_formatNumber(value) {
      const currency = this.field.attrs && this.field.attrs.currency;
      if (currency) {
        try {
          const resolved = new Intl.NumberFormat(config.locale.language, {
            style: 'currency',
            currency,
          }).resolvedOptions();

          // currency input (which is a number input underneath) only supports
          // "." as decimal separator, and does not support thousands separators
          return value.toFixed(resolved.maximumFractionDigits);
        } catch (e) {
          // fall through
        }
      }

      return value != null ? value.toString() : null;
    },
  },

  render(h) {
    return h(InputCurrency, {
      attrs: {
        ariaDescribedby: this.ariaDescribedby,
        ariaLabel: this.ariaLabel,
        id: this.id,
        labelId: this.labelId,
        readonly: true,
        value: this.$_formatNumber(this.total),
        currency: this.currency,
        inputType: 'text',
        disabled: this.field.disabled,
      },
    });
  },
};
</script>

<lang-strings>
{
  "totara_core": [
    "input_label_in_currency"
  ]
}
</lang-strings>
