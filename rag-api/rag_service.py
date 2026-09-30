from fastapi import FastAPI
from pydantic import BaseModel
from sentence_transformers import SentenceTransformer
from qdrant_client import QdrantClient
from ollama import chat



QDRANT_URL = "http://localhost:6333"
COLLECTION_NAME = "bank_faq"

EMBEDDING_MODEL = "BAAI/bge-small-en-v1.5"
OLLAMA_MODEL = "llama3.2:3b"



app = FastAPI(
    title="Muktinath Bank RAG API",
    description="Local RAG chatbot using Qdrant and Ollama",
    version="1.0.0"
)



print("Loading embedding model...")

embedding_model = SentenceTransformer(
    EMBEDDING_MODEL
)

print("Connecting to Qdrant...")

qdrant = QdrantClient(
    url=QDRANT_URL
)

print("RAG service initialized successfully.")



class ChatRequest(BaseModel):
    question: str


@app.get("/")
def home():
    return {
        "message": "Muktinath Bank RAG API is running"
    }


@app.get("/health")
def health():
    return {
        "status": "ok",
        "qdrant": QDRANT_URL,
        "collection": COLLECTION_NAME,
        "model": OLLAMA_MODEL
    }




def search_faqs(question: str, limit: int = 3):

    query_vector = embedding_model.encode(
        question,
        normalize_embeddings=True
    )

    results = qdrant.query_points(
        collection_name=COLLECTION_NAME,
        query=query_vector.tolist(),
        limit=limit
    ).points

    return results



def generate_answer(question: str, results):

    # Keep only sufficiently relevant results
    relevant_results = [
        result
        for result in results
        if result.score >= 0.75
    ]

    if not relevant_results:
        return (
            "I couldn't find that information in the available bank FAQs.",
            []
        )

    context_parts = []
    sources = []

    for result in relevant_results:

        payload = result.payload

        if payload.get("type") == "pdf":

            context_parts.append(
                f"""
Source: {payload['source']}
Page: {payload['page']}
Content:
{payload['text']}
"""
            )

            sources.append({
                "source": payload["source"],
                "page": payload["page"],
                "score": round(result.score, 4)
            })

        else:

            context_parts.append(
                f"""
Category: {payload['category']}
Question: {payload['question']}
Answer: {payload['answer']}
"""
            )

            sources.append({
                "source": payload.get(
                    "source",
                    "bank_faq.txt"
                ),
                "category": payload["category"],
                "question": payload["question"],
                "score": round(result.score, 4)
            })
    context = "\n".join(context_parts)

    prompt = f"""
You are a helpful assistant for Muktinath Bikas Bank.

Answer the user's question using ONLY the information
provided in the context below.

Do not use your own general knowledge.
Do not invent or assume information.

Answer if it is related to bank otherwise

If the answer cannot be found in the context, say:

"I couldn't find that information in the available bank FAQs."

CONTEXT:
{context}

USER QUESTION:
{question}

Give a short and direct answer.
"""

    response = chat(
        model=OLLAMA_MODEL,
        messages=[
            {
                "role": "user",
                "content": prompt
            }
        ]
    )

    return response.message.content, sources

@app.post("/chat")

def chat_endpoint(request: ChatRequest):

    question = request.question.strip()

    if not question:
        return {
            "question": "",
            "answer": "Please enter a question.",
            "sources": []
        }

    results = search_faqs(question)

    answer, sources = generate_answer(
        question,
        results
    )

    return {
        "question": question,
        "answer": answer,
        "sources": sources
    }