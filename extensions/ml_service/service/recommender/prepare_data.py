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

from service.recommender.data_subroutines.data_loader import DataLoader
from service.recommender.data_subroutines.remove_external_interactions import (
    RemoveExternalInteractions,
)
from service.recommender.config import Config


class PrepareData:
    """
    To prepare the data for training recommendations model
    """

    def __init__(self, data=None, query="mf"):
        """
        Class constructor method

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
        :param query: One of hybrid, partial or mf
        :type query: str
        """
        self.data = data
        self.query = query
        self.cfg = Config()

    def get_tenants(self):
        """
        To get a list of tenants from the data['tenants'] DataFrame

        :return: a list of tenant's names
        :rtype: list
        """
        tenants_df = self.data["tenants"]
        tenants_list = tenants_df.tenants.tolist()
        if len(tenants_list) == 0:
            tenants_list = [0]
        return tenants_list

    def get_tenant_data(self, tenant):
        """
        To get processed (required sparse matrices for LightFM and id maps) for the
        given tenant

        :param tenant: The name of the tenant whose data needs to be processed
        :type tenant: str
        :return: A dictionary of the processed data
        :rtype: dict
        """
        d_loader = DataLoader(query=self.query)

        interactions_df = self.data[f"user_interactions_{tenant}"]
        items_data = self.data[f"item_data_{tenant}"]
        users_data = self.data[f"user_data_{tenant}"]
        # Remove interactions by the users and items that are not in the current tenant
        interactions_cleaner = RemoveExternalInteractions(
            users_df=users_data, items_df=items_data, interactions_df=interactions_df
        )
        interactions_df = interactions_cleaner.clean_interactions()
        shape = (users_data.shape[0], items_data.shape[0])
        min_data = self.cfg.get_property("min_data")

        if shape[0] < min_data["min_users"] or shape[1] < min_data["min_items"]:
            processed_data = {
                "msg": (
                    "The number of users or items is too small to run the "
                    f"recommendation engine. Skipping tenant {tenant}"
                )
            }
        else:
            processed_data = d_loader.prepare_sparse_matrices(
                interactions_df=interactions_df,
                items_data=items_data,
                users_data=users_data,
            )
        return processed_data
