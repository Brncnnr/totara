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
import re
from scipy.sparse import csr_matrix
import unicodedata
from nltk.corpus import stopwords
from sklearn.feature_extraction.text import TfidfVectorizer


class TextEncoder:
    """
    For free text vectorization after cleaning
    """

    def __init__(self):
        """
        Class constructor method
        """
        self.stopwords_list = set()
        for lang in stopwords.fileids():
            self.stopwords_list = self.stopwords_list.union(set(stopwords.words(lang)))

    @staticmethod
    def remove_url(in_doc: str) -> str:
        """
        This removes URL links from the text

        :param in_doc: Free text
        :type in_doc: str
        :return: Text after removing URL's
        :rtype: str
        """
        return re.sub(pattern=r"http\S+", repl="", string=in_doc)

    @staticmethod
    def remove_emails(in_doc: str) -> str:
        """
        This removes email addresses from the free text

        :param in_doc: Free text
        :type in_doc: str
        :return: Text after removing email addresses
        :rtype: str
        """
        return re.sub(pattern=r"\S*@\S*\s?", repl="", string=in_doc)

    @staticmethod
    def remove_non_ascii(in_doc: str) -> str:
        """
        This method removes all non ascii characters from the text

        :param in_doc: Free text
        :type in_doc: str
        :return: Text after removing the non ascii characters
        :rtype: str
        """
        normal_doc = unicodedata.normalize("NFKD", in_doc)
        encoded_doc = normal_doc.encode(encoding="ascii", errors="ignore")
        decoded_doc = encoded_doc.decode("utf-8", "ignore")
        return decoded_doc

    @staticmethod
    def remove_punctuations(in_doc: str) -> str:
        """
        This method removes all the punctuations from the text

        :param in_doc: Free text
        :type in_doc: str
        :return: Text after removing punctuations
        :rtype: str
        """
        return re.sub(pattern=r"[^\w\s]", repl="", string=in_doc)

    @staticmethod
    def remove_digits(in_doc: str) -> str:
        """
        To remove numerical characters from the free text

        :param in_doc: Free text
        :type in_doc: str
        :return: Text after removing the numerical characters
        :rtype: str
        """
        return "".join([c for c in in_doc if not c.isdigit()])

    def remove_stopwords(self, in_doc: str) -> str:
        """
        To remove stop words from text

        :param in_doc: Free text
        :type in_doc: str
        :return: Text after removing stop words
        :rtype: str
        """
        words_list = in_doc.split(sep=" ")
        return " ".join(
            [word for word in words_list if word not in self.stopwords_list]
        )

    def clean_text(self, doc: str) -> str:
        """
        This method applies text cleaning tools on the free text and returns a cleaned
        text

        :param doc: Free text
        :type doc: str
        :return: Text after passing it through multiple text cleaning tools
        :rtype: str
        """
        doc = self.remove_url(in_doc=doc)
        doc = self.remove_emails(in_doc=doc)
        doc = self.remove_non_ascii(in_doc=doc)
        doc = self.remove_punctuations(in_doc=doc)
        doc = self.remove_digits(in_doc=doc)

        # Replace multiple white spaces with single white space
        doc = re.sub(pattern=r" +", repl=" ", string=doc)

        # Convert text to lower case
        doc = doc.lower()

        doc = self.remove_stopwords(in_doc=doc)
        return doc

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

    def encode_documents(self, documents) -> dict:
        """
        This method cleans the free text and then vectorize into a matrix using TF-IDF
        approach

        :param documents: List of free text documents
        :type documents: list
        :return: A dictionary containing two objects:
            `features` -- A list of all the words or tokens from the entire corpus, and
            `vectors` -- A TF-IDF matrix from the cleaned text
        :rtype: str
        """
        sanitised_documents = [self.clean_text(doc=doc) for doc in documents]
        if self.docs_empty(docs=sanitised_documents):
            return {
                "features": [],
                "vectors": csr_matrix((len(sanitised_documents), 0), dtype=np.float32),
            }

        vectorizer = TfidfVectorizer(dtype=np.float32)
        vectors = vectorizer.fit_transform(raw_documents=sanitised_documents)
        return {"features": vectorizer.get_feature_names(), "vectors": vectors}
