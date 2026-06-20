<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Services\SimpleAskService;
use App\Services\SimpleAskStreamService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ChatController extends Controller
{
    public function __construct(private SimpleAskService $askService) {}

    public function index(?Conversation $conversation = null)
    {
        $conversations = auth()->user()
            ->conversations()
            ->latest('updated_at')
            ->get(['id', 'title', 'updated_at']);

        $current = null;
        if ($conversation) {
            abort_if($conversation->user_id !== auth()->id(), 403);
            $current = [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'model' => $conversation->model,
                'messages' => $conversation->messages()
                    ->orderBy('created_at')
                    ->get(['id', 'role', 'content']),
            ];
        }

        return Inertia::render('Chat/Index', [
            'conversations' => $conversations,
            'current' => $current,
            'models' => $this->askService->getModels(),
            'preferredModel' => auth()->user()->preferred_model ?? SimpleAskService::DEFAULT_MODEL,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string',
            'model' => 'required|string',
            'conversation_id' => 'nullable|exists:conversations,id',
        ]);

        if ($validated['conversation_id'] ?? null) {
            $conversation = Conversation::findOrFail($validated['conversation_id']);
            abort_if($conversation->user_id !== auth()->id(), 403);
            $conversation->update(['model' => $validated['model']]);
        } else {
            $conversation = auth()->user()->conversations()->create([
                'model' => $validated['model'],
            ]);
        }

        auth()->user()->update(['preferred_model' => $validated['model']]);

        // On sauve UNIQUEMENT le message utilisateur ; la réponse vient du streaming
        $conversation->messages()->create([
            'role' => 'user',
            'content' => $validated['message'],
        ]);

        $conversation->touch();

        return redirect()->route('chat.index', $conversation->id);
    }

    public function stream(Request $request, SimpleAskStreamService $streamService)
    {
        $validated = $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
        ]);

        $conversation = Conversation::findOrFail($validated['conversation_id']);
        abort_if($conversation->user_id !== auth()->id(), 403);

        $history = $conversation->messages()
            ->orderBy('created_at')
            ->get(['role', 'content'])
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
            ->toArray();

        return response()->stream(function () use ($conversation, $history, $streamService) {
            // Stream les tokens au navigateur ET récupère le texte complet
            $answer = $streamService->streamToOutput($history, $conversation->model);

            // Stream fini → on persiste la réponse de l'assistant
            $conversation->messages()->create([
                'role' => 'assistant',
                'content' => $answer,
            ]);

            // Titre auto au tout premier échange (2e appel, non streamé)
            if ($conversation->messages()->count() === 2) {
                $title = trim($this->askService->sendMessage(
                    messages: [[
                        'role' => 'user',
                        'content' => "Donne un titre court (3 à 5 mots, sans guillemets) pour une conversation qui commence par : \"{$history[0]['content']}\"",
                    ]],
                    model: $conversation->model,
                ));
                $conversation->update(['title' => $title]);
            }

            $conversation->touch();
        }, headers: [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Cache-Control' => 'no-cache, no-store',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}