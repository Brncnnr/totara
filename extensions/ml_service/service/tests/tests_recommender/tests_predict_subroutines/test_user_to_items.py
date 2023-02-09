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

import numpy as np
import unittest
from unittest.mock import Mock

from service.recommender.config import Config
from service.recommender.data_subroutines.data_loader import DataLoader
from service.recommender.predict_subroutines.user_to_items import UserToItems
from service.tests.tests_recommender.generate_data import GenerateData


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
        type_cols = cfg.get_property(property_name="item_types")
        data_loader = DataLoader(query="mf")
        interactions = data_generator.get_interactions()
        users_data = data_generator.get_users()
        items_data = data_generator.get_items()

        users_map = dict(zip(users_data.index.tolist(), np.arange(users_data.shape[0])))
        items_map = dict(zip(items_data.index.tolist(), np.arange(items_data.shape[0])))

        positive_interactions = interactions.copy()
        positive_interactions = positive_interactions[positive_interactions.rating == 1]
        users_interacted = positive_interactions.user_id.unique()
        positive_inter_map = dict(
            (
                u,
                positive_interactions[
                    positive_interactions.user_id == u
                ].item_id.tolist(),
            )
            for u in users_interacted
        )

        item_type_map = data_loader.get_items_attr(
            dataframe=items_data[list(type_cols)]
        )
        self.model = Mock()
        self.mock_predictions = np.random.rand(10)
        self.model.predict.return_value = self.mock_predictions

        self.user_to_items = UserToItems(
            u_mapping=users_map,
            i_mapping=items_map,
            item_type_map=item_type_map,
            positive_inter_map=positive_inter_map,
            model=self.model,
        )

    def test_predict_called_in_get_items(self):
        """
        This method tests if the `get_items` method of the `UserToItems` class calls
        the `predict` method on the LightFM model object exactly once with the correct
        arguments
        """
        test_uid = 5
        __ = self.user_to_items.get_items(internal_uid=test_uid)
        self.assertEqual(
            first=self.model.predict.call_args[1]["user_ids"],
            second=test_uid,
            msg=(
                "The 'LightFM' model object in 'UserToItem.get_items' called with "
                f"user_ids={self.model.predict.call_args[1]['user_ids']} while it was "
                f"expected to be called with user_ids={test_uid}"
            ),
        )

    def test_get_items_overall(self):
        """
        This method tests if the `get_items` method of the `UserToItems` class returns
        a list of items as expected, i.e., after excluding the already seen items and
        ordered as per their ranking/score for the given user
        """
        test_uid = 5
        computed_recommended_items = self.user_to_items.get_items(
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
        best_with_score = self.user_to_items.top_x_by_cat(sorted_items)
        self.assertEqual(
            first=computed_recommended_items,
            second=best_with_score,
            msg=(
                "The response returned from the 'UserToItems.get_items' method is\n"
                f"{computed_recommended_items}\n while the expected response was\n"
                f"{best_with_score}"
            ),
        )
