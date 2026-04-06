<?php

namespace App\Http\Controllers\Chat;

use App\Ai\Agents\FinanceAssistantAgent;
use App\Ai\Pipelines\FinanceImportPipeline;
use App\Ai\Schemas\ChatIngestionSummarySchema;
use App\Ai\Support\TeamAiProviderFactory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\StoreChatMessageRequest;
use App\Models\Ai\IngestionBatch;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Ai\Contracts\ConversationStore;
use Laravel\Ai\Prompts\AgentPrompt;

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
        $conversationId = $this->latestConversationId($user, $team);

        return Inertia::render('chat/Index', [
            'workspace' => [
                'name' => $team->name,
                'currency' => $team->currency,
            ],
            'providerConfigured' => $team->hasAiConfiguration(),
            'canManageAi' => $user->can('update', $team),
            'conversationId' => $conversationId,
            'messages' => $this->conversationMessages($conversationId, $user),
            'ingestionBatches' => $this->conversationBatches($conversationId, $user, $team),
            'examplePrompts' => [
                'Como voy este mes?',
                'Cuales son mis ultimos movimientos?',
                'Registra estos gastos desde mi mensaje',
                'Sube un PDF o CSV para poblar movimientos en borrador',
                'Que categorias estan sobre presupuesto?',
                'Cuanto me falta para mis metas?',
                'En que deuda deberia enfocarme primero?',
            ],
        ]);
    }

    /**
     * Store and answer a chat message.
     */
    public function store(
        StoreChatMessageRequest $request,
        FinanceImportPipeline $financeImportPipeline,
        TeamAiProviderFactory $teamAiProviderFactory,
        ConversationStore $conversationStore,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $team = $user->currentTeam()->firstOrFail();

        if (! $team->hasAiConfiguration()) {
            return response()->json([
                'message' => 'La IA todavia no esta configurada para este espacio de trabajo.',
            ], 503);
        }

        $prompt = trim((string) $request->validated('prompt', ''));
        /** @var array<int, UploadedFile> $attachments */
        $attachments = $request->file('attachments', []);
        $conversationId = $request->validated('conversation_id');

        if ($financeImportPipeline->shouldUseImportFlow($prompt, $attachments)) {
            $conversationId ??= $conversationStore->storeConversation(
                $user->id,
                Str::limit($prompt !== '' ? $prompt : 'Importacion financiera', 100, preserveWords: true),
            );

            $batch = $financeImportPipeline->createBatch(
                $team,
                $user,
                $prompt,
                $attachments,
                $conversationId,
            );

            return response()->json([
                'conversationId' => $conversationId,
                'userMessage' => [
                    'id' => null,
                    'role' => 'user',
                    'content' => $prompt !== '' ? $prompt : 'Adjunte archivos para procesar.',
                    'toolCalls' => [],
                ],
                'assistantMessage' => [
                    'id' => (string) Str::uuid(),
                    'role' => 'assistant',
                    'content' => 'Estoy procesando la informacion y voy a dejar todo en borrador para tu revision.',
                    'toolCalls' => [],
                ],
                'batch' => ChatIngestionSummarySchema::fromBatch($batch),
            ]);
        }

        $agent = new FinanceAssistantAgent($user, $team);

        if (filled($conversationId) && $this->conversationExists($conversationId, $user)) {
            $agent->continue($conversationId, as: $user);
        } else {
            $agent->forUser($user);
        }

        $provider = $teamAiProviderFactory->forAgent($agent, $team);
        $response = $provider->prompt(new AgentPrompt(
            $agent,
            $prompt,
            [],
            $provider,
            $team->ai_model,
            60,
        ));

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
    protected function latestConversationId(User $user, $team): ?string
    {
        $latestMessage = DB::table('agent_conversation_messages')
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->first(['conversation_id', 'created_at']);

        $latestBatch = IngestionBatch::query()
            ->forTeam($team)
            ->where('user_id', $user->id)
            ->whereNotNull('conversation_id')
            ->latest('updated_at')
            ->first(['conversation_id', 'updated_at']);

        if ($latestMessage === null) {
            return $latestBatch?->conversation_id;
        }

        if ($latestBatch === null) {
            return $latestMessage->conversation_id;
        }

        return Carbon::parse($latestBatch->updated_at)->greaterThan(Carbon::parse($latestMessage->created_at))
            ? $latestBatch->conversation_id
            : $latestMessage->conversation_id;
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

    /**
     * Get the ingestion batches visible within the current conversation.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function conversationBatches(?string $conversationId, User $user, $team): array
    {
        if (blank($conversationId)) {
            return [];
        }

        return IngestionBatch::query()
            ->forTeam($team)
            ->where('user_id', $user->id)
            ->where('conversation_id', $conversationId)
            ->with(['files', 'suggestions'])
            ->latest('id')
            ->get()
            ->map(fn (IngestionBatch $batch) => ChatIngestionSummarySchema::fromBatch($batch))
            ->values()
            ->all();
    }

    protected function conversationExists(string $conversationId, User $user): bool
    {
        return DB::table('agent_conversations')
            ->where('id', $conversationId)
            ->where('user_id', $user->id)
            ->exists();
    }
}
