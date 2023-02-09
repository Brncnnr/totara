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

  @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
  @module totara_competency
-->

<template>
  <div>
    <p class="sr-only">
      {{ $str('filter_viewing_by_assignment', 'totara_competency') }}
    </p>
    <SelectFilter
      v-if="displayFilters"
      :show-label="true"
      :label="$str('viewing_by_assignment', 'totara_competency')"
      :options="
        filterOptions.map(option => ({ id: option.id, label: option.name }))
      "
      :disabled="disable"
      :value="value && value.key"
      @input="filterUpdated"
    />
  </div>
</template>

<script>
import SelectFilter from 'tui/components/filters/SelectFilter';

export default {
  components: {
    SelectFilter,
  },

  props: {
    filters: {
      type: Array,
      required: true,
    },
    value: {
      type: Object,
      required: true,
    },
    disable: {
      type: Boolean,
      default: false,
    },
  },

  computed: {
    displayFilters() {
      return !(this.filters.length === 1 && this.filters[0].status === 1);
    },

    filterOptions() {
      const options = [];

      this.filters.forEach(group => {
        options.push({
          name: group.name,
          id: group.id,
          value: group.value,
        });

        group.filters.forEach(x =>
          options.push(
            Object.assign({}, x, {
              name: '\xa0\xa0\xa0' + x.name,
            })
          )
        );
      });

      return options;
    },
  },

  methods: {
    toggle() {
      this.open = !this.open;
    },

    filterUpdated(value) {
      const item = this.filterOptions.find(x => x.id == value);
      this.$emit('input', item && item.value);
    },
  },
};
</script>

<lang-strings>
{
  "totara_competency": [
    "filter_viewing_by_assignment",
    "viewing_by_assignment"
    ]
}
</lang-strings>
