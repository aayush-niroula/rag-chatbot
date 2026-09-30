from pathlib import Path
import hashlib

from sentence_transformers import SentenceTransformer

from qdrant_client import QdrantClient
from qdrant_client.models import (
    PointStruct,
    Filter,
    FieldCondition,
    MatchValue,
    FilterSelector,
)


DOCUMENTS_PATH = Path("documents")

QDRANT_URL = "http://localhost:6333"
COLLECTION_NAME = "bank_faq"

EMBEDDING_MODEL = "BAAI/bge-small-en-v1.5"


def make_point_id(document_id, index):
    value = f"{document_id}:{index}"

    return hashlib.md5(
        value.encode("utf-8")
    ).hexdigest()


def parse_faqs(text: str, source: str):
    faqs = []

    current_category = "GENERAL"
    current_question = None
    current_answer = None

    def save_current_faq():
        if current_question and current_answer:
            faqs.append({
                "question": current_question.strip(),
                "answer": current_answer.strip(),
                "category": current_category,
                "source": source,
            })

    for line in text.splitlines():

        line = line.strip()

        if not line:
            continue

        # Category
        if line.startswith("===") and line.endswith("==="):
            save_current_faq()

            current_question = None
            current_answer = None

            current_category = line.strip("=").strip()

            continue

        # Question
        if line.startswith("Q:"):
            save_current_faq()

            current_question = line[2:].strip()
            current_answer = None

            continue

        # Answer
        if line.startswith("A:"):
            current_answer = line[2:].strip()

            continue

        # Continuation of answer
        if current_answer is not None:
            current_answer += " " + line

    save_current_faq()

    return faqs


def delete_document_points(client, document_id):
    print(f"Deleting old points for: {document_id}")

    client.delete(
        collection_name=COLLECTION_NAME,
        points_selector=FilterSelector(
            filter=Filter(
                must=[
                    FieldCondition(
                        key="document_id",
                        match=MatchValue(
                            value=document_id
                        ),
                    )
                ]
            )
        ),
    )


def index_document(
    client,
    model,
    file_path
):
    document_id = file_path.stem

    print(f"\nReading: {file_path.name}")

    text = file_path.read_text(
        encoding="utf-8"
    )

    faqs = parse_faqs(
        text,
        file_path.name
    )

    print(
        f"Found {len(faqs)} FAQs"
    )

    if not faqs:
        print(
            f"No FAQs found in {file_path.name}"
        )

        return 0

    # Create embedding text
    texts = [
        f"{faq['question']} {faq['answer']}"
        for faq in faqs
    ]

    print(
        f"Creating embeddings for {file_path.name}..."
    )

    embeddings = model.encode(
        texts,
        normalize_embeddings=True
    )

    # Delete old points belonging only
    # to this document
    delete_document_points(
        client,
        document_id
    )

    points = []

    for index, (faq, embedding) in enumerate(
        zip(faqs, embeddings)
    ):

        point_id = make_point_id(
            document_id,
            index
        )

        point = PointStruct(
            id=point_id,

            vector=embedding.tolist(),

            payload={
                "document_id": document_id,

                "question": faq["question"],

                "answer": faq["answer"],

                "category": faq["category"],

                "source": faq["source"],

                "type": "faq",
            }
        )

        points.append(point)

    print(
        f"Uploading {len(points)} points..."
    )

    client.upsert(
        collection_name=COLLECTION_NAME,
        points=points
    )

    print(
        f"Successfully indexed: {file_path.name}"
    )

    return len(points)


def main():

    print("\nLoading documents...")

    files = sorted(
        DOCUMENTS_PATH.glob("*.txt")
    )

    print(
        f"Found {len(files)} text files."
    )

    if not files:
        print(
            "No .txt files found."
        )

        return

    print("\nLoading embedding model...")

    model = SentenceTransformer(
        EMBEDDING_MODEL
    )

    print("\nConnecting to Qdrant...")

    client = QdrantClient(
        url=QDRANT_URL
    )

    total_indexed = 0

    for file_path in files:

        count = index_document(
            client,
            model,
            file_path
        )

        total_indexed += count

    print(
        f"\nSuccessfully indexed "
        f"{total_indexed} FAQs."
    )


if __name__ == "__main__":
    main()