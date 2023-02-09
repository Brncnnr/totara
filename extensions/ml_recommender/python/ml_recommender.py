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

import os
import time
import warnings

from config import Config
from subroutines.arg_parser import ArgParser
from subroutines.build_model import BuildModel
from subroutines.data_loader import DataLoader
from subroutines.data_reader import DataReader
from subroutines.optimize_hyperparams import OptimizeHyperparams
from subroutines.remove_external_interactions import RemoveExternalInteractions
from subroutines.similar_items import SimilarItems
from subroutines.user_to_items import UserToItems


def run_modelling_process():
    """
    This function takes the arguments supplied by the command line and
    runs for each tenant the data processing, machine learning model training
    and produces both the item recommendations for users and similar item
    recommendations.
    :return: None
    """
    # ---------------------------------------------------------------
    # Notify that process has been deprecated
    warnings.simplefilter("default")
    warnings.warn("ml_recommender has been deprecated \n", DeprecationWarning)
    # ---------------------------------------------------------------
    # Instantiate the configuration object
    cfg = Config()
    # ---------------------------------------------------------------
    # Set up command line arguments
    args = ArgParser().set_args().parse_args()

    # Set runtime variables from arguments.
    query = args.query
    data_home = args.data_path
    num_threads = args.threads
    user_result_count = args.result_count_user
    item_result_count = args.result_count_item

    # -------------------------------------------------------
    # Get list of the tenants
    data_reader = DataReader(data_home=data_home)
    tenants = data_reader.read_tenants().tenants.tolist()
    if len(tenants) == 0:
        tenants = [0]
    # ------------------------------------------------------
    # Check if the ML process has already started via the process control file
    # 'ml_started', exit the process if this had already started or crashed after
    # starting. Create the process control file 'ml_started' if this does not exist
    # already.
    started_file_path = os.path.join(data_home, "ml_started")
    if os.path.exists(started_file_path):
        print(
            "Processing already started. If it was crashed before and need restart, "
            f"re-export data or delete {started_file_path}"
        )
        exit()

    with open(file=started_file_path, mode="w") as writer:
        writer.write(f"{time.time(): 0.0f}")
    # -------------------------------------------------------
    # Loop through the list of tenants
    for tenant in tenants:
        print(f"Loading and processing/transforming data of tenant {tenant}")
        i2i_file_path = os.path.join(data_home, f"i2i_{tenant}.csv")
        i2u_file_path = os.path.join(data_home, f"i2u_{tenant}.csv")
        # Process the tenant's data from data_home directory and read into the memory
        d_loader = DataLoader(
            users_spread_hor=cfg.get_property("spread_hor")["users"],
            users_expand_dict=cfg.get_property("expand_dict")["users"],
            users_concat=cfg.get_property("concat")["users"],
            tenant=tenant,
            data_home=data_home,
        )
        t1 = time.time()
        interactions_df = data_reader.read_interactions_file(tenant=tenant)
        items_data = data_reader.read_items_file(tenant=tenant)
        users_data = data_reader.read_users_file(tenant=tenant)
        # Remove interactions by the users and items that are not in the current tenant
        interactions_cleaner = RemoveExternalInteractions(
            users_df=users_data, items_df=items_data, interactions_df=interactions_df
        )
        interactions_df = interactions_cleaner.clean_interactions()
        shape = (users_data.shape[0], items_data.shape[0])
        min_data = cfg.get_property("min_data")

        if shape[0] < min_data["min_users"] or shape[1] < min_data["min_items"]:
            print(
                "The number of users or items is too small to run the recommendation"
                f" engine. Skipping tenant {tenant}."
            )

            with open(file=i2i_file_path, mode="w") as f:
                f.write("target_iid,similar_iid,ranking")

            with open(file=i2u_file_path, mode="w") as f:
                f.write("uid,iid,ranking")

        else:
            processed_data = d_loader.load_data(
                interactions_df=interactions_df,
                items_data=items_data,
                users_data=users_data,
                query=query,
            )
            m, s = divmod((time.time() - t1), 60)
            print(
                f"The data loading and processing/transformation of tenant {tenant}"
                f" took {m: .0f} minutes and {s: .2f} seconds.\n"
            )
            if query in ["hybrid", "partial"]:
                item_alpha = cfg.get_property("item_alpha")
                user_alpha = cfg.get_property("user_alpha")
            else:
                item_alpha = 0.0
                user_alpha = 0.0
            # --------------------------------------------------
            # We will optimize the 'epochs' and the latent dimension of the user-item
            # interaction matrix called the 'no_components'
            opt_obj = OptimizeHyperparams(
                interactions=processed_data["interactions"],
                user_features=processed_data["user_features"],
                item_features=processed_data["item_features"],
                weights=processed_data["weights"],
                num_threads=num_threads,
                user_alpha=user_alpha,
                item_alpha=item_alpha,
            )
            t2 = time.time()
            epochs, comps, scores = opt_obj.run_optimization(lr=1000)

            m, s = divmod((time.time() - t2), 60)
            print(
                f"The hyper-parameters optimization of the model for tenant {tenant}"
                f" took\n{m: .0f} minutes and {s: .2f} seconds to converge.\n"
            )
            print(
                f"The best hyper-parameters found for tenant {tenant}:\n"
                f"   epochs: {epochs[-1]}\n"
                f"   n_components: {comps[-1]}\n"
                f"   The best score: {scores[-1]: .3f}\n"
            )
            # --------------------------------------------------
            # Train the final model with the optimum number of 'epochs' and the
            # 'no_components'
            print(f"Training final model for tenant {tenant}.\n")
            model_obj = BuildModel(
                interactions=processed_data["interactions"],
                weights=processed_data["weights"],
                user_features=processed_data["user_features"],
                item_features=processed_data["item_features"],
                num_threads=num_threads,
                optimized_hyperparams={
                    "epochs": epochs[-1],
                    "no_components": comps[-1],
                },
                user_alpha=user_alpha,
                item_alpha=item_alpha,
            )
            final_model = model_obj.build_model()
            # --------------------------------------------------
            # Items to item (I2I) recommendations.
            print(f"Making I2I recommendations for tenant {tenant}")
            item_representations = final_model.get_item_representations(
                features=processed_data["item_features"]
            )[1]
            similar_items = SimilarItems(
                item_mapping=processed_data["mapping"][2],
                item_representations=item_representations,
                num_items=item_result_count,
            ).all_items()
            similar_items.to_csv(
                path_or_buf=i2i_file_path, sep=",", float_format="%.12f", index=False
            )

            print(f"Making I2U recommendations for tenant {tenant}")
            # -----------------------------------------------------
            # Items to user (I2U) recommendations.
            might_like_items = UserToItems(
                u_mapping=processed_data["mapping"][0],
                i_mapping=processed_data["mapping"][2],
                item_type_map=processed_data["item_type_map"],
                user_features=processed_data["user_features"],
                item_features=processed_data["item_features"],
                positive_inter_map=processed_data["positive_inter_map"],
                model=final_model,
                num_items=user_result_count,
                num_threads=num_threads,
            ).all_items()

            might_like_items.to_csv(
                path_or_buf=i2u_file_path, sep=",", float_format="%.12f", index=False
            )
    # --------------------------------------------------------
    # Write the process control file 'ml_completed'
    with open(file=os.path.join(data_home, "ml_completed"), mode="w") as writer:
        writer.write(f"{time.time(): 0.0f}")
    print("Done\n\n")


if __name__ == "__main__":
    run_modelling_process()
