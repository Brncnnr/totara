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

from lightfm import LightFM


class BuildModel:
    """
    This is a conceptual representation of the model build process
    """

    def __init__(
        self,
        interactions=None,
        weights=None,
        item_features=None,
        num_threads=2,
        optimized_hyperparams=None,
        item_alpha=0.0,
        user_features=None,
        user_alpha=0.0,
    ):
        """
        Class constructor method
        :param interactions: the matrix containing user-item interactions of shape
            `[n_users, n_items]`
        :type interactions: np.float32 coo_matrix
        :param weights: A matrix with entries expressing weights of individual
            interactions from the interactions matrix. Its row and col arrays must be
            the same as those of the interactions matrix
        :type weights: coo_matrix
        :param item_features: A matrix of shape `[n_items, n_item_features]` where each
            row contains that item's weight over features
        :type item_features: csr_matrix
        :param num_threads: Number of parallel computation threads to use. Should not be
            higher than the number of physical cores, defaults to 2
        :type num_threads: int, optional
        :param optimized_hyperparams: Optimized set of hyper-parameters; `epochs` and
            `num_threads`
        :type optimized_hyperparams: dict
        :param item_alpha: L2 penalty on item features, defaults to 0
        :type item_alpha: float, optional
        :param user_features: A matrix of shape `[n_users, n_user_features]` where each
            row contains that user's weight over features
        :type user_features: csr_matrix
        :param user_alpha: L2 penalty on user features, defaults to 0
        :type user_alpha: float, optional
        """
        self.interactions = interactions
        self.weights = weights
        self.item_features = item_features
        self.num_threads = num_threads
        self.optimized_hyperparams = optimized_hyperparams
        self.item_alpha = item_alpha
        self.user_features = user_features
        self.user_alpha = user_alpha

    def build_model(self):
        """
        Uses instance variables to build the LightFM model object on the entire training
            set
        :return: LightFM model object
        """
        model = LightFM(
            loss="warp",
            user_alpha=self.user_alpha,
            item_alpha=self.item_alpha,
            learning_schedule="adadelta",
            no_components=self.optimized_hyperparams["no_components"],
        )
        model.fit(
            interactions=self.interactions,
            sample_weight=self.weights,
            user_features=self.user_features,
            item_features=self.item_features,
            epochs=self.optimized_hyperparams["epochs"],
            num_threads=self.num_threads,
        )

        return model
