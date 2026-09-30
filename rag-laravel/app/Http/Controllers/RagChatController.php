<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RagChatController extends Controller
{
    public function index()
    {
        return view('rag-chat');
    }

    public function chat(Request $request)
    {
        $request->validate([
            'question' => [
                'required',
                'string',
                'max:1000',
            ],
        ]);

        $response = Http::timeout(60)
            ->post(
                config('services.rag.url') . '/chat',
                [
                    'question' => $request->question,
                ]
            );

        if ($response->failed()) {
            return response()->json([
                'error' => 'RAG service is currently unavailable.',
            ], 502);
        }

        return response()->json([
            'answer'  => $response->json('answer'),
            'sources' => $response->json('sources', []),
        ]);
    }
}
