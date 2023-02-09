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


conf = {
    # Minimum number of users and items in interactions set for whom to run the
    # recommendation engine in a tenant
    "min_data": {"min_users": 10, "min_items": 10},
    # The columns in the users and items datasets that need to be expanded horizontally
    # and are currently in the form `property_1|property_2|...|property_n`
    "spread_hor": {
        "users": (
            "lang",
            "country",
            "interests",
            "asp_position",
            "positions",
            "organisations",
            "badges",
        )
    },
    # The columns in the users and items datasets that are in the key-value pairs form
    # and need expansion into horizontal format or multiple columns
    "expand_dict": {"users": ("competencies_scale",)},
    # The columns that will be concatenated for text processing. These are usually the
    # columns with free text
    "concat": {
        "users": (
            "city",
            "description",
        )
    },
    # The maximum categories in each field allowed
    "max_categories": {"users": 30},
    # The types of items from which users get recommended. Items are requested per
    # category
    "item_types": (
        "container_course",
        "container_workspace",
        "engage_article",
        "engage_microlearning",
        "totara_playlist",
    ),
    # The bounds of `epochs` and `n_components` hyper-parameters
    "bounds": {"epochs": (1, 20), "n_components": (25, 100)},
    # The values of user and item alpha while training the model
    "user_alpha": 1e-3,
    "item_alpha": 1e-3,
    # Number of words to be encoded in a content
    "words_length": 128,
}


class Config:
    """
    This is a conceptual representation of accessing the configuration elements from the
    conf object
    """

    def __init__(self):
        """
        The constructor method
        """
        self._config = conf

    def get_property(self, property_name):
        """
        This method accesses and returns the called item of the `conf` dictionary. The
        method returns `None` when the provided key does not match with any key of the
        `conf` dictionary

        :param property_name: A key from the keys of the `conf` dictionary
        :type property_name: str
        :return: An item from the `conf` dictionary whose key was used as input
        """
        value = None
        if property_name in self._config.keys():
            value = self._config[property_name]
        return value
