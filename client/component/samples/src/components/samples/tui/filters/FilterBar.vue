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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module tui
-->

<template>
  <div>
    <SamplesExample>
      <FilterBar
        :title="'Filter results'"
        :has-top-bar="filterHasTopBar"
        :has-bottom-bar="filterHasBottomBar"
        :show-reset="!filtersInactive"
        @reset="resetFilters"
      >
        <template v-slot:filters-left="{ stacked }">
          <SelectFilter
            v-model="selection.category"
            label="Within category"
            :show-label="true"
            :options="categories"
            :stacked="stacked"
          />

          <SelectFilter
            v-model="selection.colours"
            label="Colour"
            :show-label="true"
            :options="colourOptions"
            :stacked="stacked"
          />
        </template>

        <template v-slot:filters-right="{ stacked }">
          <SearchFilter
            v-model="selection.search"
            label="Filter items by search"
            :show-label="false"
            :placeholder="'Search'"
            :stacked="stacked"
            :debounce-input="false"
          />
        </template>
      </FilterBar>

      <div>
        <h2>Filter values</h2>
        {{ selection }}
      </div>
    </SamplesExample>

    <SamplesPropCtl>
      <FormRow v-slot="{ labelId }" label="hasTopBar">
        <RadioGroup v-model="filterHasTopBar" :horizontal="true">
          <Radio :value="true">true</Radio>
          <Radio :value="false">false</Radio>
        </RadioGroup>
      </FormRow>

      <FormRow v-slot="{ labelId }" label="hasBottomBar">
        <RadioGroup v-model="filterHasBottomBar" :horizontal="true">
          <Radio :value="true">true</Radio>
          <Radio :value="false">false</Radio>
        </RadioGroup>
      </FormRow>
    </SamplesPropCtl>

    <SamplesCode>
      <template v-slot:template>{{ codeTemplate }}</template>
      <template v-slot:script>{{ codeScript }}</template>
    </SamplesCode>
  </div>
</template>

<script>
import SamplesCode from 'samples/components/sample_parts/misc/SamplesCode';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';
import SamplesPropCtl from 'samples/components/sample_parts/misc/SamplesPropCtl';
import FormRow from 'tui/components/form/FormRow';
import Radio from 'tui/components/form/Radio';
import RadioGroup from 'tui/components/form/RadioGroup';

// Components
import FilterBar from 'tui/components/filters/FilterBar';
import SearchFilter from 'tui/components/filters/SearchFilter';
import SelectFilter from 'tui/components/filters/SelectFilter';

export default {
  components: {
    FormRow,
    Radio,
    RadioGroup,
    SamplesCode,
    SamplesPropCtl,
    SamplesExample,
    FilterBar,
    SearchFilter,
    SelectFilter,
  },

  data() {
    return {
      colourOptions: [
        {
          id: '',
          label: 'Please select',
        },
        {
          id: 'orange',
          label: 'Orange',
        },
        {
          id: 'darkRed',
          label: 'Dark red',
        },
      ],
      categories: [
        {
          id: '',
          label: 'No category selected',
        },
        {
          id: 'films',
          label: 'films',
        },
        {
          id: 'comics',
          label: 'Comic books',
        },
      ],
      filterHasTopBar: true,
      filterHasBottomBar: true,
      selection: {
        category: '',
        colours: '',
        search: '',
      },
      codeTemplate: `<FilterBar v-model="selection" :title="'Filter results'">

  <!-- Left aligned content -->
  <template v-slot:filters-left="{ stacked }">
    <SelectFilter
      v-model="selection.category"
      label="Within category"
      :show-label="true"
      :options="categories"
      :stacked="stacked"
    />

    <SelectFilter
      v-model="selection.colours"
      label="Colour"
      :show-label="true"
      :options="colourOptions"
      :stacked="stacked"
    />
  </template>

  <!-- Right aligned content -->
  <template v-slot:filters-right="{ stacked }">
    <SearchFilter
      v-model="selection.search"
      label="Filter items by search"
      :show-label="false"
      :placeholder="'Search'"
      :stacked="stacked"
    />
  </template>

</FilterBar>`,
      codeScript: `import FilterBar from 'tui/components/filters/FilterBar';
import SearchFilter from 'tui/components/filters/SearchFilter';
import SelectFilter from 'tui/components/filters/SelectFilter';

export default {
  components: {
    FilterBar,
    SearchFilter,
    SelectFilter,
  },

  data() {
    return {
      colourOptions: [
        {
          id: '',
          label: 'Please select',
        },
        {
          id: 'orange',
          label: 'Orange',
        },
        {
          id: 'darkRed',
          label: 'Dark red',
        },
      ],
      categories: [
        {
          id: '',
          label: 'No category selected',
        },
        {
          id: 'films',
          label: 'films',
        },
        {
          id: 'comics',
          label: 'Comic books',
        },
      ],
      selection: {
        category: '',
        colours: '',
        search: '',
      },
    }
  }
}`,
    };
  },

  computed: {
    filtersInactive() {
      return Object.keys(this.selection).every(
        key => this.selection[key] === ''
      );
    },
  },

  methods: {
    resetFilters() {
      this.selection = {
        category: '',
        colours: '',
        search: '',
      };
    },
  },
};
</script>
