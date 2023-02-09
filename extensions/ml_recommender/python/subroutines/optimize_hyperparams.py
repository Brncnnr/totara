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
from lightfm import LightFM
from lightfm.evaluation import auc_score
from lightfm.cross_validation import random_train_test_split


class OptimizeHyperparams:
    """
    This is a conceptual representation of the process of optimizing the two
    hyper-parameters `epochs` and the
    `num_components` in the LightFM model.
    """

    def __init__(
        self,
        interactions=None,
        item_features=None,
        weights=None,
        num_threads=2,
        item_alpha=0.0,
        user_features=None,
        user_alpha=0.0,
    ):
        """
        Constructor method
        :param interactions: the matrix containing user-item interactions of shape
        `[n_users, n_items]`
        :type interactions: np.float32 coo_matrix
        :param item_features: A matrix of shape `[n_items, n_item_features]` where each
            row contains that item's weight over features
        :type item_features: csr_matrix
        :param weights: A matrix with entries expressing weights of individual
            interactions from the interactions matrix. Its row and col arrays must be
            the same as those of the interactions matrix
        :type weights: coo_matrix
        :param num_threads: Number of parallel computation threads to use. Should not be
            higher than the number of physical cores, defaults to 2
        :type num_threads: int, optional
        :param item_alpha: L2 penalty on item features, defaults to 0
        :type item_alpha: float, optional
        :param user_features: A matrix of shape `[n_users, n_user_features]` where each
            row contains that user's weight over features
        :type user_features: csr_matrix
        :param user_alpha: L2 penalty on user features, defaults to 0
        :type user_alpha: float, optional
        """
        self.interactions = interactions
        self.user_features = user_features
        self.item_features = item_features
        self.weights = weights
        self.num_threads = num_threads
        self.user_alpha = user_alpha
        self.item_alpha = item_alpha

    def __compute_gradient(
        self,
        f=0.5,
        train_data=None,
        test_data=None,
        train_weights=None,
        epochs=1,
        comps=5,
    ):
        """
        Computes approximate partial derivatives of the test AUC score with respect to
        the number of epochs and with respect to the latent dimension or the number of
        components
        :param f: The AUC score at the given `epochs` and given `comps`
        :type f: float
        :param train_data: Train dataset
        :type train_data: coo_matrix
        :param test_data: Test dataset
        :type test_data: coo_matrix
        :param train_weights: A matrix with entries expressing weights of individual
            interactions from the train_data matrix. Its row and col arrays must be the
            same as those of the train_data matrix
        :type train_weights: coo_matrix
        :param epochs: Number of epochs
        :type epochs: int
        :param comps: Latent dimension or the number of components
        :type comps: int
        :returns: Partial derivative with respect to the epochs and with respect to the
            number of components, respectively
        :rtype: tuple
        """
        epochs_eps = 1
        new_epochs = epochs + epochs_eps
        comps_eps = 1
        new_comps = comps + comps_eps
        new_epochs_score = self.__compute_performance(
            train_data=train_data,
            test_data=test_data,
            train_weights=train_weights,
            epochs=new_epochs,
            comps=comps,
        )
        new_comps_score = self.__compute_performance(
            train_data=train_data,
            test_data=test_data,
            train_weights=train_weights,
            epochs=epochs,
            comps=new_comps,
        )
        grad_epochs = (new_epochs_score - f) / epochs_eps
        grad_comps = (new_comps_score - f) / comps_eps
        return grad_epochs, grad_comps

    def __compute_performance(
        self, train_data=None, test_data=None, train_weights=None, epochs=1, comps=10
    ):
        """
        Computes the AUC score on the `test_data` after building model on the
        `train_data` with the given epochs and the number of components
        :param train_data: Train dataset
        :type train_data: coo_matrix
        :param test_data: Test dataset
        :type test_data: coo_matrix
        :param train_weights: A matrix with entries expressing weights of individual
            interactions from the train_data matrix. Its row and col arrays must be the
            same as those of the train_data matrix
        :type train_weights: coo_matrix
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
            interactions=train_data,
            sample_weight=train_weights,
            user_features=self.user_features,
            item_features=self.item_features,
            epochs=epochs,
            num_threads=self.num_threads,
        )
        score = auc_score(
            model=model,
            test_interactions=test_data,
            train_interactions=train_data,
            user_features=self.user_features,
            item_features=self.item_features,
            num_threads=self.num_threads,
        )

        if score.shape[0] == 0:
            return 0

        return score.mean()

    def run_optimization(self, lr):
        """
        Computes the approximate partial derivatives of the test AUC score with respect
        to the epochs and the number of components and the takes steps (the size of the
        step is determined by `lr`) in the direction of partial derivatives of both the
        hyper-parameters, respectively. This process continues until the improvement in
        the test AUC score is smaller than 1e-5
        :param lr: The learning rate, determines the size of steps
        :type lr: float
        :returns: A tuple of three lists; epochs, comps, and scores
        :rtype: tuple
        """
        train_data, test_data = random_train_test_split(
            interactions=self.interactions,
            test_percentage=0.2,
            random_state=np.random.RandomState(10),
        )
        train_weights, __ = random_train_test_split(
            interactions=self.weights,
            test_percentage=0.2,
            random_state=np.random.RandomState(10),
        )
        epochs = np.random.randint(low=5, high=10, size=1).tolist()  # type: list
        comps = np.random.randint(low=25, high=50, size=1).tolist()  # type: list
        scores = list()
        scores.append(
            self.__compute_performance(
                train_data=train_data,
                test_data=test_data,
                train_weights=train_weights,
                epochs=epochs[0],
                comps=comps[0],
            )
        )

        advantage = 0.5
        while advantage > 1e-5:
            grad_epochs, grad_comps = self.__compute_gradient(
                f=scores[-1],
                train_data=train_data,
                test_data=test_data,
                train_weights=train_weights,
                epochs=epochs[-1],
                comps=comps[-1],
            )
            epochs.append(max(int(epochs[-1] + lr * grad_epochs), 1))
            comps.append(max(int(comps[-1] + lr * grad_comps), 1))
            scores.append(
                self.__compute_performance(
                    train_data=train_data,
                    test_data=test_data,
                    train_weights=train_weights,
                    epochs=epochs[-1],
                    comps=comps[-1],
                )
            )
            advantage = scores[-1] - scores[-2]

        return epochs, comps, scores
