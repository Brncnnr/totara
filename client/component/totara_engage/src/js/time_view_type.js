export default {
  /**
   * @var string
   */
  LESS_THAN_FIVE: 'LESS_THAN_FIVE',
  /**
   * @var string
   */
  FIVE_TO_TEN: 'FIVE_TO_TEN',
  /**
   * @var string
   */
  MORE_THAN_TEN: 'MORE_THAN_TEN',

  /**
   *
   * @param {String} value
   * @return {boolean}
   */
  isLessThanFive(value) {
    return this.LESS_THAN_FIVE === value;
  },

  /**
   *
   * @param {String} value
   * @return {boolean}
   */
  isFiveToTen(value) {
    return this.FIVE_TO_TEN === value;
  },

  /**
   *
   * @param {String} value
   * @return {boolean}
   */
  isMoreThanTen(value) {
    return this.MORE_THAN_TEN === value;
  },

  /**
   * Checking whether the value of access is something that machine can understand.
   *
   * @param {String} value
   * @return {boolean}
   */
  isValid(value) {
    return [this.LESS_THAN_FIVE, this.FIVE_TO_TEN, this.MORE_THAN_TEN].includes(
      value
    );
  },
};
