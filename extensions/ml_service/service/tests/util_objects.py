"""
This file is part of Totara Enterprise Extensions.

Copyright (C) 2021 onward Totara Learning Solutions LTD

Totara Enterprise Extensions is provided only to Totara
Learning Solutions LTD's customers and partners, pursuant to
the terms and conditions of a separate agreement with Totara
Learning Solutions LTD or its affiliate.

If you do not have an agreement with Totara Learning Solutions
LTD, you may not access, use, modify, or distribute this software.
Please contact [licensing@totaralearning.com] for more information.

@author Amjad Ali <amjad.ali@totaralearning.com>
@package ml_service
"""


class FakeModelObject:
    """
    To create fake model object for code testing
    """

    def __init__(self):
        """
        Class constructor method
        """
        self.fake_representations = [[0, 0, 0, 0, 0], [0, 1, 1, 1, 0]]

    def get_item_representations(self, features):
        """
        This returns fake_representations parameter of the class instance when features
        is not None
        :return: fake_representations parameter of the class instance or None
        :rtype: list
        """
        if features:
            return self.fake_representations


class SyntheticObjects:
    """
    A collection of fake objects for testing
    """

    features = ["feature1", "feature2", "feature3"]
    false_test_mapping = [
        {"2": 0, "3": 1},
        {"age": 30, "height": 169},
        {"engage_microlearning2": 2, "engage_microlearning3": 3},
        {"topic1": 0.01, "author_2": 1},
    ]

    true_test_mapping = [
        {"2": 0, "3": 1, "4": 2},
        {"age": 30, "height": 169},
        {
            "engage_microlearning1": 1,
            "engage_microlearning2": 2,
            "engage_microlearning3": 3,
        },
        {"topic1": 0.01, "author_2": 1},
    ]


class Elapsed:
    """
    Fake object that has a method `total_seconds` for testing code
    """

    def __init__(self, time_elapsed=0.0011):
        """
        Class constructor method
        :param time_elapsed: Any float value
        :type time_elapsed: float
        """
        self.time_elapsed = time_elapsed

    def total_seconds(self) -> float:
        """
        Returns value equal to `time_elapsed` class parameter
        :return: Value equal to `time_elapsed` class parameter
        :rtype: float
        """
        return self.time_elapsed
