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
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @module tui
 */

import { EditorContent, Format } from 'tui/editor';

const editorA = {
  rawToValue: jest.fn((content, format) => ({ e: 'a', content, format })),
  valueToRaw: jest.fn(value => value.content),
  isContentEmpty: jest.fn(value => !value.content),
  getValueFormat: jest.fn(value => value.format),
};
const editorB = {
  rawToValue: jest.fn((content, format) => ({ e: 'b', content, format })),
  valueToRaw: jest.fn(value => value.content),
  isContentEmpty: jest.fn(value => !value.content),
  getValueFormat: jest.fn(value => value.format),
};

describe('EditorContent', () => {
  let ec;

  beforeEach(() => {
    ec = new EditorContent({
      format: 42,
      content: 'test',
      fileItemId: 99,
    });

    editorA.rawToValue.mockClear();
    editorB.rawToValue.mockClear();
  });

  it('holds content and related info', () => {
    ec = new EditorContent();
    expect(ec.format).toBe(null);
    expect(ec.getContent()).toBe(null);
    expect(ec.fileItemId).toBe(null);
    expect(ec.isEmpty).toBe(true);

    ec = new EditorContent({
      format: 42,
      content: 'test content',
      fileItemId: 99,
      foo: 'bar',
    });

    expect(ec.format).toBe(42);
    expect(ec.getContent()).toBe('test content');
    expect(ec.fileItemId).toBe(99);
    expect(ec.foo).toBe(undefined);
    expect(ec.isEmpty).toBe(false);
  });

  it('converts content to native value for editor', () => {
    const nvA = ec._getNativeValue(editorA);

    expect(editorA.rawToValue).toHaveBeenCalledTimes(1);
    expect(editorA.rawToValue).toHaveBeenCalledWith('test', 42);
    expect(nvA).toEqual({ e: 'a', content: 'test', format: 42 });
    expect(ec._getNativeValue(editorA)).toBe(nvA);
    expect(editorA.rawToValue).toHaveBeenCalledTimes(1);

    const nvB = ec._getNativeValue(editorB);

    expect(editorA.rawToValue).toHaveBeenCalledTimes(1);
    expect(editorB.rawToValue).toHaveBeenCalledTimes(1);
    expect(editorB.rawToValue).toHaveBeenCalledWith('test', 42);
    expect(nvB).not.toBe(nvA);
    expect(nvB).toEqual({ e: 'b', content: 'test', format: 42 });
    expect(ec._getNativeValue(editorB)).toBe(nvB);
    expect(editorB.rawToValue).toHaveBeenCalledTimes(1);
  });

  it('can be updated with a new native value', () => {
    const nv = { content: 'hello' };
    const ec2 = ec._updateNativeValue(editorA, nv);

    expect(ec2._getNativeValue(editorA)).toBe(nv);
  });

  it('converts native value back to content', () => {
    const nv = { content: 'hello', format: 42 };
    const ec2 = ec._updateNativeValue(editorA, nv);

    expect(ec2.getContent()).toBe('hello');

    expect(editorA.valueToRaw).toHaveBeenCalledWith(nv, 42);
  });

  it('supports native values for multiple editors at the same time', () => {
    const nv = { content: 'hello', format: 42 };
    const ec2 = ec._updateNativeValue(editorA, nv);

    expect(ec2._getNativeValue(editorA)).toBe(nv);

    const nvB = ec2._getNativeValue(editorB);

    expect(editorA.valueToRaw).toHaveBeenCalledWith(nv, 42);
    expect(editorB.rawToValue).toHaveBeenCalledWith('hello', 42);

    expect(nvB).not.toBe(nv);
    expect(nvB).toEqual({ e: 'b', content: 'hello', format: 42 });
  });

  it('supports checking if content is empty', () => {
    const ecWith = (content, format = 42) =>
      new EditorContent({ format, content });

    expect(ecWith(null).isEmpty).toBe(true);
    expect(ecWith('').isEmpty).toBe(true);

    expect(ecWith(null, Format.JSON_EDITOR).isEmpty).toBe(true);

    const jsonEmptyChecks = [
      { val: null, result: true },
      { val: {}, result: true },
      { val: { content: [] }, result: true },
      { val: { content: [{ type: 'paragraph' }] }, result: true },
      { val: { content: [{ type: 'paragraph', content: [] }] }, result: true },
      {
        val: { content: [{ type: 'paragraph', content: [{}] }] },
        result: false,
      },
      {
        val: { content: [{ type: 'paragraph' }, { type: 'paragraph' }] },
        result: false,
      },
      { val: { content: [{ type: 'foo' }] }, result: false },
    ];

    jsonEmptyChecks.forEach(check => {
      expect(
        ecWith(JSON.stringify(check.val), Format.JSON_EDITOR).isEmpty
      ).toBe(check.result);
    });

    const contentFoo = { content: 'foo' };
    expect(ecWith()._updateNativeValue(editorA, contentFoo).isEmpty).toBe(
      false
    );
    expect(editorA.isContentEmpty).toHaveBeenCalledWith(contentFoo);
    const contentEmpty = { content: '' };
    expect(ecWith()._updateNativeValue(editorA, contentEmpty).isEmpty).toBe(
      true
    );
    expect(editorA.isContentEmpty).toHaveBeenCalledWith(contentEmpty);
  });
});
