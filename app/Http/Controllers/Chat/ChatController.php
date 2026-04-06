<?php

namespace App\Http\Controllers\Chat;

use App\Ai\Agents\FinanceAssistantAgent;
use App\Ai\Pipelines\FinanceImportPipeline;
use App\Ai\Schemas\ChatIngestionSummarySchema;
use App\Ai\Support\TeamAiProviderFactory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\StoreChatMessageRequest;
use App\Models\Ai\IngestionBatch;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
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
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\StreamableAgentResponse;
use Laravel\Ai\Responses\StreamedAgentResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    public function __construct(
        protected TeamAiProviderFactory $teamAiProviderFactory,
    ) {}

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
        ConversationStore $conversationStore,
    ): JsonResponse {
        [$user, $team, $prompt, $attachments, $conversationId] = $this->validatedChatContext($request);

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
                'userMessage' => $this->userMessage(
                    $prompt !== '' ? $prompt : 'Adjunte archivos para procesar.',
                ),
                'assistantMessage' => $this->assistantMessage(
                    (string) Str::uuid(),
                    'Estoy procesando la informacion y voy a dejar todo en borrador para tu revision.',
                    [],
                ),
                'batch' => ChatIngestionSummarySchema::fromBatch($batch),
            ]);
        }

        $response = $this->promptAssistant($user, $team, $prompt, $conversationId);

        return response()->json([
            'conversationId' => $response->conversationId,
            'userMessage' => $this->userMessage($prompt),
            'assistantMessage' => $this->assistantMessage(
                $response->invocationId,
                $response->text,
                $response->toolCalls
                    ->map(fn ($toolCall) => [
                        'id' => $toolCall->id,
                        'name' => $toolCall->name,
                        'arguments' => $toolCall->arguments,
                    ])
                    ->values()
                    ->all(),
            ),
        ]);
    }

    /**
     * Stream a chat message response for read-only assistant prompts.
     */
    public function stream(
        StoreChatMessageRequest $request,
        FinanceImportPipeline $financeImportPipeline,
    ): JsonResponse|StreamedResponse {
        [$user, $team, $prompt, $attachments, $conversationId] = $this->validatedChatContext($request);

        if ($financeImportPipeline->shouldUseImportFlow($prompt, $attachments)) {
            return response()->json([
                'message' => 'Este mensaje debe procesarse como ingesta asistida.',
                'shouldFallbackToImport' => true,
            ], 409);
        }

        $stream = $this->streamAssistant($user, $team, $prompt, $conversationId);
        $finalResponse = null;

        $stream->then(function (StreamedAgentResponse $response) use (&$finalResponse): void {
            $finalResponse = $response;
        });

        return response()->stream(function () use ($stream, &$finalResponse): void {
            foreach ($stream as $event) {
                echo $this->ssePayload([
                    'type' => 'stream_event',
                    'event' => $event->toArray(),
                ]);

                if (function_exists('ob_flush')) {
                    @ob_flush();
                }

                flush();
            }

            $assistantMessage = $finalResponse instanceof StreamedAgentResponse
                ? $this->assistantMessage(
                    $finalResponse->invocationId,
                    $finalResponse->text,
                    $finalResponse->toolCalls
                        ->map(fn ($toolCall) => [
                            'id' => $toolCall->id,
                            'name' => $toolCall->name,
                            'arguments' => $toolCall->arguments,
                        ])
                        ->values()
                        ->all(),
                )
                : null;

            echo $this->ssePayload([
                'type' => 'final_message',
                'conversationId' => $finalResponse?->conversationId ?? $stream->conversationId,
                'assistantMessage' => $assistantMessage,
            ]);

            echo "data: [DONE]\n\n";
        }, 200, [
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'text/event-stream',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Get the latest chat conversation id for the given user.
     */
    protected function latestConversationId(User $user, Team $team): ?string
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
    protected function conversationBatches(?string $conversationId, User $user, Team $team): array
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

    /**
     * Determine whether a conversation belongs to the current user.
     */
    protected function conversationExists(string $conversationId, User $user): bool
    {
        return DB::table('agent_conversations')
            ->where('id', $conversationId)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Validate and normalize the current chat request context.
     *
     * @return array{0: User, 1: Team, 2: string, 3: array<int, UploadedFile>, 4: ?string}
     */
    protected function validatedChatContext(StoreChatMessageRequest $request): array
    {
        /** @var User $user */
        $user = $request->user();
        $team = $user->currentTeam()->firstOrFail();

        if (! $team->hasAiConfiguration()) {
            throw new HttpResponseException(response()->json([
                'message' => 'La IA todavia no esta configurada para este espacio de trabajo.',
            ], 503));
        }

        /** @var array<int, UploadedFile> $attachments */
        $attachments = $request->file('attachments', []);

        return [
            $user,
            $team,
            trim((string) $request->validated('prompt', '')),
            $attachments,
            $request->validated('conversation_id'),
        ];
    }

    /**
     * Build an assistant agent configured for the current conversation.
     */
    protected function assistantAgent(User $user, Team $team, ?string $conversationId): FinanceAssistantAgent
    {
        $agent = new FinanceAssistantAgent($user, $team);

        if (filled($conversationId) && $this->conversationExists($conversationId, $user)) {
            $agent->continue($conversationId, as: $user);
        } else {
            $agent->forUser($user);
        }

        return $agent;
    }

    /**
     * Prompt the assistant synchronously and return the response.
     */
    protected function promptAssistant(User $user, Team $team, string $prompt, ?string $conversationId): AgentResponse
    {
        $agent = $this->assistantAgent($user, $team, $conversationId);
        $provider = $this->teamAiProviderFactory->forAgent($agent, $team);

        return $provider->prompt(new AgentPrompt(
            $agent,
            $prompt,
            [],
            $provider,
            $team->ai_model,
            60,
        ));
    }

    /**
     * Stream the assistant response for the given prompt.
     */
    protected function streamAssistant(User $user, Team $team, string $prompt, ?string $conversationId): StreamableAgentResponse
    {
        $agent = $this->assistantAgent($user, $team, $conversationId);
        $provider = $this->teamAiProviderFactory->forAgent($agent, $team);

        return $provider->stream(new AgentPrompt(
            $agent,
            $prompt,
            [],
            $provider,
            $team->ai_model,
            60,
        ));
    }

    /**
     * Build a normalized user message payload.
     *
     * @return array<string, mixed>
     */
    protected function userMessage(string $prompt): array
    {
        return [
            'id' => null,
            'role' => 'user',
            'content' => $prompt,
            'toolCalls' => [],
        ];
    }

    /**
     * Build a normalized assistant message payload.
     *
     * @param  array<int, array<string, mixed>>  $toolCalls
     * @return array<string, mixed>
     */
    protected function assistantMessage(string $id, string $content, array $toolCalls): array
    {
        return [
            'id' => $id,
            'role' => 'assistant',
            'content' => $content,
            'toolCalls' => $toolCalls,
        ];
    }

    /**
     * Format an SSE payload frame.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function ssePayload(array $payload): string
    {
        return 'data: '.json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n\n";
    }
}
