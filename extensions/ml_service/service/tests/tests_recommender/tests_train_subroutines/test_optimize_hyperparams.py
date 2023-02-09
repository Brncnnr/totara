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
from unittest.mock import patch

from service.recommender.train_subroutines.optimize_hyperparams import (
    OptimizeHyperparams,
)
from service.recommender.data_subroutines.data_loader import DataLoader
from service.tests.tests_recommender.generate_data import GenerateData
from service.recommender.config import Config


class TestOptimizeHyperparams(unittest.TestCase):
    """
    This class is the test object to test units of the class `OptimizeHyperparams`
    """

    def setUp(self):
        """
        Hook method for setting up the fixture before exercising it
        """
        self.num_threads = 6
        self.user_alpha = 1e-6
        self.item_alpha = 1e-6
        data_generator = GenerateData()
        interactions = data_generator.get_interactions()
        users_data = data_generator.get_users()
        items_data = data_generator.get_items()
        data_loader = DataLoader(query="mf")
        self.longMessage = False
        processed_data = data_loader.prepare_sparse_matrices(
            interactions_df=interactions,
            users_data=users_data,
            items_data=items_data,
        )
        self.optimizer = OptimizeHyperparams(
            processed_data=processed_data,
            num_threads=self.num_threads,
            user_alpha=self.user_alpha,
            item_alpha=self.item_alpha,
        )

    @patch("service.recommender.train_subroutines.optimize_hyperparams.auc_score")
    @patch("service.recommender.train_subroutines.optimize_hyperparams.LightFM")
    def test_compute_performance(self, mock_lightfm, mock_auc_score) -> None:
        """
        This method tests if the `compute_performance` method of the
        `OptimizeHyperparams` class instantiates the `LightFM` class with expected
        arguments and it calls the `fit` method of `LightFM` class once with the correct
        arguments, and if the `auc_score` function has been called and if the
        returned value is correct with mocked auc_score.
        """
        epochs = 3
        comps = 5
        scores_array = np.random.rand(10)
        mock_auc_score.return_value = scores_array
        performance = self.optimizer.compute_performance(epochs=epochs, comps=comps)

        self.assertEqual(
            first=mock_lightfm.call_args,
            second=unittest.mock.call(
                loss="warp",
                user_alpha=self.user_alpha,
                item_alpha=self.item_alpha,
                learning_schedule="adadelta",
                no_components=comps,
            ),
            msg=(
                f"The 'LightFM' class is instantiated with \n{mock_lightfm.call_args}\n"
                "while it was expected to be called with\ncall(loss='warp', user_alpha="
                f"{self.user_alpha}, item_alpha={self.item_alpha}, learning_schedule="
                f"'adadelta', no_components={comps})"
            ),
        )

        self.assertEqual(
            first=mock_lightfm().fit.call_args,
            second=unittest.mock.call(
                interactions=self.optimizer.train_data,
                sample_weight=self.optimizer.train_weights,
                user_features=self.optimizer.user_features,
                item_features=self.optimizer.item_features,
                epochs=epochs,
                num_threads=self.num_threads,
            ),
            msg=(
                "The 'LightFM.fit' method is called with\n"
                f"{mock_lightfm().fit.call_args}\nwhile it was expected to be called "
                f"with\ncall(interactions={self.optimizer.train_data}, sample_weight="
                f"{self.optimizer.train_weights}, user_features="
                f"{self.optimizer.user_features}, item_features="
                f"{self.optimizer.item_features}, epochs={epochs}, num_threads="
                f"{self.num_threads})"
            ),
        )

        self.assertTrue(
            expr=mock_auc_score.called,
            msg="The 'auc_score' has not been used in the "
            "`OptimizeHyperparams.compute_performance` method",
        )

        expected_performance = scores_array.mean()

        self.assertEqual(
            first=performance,
            second=expected_performance,
            msg=(
                "The response from the `OptimizeHyperparams.compute_performance` is "
                f"{performance}, while it was expected to be {expected_performance}"
            ),
        )

    @patch(
        target="service.recommender.train_subroutines.optimize_hyperparams.auc_score"
    )
    def test_compute_performance_no_interactions(self, mock_auc_score) -> None:
        """
        This method tests if the `compute_performance` method of the
        `OptimizeHyperparams` class returns expected response when there are zero past
        user interactions
        """
        epochs = 3
        comps = 5
        scores_array = np.array([], dtype=np.float32)
        mock_auc_score.return_value = scores_array
        with patch(
            target=(
                "service.recommender.train_subroutines.optimize_hyperparams.LightFM"
            )
        ):
            performance = self.optimizer.compute_performance(
                epochs=epochs,
                comps=comps,
            )

        self.assertEqual(
            first=performance,
            second=0,
            msg=(
                "The 'OptimizeHyperparams.compute_performance' method returns "
                f"{performance} while the expected response was '0'"
            ),
        )

    @patch(
        "service.recommender.train_subroutines.optimize_hyperparams.OptimizeHyperparams"
        ".compute_performance"
    )
    def test_simulated_annealing(self, mock_performance) -> None:
        """
        This method tests if the method `OptimizeHyperparams.simulated_annealing`
        behaves as expected
        """
        mock_performance.return_value = -1
        epochs, comps, scores = self.optimizer.simulated_annealing(
            n_iterations=100, step_size=1, temp=1
        )

        cfg = Config()
        bounds = cfg.get_property(property_name="bounds")

        self.assertTrue(
            expr=bounds["epochs"][0] <= epochs[-1] <= bounds["epochs"][1],
            msg=(
                f"The optimized number of epochs {epochs[-1]} is not in the range "
                f"{bounds['epochs']}"
            ),
        )

        self.assertTrue(
            expr=bounds["n_components"][0] <= comps[-1] <= bounds["n_components"][1],
            msg=(
                f"The optimized number of number of components {comps[-1]} is not in "
                f"the range {bounds['n_components']}"
            ),
        )

        self.assertEqual(
            first=scores[-1],
            second=-1,
            msg=(
                "The AUC score of the model with the optimized hyper-parameters is "
                f"{scores[-1]} while it was expected to be -1"
            ),
        )

    @patch(
        "service.recommender.train_subroutines.optimize_hyperparams.OptimizeHyperparams"
        ".simulated_annealing"
    )
    def test_run_optimization(self, mock_simulated_annealing) -> None:
        """
        This method tests if the returned `epochs`, `comps` and `scores` from the
        `run_optimization` method of the `OptimizeHyperparams` class are as expected.
        And it tests if the method `simulated_annealing` has been called with the
        arguments as expected
        """
        mock_response = ([3, 9, 8], [70, 60, 55], [-1, -0.5, -0.25])
        mock_simulated_annealing.return_value = mock_response

        computed_response = self.optimizer.run_optimization()

        self.assertEqual(
            first=mock_simulated_annealing.call_args,
            second=unittest.mock.call(n_iterations=30, step_size=0.2, temp=5),
            msg=(
                "The method 'OptimizeHyperparams.simulated_annealing' called with\n"
                f"{mock_simulated_annealing.call_args}\n"
                "while it was expected to be called with\n"
                f"call(n_iterations=30, step_size=0.2, temp=5)"
            ),
        )

        self.assertEqual(
            first=computed_response,
            second=mock_response,
            msg=(
                "The response from the method 'OptimizeHyperparams.run_optimization' "
                f"has returned {computed_response} while the expected response was "
                f"{mock_response}"
            ),
        )
