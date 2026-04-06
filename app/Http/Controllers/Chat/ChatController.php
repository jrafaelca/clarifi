<?php

namespace App\Http\Controllers\Chat;

use App\Ai\Agents\FinanceAssistantAgent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\StoreChatMessageRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    /**
     * Display the chat page.
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $team = $user->currentTeam()->firstOrFail();
        $conversationId = $this->latestConversationId($user);

        return Inertia::render('chat/Index', [
            'workspace' => [
                'name' => $team->name,
                'currency' => $team->currency,
            ],
            'providerConfigured' => filled(config('ai.providers.openai.key')),
            'conversationId' => $conversationId,
            'messages' => $this->conversationMessages($conversationId, $user),
            'examplePrompts' => [
                'How am I doing this month?',
                'What are my latest transactions?',
                'Which categories are over budget?',
                'How much is left for my goals?',
                'What debt should I focus on first?',
            ],
        ]);
    }

    /**
     * Store and answer a chat message.
     */
    public function store(StoreChatMessageRequest $request): JsonResponse
    {
        if (blank(config('ai.providers.openai.key'))) {
            return response()->json([
                'message' => 'OPENAI_API_KEY is not configured for ClariFi yet.',
            ], 503);
        }

        /** @var User $user */
        $user = $request->user();
        $team = $user->currentTeam()->firstOrFail();
        $agent = new FinanceAssistantAgent($user, $team);
        $conversationId = $request->validated('conversation_id');

        if (filled($conversationId)) {
            $agent->continue($conversationId, as: $user);
        } else {
            $agent->forUser($user);
        }

        $prompt = $request->validated('prompt');
        $response = $agent->prompt($prompt);

        return response()->json([
            'conversationId' => $response->conversationId,
            'userMessage' => [
                'id' => null,
                'role' => 'user',
                'content' => $prompt,
                'toolCalls' => [],
            ],
            'assistantMessage' => [
                'id' => $response->invocationId,
                'role' => 'assistant',
                'content' => $response->text,
                'toolCalls' => $response->toolCalls
                    ->map(fn ($toolCall) => [
                        'id' => $toolCall->id,
                        'name' => $toolCall->name,
                        'arguments' => $toolCall->arguments,
                    ])
                    ->values()
                    ->all(),
            ],
        ]);
    }

    /**
     * Get the latest chat conversation id for the given user.
     */
    protected function latestConversationId(User $user): ?string
    {
        return DB::table('agent_conversation_messages')
            ->where('user_id', $user->id)
            ->where('agent', FinanceAssistantAgent::class)
            ->latest('created_at')
            ->value('conversation_id');
    }

    /**
     * Get the visible messages for the conversation.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function conversationMessages(?string $conversationId, User $user): array
    {
        if (blank($conversationId)) {
            return [];
        }

        return DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversationId)
            ->where('user_id', $user->id)
            ->where('agent', FinanceAssistantAgent::class)
            ->orderBy('created_at')
            ->get()
            ->map(fn ($record) => [
                'id' => $record->id,
                'role' => $record->role,
                'content' => $record->content,
                'toolCalls' => collect(json_decode($record->tool_calls, true) ?: [])
                    ->map(fn ($toolCall) => [
                        'id' => $toolCall['id'],
                        'name' => $toolCall['name'],
                        'arguments' => $toolCall['arguments'],
                    ])
                    ->values()
                    ->all(),
                'createdAt' => $record->created_at,
            ])
            ->all();
    }
}
