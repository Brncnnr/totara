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

  @author Dave Wallace <dave.wallace@totaralearning.com>
  @module samples
-->

<template>
  <div class="tui-grid-example">
    <SamplesExample>
      <Grid
        :direction="gridDirection"
        :use-horizontal-gap="useHorizontalGap"
        :use-vertical-gap="useVerticalGap"
        :max-units="maxUnits"
        :stack-at="stackAt"
      >
        <GridItem
          v-for="(item, index) in gridItems"
          :key="index"
          :units="gridItems[index].units"
          :grows="gridItems[index].grows"
          :shrinks="gridItems[index].shrinks"
          :overflows="gridItems[index].overflows"
          :hyphens="gridItems[index].hyphens"
        >
          <Textarea
            :ref="'textarea-' + index"
            :placeholder="'GridItem content'"
            @input="handleTextareaInput(index, $event)"
          />

          <Popover :triggers="['click']">
            <template v-slot:trigger>
              <ButtonIcon
                :aria-label="'GridItem options'"
                :caret="false"
                :styleclass="{
                  xsmall: true,
                  transparent: true,
                  transparentNoPadding: true,
                }"
              >
                <EditIcon />
              </ButtonIcon>
            </template>

            <p>Properties for this GridItem</p>
            <Form>
              <FormRow label="Units">
                <InputNumber
                  :placeholder="'(Integer)'"
                  :min="1"
                  :max="parseInt(maxUnits)"
                  @input="handleChange(index, 'units', parseInt($event))"
                />
              </FormRow>
              <FormRow label="Grows">
                <RadioGroup
                  v-model="gridItems[index].grows"
                  :horizontal="true"
                  @input="handleChange(index, 'grows', $event)"
                >
                  <Radio :value="true">True</Radio>
                  <Radio :value="false">False (default)</Radio>
                </RadioGroup>
              </FormRow>
              <FormRow label="Shrinks">
                <RadioGroup
                  v-model="gridItems[index].shrinks"
                  :horizontal="true"
                  @input="handleChange(index, 'shrinks', $event)"
                >
                  <Radio :value="false">False</Radio>
                  <Radio :value="true">True (default)</Radio>
                </RadioGroup>
              </FormRow>
              <FormRow label="Overflows">
                <RadioGroup
                  v-model="gridItems[index].overflows"
                  :horizontal="true"
                  @input="handleChange(index, 'overflows', $event)"
                >
                  <Radio :value="true">True</Radio>
                  <Radio :value="false">False (default)</Radio>
                </RadioGroup>
              </FormRow>
              <FormRow label="Hyphens">
                <RadioGroup
                  v-model="gridItems[index].hyphens"
                  :horizontal="true"
                  @input="handleChange(index, 'hyphens', $event)"
                >
                  <Radio :value="false">False</Radio>
                  <Radio :value="true">True (default)</Radio>
                </RadioGroup>
              </FormRow>
              <FormRow label="Order">
                <em>Supported, but refer to implementation</em>
              </FormRow>
              <FormRow label="HTML tag">
                <em>Supported, but refer to implementation</em>
              </FormRow>
            </Form>
          </Popover>
        </GridItem>
      </Grid>

      <br />
      <p>
        <Button text="Add GridItem" @click="addGridItem" />
        <Button text="Remove GridItem" @click="removeGridItem" />
      </p>
    </SamplesExample>

    <SamplesPropCtl>
      <FormRow label="Max GridItem units before wrapping">
        <RadioGroup v-model="maxUnits" :horizontal="true">
          <Radio :value="'12'">12 (default)</Radio>
          <Radio :value="'16'">16</Radio>
        </RadioGroup>
      </FormRow>
      <FormRow label="Direction">
        <RadioGroup v-model="gridDirection" :horizontal="true">
          <Radio :value="'horizontal'">Horizontal (default)</Radio>
          <Radio :value="'vertical'">Vertical</Radio>
        </RadioGroup>
      </FormRow>
      <FormRow label="Use a horizontal gap">
        <RadioGroup v-model="useHorizontalGap" :horizontal="true">
          <Radio :value="true">True (default)</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>
      <FormRow label="Use a vertical gap">
        <RadioGroup v-model="useVerticalGap" :horizontal="true">
          <Radio :value="true">True (default)</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>
      <FormRow label="Become stacked at width (px)">
        <RadioGroup v-model="stackAt" :horizontal="true">
          <Radio :value="0">0 (default)</Radio>
          <Radio :value="400">400</Radio>
        </RadioGroup>
      </FormRow>
      <FormRow label="HTML tag for Grid container">
        <em>Supported, but refer to implementation</em>
      </FormRow>
    </SamplesPropCtl>
  </div>
</template>

<script>
import Vue from 'vue';
import Grid from 'tui/components/grid/Grid';
import GridItem from 'tui/components/grid/GridItem';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';
import SamplesPropCtl from 'samples/components/sample_parts/misc/SamplesPropCtl';
import Form from 'tui/components/form/Form';
import FormRow from 'tui/components/form/FormRow';
import InputNumber from 'tui/components/form/InputNumber';
import Textarea from 'tui/components/form/Textarea';
import Radio from 'tui/components/form/Radio';
import RadioGroup from 'tui/components/form/RadioGroup';
import Button from 'tui/components/buttons/Button';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import EditIcon from 'tui/components/icons/Edit';
import Popover from 'tui/components/popover/Popover';

export default {
  components: {
    Grid,
    GridItem,
    SamplesExample,
    SamplesPropCtl,
    Form,
    FormRow,
    Radio,
    RadioGroup,
    InputNumber,
    Textarea,
    Button,
    ButtonIcon,
    EditIcon,
    Popover,
  },
  data() {
    return {
      /**
       * Main Grid
       **/
      gridDirection: 'horizontal',
      useHorizontalGap: true,
      useVerticalGap: true,
      maxUnits: '12',
      stackAt: 0,
      /**
       * GridItems
       **/
      stubGridItem: {
        units: 4,
        grows: false,
        shrinks: true,
        overflows: false,
        hyphens: true,
      },
      // Modified dynamically by add/remove buttons and also individual GridItem
      // prop settings popover component
      gridItems: [
        {
          units: 4,
          grows: false,
          shrinks: true,
          overflows: false,
          hyphens: true,
        },
        {
          units: 4,
          grows: false,
          shrinks: true,
          overflows: false,
          hyphens: true,
        },
        {
          units: 4,
          grows: false,
          shrinks: true,
          overflows: false,
          hyphens: true,
        },
      ],
    };
  },
  methods: {
    /**
     * Adjust the size of the GridItem Textarea component so that we can show
     * the effects of `overflows` and `hyphens` props
     **/
    handleTextareaInput: function(index) {
      // need to use `$refs[*][0].$el` instead of just `$refs[*][0]` as the ref
      // returned from the latter refers to the Textarea Vue component, not the
      // actual DOM element
      let ref = this.$refs['textarea-' + index][0].$el;
      ref.style.height = '1px';
      ref.style.height = ref.scrollHeight + 'px';
    },

    /**
     * Update a GridItem's :units property reactively
     **/
    handleChange: function(index, prop, eventValue) {
      if (eventValue === null || typeof eventValue === 'undefined') {
        return;
      }

      // re-create gridItems Array index to invoke reactivity
      let newObj = this.createNewGridItem(this.gridItems[index]);
      newObj[prop] = eventValue;
      Vue.set(this.gridItems, index, newObj);
    },

    /**
     * Adds a GridItem to the example Grid
     **/
    addGridItem: function() {
      this.gridItems.push(this.createNewGridItem());
    },

    /**
     * Removes a GridItem to the example Grid
     **/
    removeGridItem: function() {
      if (!this.gridItems.length) {
        return;
      }
      this.gridItems.pop();
    },

    /**
     * Create a new GridItem object with stub values
     **/
    createNewGridItem: function(oldItem) {
      let obj = {};
      Object.assign(obj, oldItem || this.stubGridItem);
      return obj;
    },
  },
};
</script>
<style lang="scss">
.tui-grid-example .tui-grid-item {
  position: relative; /* parental control of popover trigger position */
  background-color: var(--color-neutral-4);

  > div > .tui-iconBtn {
    position: absolute;
    top: 6px;
    right: 15px;
    z-index: 1;
  }

  textarea {
    overflow: hidden;
  }

  .tui-popoverFrame {
    width: 500px;
    max-width: 500px;
  }
}
</style>
