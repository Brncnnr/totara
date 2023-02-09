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
from unittest.mock import patch

from subroutines.optimize_hyperparams import OptimizeHyperparams


class TestOptimizeHyperparams(unittest.TestCase):
    """
    This class is the test object to test units of the class `OptimizeHyperparams`
    """

    def setUp(self):
        """
        Hook method for setting up the fixture before exercising it
        """
        interactions = 50
        user_features = 35
        item_features = 30
        weights = 20
        num_threads = 2
        user_alpha = 0.1
        item_alpha = 0.0
        self.optimizer = OptimizeHyperparams(
            interactions=interactions,
            user_features=user_features,
            item_features=item_features,
            weights=weights,
            num_threads=num_threads,
            user_alpha=user_alpha,
            item_alpha=item_alpha,
        )

    @patch(
        "subroutines.optimize_hyperparams.OptimizeHyperparams"
        "._OptimizeHyperparams__compute_performance"
    )
    def test_compute_gradient(self, mock_compute_performance):
        """
        This method tests if the `__compute_gradient` method of the
        `OptimizeHyperparams` class calls the `__compute_performance` method twice, and
        if it returns the correct gradients with the mocked performance values
        """
        f = 9.0
        train_data = None
        test_data = None
        train_weights = (None,)
        epochs = 5
        comps = 5
        mock_compute_performance.return_value = 10.0
        grads = self.optimizer._OptimizeHyperparams__compute_gradient(
            f=f,
            train_data=train_data,
            test_data=test_data,
            train_weights=train_weights,
            epochs=epochs,
            comps=comps,
        )
        self.assertEqual(mock_compute_performance.call_count, 2)
        self.assertEqual(grads[0], (mock_compute_performance.return_value - f) / 1)
        self.assertEqual(grads[1], (mock_compute_performance.return_value - f) / 1)

    @patch("subroutines.optimize_hyperparams.auc_score")
    @patch("subroutines.optimize_hyperparams.LightFM.fit")
    def test_compute_performance(self, mock_lightfm_fit, mock_auc_score):
        """
        This method tests if the `__compute_performance` method of the
        `OptimizeHyperparams` class calls the `fit` method of `LightFM` class once with
        the correct arguments, and if the `auc_score` function has been called once and
        if the returned value is correct with mocked auc_score.
        """
        train_data = 10
        test_data = 5
        train_weights = 7
        epochs = 3
        comps = 5
        scores_array = np.random.rand(10)
        mock_auc_score.return_value = scores_array
        performance = self.optimizer._OptimizeHyperparams__compute_performance(
            train_data=train_data,
            test_data=test_data,
            train_weights=train_weights,
            epochs=epochs,
            comps=comps,
        )
        mock_lightfm_fit.assert_called_once_with(
            interactions=train_data,
            sample_weight=train_weights,
            user_features=self.optimizer.user_features,
            item_features=self.optimizer.item_features,
            epochs=epochs,
            num_threads=self.optimizer.num_threads,
        )
        mock_auc_score.assert_called_once()
        self.assertEqual(performance, scores_array.mean())

    @patch(target="subroutines.optimize_hyperparams.auc_score")
    def test_compute_performance_no_interactions(self, mock_auc_score):
        """
        This method tests if the `__compute_performance` method of the
        `OptimizeHyperparams` class returns expected response when there are zero past
        user interactions
        """
        train_data = 10
        test_data = 5
        train_weights = 7
        epochs = 3
        comps = 5
        scores_array = np.array([], dtype=np.float32)
        mock_auc_score.return_value = scores_array
        with patch(target="subroutines.optimize_hyperparams.LightFM"):
            performance = self.optimizer._OptimizeHyperparams__compute_performance(
                train_data=train_data,
                test_data=test_data,
                train_weights=train_weights,
                epochs=epochs,
                comps=comps
            )

        self.assertEqual(
            first=performance,
            second=0,
            msg=(
                "The 'OptimizeHyperparams.__compute_performance' method returns "
                f"{performance} while the expected response was '0'"
            )
        )

    @patch("subroutines.optimize_hyperparams.np.random.randint")
    @patch(
        "subroutines.optimize_hyperparams.OptimizeHyperparams"
        "._OptimizeHyperparams__compute_performance"
    )
    @patch("subroutines.optimize_hyperparams.random_train_test_split")
    def test_run_optimization(
        self, mock_train_test_split, mock_compute_performance, mock_randint
    ):
        """
        This method tests if the returned `test_score`, `comps` and `epochs` from the
        `run_optimization` method of the `OptimizeHyperparams` class are as expected.
        The `train_test_split` has been called correct number of times, and
        `__compute_performance` has been called, and also the `np.random.randint` has
        been called to generate the random `epochs` and `comps`.
        """
        test_score = 0.5
        lr = 10
        mock_train_test_split.return_value = (15, 5)
        mock_compute_performance.return_value = test_score
        mock_randint.return_value = np.array([5])
        epochs, comps, scores = self.optimizer.run_optimization(lr=lr)
        self.assertEqual(scores[-1], test_score)
        self.assertEqual(comps[-1], 5)
        self.assertEqual(epochs[-1], 5)
        self.assertEqual(mock_train_test_split.call_count, 2)
        mock_compute_performance.assert_called()
        self.assertEqual(mock_randint.call_count, 2)
