<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class MessageController extends Controller
{
    private const UNSENT_MESSAGE_PLACEHOLDER = 'This message was unsent';

    private const MESSAGE_IMAGE_DIRECTORY = 'images/messages/images';

    private const LEGACY_MESSAGE_IMAGE_DIRECTORY = 'messages';

    private const MESSAGE_IMAGE_QUALITY = 75;

    private const MESSAGE_PNG_COMPRESSION = 7;

    public function main(Request $request)
    {
        $currentUserId = session('usr_id');
        $search = $request->query('search', '');
        $conversations = $this->getConversationRail($currentUserId, $search);
        $users = $this->getComposeUsers($currentUserId);

        return view('messages.main', compact('conversations', 'users', 'search'));
    }

    public function personal(Request $request, $mesg_group_id)
    {
        $currentUserId = session('usr_id');
        $search = $request->query('search', '');

        // Verify user belongs to this group
        $isMember = DB::table('message_groups')
            ->where('mesg_group_id', $mesg_group_id)
            ->where('usr_id', $currentUserId)
            ->where('mesg_active', 1)
            ->exists();

        if (! $isMember) {
            abort(403, 'Unauthorized');
        }

        // Fetch group info
        $groupInfo = DB::table('message_groups')
            ->where('mesg_group_id', $mesg_group_id)
            ->where('mesg_active', 1)
            ->first();

        // Fetch all members of this group
        $members = DB::table('message_groups as mg')
            ->join('users as u', 'u.usr_id', '=', 'mg.usr_id')
            ->where('mg.mesg_group_id', $mesg_group_id)
            ->where('mg.mesg_active', 1)
            ->select('u.usr_id', 'u.usr_first_name', 'u.usr_last_name')
            ->get();

        // Determine if group or private
        $isGroup = $members->count() > 2 || ! empty($groupInfo->mesg_group_name);

        // Fetch messages paginated (oldest first for chat display)
        $messages = DB::table('messages as m')
            ->join('users as u', 'u.usr_id', '=', 'm.mes_created_by')
            ->where('m.mesg_group_id', $mesg_group_id)
            ->select(
                'm.mes_id',
                'm.mes_uuid',
                'm.mes_content',
                'm.mes_image',
                'm.mes_date_created',
                'm.mes_date_modified',
                'm.mes_created_by',
                'm.mes_active',
                DB::raw("CONCAT(u.usr_first_name, ' ', u.usr_last_name) as sender_name"),
                'u.usr_first_name'
            )
            ->orderBy('m.mes_date_created', 'asc')
            ->paginate(50, ['*'], 'messages_page');
        $messages->appends($request->except('messages_page'));
        $messages->getCollection()->transform(function ($message) use ($currentUserId) {
            $message->mes_image_url = $this->registerMessageImageAccess($message, (int) $currentUserId);

            return $message;
        });

        $conversations = $this->getConversationRail($currentUserId, $search, (int) $mesg_group_id);
        $users = $this->getComposeUsers($currentUserId);
        $chatContext = $this->buildChatContext($groupInfo, $members, $currentUserId);

        return view('messages.personal', compact(
            'messages',
            'members',
            'isGroup',
            'mesg_group_id',
            'conversations',
            'users',
            'search',
            'chatContext'
        ));
    }

    public function send(Request $request)
    {
        $request->validate([
            'mesg_group_id' => 'required|integer',
        ]);

        $currentUserId = session('usr_id');

        // Verify membership
        $isMember = DB::table('message_groups')
            ->where('mesg_group_id', $request->mesg_group_id)
            ->where('usr_id', $currentUserId)
            ->where('mesg_active', 1)
            ->exists();

        if (! $isMember) {
            abort(403);
        }

        $payload = $this->validateMessagePayload($request);
        $this->createMessage((int) $request->mesg_group_id, (int) $currentUserId, $payload);

        return redirect()->to(url('messages/chat/'.$request->mesg_group_id));
    }

    public function renameGroup(Request $request, $mesg_group_id)
    {
        $currentUserId = (int) session('usr_id');
        $this->getMutableGroupMemberIds((int) $mesg_group_id, $currentUserId, 'renamed');

        $request->merge([
            'mesg_group_name' => trim((string) $request->input('mesg_group_name', '')),
        ]);

        $validated = $request->validate([
            'mesg_group_name' => 'required|string|max:100',
        ]);

        DB::table('message_groups')
            ->where('mesg_group_id', $mesg_group_id)
            ->where('mesg_active', 1)
            ->update([
                'mesg_group_name' => $validated['mesg_group_name'],
            ]);

        return redirect()
            ->to(url('messages/chat/'.$mesg_group_id))
            ->with('successMessage', 'Group chat renamed successfully.');
    }

    public function updateGroupAvatar(Request $request, $mesg_group_id)
    {
        $currentUserId = (int) session('usr_id');
        $groupId = (int) $mesg_group_id;
        $this->getMutableGroupMemberIds($groupId, $currentUserId, 'changed');

        $validated = $request->validate([
            'group_avatar' => 'required|image|mimes:jpeg,jpg,png,webp|max:8192',
        ]);

        $avatar = $validated['group_avatar'];
        $oldAvatar = $this->getCurrentGroupAvatar($groupId);
        $fileName = 'group_'.$groupId.'_'.time().'_'.generateuuid().'.'.strtolower($avatar->getClientOriginalExtension());
        $folderPath = public_path('images/messages/group_images');

        if (! is_dir($folderPath)) {
            mkdir($folderPath, 0755, true);
        }

        $avatar->move($folderPath, $fileName);

        DB::table('message_groups')
            ->where('mesg_group_id', $groupId)
            ->where('mesg_active', 1)
            ->update([
                'mesg_group_image' => $fileName,
            ]);

        $this->deleteUnusedGroupAvatar($oldAvatar, $groupId);

        return redirect()
            ->to(url('messages/chat/'.$groupId))
            ->with('successMessage', 'Group chat avatar updated successfully.');
    }

    public function removeGroupAvatar(Request $request, $mesg_group_id)
    {
        $currentUserId = (int) session('usr_id');
        $groupId = (int) $mesg_group_id;
        $this->getMutableGroupMemberIds($groupId, $currentUserId, 'changed');
        $oldAvatar = $this->getCurrentGroupAvatar($groupId);

        DB::table('message_groups')
            ->where('mesg_group_id', $groupId)
            ->where('mesg_active', 1)
            ->update([
                'mesg_group_image' => null,
            ]);

        $this->deleteUnusedGroupAvatar($oldAvatar, $groupId);

        return redirect()
            ->to(url('messages/chat/'.$groupId))
            ->with('successMessage', 'Group chat avatar removed successfully.');
    }

    public function compose(Request $request)
    {
        $request->validate([
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'integer|exists:users,usr_id',
        ]);

        $payload = $this->validateMessagePayload($request);
        $currentUserId = (int) session('usr_id');
        $recipients = array_values(array_unique(array_map('intval', $request->recipients)));
        $imagePath = $this->storeMessageImage($payload['mes_image']);

        try {
            $groupId = DB::transaction(function () use ($currentUserId, $recipients, $payload, $imagePath) {
                if (count($recipients) === 1) {
                    $existingDirectGroupId = $this->find_direct_group($currentUserId, $recipients[0]);

                    if ($existingDirectGroupId) {
                        $groupId = $existingDirectGroupId;
                    }
                }

                // Full participant list = sender + all recipients (sorted for matching)
                $allParticipants = array_unique(array_merge([$currentUserId], $recipients));
                sort($allParticipants);

                if (! isset($groupId)) {
                    // Try to find existing group with EXACTLY these members
                    $existingGroupId = $this->find_exact_group($allParticipants);

                    if ($existingGroupId) {
                        $groupId = $existingGroupId;
                    } else {
                        // Create new group — get next group id
                        $lastGroup = DB::table('message_groups')->max('mesg_group_id');
                        $groupId = $lastGroup ? $lastGroup + 1 : 1;

                        // Insert one row per participant
                        $now = Carbon::now();
                        foreach ($allParticipants as $userId) {
                            DB::table('message_groups')->insert([
                                'mesg_uuid' => generateuuid(),
                                'mesg_group_id' => $groupId,
                                'mesg_group_name' => null,
                                'mesg_group_image' => null,
                                'usr_id' => $userId,
                                'mesg_date_created' => $now,
                                'mesg_created_by' => $currentUserId,
                                'mesg_active' => 1,
                            ]);
                        }
                    }
                }

                $this->insertMessageRecord($groupId, $currentUserId, $payload['mes_content'], $imagePath);

                return $groupId;
            });
        } catch (Throwable $exception) {
            $this->deleteMessageImage($imagePath);

            throw $exception;
        }

        return redirect()->to(url('messages/chat/'.$groupId));
    }

    public function unsend(Request $request, $mes_id)
    {
        $message = $this->getEditableMessage((int) $mes_id);

        DB::table('messages')
            ->where('mes_id', $message->mes_id)
            ->update([
                'mes_content' => self::UNSENT_MESSAGE_PLACEHOLDER,
                'mes_active' => 0,
            ]);

        Cache::forget($this->messageImageCacheKey((int) $message->mes_id));

        return redirect()
            ->to(url('messages/chat/'.$message->mesg_group_id))
            ->with('success', 'Message unsent successfully.');
    }

    public function update(Request $request, $mes_id)
    {
        $message = $this->getEditableMessage((int) $mes_id);
        $validator = Validator::make($request->all(), [
            'mes_content' => 'nullable|string|max:5000',
        ]);

        $validator->after(function ($validator) use ($request, $message) {
            if ($validator->errors()->has('mes_content')) {
                return;
            }

            if ($this->normalizeMessageContent($request->input('mes_content')) === '' && empty($message->mes_image)) {
                $validator->errors()->add('mes_content', 'Write a message before saving your changes.');
            }
        });

        if ($validator->fails()) {
            return redirect()
                ->to(url('messages/chat/'.$message->mesg_group_id))
                ->withErrors($validator, 'editMessage')
                ->withInput()
                ->with('editMessageId', $message->mes_id);
        }

        DB::table('messages')
            ->where('mes_id', $message->mes_id)
            ->update([
                'mes_content' => $this->normalizeMessageContent($request->input('mes_content')),
                'mes_date_modified' => Carbon::now(),
            ]);

        return redirect()
            ->to(url('messages/chat/'.$message->mesg_group_id))
            ->with('success', 'Message edited successfully.');
    }

    public function image(Request $request, $mes_id)
    {
        $access = Cache::get($this->messageImageCacheKey((int) $mes_id));
        $token = $request->query('token');

        if (
            ! is_array($access)
            || ! is_string($token)
            || ! isset($access['path'], $access['token_hash'])
            || ! hash_equals((string) $access['token_hash'], hash('sha256', $token))
        ) {
            abort(404);
        }

        $imagePath = $access['path'];

        $imageDisk = $this->messageImageDisk($imagePath);

        if ($imageDisk === null) {
            abort(404);
        }

        $storage = Storage::disk($imageDisk);

        if (! $storage->exists($imagePath)) {
            abort(404);
        }

        $response = response()->file($storage->path($imagePath), [
            'Content-Disposition' => 'inline',
            'Content-Type' => $storage->mimeType($imagePath) ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);

        $response->setPrivate();
        $response->setMaxAge(0);
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }

    private function registerMessageImageAccess($message, int $currentUserId): ?string
    {
        if (
            (int) $message->mes_active !== 1
            || ! $this->isValidMessageImagePath($message->mes_image)
        ) {
            return null;
        }

        $expiresAt = Carbon::now()->addMinutes(max(1, (int) config('session.lifetime', 120)));
        $token = hash_hmac(
            'sha256',
            (int) $message->mes_id.'|'.$message->mes_image,
            (string) config('app.key')
        );
        Cache::put(
            $this->messageImageCacheKey((int) $message->mes_id),
            [
                'path' => $message->mes_image,
                'token_hash' => hash('sha256', $token),
            ],
            $expiresAt
        );

        return route('messages.image', [
            'mes_id' => (int) $message->mes_id,
            'token' => $token,
        ], false);
    }

    private function messageImageCacheKey(int $messageId): string
    {
        return 'message-image:'.$messageId;
    }

    private function find_direct_group(int $currentUserId, int $recipientId): ?int
    {
        return DB::table('message_groups')
            ->where('mesg_active', 1)
            ->where(function ($query) {
                $query->whereNull('mesg_group_name')
                    ->orWhere('mesg_group_name', '');
            })
            ->select('mesg_group_id')
            ->groupBy('mesg_group_id')
            ->havingRaw('COUNT(DISTINCT usr_id) = 2')
            ->havingRaw('COUNT(DISTINCT CASE WHEN usr_id IN (?, ?) THEN usr_id END) = 2', [
                $currentUserId,
                $recipientId,
            ])
            ->orderByDesc(DB::raw('MAX(mesg_date_created)'))
            ->value('mesg_group_id');
    }

    private function find_exact_group(array $participantIds): ?int
    {
        $count = count($participantIds);

        // Get all group_ids where member count matches
        $candidateGroups = DB::table('message_groups')
            ->where('mesg_active', 1)
            ->select('mesg_group_id')
            ->groupBy('mesg_group_id')
            ->havingRaw('COUNT(DISTINCT usr_id) = ?', [$count])
            ->pluck('mesg_group_id');

        foreach ($candidateGroups as $groupId) {
            $groupMembers = DB::table('message_groups')
                ->where('mesg_group_id', $groupId)
                ->where('mesg_active', 1)
                ->pluck('usr_id')
                ->sort()
                ->values()
                ->toArray();

            $sortedParticipants = $participantIds;
            sort($sortedParticipants);

            if ($groupMembers === $sortedParticipants) {
                return $groupId;
            }
        }

        return null;
    }

    private function getComposeUsers(int $currentUserId)
    {
        return DB::table('users')
            ->where('usr_id', '!=', $currentUserId)
            ->select('usr_id', 'usr_first_name', 'usr_middle_name', 'usr_last_name')
            ->orderBy('usr_first_name')
            ->get();
    }

    private function getEditableMessage(int $messageId)
    {
        $currentUserId = (int) session('usr_id');
        $message = DB::table('messages')
            ->where('mes_id', $messageId)
            ->first();

        if (! $message) {
            abort(404);
        }

        if ((int) $message->mes_created_by !== $currentUserId) {
            abort(403);
        }

        if ((int) $message->mes_active !== 1) {
            abort(403, 'This message can no longer be changed.');
        }

        $isMember = DB::table('message_groups')
            ->where('mesg_group_id', $message->mesg_group_id)
            ->where('usr_id', $currentUserId)
            ->where('mesg_active', 1)
            ->exists();

        if (! $isMember) {
            abort(403);
        }

        if (Carbon::parse($message->mes_date_created)->addHour()->lt(Carbon::now())) {
            abort(403, 'Messages can only be changed within 1 hour.');
        }

        return $message;
    }

    private function validateMessagePayload(Request $request): array
    {
        $validated = $request->validate([
            'mes_content' => 'nullable|string|max:5000',
            'mes_image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:8192',
        ]);

        $content = $this->normalizeMessageContent($validated['mes_content'] ?? null);

        if ($content === '' && ! $request->hasFile('mes_image')) {
            throw ValidationException::withMessages([
                'mes_content' => 'Write a message or attach an image.',
            ]);
        }

        return [
            'mes_content' => $content,
            'mes_image' => $request->file('mes_image'),
        ];
    }

    private function normalizeMessageContent($content): string
    {
        return is_string($content) ? trim($content) : '';
    }

    private function createMessage(int $groupId, int $userId, array $payload): void
    {
        $imagePath = $this->storeMessageImage($payload['mes_image']);

        try {
            $this->insertMessageRecord($groupId, $userId, $payload['mes_content'], $imagePath);
        } catch (Throwable $exception) {
            $this->deleteMessageImage($imagePath);

            throw $exception;
        }
    }

    private function insertMessageRecord(int $groupId, int $userId, string $content, ?string $imagePath): void
    {
        DB::table('messages')->insert([
            'mes_uuid' => generateuuid(),
            'mesg_group_id' => $groupId,
            'mes_content' => $content,
            'mes_image' => $imagePath,
            'mes_date_created' => Carbon::now(),
            'mes_date_modified' => null,
            'mes_created_by' => $userId,
            'mes_active' => 1,
        ]);
    }

    private function storeMessageImage($image): ?string
    {
        if (! $image) {
            return null;
        }

        $extension = strtolower($image->getClientOriginalExtension());
        $fileName = generateuuid().'.'.$extension;
        $imagePath = self::MESSAGE_IMAGE_DIRECTORY.'/'.$fileName;
        $storage = Storage::disk('public');

        if (! $storage->exists(self::MESSAGE_IMAGE_DIRECTORY) && ! $storage->makeDirectory(self::MESSAGE_IMAGE_DIRECTORY)) {
            throw new RuntimeException('Unable to store the message image.');
        }

        $source = match ($extension) {
            'jpg', 'jpeg' => imagecreatefromjpeg($image->getPathname()),
            'png' => imagecreatefrompng($image->getPathname()),
            'webp' => imagecreatefromwebp($image->getPathname()),
            default => false,
        };

        if ($source === false) {
            throw new RuntimeException('Unable to process the message image.');
        }

        try {
            if ($extension === 'png') {
                if (! imageistruecolor($source)) {
                    imagepalettetotruecolor($source);
                }

                imagealphablending($source, false);
                imagesavealpha($source, true);
            }

            $stored = match ($extension) {
                'jpg', 'jpeg' => imagejpeg($source, $storage->path($imagePath), self::MESSAGE_IMAGE_QUALITY),
                'png' => imagepng($source, $storage->path($imagePath), self::MESSAGE_PNG_COMPRESSION),
                'webp' => imagewebp($source, $storage->path($imagePath), self::MESSAGE_IMAGE_QUALITY),
                default => false,
            };
        } finally {
            imagedestroy($source);
        }

        if (! $stored) {
            $storage->delete($imagePath);

            throw new RuntimeException('Unable to store the message image.');
        }

        return $imagePath;
    }

    private function deleteMessageImage(?string $imagePath): void
    {
        $imageDisk = $this->messageImageDisk($imagePath);

        if ($imageDisk !== null) {
            Storage::disk($imageDisk)->delete($imagePath);
        }
    }

    private function isValidMessageImagePath($imagePath): bool
    {
        return $this->messageImageDisk($imagePath) !== null;
    }

    private function messageImageDisk($imagePath): ?string
    {
        if (! is_string($imagePath)) {
            return null;
        }

        $filePattern = '/[a-z0-9]{32}\.(?:jpe?g|png|webp)$';

        if (preg_match('#^'.self::MESSAGE_IMAGE_DIRECTORY.$filePattern.'#', $imagePath) === 1) {
            return 'public';
        }

        if (preg_match('#^'.self::LEGACY_MESSAGE_IMAGE_DIRECTORY.$filePattern.'#', $imagePath) === 1) {
            return 'local';
        }

        return null;
    }

    private function getMutableGroupMemberIds(int $groupId, int $currentUserId, string $action)
    {
        $activeMemberIds = DB::table('message_groups')
            ->where('mesg_group_id', $groupId)
            ->where('mesg_active', 1)
            ->distinct()
            ->pluck('usr_id')
            ->map(fn ($userId) => (int) $userId);

        if (! $activeMemberIds->contains($currentUserId)) {
            abort(403, 'Unauthorized');
        }

        if ($activeMemberIds->count() <= 2) {
            abort(403, 'Only group chats can be '.$action.'.');
        }

        return $activeMemberIds;
    }

    private function getCurrentGroupAvatar(int $groupId): ?string
    {
        $avatar = DB::table('message_groups')
            ->where('mesg_group_id', $groupId)
            ->where('mesg_active', 1)
            ->whereNotNull('mesg_group_image')
            ->value('mesg_group_image');

        return $avatar ?: null;
    }

    private function deleteUnusedGroupAvatar(?string $fileName, int $groupId): void
    {
        if (empty($fileName)) {
            return;
        }

        $isUsedByAnotherGroup = DB::table('message_groups')
            ->where('mesg_group_id', '!=', $groupId)
            ->where('mesg_group_image', $fileName)
            ->exists();

        if ($isUsedByAnotherGroup) {
            return;
        }

        $path = public_path('images/messages/group_images/'.basename($fileName));

        if (is_file($path)) {
            unlink($path);
        }
    }

    private function getConversationRail(int $currentUserId, string $search = '', ?int $activeGroupId = null)
    {
        $query = DB::table('message_groups as mg')
            ->join('messages as last_msg', function ($join) {
                $join->on('last_msg.mes_id', '=', DB::raw(
                    '(SELECT m2.mes_id FROM messages m2
                      WHERE m2.mesg_group_id = mg.mesg_group_id
                      ORDER BY m2.mes_date_created DESC
                      LIMIT 1)'
                ));
            })
            ->join('users as sender', 'sender.usr_id', '=', 'last_msg.mes_created_by')
            ->where('mg.usr_id', $currentUserId)
            ->where('mg.mesg_active', 1);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('mg.mesg_group_name', 'LIKE', "%$search%")
                    ->orWhereExists(function ($sub) use ($search) {
                        $sub->select(DB::raw(1))
                            ->from('message_groups as mg2')
                            ->join('users as u2', 'u2.usr_id', '=', 'mg2.usr_id')
                            ->whereColumn('mg2.mesg_group_id', 'mg.mesg_group_id')
                            ->where('mg2.mesg_active', 1)
                            ->where(DB::raw("CONCAT(u2.usr_first_name, ' ', u2.usr_last_name)"), 'LIKE', "%$search%");
                    });
            });
        }

        $query->select(
            'mg.mesg_group_id',
            'mg.mesg_group_name',
            'mg.mesg_group_image',
            'last_msg.mes_content',
            'last_msg.mes_image',
            'last_msg.mes_active',
            'last_msg.mes_date_created as last_message_date',
            DB::raw("CONCAT(sender.usr_first_name, ' ', sender.usr_last_name) as sender_name"),
            'last_msg.mes_created_by as sender_id'
        )
            ->orderBy('last_msg.mes_date_created', 'desc');

        $conversations = $search === ''
            ? $query->paginate(10, ['*'], 'conversations_page')
            : $query->get();

        if ($conversations instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $conversations->appends(request()->except('conversations_page'));
        }

        return $this->normalizeConversationRail($conversations, $currentUserId, $search, $activeGroupId);
    }

    private function normalizeConversationRail($conversations, int $currentUserId, string $search = '', ?int $activeGroupId = null)
    {
        $items = collect($conversations instanceof \Illuminate\Pagination\LengthAwarePaginator ? $conversations->items() : $conversations->all());
        $groupIds = $items->pluck('mesg_group_id')->filter()->unique()->values();

        if ($activeGroupId && $search === '' && ! $groupIds->contains($activeGroupId)) {
            $activeConversation = $this->getConversationByGroupId($currentUserId, $activeGroupId);

            if ($activeConversation) {
                $items->prepend($activeConversation);

                if ($conversations instanceof \Illuminate\Pagination\LengthAwarePaginator && $items->count() > $conversations->perPage()) {
                    $items = $items->take($conversations->perPage());
                }

                $groupIds = $items->pluck('mesg_group_id')->filter()->unique()->values();
            }
        }

        $members = $groupIds->isEmpty()
            ? collect()
            : DB::table('message_groups as mg')
                ->join('users as u', 'u.usr_id', '=', 'mg.usr_id')
                ->whereIn('mg.mesg_group_id', $groupIds->all())
                ->where('mg.mesg_active', 1)
                ->select('mg.mesg_group_id', 'u.usr_id', 'u.usr_first_name', 'u.usr_last_name')
                ->get()
                ->groupBy('mesg_group_id');

        $normalized = $items->map(function ($conversation) use ($members, $currentUserId, $activeGroupId, $search) {
            return $this->formatConversationListItem(
                $conversation,
                $members->get($conversation->mesg_group_id, collect()),
                $currentUserId,
                $activeGroupId,
                $search
            );
        });

        if ($conversations instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $conversations->setCollection($normalized);

            return $conversations;
        }

        return $normalized;
    }

    private function getConversationByGroupId(int $currentUserId, int $groupId)
    {
        return DB::table('message_groups as mg')
            ->join('messages as last_msg', function ($join) {
                $join->on('last_msg.mes_id', '=', DB::raw(
                    '(SELECT m2.mes_id FROM messages m2
                      WHERE m2.mesg_group_id = mg.mesg_group_id
                      ORDER BY m2.mes_date_created DESC
                      LIMIT 1)'
                ));
            })
            ->join('users as sender', 'sender.usr_id', '=', 'last_msg.mes_created_by')
            ->where('mg.usr_id', $currentUserId)
            ->where('mg.mesg_active', 1)
            ->where('mg.mesg_group_id', $groupId)
            ->select(
                'mg.mesg_group_id',
                'mg.mesg_group_name',
                'mg.mesg_group_image',
                'last_msg.mes_content',
                'last_msg.mes_image',
                'last_msg.mes_active',
                'last_msg.mes_date_created as last_message_date',
                DB::raw("CONCAT(sender.usr_first_name, ' ', sender.usr_last_name) as sender_name"),
                'last_msg.mes_created_by as sender_id'
            )
            ->first();
    }

    private function formatConversationListItem($conversation, $members, int $currentUserId, ?int $activeGroupId = null, string $search = '')
    {
        $otherMembers = $members->where('usr_id', '!=', $currentUserId)->values();
        $isGroup = $members->count() > 2 || ! empty($conversation->mesg_group_name);

        if (! empty($conversation->mesg_group_name)) {
            $title = $conversation->mesg_group_name;
        } else {
            $title = $otherMembers
                ->map(fn ($member) => trim($member->usr_first_name.' '.$member->usr_last_name))
                ->filter()
                ->implode(', ');
        }

        if ($title === '') {
            $title = 'Conversation';
        }

        $isSelf = (int) $conversation->sender_id === $currentUserId;
        $messagePreview = (int) $conversation->mes_active === 0
            ? self::UNSENT_MESSAGE_PLACEHOLDER
            : ($this->normalizeMessageContent($conversation->mes_content) !== ''
                ? $conversation->mes_content
                : (! empty($conversation->mes_image) ? 'Image' : ''));
        $preview = $isGroup && ! $isSelf
            ? $conversation->sender_name.': '.$messagePreview
            : ($isSelf ? 'You: '.$messagePreview : $messagePreview);

        $avatarUrl = null;
        $avatarIcon = $isGroup ? 'fas fa-users' : 'fas fa-user';

        if ($isGroup && ! empty($conversation->mesg_group_image)) {
            $avatarUrl = asset('images/messages/group_images/'.$conversation->mesg_group_image);
        } elseif (! $isGroup) {
            $firstOther = $otherMembers->first();
            $avatarUrl = asset(getAvatar($firstOther->usr_id ?? $currentUserId));
        }

        $query = [];
        if ($search !== '') {
            $query['search'] = $search;
        }
        if (request()->filled('conversations_page')) {
            $query['conversations_page'] = request()->query('conversations_page');
        }

        return (object) [
            'id' => $conversation->mesg_group_id,
            'title' => $title,
            'preview' => $preview,
            'is_group' => $isGroup,
            'is_active' => (int) $conversation->mesg_group_id === (int) $activeGroupId,
            'last_message_label' => Carbon::parse($conversation->last_message_date)->format('M j'),
            'avatar_url' => $avatarUrl,
            'avatar_icon' => $avatarIcon,
            'link' => url('messages/chat/'.$conversation->mesg_group_id).(! empty($query) ? '?'.http_build_query($query) : ''),
        ];
    }

    private function buildChatContext($groupInfo, $members, int $currentUserId)
    {
        $otherMembers = $members->where('usr_id', '!=', $currentUserId)->values();
        $isGroup = $members->count() > 2 || ! empty($groupInfo->mesg_group_name);

        if (! empty($groupInfo->mesg_group_name)) {
            $title = $groupInfo->mesg_group_name;
        } else {
            $title = $otherMembers
                ->map(fn ($member) => trim($member->usr_first_name.' '.$member->usr_last_name))
                ->filter()
                ->implode(', ');
        }

        if ($title === '') {
            $title = 'Conversation';
        }

        if ($isGroup) {
            $subtitle = $members->count().' members';
        } else {
            $subtitle = 'Direct conversation';
        }

        $avatarUrl = null;
        $avatarIcon = $isGroup ? 'fas fa-users' : 'fas fa-user';

        if ($isGroup && ! empty($groupInfo->mesg_group_image)) {
            $avatarUrl = asset('images/messages/group_images/'.$groupInfo->mesg_group_image);
        } elseif (! $isGroup) {
            $firstOther = $otherMembers->first();
            $avatarUrl = asset(getAvatar($firstOther->usr_id ?? $currentUserId));
        }

        return (object) [
            'title' => $title,
            'subtitle' => $subtitle,
            'group_name' => $groupInfo->mesg_group_name,
            'is_group' => $isGroup,
            'can_rename' => $members->count() > 2,
            'can_update_avatar' => $members->count() > 2,
            'has_group_avatar' => $isGroup && ! empty($groupInfo->mesg_group_image),
            'avatar_url' => $avatarUrl,
            'avatar_icon' => $avatarIcon,
        ];
    }
}
