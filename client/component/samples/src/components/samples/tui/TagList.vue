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

  @author Alvin Smith <alvin.smith@totaralearning.com>
  @module samples
-->

<template>
  <div>
    <SamplesExample>
      <TagList
        v-bind="bindProps"
        :filter="searchItem"
        class="tui-tagListWrapper"
        :tags="tags"
        :items="items"
        @select="select"
        @remove="remove"
        @filter="filter"
        @scrollbottom="scrollBottom"
      >
        <template v-if="withAvatar" v-slot:tag="{ tag }">
          <div class="tui-customTag">
            <Avatar :src="tag.url" alt="" size="xsmall" />
            <span>{{ tag.text }}</span>
          </div>
        </template>
        <template v-slot:item="{ item }">
          <div>
            <Avatar :src="item.url" alt="" size="medium" />
            <p>{{ item.name }}</p>
          </div>
        </template>
      </TagList>
      <br />
      <Button text="Change Style" @click="withAvatar = !withAvatar" />
    </SamplesExample>
    <SamplesPropCtl>
      <Uniform :initial-values="values" @change="v => (values = v)">
        <FormRow label="Virtual scroll">
          <FormRadioGroup name="virtualScroll" :horizontal="true">
            <Radio :value="true">True</Radio>
            <Radio :value="false">False</Radio>
          </FormRadioGroup>
        </FormRow>
      </Uniform>
    </SamplesPropCtl>
  </div>
</template>

<script>
import Avatar from 'tui/components/avatar/Avatar';
import Button from 'tui/components/buttons/Button';
import TagList from 'tui/components/tag/TagList';
import { createSquareImage } from '../../../../../tui/src/js/internal/placeholder_generator.js';
import SamplesPropCtl from 'samples/components/sample_parts/misc/SamplesPropCtl';
import { Uniform, FormRow, FormRadioGroup } from 'tui/components/uniform';
import Radio from 'tui/components/form/Radio';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';
import { uniqueId } from 'tui/util';

let counter = 0;

const getPageData = count => {
  const dataItems = [];
  for (let i = 0; i < count; i++) {
    counter++;
    dataItems.push({
      id: uniqueId(),
      url: createSquareImage('#f66'),
      name: 'Mike',
    });
  }
  return dataItems;
};

const pageSize = 10;

export default {
  components: {
    Avatar,
    Button,
    TagList,
    SamplesPropCtl,
    Uniform,
    FormRow,
    FormRadioGroup,
    Radio,
    SamplesExample,
  },
  data() {
    return {
      tags: [],
      fetchData: [],
      searchItem: '',
      withAvatar: false,
      counter: counter,
      values: {
        virtualScroll: false,
      },
      virtualScrollOpt: {
        dataKey: 'id',
        ariaLabel: 'aria-label',
        isLoading: false,
      },
    };
  },
  computed: {
    bindProps() {
      if (this.values.virtualScroll) {
        return {
          virtualScrollOptions: this.virtualScrollOpt,
        };
      }
      return {};
    },

    items() {
      const items = this.fetchData.filter(
        item => !this.tags.some(tag => item.id === tag.id)
      );
      if (this.searchItem) {
        return items.filter(item =>
          item.name.toUpperCase().includes(this.searchItem.toUpperCase())
        );
      }
      return items;
    },
  },
  mounted() {
    this.fetchData = [
      {
        id: 1001,
        url: createSquareImage('#f66'),
        name: 'Mike',
      },
      {
        id: 1002,
        url: createSquareImage('#fc6'),
        name: 'John',
      },
      {
        id: 1003,
        url: createSquareImage('#ff6'),
        name: 'Eric',
      },
      {
        id: 1004,
        url: createSquareImage('#3f9'),
        name: 'George',
      },
      {
        id: 1005,
        url: createSquareImage('#39f'),
        name: 'Mike',
      },
      {
        id: 1006,
        url: createSquareImage('#c6f'),
        name: 'John',
      },
      {
        id: 1007,
        url: createSquareImage('#f66'),
        name: 'Eric',
      },
      {
        id: 1008,
        url: createSquareImage('#fc6'),
        name: 'George',
      },
      {
        id: 1009,
        url: createSquareImage('#ff6'),
        name: 'Mike',
      },
      {
        id: 10010,
        url: createSquareImage('#3f9'),
        name: 'John',
      },
      {
        id: 10011,
        url: createSquareImage('#39f'),
        name: 'Eric',
      },
      {
        id: 10012,
        url: createSquareImage('#c6f'),
        name: 'George',
      },
    ];
  },
  methods: {
    select(item) {
      const { name, id, url, alt } = item;
      this.tags.push({ text: name, id, url, alt });
      this.searchItem = '';
    },
    remove(tag) {
      this.tags = this.tags.filter(t => t !== tag);
    },
    filter(value) {
      this.searchItem = value;
    },
    scrollBottom() {
      if (this.virtualScrollOpt.isLoading) {
        return;
      }

      this.virtualScrollOpt.isLoading = true;

      setTimeout(() => {
        this.virtualScrollOpt.isLoading = false;
        this.fetchData = this.fetchData.concat(getPageData(pageSize));
      }, 500);
    },
  },
};
</script>

<style lang="scss">
.tui-tagListWrapper {
  width: 700px;
}
.tui-customTag {
  padding: 0.4rem;
  border: 1px solid var(--btn-text-color);
  border-radius: 6px;
}
</style>
