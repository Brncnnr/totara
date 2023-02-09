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
 * @author Simon Tegg <simon.tegg@totaralearning.com>
 * @module tui_xstate
 * @jest-environment jsdom
 */
import { updateNode } from '../../internal/configure_machine';
import { $SHOW_ERROR, $QUERY_ALL } from '../../constants';

describe('configure_machine', () => {
  test('updateNode adds default onError handler to StateNode invoke', () => {
    const id = 'stateId';
    const node = {
      id,
      meta: {
        defaultErrorTarget: true,
      },
      invoke: {
        src: 'aService',
        onDone: 'aTarget',
      },
    };

    const update = updateNode({
      location: 'invokingState',
      node,
      key: id,
      parent: null,
      queryServices: {},
      invokeIds: {},
    });

    expect(update).not.toBe(node);
    expect(update.invoke).toHaveProperty('onError');
    expect(update.invoke.onError).toHaveProperty('actions', $SHOW_ERROR);
    expect(update.invoke.onError).toHaveProperty('target', `#${id}`);
    expect(update.invoke.id).toEqual(node.invoke.src);
  });

  test('updateNode leaves handled onError', () => {
    const defaultErrorTargetId = '#ready';
    const node = {
      invoke: {
        id: 'aService',
        src: 'aService',
        onDone: 'aTarget',
        onError: '#ready',
      },
    };

    const update = updateNode({
      location: 'invokingState',
      node,
      queryServices: {},
      invokeIds: {},
    });

    expect(update).toBe(node);
    expect(update.invoke).toHaveProperty('onError');
    expect(update.invoke.onError).not.toHaveProperty('actions', $SHOW_ERROR);
    expect(update.invoke.onError).not.toHaveProperty(
      'target',
      defaultErrorTargetId
    );
  });

  test('updateNode adds query observe action to entry actions', () => {
    const src = 'aService';
    const queryServices = { [src]: jest.fn() };
    const node = {
      invoke: {
        src,
        onDone: 'aTarget',
        onError: '#ready',
      },
    };

    const update = updateNode({
      location: 'invokingState',
      node,
      queryServices,
      invokeIds: {},
    });

    expect(update).not.toBe(node);
    expect(update.entry).toEqual([`observe_${src}`]);
  });

  test('updateNode preserves existing entry actions', () => {
    const src = 'aService';
    const queryServices = { [src]: jest.fn() };
    const actionName = 'anAction';
    const node = {
      entry: [actionName],
      invoke: {
        src,
        onDone: 'aTarget',
        onError: '#ready',
      },
    };

    const update = updateNode({
      location: 'invokingState',
      node,
      queryServices,
      invokeIds: {},
    });

    expect(update).not.toBe(node);
    expect(update.entry).toEqual([actionName, `observe_${src}`]);

    const node2 = {
      entry: actionName,
      invoke: {
        src,
        onDone: 'aTarget',
        onError: '#ready',
      },
    };

    const update2 = updateNode({
      location: 'invokingState',
      node: node2,
      queryServices,
      invokeIds: {},
    });

    expect(update2.entry).toEqual([actionName, `observe_${src}`]);
  });

  test('updateNode configures machine to observe mutiple queries invoked in a state', () => {
    const id = 'stateId';
    const src = 'aService';
    const src2 = 'anotherService';
    const queryServices = { [src]: jest.fn(), [src2]: jest.fn() };
    const actionName = 'anAction';
    const node = {
      id,
      meta: {
        defaultErrorTarget: true,
      },
      entry: [actionName],
      invoke: [
        {
          src,
          onDone: { actions: 'setDone' },
        },
        {
          src: src2,
          onDone: { actions: 'setDone' },
        },
      ],
    };

    const update = updateNode({
      location: 'invokingState',
      node,
      key: id,
      parent: null,
      queryServices,
      invokeIds: {},
    });

    expect(update.entry).toEqual([
      actionName,
      `observe_${src}`,
      `observe_${src2}`,
    ]);
    expect(update.invoke[0]).toHaveProperty('onError', {
      target: `#${id}`,
      actions: $SHOW_ERROR,
    });
    expect(update.invoke[1]).toHaveProperty('onError', {
      target: `#${id}`,
      actions: $SHOW_ERROR,
    });
  });

  test('updateNode configures machine to observe mutiple queries given to $queryAll service', () => {
    const id = 'ready';
    const src = 'aService';
    const src2 = 'anotherService';
    const queryServices = { [src]: jest.fn(), [src2]: jest.fn() };
    const actionName = 'anAction';
    const node = {
      id,
      meta: {
        defaultErrorTarget: true,
      },
      entry: [actionName],
      invoke: {
        src: {
          type: $QUERY_ALL,
          queries: [src, src2],
        },
      },
    };

    const update = updateNode({
      location: 'invokingState',
      node,
      key: id,
      parent: null,
      queryServices,
      invokeIds: {},
    });

    expect(update.entry).toEqual([
      actionName,
      `observe_${src}`,
      `observe_${src2}`,
    ]);
    expect(update.invoke).toHaveProperty('onError', {
      target: `#${id}`,
      actions: $SHOW_ERROR,
    });
  });
});
