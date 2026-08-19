<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\ModerationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\AssignTicketRequest;
use App\Http\Requests\Ticket\ChangeTicketPriorityRequest;
use App\Http\Requests\Ticket\ChangeTicketStatusRequest;
use App\Http\Requests\Ticket\CreateTicketRequest;
use App\Http\Requests\Ticket\ReactToTicketMessageRequest;
use App\Http\Requests\Ticket\ReplyTicketRequest;
use App\Http\Requests\Ticket\SearchTicketRequest;
use App\Http\Requests\Ticket\UpdateTicketRequest;
use App\Http\Requests\Ticket\UploadTicketAttachmentRequest;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Repository\TicketAttachmentRepository;
use App\Services\TicketAttachmentService;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TicketController extends Controller
{
    public function index(SearchTicketRequest $request, TicketService $tickets): JsonResponse
    {
        $paginator = $tickets->paginate($request->user(), $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tickets retrieved successfully.',
            'data' => [
                'tickets' => collect($paginator->items())->map(fn (Ticket $ticket) => $this->serializeTicketListItem($ticket)),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
            ],
        ]);
    }

    public function store(CreateTicketRequest $request, TicketService $tickets): JsonResponse
    {
        try {
            $ticket = $tickets->create($request->user(), $request->validated(), $request->file('attachments', []));
        } catch (BusinessRuleException $exception) {
            return $this->businessError($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'Ticket created successfully.',
            'data' => ['ticket' => $this->serializeTicket($ticket, $request->user())],
        ], 201);
    }

    public function show(Request $request, TicketService $tickets, int $ticket): JsonResponse
    {
        try {
            $record = $tickets->show($request->user(), $ticket);
        } catch (BusinessRuleException $exception) {
            return $this->businessError($exception, 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Ticket retrieved successfully.',
            'data' => ['ticket' => $this->serializeTicket($record, $request->user())],
        ]);
    }

    public function update(UpdateTicketRequest $request, TicketService $tickets, int $ticket): JsonResponse
    {
        return $this->ticketAction(
            fn () => $tickets->update($request->user(), $ticket, $request->validated()),
            $request,
            'Ticket updated successfully.',
        );
    }

    public function assign(AssignTicketRequest $request, TicketService $tickets, int $ticket): JsonResponse
    {
        return $this->ticketAction(
            fn () => $tickets->assign($request->user(), $ticket, (int) $request->validated('assigned_agent_id')),
            $request,
            'Ticket assigned successfully.',
        );
    }

    public function status(ChangeTicketStatusRequest $request, TicketService $tickets, int $ticket): JsonResponse
    {
        return $this->ticketAction(
            fn () => $tickets->changeStatus($request->user(), $ticket, $request->validated('status')),
            $request,
            'Ticket status updated successfully.',
        );
    }

    public function priority(ChangeTicketPriorityRequest $request, TicketService $tickets, int $ticket): JsonResponse
    {
        return $this->ticketAction(
            fn () => $tickets->changePriority($request->user(), $ticket, (int) $request->validated('priority_id')),
            $request,
            'Ticket priority updated successfully.',
        );
    }

    public function reply(ReplyTicketRequest $request, TicketService $tickets, int $ticket): JsonResponse
    {
        return $this->ticketAction(
            fn () => $tickets->reply(
                $request->user(),
                $ticket,
                $request->validated('message', null),
                $request->file('attachments', []),
                false,
                $request->validated('reply_to_message_id', null),
            ),
            $request,
            'Reply added successfully.',
        );
    }

    public function internalNote(ReplyTicketRequest $request, TicketService $tickets, int $ticket): JsonResponse
    {
        return $this->ticketAction(
            fn () => $tickets->reply(
                $request->user(),
                $ticket,
                $request->validated('message', null),
                $request->file('attachments', []),
                true,
                $request->validated('reply_to_message_id', null),
            ),
            $request,
            'Internal note added successfully.',
        );
    }

    public function deleteMessage(Request $request, TicketService $tickets, int $ticket, int $message): JsonResponse
    {
        return $this->ticketAction(
            fn () => $tickets->deleteMessage($request->user(), $ticket, $message),
            $request,
            'Message deleted successfully.',
        );
    }

    public function reactToMessage(ReactToTicketMessageRequest $request, TicketService $tickets, int $ticket, int $message): JsonResponse
    {
        return $this->ticketAction(
            fn () => $tickets->reactToMessage($request->user(), $ticket, $message, $request->validated('reaction')),
            $request,
            'Reaction updated successfully.',
        );
    }

    public function markRead(Request $request, TicketService $tickets, int $ticket): JsonResponse
    {
        return $this->ticketAction(
            fn () => $tickets->markRead($request->user(), $ticket),
            $request,
            'Conversation marked as read.',
        );
    }

    public function reopen(Request $request, TicketService $tickets, int $ticket): JsonResponse
    {
        return $this->ticketAction(fn () => $tickets->reopen($request->user(), $ticket), $request, 'Ticket reopened successfully.');
    }

    public function close(Request $request, TicketService $tickets, int $ticket): JsonResponse
    {
        return $this->ticketAction(fn () => $tickets->close($request->user(), $ticket), $request, 'Ticket closed successfully.');
    }

    public function cancel(Request $request, TicketService $tickets, int $ticket): JsonResponse
    {
        return $this->ticketAction(fn () => $tickets->cancel($request->user(), $ticket), $request, 'Ticket cancelled successfully.');
    }

    public function uploadAttachment(UploadTicketAttachmentRequest $request, TicketService $tickets, int $ticket): JsonResponse
    {
        return $this->ticketAction(
            fn () => $tickets->uploadAttachments($request->user(), $ticket, $request->file('attachments', [])),
            $request,
            'Attachments uploaded successfully.',
        );
    }

    public function downloadAttachment(
        Request $request,
        TicketAttachmentRepository $attachments,
        TicketAttachmentService $attachmentService,
        TicketService $tickets,
        int $ticket,
        int $attachment
    ): Response {
        $record = $attachments->findById($attachment);

        if (! $record || $record->ticket_id !== $ticket || ! $tickets->canAccessAttachment($request->user(), $record)) {
            return response()->json(['success' => false, 'message' => 'Attachment not found or access is not allowed.'], 404);
        }

        return response($attachmentService->read($record), 200, [
            'Content-Type' => $record->mime_type,
            'Content-Disposition' => 'attachment; filename="'.$record->original_name.'"',
        ]);
    }

    public function thumbnailAttachment(
        Request $request,
        TicketAttachmentRepository $attachments,
        TicketAttachmentService $attachmentService,
        TicketService $tickets,
        int $ticket,
        int $attachment
    ): Response {
        $record = $attachments->findById($attachment);

        if (! $record || $record->ticket_id !== $ticket || ! $tickets->canAccessAttachment($request->user(), $record)) {
            return response()->json(['success' => false, 'message' => 'Attachment not found or access is not allowed.'], 404);
        }

        $thumbnail = $attachmentService->readThumbnail($record);
        if ($thumbnail === null) {
            return response()->json(['success' => false, 'message' => 'Thumbnail not available.'], 404);
        }

        return response($thumbnail, 200, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=604800',
        ]);
    }

    public function activity(Request $request, TicketService $tickets, int $ticket): JsonResponse
    {
        try {
            $record = $tickets->show($request->user(), $ticket);
        } catch (BusinessRuleException $exception) {
            return $this->businessError($exception, 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Ticket activity retrieved successfully.',
            'data' => ['activity' => $this->serializeActivities($record)],
        ]);
    }

    private function ticketAction(callable $action, Request $request, string $message): JsonResponse
    {
        try {
            $ticket = $action();
        } catch (BusinessRuleException $exception) {
            return $this->businessError($exception);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => ['ticket' => $this->serializeTicket($ticket, $request->user())],
        ]);
    }

    private function businessError(BusinessRuleException $exception, int $status = 403): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $exception->getMessage(),
        ];

        if ($exception instanceof ModerationException) {
            $payload['errors'] = $exception->errors();
        }

        return response()->json($payload, $status);
    }

    private function serializeTicket(Ticket $ticket, ?object $viewer, bool $includeDetail = true): array
    {
        $isCustomer = $viewer?->hasRole(Role::CUSTOMER) ?? false;

        return [
            'id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'subject' => $ticket->subject,
            'description' => $includeDetail ? $ticket->description : null,
            'status' => $ticket->status,
            'customer' => $this->serializeUser($ticket->customer),
            'department' => $ticket->department?->only(['id', 'name', 'slug']),
            'category' => $ticket->category?->only(['id', 'name', 'slug']),
            'priority' => $ticket->priority?->only(['id', 'name', 'slug']),
            'assigned_agent' => $this->serializeUser($ticket->assignedAgent),
            'first_response_due_at' => $ticket->first_response_due_at?->toISOString(),
            'resolution_due_at' => $ticket->resolution_due_at?->toISOString(),
            'first_responded_at' => $ticket->first_responded_at?->toISOString(),
            'resolved_at' => $ticket->resolved_at?->toISOString(),
            'closed_at' => $ticket->closed_at?->toISOString(),
            'cancelled_at' => $ticket->cancelled_at?->toISOString(),
            'created_at' => $ticket->created_at?->toISOString(),
            'updated_at' => $ticket->updated_at?->toISOString(),
            'messages' => $includeDetail ? $this->serializeMessages($ticket, $isCustomer) : [],
            'attachments' => $includeDetail ? $this->serializeAttachments($ticket) : [],
            'activity' => $includeDetail && ! $isCustomer ? $this->serializeActivities($ticket) : [],
        ];
    }

    private function serializeTicketListItem(Ticket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'subject' => $ticket->subject,
            'status' => $ticket->status,
            'priority' => $ticket->priority_lookup_id ? [
                'id' => (int) $ticket->priority_lookup_id,
                'name' => $ticket->priority_lookup_name,
                'slug' => $ticket->priority_lookup_slug,
            ] : null,
            'first_response_due_at' => $ticket->first_response_due_at?->toISOString(),
            'resolution_due_at' => $ticket->resolution_due_at?->toISOString(),
            'first_responded_at' => $ticket->first_responded_at?->toISOString(),
            'resolved_at' => $ticket->resolved_at?->toISOString(),
            'closed_at' => $ticket->closed_at?->toISOString(),
            'cancelled_at' => $ticket->cancelled_at?->toISOString(),
            'created_at' => $ticket->created_at?->toISOString(),
            'updated_at' => $ticket->updated_at?->toISOString(),
        ];
    }

    private function serializeMessages(Ticket $ticket, bool $isCustomer): array
    {
        return $ticket->messages
            ->filter(fn (TicketMessage $message) => ! $isCustomer || $message->message_type !== TicketMessage::TYPE_INTERNAL_NOTE)
            ->sortBy('created_at')
            ->map(fn (TicketMessage $message) => [
                'id' => $message->id,
                'message' => $message->deleted_at ? null : $message->message,
                'message_type' => $message->message_type,
                'reply_to' => $this->serializeReplyPreview($message->replyTo, $isCustomer),
                'user' => $this->serializeUser($message->user),
                'deleted_at' => $message->deleted_at?->toISOString(),
                'deleted_by' => $this->serializeUser($message->deletedBy),
                'is_deleted' => $message->deleted_at !== null,
                'attachments' => $message->deleted_at ? [] : $message->attachments->map(fn ($attachment) => $this->serializeAttachment($attachment))->values(),
                'reactions' => $this->serializeReactions($message),
                'read_by' => $this->serializeReads($message),
                'created_at' => $message->created_at?->toISOString(),
            ])
            ->values()
            ->all();
    }

    private function serializeReplyPreview(?TicketMessage $message, bool $isCustomer): ?array
    {
        if ($message === null || ($isCustomer && $message->message_type === TicketMessage::TYPE_INTERNAL_NOTE)) {
            return null;
        }

        return [
            'id' => $message->id,
            'message' => $message->deleted_at ? null : str($message->message)->limit(160)->toString(),
            'message_type' => $message->message_type,
            'user' => $this->serializeUser($message->user),
            'is_deleted' => $message->deleted_at !== null,
        ];
    }

    private function serializeAttachments(Ticket $ticket): array
    {
        return $ticket->attachments->map(fn ($attachment) => $this->serializeAttachment($attachment))->values()->all();
    }

    private function serializeAttachment($attachment): array
    {
        return [
            'id' => $attachment->id,
            'ticket_id' => $attachment->ticket_id,
            'ticket_message_id' => $attachment->ticket_message_id,
            'original_name' => $attachment->original_name,
            'mime_type' => $attachment->mime_type,
            'file_size' => $attachment->file_size,
            'download_url' => "/tickets/{$attachment->ticket_id}/attachments/{$attachment->id}",
            'is_image' => str_starts_with((string) $attachment->mime_type, 'image/'),
            'is_video' => str_starts_with((string) $attachment->mime_type, 'video/'),
            'thumbnail_url' => str_starts_with((string) $attachment->mime_type, 'image/')
                ? "/tickets/{$attachment->ticket_id}/attachments/{$attachment->id}/thumbnail"
                : null,
            'created_at' => $attachment->created_at?->toISOString(),
        ];
    }

    private function serializeReactions(TicketMessage $message): array
    {
        return $message->reactions
            ->groupBy('reaction')
            ->map(fn ($items, string $reaction) => [
                'reaction' => $reaction,
                'count' => $items->count(),
                'users' => $items->map(fn ($item) => $this->serializeUser($item->user))->values(),
                'user_ids' => $items->pluck('user_id')->map(fn ($id) => (int) $id)->values(),
            ])
            ->values()
            ->all();
    }

    private function serializeReads(TicketMessage $message): array
    {
        return $message->reads
            ->sortBy('read_at')
            ->map(fn ($read) => [
                'user' => $this->serializeUser($read->user),
                'read_at' => $read->read_at?->toISOString(),
            ])
            ->values()
            ->all();
    }

    private function serializeActivities(Ticket $ticket): array
    {
        return $ticket->activities
            ->sortBy('created_at')
            ->map(fn ($activity) => [
                'id' => $activity->id,
                'action' => $activity->action,
                'old_value' => $activity->old_value,
                'new_value' => $activity->new_value,
                'metadata' => $activity->metadata,
                'user' => $this->serializeUser($activity->user),
                'created_at' => $activity->created_at?->toISOString(),
            ])
            ->values()
            ->all();
    }

    private function serializeUser($user): ?array
    {
        if ($user === null) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'customer_label' => $user->customer_label,
            'role' => $user->role?->only(['id', 'name', 'slug']),
        ];
    }
}
