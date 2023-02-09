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


from service.recommender.config import Config
from service.recommender.train_subroutines.build_model import BuildModel
from service.recommender.train_subroutines.optimize_hyperparams import (
    OptimizeHyperparams,
)
from service.recommender.prepare_data import PrepareData


class TrainRecommender:
    """
    This is the conceptual representation of the recommendation model training process
    """

    def __init__(self, data=None, query="mf", num_threads=2):
        """
        This is the class constructor method

        :param data: The data dictionary for model training. This has the following
            keys

            | **data_status:** always 'success',
            | **tenants:** a pandas DataFrame that has a column called `tenants`,
            | **user_interactions_{tenant}:** one or more pandas DataFrames of
                user-to-item interactions depending on the number of tenants in
                `tenants` DataFrame,
            | **user_data_{tenant}:** one or more pandas DataFrames of user data
                depending on the number of tenants in `tenants` DataFrame, and
            | **item_data_{tenant}:** one or more pandas DataFrames of item data
                depending on the number of tenants in `tenants` DataFrame.
        :type data: dict
        :param query: One of 'mf' (collaborative filtering), 'partial' (content based
            filtering without text processing), or 'hybrid' (content based filtering
            with text processing). The data preparation/processing depends on this
            parameter, defaults to 'mf'
        :type query: str, optional
        :param num_threads: Number of parallel computation threads to use. Should not be
            higher than the number of physical cores, defaults to 2
        :type num_threads: int, optional
        """
        self.data = data
        self.query = query
        self.num_threads = num_threads
        self.cfg = Config()

    def train_models(self):
        """
        To train recommendation models after fetching and processing the data

        :return: A dictionary containing a dictionary for each tenant. Each tenant's
            dictionary has the recommendation model and some additional required data.
            A full list of keys and values in each tenant is

            | **msg:** either 'success' or useful message for failure,
            | **epochs:** a list of 'epochs' hyper-parameter while running optimization
                for hyper-parameters. The last one is the best one,
            | **n_components:** a list of 'n_components' hyper-parameter while running
                optimization for hyper-parameters. The last one is the best one,
            | **score:** a list of 'score' while running optimization for
                hyper-parameters. The last one is the best one,
            | **model:** LightFM trained model for this tenant,
            | **mappings:** a tuple of mappings (user id, user features, item id, item
                features),
            | **item_features:** a sparse matrix of item features data,
            | **user_features:** a sparse matrix of user features data,
            | **item_type_map:** a map between item ids and item types, and
            | **positive_interactions_map:** a map between user ids and list of items
                they have interacted with.
        :rtype: dict
        """
        data_processor = PrepareData(data=self.data, query=self.query)
        tenants = data_processor.get_tenants()
        models = {}
        for tenant in tenants:
            tenant_data = data_processor.get_tenant_data(tenant=tenant)
            if "msg" in tenant_data:
                models[tenant] = {"msg": tenant_data["msg"]}
            else:
                if self.query in ["hybrid", "partial"]:
                    item_alpha = self.cfg.get_property("item_alpha")
                    user_alpha = self.cfg.get_property("user_alpha")
                else:
                    item_alpha = 0.0
                    user_alpha = 0.0
                # --------------------------------------------------
                # We will optimize the 'epochs' and the latent dimension of the
                # user-item interaction matrix called the 'no_components'
                opt_obj = OptimizeHyperparams(
                    processed_data=tenant_data,
                    num_threads=self.num_threads,
                    user_alpha=user_alpha,
                    item_alpha=item_alpha,
                )
                epochs, comps, scores = opt_obj.run_optimization()
                # --------------------------------------------------
                # Train the final model with the optimum number of 'epochs' and the
                # 'no_components'
                model_obj = BuildModel(
                    processed_data=tenant_data,
                    num_threads=self.num_threads,
                    optimized_hyperparams={
                        "epochs": epochs[-1],
                        "no_components": comps[-1],
                    },
                    user_alpha=user_alpha,
                    item_alpha=item_alpha,
                )
                final_model = model_obj.build_model()
                models[tenant] = {
                    "msg": "success",
                    "epochs": epochs,
                    "n_components": comps,
                    "score": scores,
                    "model": final_model,
                    "mappings": tenant_data["mappings"],
                    "item_features": tenant_data["items_processed_data"],
                    "user_features": tenant_data["users_processed_data"],
                    "item_type_map": tenant_data["item_type_map"],
                    "positive_interactions_map": tenant_data["interactions"][
                        "positive_interactions_map"
                    ],
                }
        return models
