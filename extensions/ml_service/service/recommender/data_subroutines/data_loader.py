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
import pandas as pd
from scipy.sparse import csr_matrix, coo_matrix, hstack

from service.recommender.data_subroutines.text_encoder import TextEncoder
from service.recommender.config import Config


class DataLoader:
    """
    This is a conceptual representation of the process to read, preprocess and transform
    data that was exported by the Totara instance, so that the data is consumable by the
    LightFM model class.
    """

    def __init__(self, query="mf"):
        """
        Class constructor method

        :param query: One of 'mf' (collaborative filtering), 'partial' (content based
            filtering without text processing), or 'hybrid' (content based filtering
            with text processing). The data preparation/processing depends on this
            parameter, defaults to 'mf'
        :type query: str, optional
        """
        cfg = Config()
        self.query = query
        self.users_spread_hor = cfg.get_property("spread_hor")["users"]
        self.users_spread_dict = cfg.get_property("expand_dict")["users"]
        self.users_concat = cfg.get_property("concat")["users"]
        self.type_cols = cfg.get_property("item_types")
        self.words_length = cfg.get_property("words_length")
        self.max_categories = cfg.get_property("max_categories")["users"]

    def strings_to_cols(self, data_frame):
        """
        This method converts takes the columns in `users_spread_dict` class parameter
        from the input `data_frame` into multiple columns. The entries of each such
        column must be `|` separated and in the format key:value pairs. The names and
        number of the resultant columns depend on the unique strings of 'key' found in
        each column of the given data_frame instance.

        :param data_frame: A pandas DataFrame instance that has columns listed in
            `users_spread_dict` parameter of the instance and these have the format as
            mentioned above.
        :type data_frame: An instance of pandas DataFrame
        :return: An instance of pandas DataFrame where the columns listed in
            `users_spread_dict` have been replaced with multiple columns where header of
            each new column is an individual key and the values are the scale of that
            value for each user.
        :rtype: An instance of pandas DataFrame
        """
        for col in self.users_spread_dict:
            data_frame.loc[:, col] = data_frame[col].apply(
                lambda v: str(v) if not pd.isnull(v) else ""
            )
            list_of_lists = data_frame[col].str.split(pat="|")
            list_of_dicts = []
            for item in list_of_lists:
                if len(item) == 1 and item[0] == "":
                    row_dict = {}
                else:
                    row_dict = dict(w.split(sep=":", maxsplit=1) for w in item)
                list_of_dicts.append(row_dict)
            df_of_dicts = pd.DataFrame.from_records(
                data=list_of_dicts, index=data_frame.index
            )
            df_of_dicts.fillna(value=0, inplace=True)
            df_of_dicts = df_of_dicts.add_prefix(prefix=f"{col}_")
            data_frame = data_frame.join(other=df_of_dicts, how="left")
            data_frame.drop(labels=col, axis=1, inplace=True)

        return data_frame

    @staticmethod
    def get_items_attr(dataframe=None):
        """
        This method creates a map between the item_id and item_type from the input
        dataframe

        :param dataframe: A pandas DataFrame where row labels are item ids, column
            headers are item_types and the values in these columns are binary coded
            (0 or 1)
        :type dataframe: Pandas DataFrame object
        :return: A dictionary whose keys are item_id and the values are item_type
        :rtype: dict
        """
        dataframe_stacked = dataframe[dataframe == 1].stack().reset_index()
        item_type_map = pd.Series(
            dataframe_stacked.level_1.values, index=dataframe_stacked.item_id
        ).to_dict()
        return item_type_map

    def items_csr(self, items_data=None):
        """
        This method uses the pandas DataFrame of the items data exported from the Totara
        instance and transforms it in the form consumable by the LightFM model

        :param items_data: The data exported from the Totara instance
        :return: A dictionary containing four items;

            | **item_map:** a dictionary where the keys are Totara ids and the values are
                internal ids for LightFM model,
            | **item_type_map:** a dictionary where the keys are Totara ids and the values
                are item types,
            | **item_features:** a full list of all the possible features of the items
                data, and
            | **users_features_data:** a sparse matrix of items features.
        :rtype: dict
        """

        items_data["internal_id"] = np.arange(len(items_data))
        item_map = items_data.internal_id.to_dict()

        item_type_map = self.get_items_attr(dataframe=items_data[list(self.type_cols)])

        items_features_data = None
        item_features = None

        if self.query == "hybrid":
            text_docs = items_data.document.tolist()

            text_encoder = TextEncoder()
            embeddings_result = text_encoder.encode_documents(documents=text_docs)
            embeddings = embeddings_result["vectors"]
            features = embeddings_result["features"]

            text_csr = csr_matrix(embeddings, dtype=np.float32)

            items_data.drop(labels=["document"], axis=1, inplace=True)

            structured_items_csr = csr_matrix(items_data.values, dtype=np.float32)

            items_features_data = hstack(
                blocks=[structured_items_csr, text_csr], format="csr"
            )
            item_features = items_data.columns.tolist() + features

        elif self.query == "partial":
            items_data.drop(labels=["document"], axis=1, inplace=True)
            items_features_data = csr_matrix(items_data.values)

            item_features = items_data.columns.tolist()

        return {
            "item_map": item_map,
            "item_type_map": item_type_map,
            "item_features": item_features,
            "items_features_data": items_features_data,
        }

    def users_csr(self, users_data=None):
        """
        Uses the pandas DataFrame `users_data` and prepares user data in the required
        format for LightFM model

        :param users_data: The users data exported from the Totara instance
        :type users_data: A pandas DataFrame
        :return: A dictionary containing three items:

            | **user_map:** a dictionary where the keys are Totara ids and the values
                are internal ids for LightFM model,
            | **user_features:** a full list of all the possible features of the users
                data, and
            | **users_features_data:** a sparse matrix of user features.
        :rtype: dict
        """
        user_ids = users_data.index.tolist()
        user_map_zip = zip(user_ids, range(len(user_ids)))
        user_map = dict(user_map_zip)

        users_features_data = None
        user_features = None

        if self.query in ["partial", "hybrid"]:
            users_data = users_data[
                list(self.users_spread_hor)
                + list(self.users_spread_dict)
                + list(self.users_concat)
            ]
            users_data = users_data.copy()
            for col in self.users_spread_hor:
                users_data.loc[:, col] = users_data[col].apply(
                    lambda v: str(v) if not pd.isnull(v) else ""
                )
                col_wide_df = users_data[col].str.get_dummies(sep="|")
                if col_wide_df.shape[1] >= self.max_categories:
                    rows_sum = col_wide_df.sum()
                    sorted_columns = rows_sum.sort_values(ascending=False).index
                    col_wide_df = col_wide_df[sorted_columns[: self.max_categories]]
                users_data = users_data.join(
                    other=col_wide_df.add_prefix(prefix=f"{col}_"),
                    how="left",
                )
                users_data.drop(labels=col, axis=1, inplace=True)

            users_data = self.strings_to_cols(data_frame=users_data.copy())

        if self.query == "hybrid":
            users_data["document"] = users_data[list(self.users_concat)].apply(
                lambda row: " ".join(row.values.astype(str)), axis=1
            )

            text_docs = users_data.document.tolist()
            text_encoder = TextEncoder()
            embeddings_result = text_encoder.encode_documents(documents=text_docs)
            embeddings = embeddings_result["vectors"]
            features = embeddings_result["features"]

            text_csr = csr_matrix(embeddings, dtype=np.float32)

            users_data.drop(
                labels=list(self.users_concat) + ["document"], axis=1, inplace=True
            )

            structured_users_csr = csr_matrix(users_data.values, dtype=np.float32)

            users_features_data = hstack(
                blocks=[structured_users_csr, text_csr], format="csr"
            )
            user_features = users_data.columns.tolist() + features

        elif self.query == "partial":
            users_data.drop(labels=list(self.users_concat), axis=1, inplace=True)
            users_features_data = csr_matrix(users_data.values)

            user_features = users_data.columns.tolist()

        return {
            "user_map": user_map,
            "user_features": user_features,
            "users_features_data": users_features_data,
        }

    @staticmethod
    def interactions_coo(interactions_df, user_map, item_map):
        """
        To create sparse matrix of interactions between users and items along with
        other useful information. The indices of the sparse matrices are based on the
        user id and item id maps provided

        :param interactions_df: Dataframe containing the columns 'user_id', 'item_id',
            and 'rating'
        :type interactions_df: pandas DataFrame
        :param user_map: A map between the Totara user id and internal user id for
            LightFM model
        :type user_map: dict
        :param item_map: A map between the Totara item id and internal item id for
            LightFM model
        :type item_map: dict
        :return: A dictionary containing three objects;

            | **interactions:** the sparse matrix of interactions between the users and
                the items,
            | **weights:** the sparse matrix of weights or ratings that users gave to the
                items after interactions, and
            | **positive_interactions_map:** a dictionary where for each user as key, the
                value is a list of item ids that the user has interacted with.
        :rtype: dict
        """
        # Only keep the latest interactions and ratings
        interactions_df["timestamp"] = interactions_df.timestamp.astype(int)
        latest_records = interactions_df.groupby(
            ["user_id", "item_id"]
        ).timestamp.transform(max)
        interactions_df = interactions_df.loc[
            interactions_df.timestamp == latest_records
        ]

        interactions_df.drop_duplicates(
            subset=["user_id", "item_id"], keep="first", inplace=True
        )

        positive_interactions = interactions_df.copy()
        positive_interactions = positive_interactions[positive_interactions.rating == 1]
        users_interacted = positive_interactions.user_id.unique()
        positive_inter_map = dict(
            (
                u,
                positive_interactions[
                    positive_interactions.user_id == u
                ].item_id.tolist(),
            )
            for u in users_interacted
        )

        interactions_df["internal_user_id"] = interactions_df.user_id.apply(
            lambda x: user_map[x]
        )
        interactions_df["internal_item_id"] = interactions_df.item_id.apply(
            lambda x: item_map[x]
        )

        interactions_df["interacted"] = interactions_df.rating.map({0: 1, 1: 1})
        interactions_df = interactions_df[interactions_df.interacted == 1]

        interactions_coo = coo_matrix(
            (
                interactions_df.interacted,
                (interactions_df.internal_user_id, interactions_df.internal_item_id),
            ),
            shape=(len(user_map), len(item_map)),
        )

        interactions_df["rating"] = interactions_df["rating"] + 1

        weights_coo = coo_matrix(
            (
                interactions_df.rating,
                (interactions_df.internal_user_id, interactions_df.internal_item_id),
            ),
            shape=(len(user_map), len(item_map)),
        )

        return {
            "interactions": interactions_coo,
            "weights": weights_coo,
            "positive_interactions_map": positive_inter_map,
        }

    def prepare_sparse_matrices(
        self, interactions_df=None, users_data=None, items_data=None
    ):
        """
        To create sparse matrices of interactions, user features, item features and
        other useful information

        :param interactions_df: Dataframe containing the columns 'user_id', 'item_id',
            and 'rating'
        :type interactions_df: pandas DataFrame
        :param users_data: The users data exported from the Totara instance
        :type users_data: A pandas DataFrame
        :param items_data: The data exported from the Totara instance
        :type items_data: A pandas DataFrame
        :return: A dictionary containing sparse matrices form of user and item features
            and interactions datasets along with some useful maps. It contains the
            following values:

            | **users_processed_data:** a sparse matrix of user features,
            | **item_processed_data:** a sparse matrix of item feature,
            | **interactions:** a dictionary containing three objects:

                | **interactions:** the sparse matrix of interactions between the users
                    and the items,
                | **weights:** the sparse matrix of weights or ratings that users gave to
                    the items after interactions, and
                | **positive_interactions_map:** a dictionary where for each user as key,
                    the value is a list of item ids that the user has interacted with.

            | **mappings:** a tuple of four objects which are:

                | **0:** user map, a dictionary,
                | **1:** user features, a list of feature labels,
                | **2:** item map, a dictionary, and
                | **3:** item features, a list of feature labels.

            | **item_type_map:** a dictionary of item types.
        :rtype: dict
        """
        users_processed_data = self.users_csr(users_data=users_data)
        items_processed_data = self.items_csr(items_data=items_data)
        interactions = self.interactions_coo(
            interactions_df=interactions_df,
            user_map=users_processed_data["user_map"],
            item_map=items_processed_data["item_map"],
        )

        mappings = (
            users_processed_data["user_map"],
            users_processed_data["user_features"],
            items_processed_data["item_map"],
            items_processed_data["item_features"],
        )

        return {
            "users_processed_data": users_processed_data["users_features_data"],
            "items_processed_data": items_processed_data["items_features_data"],
            "interactions": interactions,
            "mappings": mappings,
            "item_type_map": items_processed_data["item_type_map"],
        }
