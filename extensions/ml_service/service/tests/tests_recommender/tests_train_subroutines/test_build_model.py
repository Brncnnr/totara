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

import unittest
from unittest.mock import patch

from service.recommender.train_subroutines.build_model import BuildModel
from service.recommender.data_subroutines.data_loader import DataLoader
from service.tests.tests_recommender.generate_data import GenerateData


class TestBuildModel(unittest.TestCase):
    """
    The test object to test units of the class `BuildModel`
    """

    def setUp(self):
        """
        Hook method for setting up the fixture before exercising it
        """
        self.num_threads = 6
        self.user_alpha = 1e-6
        self.item_alpha = 1e-6
        self.hyperparams = {"epochs": 10, "no_components": 10}
        data_generator = GenerateData()
        interactions = data_generator.get_interactions()
        users_data = data_generator.get_users()
        items_data = data_generator.get_items()
        data_loader = DataLoader("mf")
        processed_data = data_loader.prepare_sparse_matrices(
            interactions_df=interactions,
            users_data=users_data,
            items_data=items_data,
        )

        self.model = BuildModel(
            processed_data=processed_data,
            num_threads=self.num_threads,
            optimized_hyperparams=self.hyperparams,
            item_alpha=self.item_alpha,
            user_alpha=self.user_alpha,
        )
        self.longMessage = False

    @patch("service.recommender.train_subroutines.build_model.LightFM")
    def test_build_model(self, mock_model):
        """
        This method tests if the class method `LightFM` has been instantiated correctly
        and the method `fit` has been called with correct arguments
        """
        self.model.build_model()

        self.assertEqual(
            first=mock_model.call_args,
            second=unittest.mock.call(
                loss="warp",
                user_alpha=self.user_alpha,
                item_alpha=self.item_alpha,
                learning_schedule="adadelta",
                no_components=self.hyperparams["no_components"],
            ),
            msg=(
                "The class 'BuildModel' was initiated with\n"
                f"{mock_model.call_args}\n"
                "while it was expected to be instantiated with\n"
                f"call(loss='warp', user_alpha={self.user_alpha}, "
                f"item_alpha={self.item_alpha}, learning_schedule='adadelta', "
                f"no_components={self.hyperparams['no_components']})"
            ),
        )

        self.assertEqual(
            first=mock_model().fit.call_args,
            second=unittest.mock.call(
                interactions=self.model.interactions,
                sample_weight=self.model.weights,
                user_features=self.model.user_features,
                item_features=self.model.item_features,
                epochs=self.hyperparams["epochs"],
                num_threads=self.num_threads,
            ),
            msg=(
                "The method 'BuildModel.fit' was called with\n"
                f"{mock_model().fit.call_args}\n"
                "while it was expected to be called with\n"
                f"call(interactions={self.model.interactions}, sample_weight="
                f"{self.model.weights}, user_features={self.model.user_features}, "
                f"item_features={self.model.item_features}, epochs="
                f"{self.hyperparams['epochs']}, num_threads={self.num_threads})"
            ),
        )
