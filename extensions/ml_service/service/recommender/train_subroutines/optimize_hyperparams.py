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
from lightfm import LightFM
from lightfm.evaluation import auc_score
from lightfm.cross_validation import random_train_test_split

from service.recommender.config import Config


class OptimizeHyperparams:
    """
    This is a conceptual representation of the process of optimizing the two
    hyper-parameters `epochs` and the `num_components` in the LightFM model.
    """

    def __init__(
        self,
        processed_data=None,
        num_threads=2,
        item_alpha=0.0,
        user_alpha=0.0,
    ):
        """
        Constructor method

        :param processed_data: Data for model building
        :type processed_data: dict
        :param num_threads: Number of parallel computation threads to use. Should not be
            higher than the number of physical cores, defaults to 2
        :type num_threads: int, optional
        :param item_alpha: L2 penalty on item features, defaults to 0
        :type item_alpha: float, optional
        :param user_alpha: L2 penalty on user features, defaults to 0
        :type user_alpha: float, optional
        """
        interactions = processed_data["interactions"]["interactions"]
        self.user_features = processed_data["users_processed_data"]
        self.item_features = processed_data["items_processed_data"]
        weights = processed_data["interactions"]["weights"]

        self.train_data, self.test_data = random_train_test_split(
            interactions=interactions,
            test_percentage=0.2,
            random_state=np.random.RandomState(10),
        )
        self.train_weights, __ = random_train_test_split(
            interactions=weights,
            test_percentage=0.2,
            random_state=np.random.RandomState(10),
        )
        self.num_threads = num_threads
        self.user_alpha = user_alpha
        self.item_alpha = item_alpha
        cfg = Config()
        self.bounds = cfg.get_property("bounds")

    def compute_performance(self, epochs=1, comps=10):
        """
        Computes the AUC score on the `test_data` after building model on the
        `train_data` with the given epochs and the number of components

        :param epochs: Number of epochs
        :type epochs: int
        :param comps: Latent dimension or the number of components
        :type comps: int
        :return: The AUC score on the `test_data`
        :rtype: float
        """
        model = LightFM(
            loss="warp",
            user_alpha=self.user_alpha,
            item_alpha=self.item_alpha,
            learning_schedule="adadelta",
            no_components=comps,
        )
        model.fit(
            interactions=self.train_data,
            sample_weight=self.train_weights,
            user_features=self.user_features,
            item_features=self.item_features,
            epochs=epochs,
            num_threads=self.num_threads,
        )
        score = auc_score(
            model=model,
            test_interactions=self.test_data,
            train_interactions=self.train_data,
            user_features=self.user_features,
            item_features=self.item_features,
            num_threads=self.num_threads,
        )

        if score.shape[0] == 0:
            return 0

        return score.mean()

    def simulated_annealing(self, n_iterations, step_size, temp):
        """
        Find optimum hyper-parameters 'epochs' and 'n_components' with simulated
        annealing method

        :param n_iterations: Number of iterations for convergence
        :type n_iterations: int
        :param step_size: A real number in the range of [0, 1] that determines how far
            the random new guesses should be from the previous values of
            hyper-parameters
        :type step_size: float
        :param temp: An arbitrary real number that determines the acceptance
            probability of slightly worse solutions
        :type temp: float
        :return: A tuple composed of three objects:

            | **0:** a list of epochs with the final one being the best one,
            | **1:** a list of components with the final one being the best one, and
            | **2:** a list of scores with the final one being the best one.
        :rtype: tuple
        """
        # Generate an initial point
        parameter_bounds = np.asarray(
            [self.bounds["epochs"], self.bounds["n_components"]]
        )
        parameter_range = parameter_bounds[:, 1] - parameter_bounds[:, 0]
        best = np.rint(
            parameter_bounds[:, 0]
            + np.random.rand(len(parameter_bounds))
            * (parameter_bounds[:, 1] - parameter_bounds[:, 0])
        ).astype(int)
        # Evaluate the initial point
        best_eval = -1 * self.compute_performance(epochs=best[0], comps=best[1])

        # Current working solution
        curr, curr_eval = best, best_eval
        epochs, comps, scores = [best[0]], [best[1]], [-best_eval]

        # Run the algorithm
        for i in range(n_iterations):
            # Take a step
            candidate = curr + np.rint(
                np.random.randn(len(parameter_bounds)) * parameter_range * step_size
            ).astype(int)
            # Make sure the new candidate is in bounded region
            for c in range(len(candidate)):
                candidate[c] = min(
                    parameter_bounds[c, 1], max(parameter_bounds[c, 0], candidate[c])
                )

            # Evaluate candidate point
            candidate_eval = -1 * self.compute_performance(
                epochs=candidate[0], comps=candidate[1]
            )
            # Check for the new best solution
            if candidate_eval < best_eval:
                # Store new best point
                best, best_eval = candidate, candidate_eval
                # Store progress
                epochs.append(candidate[0])
                comps.append(candidate[1])
                scores.append(-candidate_eval)
            # Difference between candidate and current point evaluation
            diff = candidate_eval - curr_eval
            # Calculate temperature for current step
            t = temp / float(i + 1)
            # Calculate metropolis acceptance criterion
            metropolis = np.exp(-diff / t)
            # Check if we should keep the new point
            if diff < 0 or np.random.rand() < metropolis:
                # Store the new current point
                curr, curr_eval = candidate, candidate_eval
        return epochs, comps, scores

    def run_optimization(self):
        """
        Runs the optimization algorithm simulated annealing and returns the resultant
        scores and hyper-parameters

        :returns: A tuple of three lists; epochs, comps, and scores
        :rtype: tuple
        """
        epochs, comps, scores = self.simulated_annealing(
            n_iterations=30, step_size=0.2, temp=5
        )

        return epochs, comps, scores
