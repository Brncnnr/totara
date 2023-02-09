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

from lightfm import LightFM


class BuildModel:
    """
    This is a conceptual representation of the model build process
    """

    def __init__(
        self,
        processed_data=None,
        num_threads=2,
        optimized_hyperparams=None,
        item_alpha=0.0,
        user_alpha=0.0,
    ):
        """
        Class constructor method

        :param processed_data: Data for model building
        :type processed_data: dict
        :param num_threads: Number of parallel computation threads to use. Should not be
            higher than the number of physical cores, defaults to 2
        :type num_threads: int, optional
        :param optimized_hyperparams: Optimized set of hyper-parameters; `epochs` and
            `num_threads`
        :type optimized_hyperparams: dict
        :param item_alpha: L2 penalty on item features, defaults to 0
        :type item_alpha: float, optional
        :param user_alpha: L2 penalty on user features, defaults to 0
        :type user_alpha: float, optional
        """
        self.interactions = processed_data["interactions"]["interactions"]
        self.weights = processed_data["interactions"]["weights"]
        self.user_features = processed_data["users_processed_data"]
        self.item_features = processed_data["items_processed_data"]
        self.num_threads = num_threads
        self.optimized_hyperparams = optimized_hyperparams
        self.item_alpha = item_alpha
        self.user_alpha = user_alpha

    def build_model(self):
        """
        Uses instance variables to build the LightFM model object on the entire training
        set

        :return: LightFM model
        :rtype: LightFM model object
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
