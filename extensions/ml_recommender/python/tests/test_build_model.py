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

import unittest
from unittest.mock import patch

from subroutines.build_model import BuildModel


class TestBuildModel(unittest.TestCase):
    """
    The test object to test units of the class `BuildModel`
    """

    def setUp(self):
        """
        Hook method for setting up the fixture before exercising it
        """
        self.interactions = 50
        self.weights = 40
        self.user_features = ["user_feature1", "user_feature2", "user_feature3"]
        self.item_features = ["item_feature1", "item_feature2", "item_feature3"]
        self.num_threads = 6
        self.item_alpha = 1e-6
        self.hyperparams = {"epochs": 10, "no_components": 10}
        self.model = BuildModel(
            interactions=self.interactions,
            weights=self.weights,
            user_features=self.user_features,
            item_features=self.item_features,
            num_threads=self.num_threads,
            item_alpha=self.item_alpha,
            optimized_hyperparams=self.hyperparams,
        )

    @patch("subroutines.build_model.LightFM.fit")
    def test_build_model(self, mock_model_fit):
        """
        This method tests if the class method `LightFM.fit` has been called correctly
        """
        self.model.build_model()
        mock_model_fit.assert_called_once_with(
            interactions=self.interactions,
            sample_weight=self.weights,
            user_features=self.user_features,
            item_features=self.item_features,
            epochs=self.hyperparams["epochs"],
            num_threads=self.num_threads,
        )

    def test_components(self):
        """
        This method tests if the class `LightFM` has been instantiated with the correct
        size of lateral dimensions
        """
        self.assertEqual(
            self.model.optimized_hyperparams["no_components"],
            self.hyperparams["no_components"],
        )
