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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @module tui
 */

import { mount } from '@vue/test-utils';
import PercentageDoughnut from 'tui_charts/components/PercentageDoughnut';
import ChartJs from 'tui_charts/components/ChartJs';
import { axe } from 'jest-axe';

let wrapper;

// We are mocking theme to override css variables
jest.mock('tui/theme', () => {
  return {
    getVar(name) {
      let vars = {
        'color-chart-background-1': '#3869B1',
        'font-size-chart-doughnut-label': '24',
        'font-size-chart-doughnut-percentage-label': '40',
        'color-chart-doughnut-label': '#000',
        'color-neutral-4': '#e6e4e4',
      };
      return vars[name];
    },
  };
});

describe('PercentageDoughnut', () => {
  beforeAll(() => {
    wrapper = mount(PercentageDoughnut, {
      propsData: {
        percentage: 75,
        header: 'I am a big percentage doughnut',
      },
    });
  });

  it('renders correctly', () => {
    expect(wrapper.element).toMatchSnapshot();
  });

  it('initializes correct defaults for charts component', () => {
    let data = {
      datasets: [
        {
          data: [75, 25],
          borderWidth: 2,
          borderColor: ['#3869B1', '#e6e4e4'],
          backgroundColor: ['#3869B1', '#e6e4e4'],
          hoverBackgroundColor: ['#3869B1', '#e6e4e4'],
          hoverBorderColor: ['#3869B1', '#e6e4e4'],
        },
      ],
    };

    let options = {
      cutoutPercentage: 85,
      tooltips: {
        enabled: false,
      },
      plugins: {
        doughnutlabel: {
          labels: [
            {
              text: '75%',
              font: {
                size: 40,
                weight: 'bold',
              },
              color: '#000',
            },
          ],
        },
      },
      legend: {
        display: false,
      },
    };

    expect(wrapper.find(ChartJs).props('options')).toMatchObject(options);
    expect(wrapper.find(ChartJs).props('data')).toMatchObject(data);
    expect(wrapper.find(ChartJs).props('header')).toMatch(
      'I am a big percentage doughnut'
    );
    expect(wrapper.find(ChartJs).props('ariaLabel')).toMatch('75%');
  });

  it('passes correct properties to the chart component', () => {
    let doughnut = mount(PercentageDoughnut, {
      propsData: {
        percentage: 57,
        header: 'I am a big percentage doughnut!!',
        square: true,
        color: 'yellow',
        backgroundColor: '#123456',
        labelColor: '#654321',
        label: 'I am a label',
        labelFontSize: 16,
        percentageFontSize: 32,
        cutout: 80,
      },
    });

    let data = {
      datasets: [
        {
          data: [57, 43],
          borderWidth: 2,
          borderColor: ['yellow', '#123456'],
          backgroundColor: ['yellow', '#123456'],
          hoverBackgroundColor: ['yellow', '#123456'],
          hoverBorderColor: ['yellow', '#123456'],
        },
      ],
    };

    let options = {
      cutoutPercentage: 80,
      tooltips: {
        enabled: false,
      },
      plugins: {
        doughnutlabel: {
          labels: [
            {
              text: '57%',
              font: {
                size: 32,
                weight: 'bold',
              },
              color: '#654321',
            },
            {
              text: 'I am a label',
              font: {
                size: 16,
              },
              color: '#654321',
            },
          ],
        },
      },
      legend: {
        display: false,
      },
      layout: {
        padding: {
          left: 0,
          right: 0,
          top: 0,
          bottom: 0,
        },
      },
    };

    expect(doughnut.find(ChartJs).props('canvasAttributes')).toMatchObject({
      width: '100%',
      height: '100%',
    });
    expect(doughnut.find(ChartJs).props('options')).toMatchObject(options);
    expect(doughnut.find(ChartJs).props('data')).toMatchObject(data);
    expect(doughnut.find(ChartJs).props('header')).toMatch(
      'I am a big percentage doughnut!!'
    );
    expect(doughnut.find(ChartJs).props('ariaLabel')).toMatch('57%');
  });

  it('does not include second value in the dataset if 100% passed', () => {
    let doughnut = mount(PercentageDoughnut, {
      propsData: {
        percentage: 100,
        header: 'I am a big percentage doughnut!!',
      },
    });
    let data = {
      datasets: [
        {
          data: [100],
          borderWidth: 2,
          borderColor: ['#3869B1'],
          backgroundColor: ['#3869B1'],
          hoverBackgroundColor: ['#3869B1'],
          hoverBorderColor: ['#3869B1'],
        },
      ],
    };

    let options = {
      cutoutPercentage: 85,
      tooltips: {
        enabled: false,
      },
      plugins: {
        doughnutlabel: {
          labels: [
            {
              text: '100%',
              font: {
                size: 40,
                weight: 'bold',
              },
              color: '#000',
            },
          ],
        },
      },
      legend: {
        display: false,
      },
    };

    expect(doughnut.find(ChartJs).props('options')).toMatchObject(options);
    expect(doughnut.find(ChartJs).props('data')).toMatchObject(data);
    expect(doughnut.find(ChartJs).props('header')).toMatch(
      'I am a big percentage doughnut'
    );
    expect(doughnut.find(ChartJs).props('ariaLabel')).toMatch('100%');
  });

  it('should not have any accessibility violations', async () => {
    const results = await axe(wrapper.element, {
      rules: {
        region: { enabled: false },
      },
    });
    expect(results).toHaveNoViolations();
  });
});
