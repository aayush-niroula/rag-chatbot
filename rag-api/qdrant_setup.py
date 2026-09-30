from qdrant_client import QdrantClient
from qdrant_client.models import Distance, VectorParams

QDRANT_URL = "http://localhost:6333"
COLLECTION_NAME = "bank_faq"

client = QdrantClient(url=QDRANT_URL)


def create_collection():
    collections = client.get_collections().collections
    existing_names = [collection.name for collection in collections]

    if COLLECTION_NAME in existing_names:
        print(f"Collection '{COLLECTION_NAME}' already exists.")
        return

    client.create_collection(
        collection_name=COLLECTION_NAME,
        vectors_config=VectorParams(
            size=384,
            distance=Distance.COSINE
        )
    )

    print(f"Collection '{COLLECTION_NAME}' created successfully.")


if __name__ == "__main__":
    create_collection()