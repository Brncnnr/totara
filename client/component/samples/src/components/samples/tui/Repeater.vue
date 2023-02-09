<template>
  <div>
    <Repeater
      :align="align"
      :row-align="rowAlign"
      :direction="direction"
      :rows="rows"
      :min-rows="minRows"
      :max-rows="maxRows"
      :disabled="disabled"
      :delete-icon="deleteIcon"
      :allow-deleting-first-items="allowDeletingFisrtItems"
      @add="addNewSection"
      @remove="deleteSection"
    >
      <template v-slot="{ row }">
        <div>
          <InputText
            v-model="row.value"
            aria-label="aria-label"
            :disabled="row.disabled"
            :placeholder="row.label"
          />
          <RadioGroup v-model="row.battleship" :disabled="disabled">
            <Radio value="hms-victory">HMS Victory</Radio>
            <Radio value="bismarck">Bismarck</Radio>
            <Radio value="uss-enterprise">USS Enterprise</Radio>
            <Radio value="yamato">Yamato</Radio>
          </RadioGroup>
        </div>
      </template>
      <template v-if="customAddButton" v-slot:add>
        <ButtonIcon
          :aria-label="$str('add', 'core')"
          :styleclass="{ small: true }"
          :disabled="disabled"
          :text="$str('add', 'core')"
          @click="addNewSection"
        >
          <AddIcon />
        </ButtonIcon>
      </template>
    </Repeater>
    <Separator :thick="true" />
    <Button text="Change Style" @click="changeStyle" />
  </div>
</template>

<script>
import AddIcon from 'tui/components/icons/Add';
import Button from 'tui/components/buttons/Button';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import InputText from 'tui/components/form/InputText';
import Radio from 'tui/components/form/Radio';
import RadioGroup from 'tui/components/form/RadioGroup';
import Repeater from 'tui/components/form/Repeater';
import Separator from 'tui/components/decor/Separator';

export default {
  components: {
    AddIcon,
    Button,
    ButtonIcon,
    InputText,
    Radio,
    RadioGroup,
    Repeater,
    Separator,
  },

  data() {
    return {
      disabled: false,
      rows: [
        {
          value: 'first value',
          battleship: 'hms-victory',
          battleshipLabel: 'HMS Victory',
          disabled: false,
          label: 'first label',
        },
        {
          value: '',
          battleship: 'bismarck',
          battleshipLabel: 'Bismarck',
          disabled: false,
          label: 'second label',
        },
        {
          value: 'third value',
          battleship: 'uss-enterprise',
          battleshipLabel: 'USS Enterprise',
          disabled: false,
          label: 'third label',
        },
      ],
      minRows: 1,
      maxRows: 5,
      customAddButton: true,
      deleteIcon: true,
      allowDeletingFisrtItems: true,
      align: 'start',
      rowAlign: 'center',
      direction: 'vertical',
    };
  },

  watch: {
    rows: {
      deep: true,
      handler(val) {
        this.rows = val;
        console.log(this.rows);
      },
    },
  },

  methods: {
    addNewSection() {
      this.rows.push(
        Object.assign(
          {},
          {
            value: '',
            disabled: false,
            label: 'the placeholder for new row',
            battleship: '',
            battleshipLabel: 'the placeholder for battleshipLabel',
          }
        )
      );
    },
    deleteSection(row) {
      this.rows = this.rows.filter(v => v !== row);
    },
    changeStyle() {
      const align = ['start', 'center', 'end'];
      const direction = ['horizontal', 'vertical'];
      const randomIndex = length => Math.floor(Math.random() * length);
      const filterItems = (arr, item) => arr.filter(e => e !== item);

      this.customAddButton = !this.customAddButton;
      this.deleteIcon = !this.deleteIcon;
      this.allowDeletingFisrtItems = !this.allowDeletingFisrtItems;
      this.direction = filterItems(direction, this.direction)[randomIndex(2)];
      this.align = filterItems(align, this.align)[randomIndex(3)];
      this.rowAlign = filterItems(align, this.rowAlign)[randomIndex(3)];
    },
  },
};
</script>

<lang-strings>
{
  "core": [
    "add"
  ]
}
</lang-strings>
