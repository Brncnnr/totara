"""
This file is part of Totara Enterprise Extensions.

Copyright (C) 2020 onwards Totara Learning Solutions LTD

Totara Enterprise Extensions is provided only to Totara
Learning Solutions LTD's customers and partners, pursuant to
the terms and conditions of a separate agreement with Totara
Learning Solutions LTD or its affiliate.

If you do not have an agreement with Totara Learning Solutions
LTD, you may not access, use, modify, or distribute this software.
Please contact [licensing@totaralearning.com] for more information.

@author Amjad Ali <amjad.ali@totaralearning.com>
@package ml_recommender
@deprecated since Totara 17.0 ml_recommender has been deprecated.
"""


conf = {
    # Minimum number of users and items in interactions set for whom to run the
    # recommendation engine in a tenant
    "min_data": {"min_users": 10, "min_items": 10},
    # The L2 penalty on user and item features when features are being used in the model
    "user_alpha": 1e-6,
    "item_alpha": 1e-6,
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
    "lang_config": {
        "ar": {"name": "Arabic", "models": ()},
        "bg": {"name": "Bulgarian", "models": ()},
        "cs": {"name": "Czech", "models": ()},
        "da": {"name": "Danish", "models": ()},
        "de": {
            "name": "German",
            "models": (
                "de_core_news_md",
                "de_core_news_sm",
            ),
        },
        "el": {"name": "Greek", "models": ()},
        "en": {
            "name": "English",
            "models": (
                "en_core_web_md",
                "en_core_web_sm",
            ),
        },
        "es": {
            "name": "Spanish",
            "models": (
                "es_core_news_md",
                "es_core_news_sm",
            ),
        },
        "et": {"name": "Estonian", "models": ()},
        "fa": {"name": "Persian", "models": ()},
        "fi": {"name": "Finnish", "models": ()},
        "fr": {"name": "French", "models": ()},
        "he": {"name": "Hebrew", "models": ()},
        "hi": {"name": "Hindi", "models": ()},
        "hr": {"name": "Croatian", "models": ()},
        "hu": {"name": "Hungarian", "models": ()},
        "it": {
            "name": "Italian",
            "models": (
                "it_core_news_md",
                "it_core_news_sm",
            ),
        },
        "ja": {"name": "Japanese", "models": ()},
        "lt": {"name": "Lithuanian", "models": ()},
        "lv": {"name": "Latvian", "models": ()},
        "nl": {
            "name": "Dutch",
            "models": (
                "nl_core_news_md",
                "nl_core_news_sm",
            ),
        },
        "no": {"name": "Norwegian", "models": ()},
        "pl": {"name": "Polish", "models": ()},
        "pt": {"name": "Portuguese", "models": ()},
        "ro": {"name": "Romanian", "models": ()},
        "ru": {"name": "Russian", "models": ()},
        "sk": {"name": "Slovak", "models": ()},
        "sl": {"name": "Slovenian", "models": ()},
        "sr": {"name": "Serbian", "models": ()},
        "sv": {"name": "Swedish", "models": ()},
        "th": {"name": "Thai", "models": ()},
        "tr": {"name": "Turkish", "models": ()},
        "zh": {"name": "Chinese", "models": ()},
    },
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
        method returns `None` when the
        provided key does not match with any key of the `conf` dictionary
        :param property_name: A key from the keys of the `conf` dictionary
        :type property_name: str
        :return: An item from the `conf` dictionary whose key was used as input
        """
        value = None
        if property_name in self._config.keys():
            value = self._config[property_name]
        return value
