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

import numpy as np
import unittest
from pandas import DataFrame
from unittest.mock import patch, Mock

from config import Config
from subroutines.data_loader import DataLoader
from subroutines.user_to_items import UserToItems
from tests.generate_data import GenerateData


class TestUserToItems(unittest.TestCase):
    """
    This class is set up to test units of the `UserToItems` class
    """

    def setUp(self):
        """
        Hook method for setting up the fixture before exercising it
        """
        data_generator = GenerateData()
        cfg = Config()
        data_loader = DataLoader(
            users_spread_hor=cfg.get_property(property_name="spread_hor")["users"],
            users_expand_dict=cfg.get_property(property_name="expand_dict")["users"],
            users_concat=cfg.get_property(property_name="concat")["users"],
        )

        with patch("subroutines.data_loader.open"):
            processed_data = data_loader.load_data(
                interactions_df=data_generator.get_interactions(),
                users_data=data_generator.get_users(),
                items_data=data_generator.get_items(),
            )
        self.model = Mock()
        self.mock_predictions = np.random.rand(10)
        self.model.predict.return_value = self.mock_predictions
        self.user_to_items = UserToItems(
            u_mapping=processed_data["mapping"][0],
            i_mapping=processed_data["mapping"][2],
            item_type_map=processed_data["item_type_map"],
            user_features=processed_data["user_features"],
            item_features=processed_data["item_features"],
            positive_inter_map=processed_data["positive_inter_map"],
            model=self.model,
        )

    @patch("subroutines.user_to_items.np.fromiter")
    def test_fromiter_called_in_get_items(self, mock_fromiter):
        """
        This method tests if the `__get_items` method of the `UserToItems` class calls
        the `np.fromiter` function exactly once with the correct arguments
        """
        test_uid = 5
        __ = self.user_to_items._UserToItems__get_items(internal_uid=test_uid)
        mock_fromiter.assert_called_once()
        self.assertEqual(
            list(self.user_to_items.i_mapping.values()),
            list(mock_fromiter.call_args[0][0]),
        )
        self.assertDictEqual({"dtype": np.int32}, mock_fromiter.call_args[1])

    def test_predict_called_in_get_items(self):
        """
        This method tests if the `__get_items` method of the `UserToItems` class calls
        the `predict` method on the LightFM model object exactly once with the correct
        arguments
        """
        test_uid = 5
        __ = self.user_to_items._UserToItems__get_items(internal_uid=test_uid)
        self.model.predict.assert_called_once()
        self.assertEqual(self.model.predict.call_args[1]["user_ids"], test_uid)
        np.testing.assert_equal(
            self.model.predict.call_args[1]["item_ids"],
            np.fromiter(self.user_to_items.i_mapping.values(), dtype=np.int32),
        )
        self.assertEqual(
            self.model.predict.call_args[1]["user_features"],
            self.user_to_items.user_features,
        )
        self.assertEqual(
            self.model.predict.call_args[1]["item_features"],
            self.user_to_items.item_features,
        )
        self.assertEqual(
            self.model.predict.call_args[1]["num_threads"],
            self.user_to_items.num_threads,
        )

    def test_get_items_overall(self):
        """
        This method tests if the `__get_items` method of the `UserToItems` class returns
        a list of items as expected, i.e., after excluding the already seen items and
        ordered as per their ranking/score for the given user
        """
        test_uid = 5
        computed_recommended_items = self.user_to_items._UserToItems__get_items(
            internal_uid=test_uid, reduction_percentage=0.3
        )
        sorted_ids = self.mock_predictions.argsort()[::-1]
        sorted_items = [
            (
                self.user_to_items.i_mapping_rev[x],
                self.mock_predictions[x],
                self.user_to_items.item_type_map[self.user_to_items.i_mapping_rev[x]],
            )
            for x in sorted_ids
        ]
        best_with_score = self.user_to_items._UserToItems__top_x_by_cat(sorted_items)
        self.assertEqual(computed_recommended_items, best_with_score)

    def test_all_items(self):
        """
        This method tests if the `all_items` method of the `UserToItems` class returns a
        pandas` DataFrame object with the correct shape
        """
        computed_user_items = self.user_to_items.all_items()
        self.assertIsInstance(computed_user_items, DataFrame)
        self.assertEqual(computed_user_items.shape[1], 3)
