from sentence_transformers import SentenceTransformer
from qdrant_client import QdrantClient

QDRANT_URL = "http://localhost:6333"
COLLECTION_NAME = "bank_faq"
EMBEDDING_MODEL = "BAAI/bge-small-en-v1.5"


def main():
    question = "What is the maximum Home Loan amount?" 

    print(f"Question: {question}")

    # Load embedding model
    model = SentenceTransformer(EMBEDDING_MODEL)

    # Convert question into vector
    query_vector = model.encode(
        question,
        normalize_embeddings=True
    )

    # Connect to Qdrant
    client = QdrantClient(url=QDRANT_URL)

    # Search Qdrant
    results = client.query_points(
        collection_name=COLLECTION_NAME,
        query=query_vector.tolist(),
        limit=3
    ).points

    print("\nTop results:\n")

    for index, result in enumerate(results, start=1):
        print("-------------------------")
        print(f"Result {index}")
        print(f"Score: {result.score}")
        print(f"Category: {result.payload['category']}")
        print(f"Question: {result.payload['question']}")
        print(f"Answer: {result.payload['answer']}")


if __name__ == "__main__":
    main()