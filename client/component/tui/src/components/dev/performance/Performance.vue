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

  @author Qingyang Liu <qingyang.liu@totaralearning.com>
  @module tui
-->

<template>
  <div class="container tui-performance">
    <ul v-if="list.length > 0" class="tui-performance__summary">
      <li>Total Process time: {{ totalProcessingTime }} secs</li>
      <li>Max RAM: {{ maxRam }}</li>
      <li>Max peak RAM: {{ maxPeakRam }}</li>
      <li>
        Total DB Time(secs)/Reads/Writes : {{ totalDbTime }} /
        {{ totalDbReads }} / {{ totalDbWrites }}
      </li>
    </ul>
    <div class="tui-performance__container">
      <div
        v-for="(item, index) in list"
        :key="index"
        class="tui-performance__wrapper"
      >
        <div>
          {{ item.operationName }}
        </div>
        <div>Process time: {{ item.realtime }} secs</div>
        <div>DB time: {{ item.db.time }} secs</div>
        <div>Reads: {{ item.db.reads }}</div>
        <div>Writes: {{ item.db.writes }}</div>
        <div>Total RAM: {{ item.ram }}</div>
        <div>RAM peak: {{ item.ramPeak }}</div>
      </div>
    </div>
  </div>
</template>

<script>
let gb = 'GB',
  mb = 'MB',
  kb = 'KB',
  b = 'Bytes';
let ramValueTerm = '';
let currentMaxRam = '';
let currentMaxPeakRam = '';

export default {
  data() {
    return {
      list: [],
      maxRam: '',
      maxPeakRam: '',
      totalProcessingTime: 0,
      totalDbTime: 0,
      totalDbReads: 0,
      totalDbWrites: 0,
    };
  },

  computed: {
    getProcessTime() {
      return this.totalProcessingTime.toFixed(1);
    },
  },

  mounted() {
    window.addEventListener('graphql-event', this.getPerformanceData);
  },

  unmounted() {
    window.removeEventListener('graphql-event', this.getPerformanceData);
  },

  methods: {
    getPerformanceData(event) {
      let db = Object.assign({}, event.detail.db);
      db.time = db.time.toFixed(3);

      this.list.push({
        operationName: event.detail.operationName,
        realtime: event.detail.realtime.toFixed(3),
        db: db,
        ram: this.calculateRam(event.detail.ram, 'RAM'),
        ramPeak: this.calculateRam(event.detail.ramPeak, 'PEAK_RAM'),
      });

      // calculate Db stats
      this.calculateDbStats(event.detail.db);

      // sum up all the real time processing
      this.totalProcessingTime = (
        parseFloat(this.totalProcessingTime) + parseFloat(event.detail.realtime)
      ).toFixed(3);
    },

    calculateDbStats(db) {
      this.totalDbTime = (
        parseFloat(this.totalDbTime) + parseFloat(db.time)
      ).toFixed(3);

      this.totalDbReads += db.reads;
      this.totalDbWrites += db.writes;
    },

    calculateRam(size, ramType) {
      if (size === -1) {
        this.totalRam = 'UNAVAILABLE';
      }

      if (size >= 1073741824) {
        size = Math.round((size / 1073741824) * 10) / 10;
        ramValueTerm = gb;
      } else if (size >= 1048576) {
        size = Math.round((size / 1048576) * 10) / 10;
        ramValueTerm = mb;
      } else if (size >= 1024) {
        size = Math.round((size / 1024) * 10) / 10;
        ramValueTerm = kb;
      } else {
        size = parseInt(size);
        ramValueTerm = b;
      }

      // Get the maximum ram usage
      if (ramType === 'RAM') {
        if (size > currentMaxRam) {
          currentMaxRam = size;
          this.maxRam = `${currentMaxRam}${ramValueTerm}`;
        }
      }

      // Get the maximum ram peak usage
      if (ramType === 'PEAK_RAM') {
        if (size > currentMaxPeakRam) {
          currentMaxPeakRam = size;
          this.maxPeakRam = `${currentMaxPeakRam}${ramValueTerm}`;
        }
      }

      return `${size}${ramValueTerm}`;
    },
  },
};
</script>

<style lang="scss">
.tui-performance {
  &__summary {
    list-style: none;
  }

  &__container {
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
    justify-content: center;
  }

  &__wrapper {
    margin-right: var(--gap-4);
    margin-bottom: var(--gap-4);
    border: 1px solid var(--color-neutral-5);
    border-radius: 3px;

    > * {
      padding-right: var(--gap-2);
      padding-left: var(--gap-2);

      &:first-child {
        font-weight: bold;
      }

      &:not(:first-child) {
        background: var(--color-chart-transparent-3);
      }
    }
  }
}
</style>
