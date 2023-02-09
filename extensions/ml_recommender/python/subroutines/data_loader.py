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

import json
import os
import numpy as np
import pandas as pd
from lightfm.data import Dataset
from scipy.sparse import csr_matrix
from sklearn.feature_extraction.text import TfidfVectorizer

from subroutines.pre_processors import PreProcessors


class DataLoader:
    """
    This is a conceptual representation of the process to read, preprocess and transform
    data that was exported by the Totara instance, so that the data is consumable by the
    LightFM model class.
    """

    def __init__(
        self,
        users_spread_hor=("assignments",),
        users_expand_dict=("competencies_scale",),
        users_concat=("description",),
        tenant="0",
        data_home="",
    ):
        """
        Class constructor method
        :param users_spread_hor: Columns from the users provided data that will need to
            be spread horizontally
        :type users_spread_hor: tuple
        :param users_expand_dict: Columns from the users data that are strings of
            key-value pairs in the form `key_1:val_1|key_2:val_2|...|key_n:val_n` form
            and need expansion into multiple columns
        :type users_expand_dict: tuple
        :param users_concat: Columns from the users data that need to be concatenated to
            create a documents for text processing
        :type users_concat: tuple
        :param tenant: The tenant whose data is to be processed and transformed
        :type tenant: str
        :param data_home: The directory where the data files of the Recommender engine
            are found and saved
        :type data_home: PosixPath object
        """
        self.users_spread_hor = users_spread_hor
        self.users_spread_dict = users_expand_dict
        self.users_concat = users_concat
        self.tenant = tenant
        self.data_home = data_home

    @staticmethod
    def __get_interactions(interactions_df=None):
        """
        This method uses the `interactions_df` DataFrame and returns a tuple object
        composed of interactions (a list) and positive_inter_map (a dictionary with
        user-to-items information).
        :param interactions_df: The interactions data as exported from the Totara
            instance
        :type interactions_df: An instance of pandas DataFrame
        :return: A tuple where the first element is a list of tuples (user_id, item_id,
            weight) containing user-item interactions, and the second element is a
            dictionary whose keys are the Totara user ids and values are the lists
            containing the Totara item ids that user has interacted with in the past.
            Note that this dictionary only contains the Totara user ids of those users
            who have had at one interaction with an item in the past
        :rtype: tuple
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

        positive_interactions = interactions_df[interactions_df.rating == 1]
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
        interactions = [
            (int(x[0]), x[1], float(x[2])) for x in interactions_df.to_numpy()
        ]
        return interactions, positive_inter_map

    @staticmethod
    def __create_feature_dict(row):
        """
        Converts a pandas series into a dictionary whose keys are series' indices and
        the values are series' values. Removes entries where the values are not 1
        :param row: One record from the pandas DataFrame of items features
        :type row: Pandas Series object
        :return: A dictionary whose keys are series' indices and the values are series'
            values
        """
        nonzero_row = row[row == 1]
        features = dict(zip(nonzero_row.index.tolist(), nonzero_row.tolist()))
        return features

    def __create_partial_features(self, dataframe=None):
        """
        This method prepares a list of tags `(item_id, [tag1, tag2, tag3, ...])`; by
        adding only those tags to each item where that tag (column header) had a value 1
        in the pandas DataFrame
        :param dataframe: A pandas DataFrame where row labels are item ids and column
            headers are tags. The values of tags for each item can be 0 or 1
        :type dataframe: pandas DataFrame
        :return: list of tuples where each tuple if of the shape `(item_id, [tag1, tag2,
            tag3, ...])`
        :rtype: list
        """
        dataframe["features"] = dataframe.apply(
            lambda row: self.__create_feature_dict(row), axis=1
        )
        features_zip = zip(dataframe.index.tolist(), dataframe.features.tolist())
        return list(features_zip)

    def __strings_to_cols(self, data_frame):
        """
        This static method converts the `competencies_scale` column of the input
        `data_frame` into multiple columns. The entries of the `competencies_scale`
        column must be `|` separated and in the format competency:scale pairs. The names
        and number of these columns depend on the unique competency strings found in the
        `competencies_scale` column of the given data_frame instance.
        :param data_frame: A pandas DataFrame instance that has a column called
        `competencies_scale` and it has the format as mentioned above.
        :type data_frame: An instance of pandas DataFrame
        :return: An instance of pandas DataFrame where the `competencies_scale` column
            has been replaced with multiple columns where each column's header is an
            individual competency and the values are the scale of that competency for
            each user.
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
    def __get_items_attr(dataframe=None):
        """
        This method creates a map between the item_id and item_type from the input
        dataframe
        :param dataframe: A pandas DataFrame where row labels are item ids, column
            headers are item_types and the values in these columns are binary coded
            (0 or 1)
        :type dataframe: Pandas DataFrame object
        :return: A dictionary whose keys are item_id and the values are item_type
        """
        dataframe_stacked = dataframe[dataframe == 1].stack().reset_index()
        item_type_map = pd.Series(
            dataframe_stacked.level_1.values, index=dataframe_stacked.item_id
        ).to_dict()
        return item_type_map

    @staticmethod
    def docs_empty(docs: list) -> bool:
        """
        To check if the list of documents have no content

        :param docs: The list of documents to be checked
        :type docs: list
        :return: Whether the documents have no content
        :rtype: bool
        """
        if all("" == s or s.isspace() for s in docs):
            return True

        return False

    def __get_users(self, users_data=None, query="mf"):
        """
        Uses the pandas DataFrame `users_data` and returns a list of user ids
        :param users_data: The users data exported from the Totara instance
        :type users_data: A pandas DataFrame
        :param query: One of 'mf' (collaborative filtering), 'partial' (content based
            filtering without text processing), or 'hybrid' (content based filtering
            with text processing). The data preparation/processing depends on this
            parameter, defaults to 'mf'
        :type query: str, optional
        :return: A dictionary containing three items; 'features_list' - a full list of
            all the possible features of the users data, 'users_features_data' - A list
            containing tuples of the shape `(user_id, {features_name: weight, ...})`,
            and `user_ids` - a list of user ids
        :rtype: dict
        """
        user_ids = users_data.index.tolist()
        users_features_data = None
        features_list = None

        if query in ["partial", "hybrid"]:
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
                users_data = users_data.join(
                    other=col_wide_df.add_prefix(prefix=f"{col}_"),
                    how="left",
                )
                users_data.drop(labels=col, axis=1, inplace=True)

            users_data = self.__strings_to_cols(data_frame=users_data.copy())
            # -----------------------------------------------------------------
            # Create a list of features from the headers (other than `city_town` and
            # `description`) of the `users_data` DataFrame where each element of this
            # list is a tuple of the shape
            # (user_id, {feature_1: weight1, feature2: weight2, ...}), where weight1,
            # weight2, etc are all 1's, i.e., only the features/tags that have values as
            # 1 will appear in this dictionary for all users.
            users_features_data = self.__create_partial_features(
                users_data.drop(columns=list(self.users_concat))
            )
            features_list = users_data.columns.tolist()
            for col in self.users_concat:
                features_list.remove(col)

            if query == "hybrid":
                users_data["document"] = users_data[list(self.users_concat)].apply(
                    lambda row: " ".join(row.values.astype(str)), axis=1
                )
                users_data.drop(labels=list(self.users_concat), axis=1, inplace=True)
                text_preprocessor = PreProcessors()
                processed_document = []
                for doc in users_data.document:
                    # Cleanup the document and remove stopwords from it using the
                    # Preprocessors class
                    new_doc = text_preprocessor.preprocess_docs(raw_doc=doc)
                    processed_document.append(new_doc)

                # Remove the models object from memory
                del text_preprocessor

                # Convert the list of documents into a matrix of TF-IDF features
                if self.docs_empty(docs=processed_document):
                    transformed_doc = csr_matrix(
                        (len(processed_document), 0), dtype=np.float32
                    )
                    features_names = []
                else:
                    tf = TfidfVectorizer()
                    transformed_doc = tf.fit_transform(processed_document)
                    features_names = tf.get_feature_names()

                # --------------------------------------------------------------
                # Convert the matrix of TF-IDF features into a list where each row of
                # the matrix is transformed into an element of the list. This element is
                # a dictionary where the keys are the feature names (words from the
                # cleaned document) and each value is TF-IDF value of that word.
                text_features = []
                for item in range(transformed_doc.shape[0]):
                    indices = transformed_doc[item, :].indices
                    features = {}
                    if len(indices) > 0:
                        for index in indices:
                            features[features_names[index]] = transformed_doc[
                                item, index
                            ]
                    text_features.append(features)
                # -------------------------------------------------------------------
                # Append the two sets of features (text features and the other
                # features/tags) together so that the resultant shape of the list stays
                # as mentioned for the list `partial_features`
                users_features_data = [
                    (item[0][0], {**item[0][1], **item[1]})
                    for item in zip(users_features_data, text_features)
                ]
                # ---------------------------------------------------------------------
                # Create a list of all the feature names (tags and words)
                features_list = features_list + features_names
        users_processed_data = {
            "users_features_data": users_features_data,
            "features_list": features_list,
            "user_ids": user_ids,
        }

        with open(
            file=os.path.join(self.data_home, f"processed_user_data_{self.tenant}.txt"),
            mode="w",
        ) as writer:
            writer.write(json.dumps(users_features_data))

        return users_processed_data

    def __get_items(self, items_data=None, query="mf"):
        """
        This method uses the pandas DataFrame of the items data exported from the totara
        instance and returns a fully processed items data. The processing depends on the
        type of `query` defined in the instance variable of the class, defaults to None
        :param items_data: The data exported from the Totara instance
        :type items_data: A pandas DataFrame
        :param query: One of 'mf' (collaborative filtering), 'partial' (content based
            filtering without text processing), or 'hybrid' (content based filtering
            with text processing). The data preparation/processing depends on this
            parameter, defaults to 'mf'
        :type query: str, optional
        :return: A dictionary containing four items; 'features_list' - a full list of
            all the possible features of the items data, 'items_features_data' - A list
            containing tuples of the shape `(item_id, {features_name: weight, ...})`,
            `item_ids` - a list of item ids and `item_type_map` - a dictionary with keys
            as the `item_id` and values as `item_type`
        """

        item_ids = items_data.index.tolist()
        type_cols = [
            "container_course",
            "container_workspace",
            "engage_article",
            "engage_microlearning",
            "totara_playlist",
        ]

        item_type_map = self.__get_items_attr(dataframe=items_data[type_cols])

        if query == "hybrid":
            text_preprocessor = PreProcessors()
            processed_document = []
            for doc in items_data.document:
                # Cleanup the document and remove stopwords from it using the
                # Preprocessors class

                new_doc = text_preprocessor.preprocess_docs(raw_doc=doc)
                processed_document.append(new_doc)

            # Remove the models object from memory
            del text_preprocessor

            # Convert the list of documents into a matrix of TF-IDF features
            if self.docs_empty(docs=processed_document):
                transformed_doc = csr_matrix(
                    (len(processed_document), 0), dtype=np.float32
                )
                features_list = []
            else:
                tf = TfidfVectorizer()
                transformed_doc = tf.fit_transform(processed_document)
                features_list = tf.get_feature_names()

            # --------------------------------------------------------------
            # Convert the matrix of TF-IDF features into a list where each row of the
            # matrix is transformed into an element of the list. This element is a
            # dictionary where the keys are the feature names (words from the cleaned
            # document) and each value is TF-IDF value of that word.
            text_features = []
            for item in range(transformed_doc.shape[0]):
                indices = transformed_doc[item, :].indices
                features = {}
                if len(indices) > 0:
                    for index in indices:
                        features[features_list[index]] = transformed_doc[item, index]
                text_features.append(features)
            # -----------------------------------------------------------------
            # Create a list of features from the rest of the headers of the `items_data`
            # DataFrame where each element of this list is a tuple of the shape
            # (item_id, {feature_1: weight1, feature2: weight2, ...}), where weight1,
            # weight2, etc are all 1's, i.e., only the features/tags that have values as
            # 1 will appear in this dictionary for all items.
            partial_features = self.__create_partial_features(
                items_data.drop(columns=["document"])
            )
            # -------------------------------------------------------------------
            # Append the two sets of features (text features and the other
            # features/tags) together so that the resultant shape of the list stays as
            # mentioned for the list `partial_features`
            items_features_data = [
                (item[0][0], {**item[0][1], **item[1]})
                for item in zip(partial_features, text_features)
            ]
            # ---------------------------------------------------------------------
            # Create a list of all the feature names (tags and words)
            features_list = items_data.columns.tolist() + features_list
            features_list.remove("document")
        elif query == "partial":
            # As for the case of `query == 'hybrid'` except there are no text features
            items_features_data = self.__create_partial_features(
                items_data.drop(columns=["document"])
            )
            # List of features names (tags)
            features_list = items_data.columns.tolist()
            features_list.remove("document")
        else:
            # No item features for `query == 'mf'` or pure collaborative filtering
            items_features_data = None
            features_list = None

        items_processed_data = {
            "items_features_data": items_features_data,
            "features_list": features_list,
            "item_ids": item_ids,
            "item_type_map": item_type_map,
        }

        with open(
            file=os.path.join(self.data_home, f"processed_item_data_{self.tenant}.txt"),
            mode="w",
        ) as writer:
            writer.write(json.dumps(items_features_data))

        return items_processed_data

    def __transform_data(
        self, interactions_df=None, items_data=None, users_data=None, query="mf"
    ):
        """
        This method governs the process of reading the interactions data, items data and
        the users data as defined in the class instance variables
        :param interactions_df: The interactions data as exported from the Totara
            instance
        :type interactions_df: A pandas DataFrame
        :param items_data: The data exported from the Totara instance
        :type items_data: A pandas DataFrame
        :param users_data: The users data exported from the Totara instance
        :type users_data: A pandas DataFrame
        :param query: One of 'mf' (collaborative filtering), 'partial' (content based
            filtering without text processing), or 'hybrid' (content based filtering
            with text processing). The data preparation/processing depends on this
            parameter, defaults to 'mf'
        :type query: str, optional
        :return: A dictionary of four items; 'interaction' - the interactions data,
            `positive_inter_map` - the users-to-items_list map (where positive
            interactions happened), 'items_data' - the items data, and the 'user_data'
            - the user data.
        """

        interactions, positive_inter_map = self.__get_interactions(
            interactions_df=interactions_df
        )
        items_data = self.__get_items(items_data=items_data, query=query)
        users_data = self.__get_users(users_data=users_data, query=query)

        processed_data = {
            "interactions": interactions,
            "positive_inter_map": positive_inter_map,
            "items_data": items_data,
            "users_data": users_data,
        }
        return processed_data

    def load_data(
        self, interactions_df=None, items_data=None, users_data=None, query="mf"
    ):
        """
        This method takes reads runs other methods of the class to read and preprocess
        interactions, items and users data and transforms that into the sparse matrices
        that can be consumed by the LightFM model class.
        :param interactions_df: The interactions data as exported from the Totara
            instance
        :type interactions_df: A pandas DataFrame
        :param items_data: The data exported from the Totara instance
        :type items_data: A pandas DataFrame
        :param users_data: The users data exported from the Totara instance
        :type users_data: A pandas DataFrame
        :param query: One of 'mf' (collaborative filtering), 'partial' (content based
            filtering without text processing), or 'hybrid' (content based filtering
            with text processing). The data preparation/processing depends on this
            parameter, defaults to 'mf'
        :type query: str, optional
        :return: A dictionary with the items; `interactions` - a sparse matrix of
            user-item interaction, `weights` - a sparse matrix of of sample weights of
            the same shape as the `interactions`, `item_features` - a sparse matrix of
            the shape `[n_items, n_features]` where each row contains item's weights
            over features, `user_features` - a sparse matrix of the shape
            `[n_users, n_features]` where each row contains user's weights over
            features, `mapping` - a tuple of four dictionaries (user id map,
            user features map, item id map, item feature map), `item_type_map` - a
            dictionary with keys as the `item_id` and values as `item_type`, and
            `positive_inter_map` - a dictionary where keys are the Totara user ids (of
            the users who interacted with at least one item) and values are lists of the
            Totara item ids
        """
        # Read all datasets, preprocess and transform data to be consumed by the LightFM
        # data class
        transformed_data = self.__transform_data(
            interactions_df=interactions_df,
            items_data=items_data,
            users_data=users_data,
            query=query,
        )
        # Instantiate Dataset class
        dataset = Dataset(user_identity_features=False, item_identity_features=False)

        # Use fit method of the Dataset class to setup the user/item id and feature name
        # mappings.
        dataset.fit(
            users=transformed_data["users_data"]["user_ids"],
            items=transformed_data["items_data"]["item_ids"],
            user_features=transformed_data["users_data"]["features_list"],
            item_features=transformed_data["items_data"]["features_list"],
        )

        # Prepare the interaction and weights sparse matrices
        interactions, weights = dataset.build_interactions(
            data=transformed_data["interactions"]
        )

        if query in ["partial", "hybrid"]:
            # Prepare the user item features sparse matrix if the admin user is not
            # asking for content based filtering
            user_features = dataset.build_user_features(
                data=transformed_data["users_data"]["users_features_data"]
            )
            item_features = dataset.build_item_features(
                data=transformed_data["items_data"]["items_features_data"]
            )
        else:
            # No user and item features matrix if the admin user wants only the
            # collaborative filtering
            user_features = None
            item_features = None

        results = {
            "interactions": interactions,
            "weights": weights,
            "user_features": user_features,
            "item_features": item_features,
            "mapping": dataset.mapping(),
            "item_type_map": transformed_data["items_data"]["item_type_map"],
            "positive_inter_map": transformed_data["positive_inter_map"],
        }
        return results
