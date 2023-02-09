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
import random
import unittest
from scipy.sparse import coo_matrix, csr_matrix

from service.recommender.config import Config
from service.recommender.data_subroutines.data_loader import DataLoader
from service.tests.tests_recommender.generate_data import GenerateData


class TestDataLoader(unittest.TestCase):
    """
    This class is the test object to test units of the class `DataLoader`
    """

    def setUp(self) -> None:
        """
        Hook method for setting up the fixture before exercising it
        """
        data_obj = GenerateData()
        cfg = Config()
        self.interactions = data_obj.get_interactions()
        self.items_data = data_obj.get_items()
        self.users_data = data_obj.get_users()
        self.longMessage = False
        self.columns_required = cfg.get_property("item_types")

    def test_strings_to_cols(self) -> None:
        """
        This method tests if the `strings_to_cols` method of the `DataLoader` class
        returns the correct response
        """
        data_loader = DataLoader(query="hybrid")
        transformed_df = data_loader.strings_to_cols(data_frame=self.users_data)
        self.assertIn(
            member="competencies_scale",
            container=self.users_data.columns,
            msg=(
                "The column 'competencies_scale' is not found in the DataFrame which is"
                " the input of 'DataLoader.strings_to_cols' method"
            ),
        )
        self.assertNotIn(
            member="competencies_scale",
            container=transformed_df.columns,
            msg=(
                "The column 'competencies_scale' is still present in the DataFrame "
                "which is the response of 'DataLoader.strings_to_cols' method"
            ),
        )
        competency_cols = [col for col in transformed_df.columns if "competency" in col]
        self.assertTrue(
            expr=len(competency_cols) > 0,
            msg=(
                "There are no columns in the returned DataFrame from the "
                "'DataLoader.strings_to_cols' that are the result of expansion of the "
                "column 'competencies_scale'"
            ),
        )

    def test_get_items_attr(self) -> None:
        """
        This method tests if the `get_item_attr` method of the `DataLoader` class
        returns a dictionary, and has the same length as the item number.
        """
        data_loader = DataLoader(query="hybrid")
        test_items_df = self.items_data.drop(
            labels=self.items_data.columns.difference(self.columns_required), axis=1
        )
        computed_items_attr = data_loader.get_items_attr(dataframe=test_items_df)
        self.assertIsInstance(
            obj=computed_items_attr,
            cls=dict,
            msg=(
                f"The returned object is of type {type(computed_items_attr)} "
                "while it was expected to be of type <class 'dict'>"
            ),
        )
        self.assertEqual(
            first=test_items_df.shape[0],
            second=len(computed_items_attr),
            msg=(
                "The length of the response DataFrame from 'DataLoader.items_attr' is "
                f"{len(computed_items_attr)} while it was "
                f"expected to be {test_items_df.shape[0]}"
            ),
        )

    def test_items_csr(self) -> None:
        """
        This method test if the `items_csr` method of the `DataLoader` class behaves
        as expected with different combination of arguments
        """

        # Testing for `query = "mf"`
        data_loader = DataLoader(query="mf")
        computed_items_csr = data_loader.items_csr(items_data=self.items_data)

        self.assertIsInstance(
            obj=computed_items_csr,
            cls=dict,
            msg=(
                f"The returned object is of type {type(computed_items_csr)} "
                "while it was expected to be of type <class 'dict'>"
            ),
        )
        random_index = random.choice(self.items_data.index.tolist())
        computed_internal_id = computed_items_csr["item_map"][random_index]
        expected_internal_id = np.where(self.items_data.index == random_index)[0][0]
        self.assertEqual(
            first=computed_internal_id,
            second=expected_internal_id,
            msg=(
                f"The computed internal_id of item '{random_index}' is "
                f"'{computed_internal_id}' while it was expected to be "
                f"'{expected_internal_id}'"
            ),
        )

        random_item_row = self.items_data.loc[random_index, list(self.columns_required)]
        expected_item_type = random_item_row[random_item_row == 1].index.tolist()[0]
        computed_item_type = computed_items_csr["item_type_map"][random_index]
        self.assertEqual(
            first=computed_item_type,
            second=expected_item_type,
            msg=(
                f"The computed item type of item '{random_index}' is "
                f"'{computed_item_type}' while it was expected to be "
                f"'{expected_item_type}'"
            ),
        )

        self.assertIsNone(
            obj=computed_items_csr["item_features"],
            msg=(
                "The value of 'item_features' key of the response from "
                "'DataLoader.items_csr' for the argument query='mf' was expected to be "
                "None but it is not"
            ),
        )

        self.assertIsNone(
            obj=computed_items_csr["items_features_data"],
            msg=(
                "The value of 'items_features_data' key of the response from "
                "'DataLoader.items_csr' for the argument query='mf' was expected to be "
                "None but it is not"
            ),
        )

        # ---------------------------------------------------------------------
        # Testing for `query = "hybrid"`
        data_loader = DataLoader(query="hybrid")
        computed_items_csr = data_loader.items_csr(items_data=self.items_data)

        self.assertIsInstance(
            obj=computed_items_csr["item_features"],
            cls=list,
            msg=(
                "The value of the key 'item_features' of the response from "
                "'DataLoader.items_csr' is an instance of "
                f"{type(computed_items_csr['item_features'])} while it was expected to "
                "be of instance <class 'list'>"
            ),
        )

        self.assertIsInstance(
            obj=computed_items_csr["items_features_data"],
            cls=csr_matrix,
            msg=(
                "The value of the key 'items_features_data' of the response from "
                "'DataLoader.items_csr' is an instance of "
                f"{type(computed_items_csr['items_features_data'])} while it was "
                "expected to be of instance <class 'scipy.sparse.csr.csr_matrix'>"
            ),
        )

        self.assertEqual(
            first=computed_items_csr["items_features_data"].shape[0],
            second=self.items_data.shape[0],
            msg=(
                "The length of the value of key 'items_features_data' sparse matrix of "
                "the response from 'DataLoader.items_csr' is "
                f"{computed_items_csr['items_features_data'].shape[0]} while it was "
                f"expected to be {self.items_data.shape[0]}"
            ),
        )

    def test_users_csr(self) -> None:
        """
        This method test if the `users_csr` method of the `DataLoader` class behaves
        as expected with different combination of arguments
        """

        # Testing for `query = "mf"`
        data_loader = DataLoader(query="mf")
        computed_users_csr = data_loader.users_csr(users_data=self.users_data)

        self.assertIsInstance(
            obj=computed_users_csr,
            cls=dict,
            msg=(
                f"The returned object is of type {type(computed_users_csr)} "
                "while it was expected to be of type <class 'dict'>"
            ),
        )
        random_index = random.choice(self.users_data.index.tolist())
        computed_internal_id = computed_users_csr["user_map"][random_index]
        expected_internal_id = np.where(self.users_data.index == random_index)[0][0]
        self.assertEqual(
            first=computed_internal_id,
            second=expected_internal_id,
            msg=(
                f"The computed internal_id of user '{random_index}' is "
                f"'{computed_internal_id}' while it was expected to be "
                f"'{expected_internal_id}'"
            ),
        )

        self.assertIsNone(
            obj=computed_users_csr["user_features"],
            msg=(
                "The value of 'user_features' key of the response from "
                "'DataLoader.users_csr' for the argument query='mf' was expected to be "
                "None but it is not"
            ),
        )

        self.assertIsNone(
            obj=computed_users_csr["users_features_data"],
            msg=(
                "The value of 'users_features_data' key of the response from "
                "'DataLoader.users_csr' for the argument query='mf' was expected to be "
                "None but it is not"
            ),
        )

        # ----------------------------------------------------------------------
        # Testing for `query = "hybrid"
        data_loader = DataLoader(query="hybrid")
        computed_users_csr = data_loader.users_csr(users_data=self.users_data)

        self.assertIsInstance(
            obj=computed_users_csr["user_features"],
            cls=list,
            msg=(
                "The value of the key 'item_features' of the response from "
                "'DataLoader.items_csr' is an instance of "
                f"{type(computed_users_csr['user_features'])} while it was expected to "
                "be of instance <class 'list'>"
            ),
        )

        self.assertIsInstance(
            obj=computed_users_csr["users_features_data"],
            cls=csr_matrix,
            msg=(
                "The value of the key 'users_features_data' of the response from "
                "'DataLoader.users_csr' is an instance of "
                f"{type(computed_users_csr['users_features_data'])} while it was "
                "expected to be of instance <class 'scipy.sparse.csr.csr_matrix'>"
            ),
        )

        self.assertEqual(
            first=computed_users_csr["users_features_data"].shape[0],
            second=self.users_data.shape[0],
            msg=(
                "The length of the value of key 'users_features_data' sparse matrix of "
                "the response from 'DataLoader.users_csr' is "
                f"{computed_users_csr['users_features_data'].shape[0]} while it was "
                f"expected to be {self.users_data.shape[0]}"
            ),
        )

    def test_interactions_coo(self) -> None:
        """
        This method tests if the `interactions_coo` method of the `DataLoader` class
        returns the correct response object
        """
        item_map_df = self.items_data.copy()
        item_map_df["internal_id"] = np.arange(len(item_map_df))
        item_map = item_map_df.internal_id.to_dict()

        user_map_df = self.users_data.copy()
        user_map_df["internal_id"] = np.arange(len(user_map_df))
        user_map = user_map_df.internal_id.to_dict()

        data_loader = DataLoader(query="mf")
        computed_int_coo = data_loader.interactions_coo(
            interactions_df=self.interactions, user_map=user_map, item_map=item_map
        )

        self.assertIsInstance(
            obj=computed_int_coo,
            cls=dict,
            msg=(
                "The response from the 'DataLoader.interactions_coo' is an instance of "
                f"{type(computed_int_coo)} while this was expected to be <class 'dict'>"
            ),
        )

        self.assertIsInstance(
            obj=computed_int_coo["interactions"],
            cls=coo_matrix,
            msg=(
                "The value of the 'interactions' key of the response from the "
                "'DataLoader.interactions_coo' is an instance of "
                f"{type(computed_int_coo['interactions'])} while it was expected to be "
                "of instance <class 'scipy.sparse.csr.coo_matrix'>"
            ),
        )

        self.assertEqual(
            first=computed_int_coo["interactions"].shape,
            second=computed_int_coo["weights"].shape,
            msg=(
                "The shape of the 'interactions' sparse matrix from the "
                "'DataLoader.interactions_coo' method has a shape "
                f"{computed_int_coo['interactions'].shape} while the shape of the "
                f"'weights' sparse matrix is {computed_int_coo['weights'].shape}"
            ),
        )

        self.assertEqual(
            first=computed_int_coo["interactions"].shape,
            second=(len(set(self.users_data.index)), len(set(self.items_data.index))),
            msg=(
                "The shape of the 'interactions' sparse matrix from the "
                "'DataLoader.interactions_coo' method has a shape "
                f"{computed_int_coo['interactions'].shape} while it was expected "
                f"to be ({len(set(self.users_data.index))}, "
                f"{len(set(self.items_data.index))})"
            ),
        )

        self.assertIsInstance(
            obj=computed_int_coo["positive_interactions_map"],
            cls=dict,
            msg=(
                "The value of the 'positive_interactions_map' key of the response from "
                "the 'DataLoader.interactions_coo' is an instance of "
                f"{type(computed_int_coo['positive_interactions_map'])} while it was "
                "expected to be of instance <class 'dict'>"
            ),
        )

    def test_prepare_sparse_matrices(self) -> None:
        """
        This method tests if the `prepare_sparse_matrices` method of the `DataLoader`
        class returns the correct response
        """
        data_loader = DataLoader(query="mf")
        computed_response = data_loader.prepare_sparse_matrices(
            interactions_df=self.interactions,
            users_data=self.users_data,
            items_data=self.items_data,
        )

        self.assertIsInstance(
            obj=computed_response,
            cls=dict,
            msg=(
                "The returned response from 'DataLoader.prepare_sparse_matrices' is "
                f"an instance of {type(computed_response)} while it was expected to "
                "be an instance of <class 'dict'>"
            ),
        )

        self.assertIn(
            member="users_processed_data",
            container=computed_response,
            msg=(
                "The item with the key 'users_processed_data' is not found in the "
                "response from 'DataLoader.prepare_sparse_matrices'"
            ),
        )

        self.assertIn(
            member="items_processed_data",
            container=computed_response,
            msg=(
                "The item with the key 'items_processed_data' is not found in the "
                "response from 'DataLoader.prepare_sparse_matrices'"
            ),
        )

        self.assertIn(
            member="interactions",
            container=computed_response,
            msg=(
                "The item with the key 'interactions' is not found in the "
                "response from 'DataLoader.prepare_sparse_matrices'"
            ),
        )

    def test_interactions_coo_duplicates(self) -> None:
        """
        This method tests if the `interactions_coo` method of the `DataLoader` class
        returns the correct response if supplied interactions include duplicated records
        """
        item_map_df = self.items_data.copy()
        item_map_df["internal_id"] = np.arange(len(item_map_df))
        item_map = item_map_df.internal_id.to_dict()

        user_map_df = self.users_data.copy()
        user_map_df["internal_id"] = np.arange(len(user_map_df))
        user_map = user_map_df.internal_id.to_dict()

        duplicated_interactions = pd.concat(objs=[self.interactions, self.interactions])

        data_loader = DataLoader(query="mf")
        computed_int_coo = data_loader.interactions_coo(
            interactions_df=duplicated_interactions,
            user_map=user_map,
            item_map=item_map,
        )

        self.assertIsInstance(
            obj=computed_int_coo,
            cls=dict,
            msg=(
                "The response from the 'DataLoader.interactions_coo' is an instance of "
                f"{type(computed_int_coo)} while this was expected to be <class 'dict'>"
            ),
        )

        self.assertIsInstance(
            obj=computed_int_coo["interactions"],
            cls=coo_matrix,
            msg=(
                "The value of the 'interactions' key of the response from the "
                "'DataLoader.interactions_coo' is an instance of "
                f"{type(computed_int_coo['interactions'])} while it was expected to be "
                "of instance <class 'scipy.sparse.csr.coo_matrix'>"
            ),
        )

        self.assertEqual(
            first=computed_int_coo["interactions"].shape,
            second=computed_int_coo["weights"].shape,
            msg=(
                "The shape of the 'interactions' sparse matrix from the "
                "'DataLoader.interactions_coo' method has a shape "
                f"{computed_int_coo['interactions'].shape} while the shape of the "
                f"'weights' sparse matrix is {computed_int_coo['weights'].shape}"
            ),
        )

        self.assertEqual(
            first=computed_int_coo["interactions"].shape,
            second=(len(set(self.users_data.index)), len(set(self.items_data.index))),
            msg=(
                "The shape of the 'interactions' sparse matrix from the "
                "'DataLoader.interactions_coo' method has a shape "
                f"{computed_int_coo['interactions'].shape} while it was expected "
                f"to be ({len(set(self.users_data.index))}, "
                f"{len(set(self.items_data.index))})"
            ),
        )

        self.assertIsInstance(
            obj=computed_int_coo["positive_interactions_map"],
            cls=dict,
            msg=(
                "The value of the 'positive_interactions_map' key of the response from "
                "the 'DataLoader.interactions_coo' is an instance of "
                f"{type(computed_int_coo['positive_interactions_map'])} while it was "
                "expected to be of instance <class 'dict'>"
            ),
        )
