from pathlib import Path
import hashlib

from pypdf import PdfReader
from sentence_transformers import SentenceTransformer

from qdrant_client import QdrantClient
from qdrant_client.models import (
    PointStruct,
    Filter,
    FieldCondition,
    MatchValue,
    FilterSelector,
)


PDFS_PATH = Path("pdfs")

QDRANT_URL = "http://localhost:6333"
COLLECTION_NAME = "bank_faq"

EMBEDDING_MODEL = "BAAI/bge-small-en-v1.5"


def make_point_id(document_id, page, chunk_index):
    value = f"{document_id}:{page}:{chunk_index}"

    return hashlib.md5(
        value.encode("utf-8")
    ).hexdigest()


def extract_pdf_text(pdf_path):
    reader = PdfReader(pdf_path)

    pages = []

    for page_number, page in enumerate(reader.pages, start=1):

        text = page.extract_text()

        if not text:
            continue

        text = text.strip()

        if text:
            pages.append({
                "page": page_number,
                "text": text,
            })

    return pages


def create_chunks(
    pages,
    chunk_size=800,
    overlap=100
):
    chunks = []

    for page in pages:

        words = page["text"].split()

        start = 0
        chunk_index = 0

        while start < len(words):

            end = start + chunk_size

            chunk_words = words[start:end]

            if not chunk_words:
                break

            chunks.append({
                "text": " ".join(chunk_words),
                "page": page["page"],
                "chunk_index": chunk_index,
            })

            chunk_index += 1

            start += chunk_size - overlap

    return chunks


def delete_document_points(
    client,
    document_id
):
    print(
        f"Deleting old points for: {document_id}"
    )

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


def index_pdf(
    client,
    model,
    pdf_path
):
    document_id = pdf_path.stem

    print(
        f"\nReading PDF: {pdf_path.name}"
    )

    pages = extract_pdf_text(
        pdf_path
    )

    print(
        f"Extracted text from "
        f"{len(pages)} pages."
    )

    if not pages:
        print(
            f"No text found in {pdf_path.name}"
        )

        return 0

    chunks = create_chunks(
        pages
    )

    print(
        f"Created {len(chunks)} chunks."
    )

    if not chunks:
        print(
            f"No chunks created for "
            f"{pdf_path.name}"
        )

        return 0

    texts = [
        chunk["text"]
        for chunk in chunks
    ]

    print(
        f"Creating embeddings for "
        f"{pdf_path.name}..."
    )

    embeddings = model.encode(
        texts,
        normalize_embeddings=True
    )

    # Delete only the old points
    # belonging to this PDF.
    delete_document_points(
        client,
        document_id
    )

    points = []

    for chunk, embedding in zip(
        chunks,
        embeddings
    ):

        page = chunk["page"]

        chunk_index = chunk["chunk_index"]

        point_id = make_point_id(
            document_id,
            page,
            chunk_index
        )

        point = PointStruct(
            id=point_id,

            vector=embedding.tolist(),

            payload={
                "document_id": document_id,

                "text": chunk["text"],

                "source": pdf_path.name,

                "page": page,

                "chunk_index": chunk_index,

                "type": "pdf",
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
        f"Successfully indexed: "
        f"{pdf_path.name}"
    )

    return len(points)


def main():

    print("\nLoading PDFs...")

    files = sorted(
        PDFS_PATH.glob("*.pdf")
    )

    print(
        f"Found {len(files)} PDF files."
    )

    if not files:
        print(
            "No PDF files found."
        )

        return

    print(
        "\nLoading embedding model..."
    )

    model = SentenceTransformer(
        EMBEDDING_MODEL
    )

    print(
        "\nConnecting to Qdrant..."
    )

    client = QdrantClient(
        url=QDRANT_URL
    )

    total_indexed = 0

    for pdf_path in files:

        count = index_pdf(
            client,
            model,
            pdf_path
        )

        total_indexed += count

    print(
        f"\nSuccessfully indexed "
        f"{total_indexed} PDF chunks."
    )


if __name__ == "__main__":
    main()