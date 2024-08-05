<?php

declare(strict_types=1);

namespace App\Models;

use App\Events\NotificationEvent;
use App\Models\Casts\ArrayFieldsCast;
use App\Traits\CanResetPassword;
use App\Traits\MustVerifyEmail;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

/**
 * Class User
 *
 * @property int $id
 * @property int|null $referal_id
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string|null $new_email
 * @property string $password
 * @property string|null $remember_token
 * @property bool $is_admin
 * @property int $is_public
 * @property bool $is_verified
 * @property int $change_public
 * @property string|null $public_family
 * @property string|null $public_name
 * @property string|null $public_avatar
 * @property array $hiddens
 * @property array $settings
 * @property Carbon|null $last_action_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Collection<int, ChatMessage> $chatMessages
 * @property Collection<int, ChatNotification> $chatNotifications
 * @property Collection<int, ChatParticipant> $chatParticipants
 * @property Collection<int, DeskTask> $deskAttach
 * @property Collection<int, DeskComment> $deskComments
 * @property Collection<int, DeskImage> $deskImages
 * @property Collection<int, DeskTask> $deskTasks
 * @property Collection<int, EnterRequest> $enterRequests
 * @property Collection<int, HelpOfferLink> $helpOfferLinks
 * @property UserInfo $info
 * @property Collection<int, InviteLink> $inviteLinks
 * @property Collection<int, User> $invited
 * @property Collection<int, MemberList> $memberLists
 * @property Collection<int, Organization> $membership
 * @property Mfa|null $mfa
 * @property Collection<int, News> $news
 * @property Collection<int, NewsClick> $newsClicks
 * @property Collection<int, NewsImpression> $newsImpressions
 * @property Collection<int, Organization> $organizations
 * @property Collection<int, Organization> $organizationsAdminister
 * @property Collection<int, Quiz> $quizzes
 * @property Collection<int, QuizAnswer> $quizAnswers
 * @property User|null $referal
 * @property UserSecure $secure
 * @property Collection<int, Suggestion> $suggestions
 * @property bool $canRemoved Virtual property for list of members
 */
class User extends Authenticatable
{
    use CanResetPassword;
    use HasApiTokens;
    use HasFactory;
    use MustVerifyEmail;
    use Notifiable;

    public const SECURE_FIELDS = [
        'family',
        'name',
        'patronymic',
        'birthday',
        'work_position',
        'address',
        'phone',
    ];

    // TODO: Remove fields from fillable
    protected $fillable = [
        'email',
        'password',
        'is_admin',
        'is_public',
        'is_verified',
        'hiddens',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_admin' => 'bool',
        'is_verified' => 'bool',
        'hiddens' => ArrayFieldsCast::class,
        'settings' => 'array',
        'last_action_at' => 'datetime',
    ];

    /**
     * @return HasMany<ChatMessage>
     */
    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    /**
     * @return HasMany<ChatNotification>
     */
    public function chatNotifications(): HasMany
    {
        return $this->hasMany(ChatNotification::class);
    }

    /**
     * @return HasMany<ChatParticipant>
     */
    public function chatParticipants(): HasMany
    {
        return $this->hasMany(ChatParticipant::class);
    }

    /**
     * @return BelongsToMany<DeskTask>
     */
    public function deskAttach(): BelongsToMany
    {
        return $this->belongsToMany(DeskTask::class);
    }

    /**
     * @return HasMany<DeskComment>
     */
    public function deskComments(): HasMany
    {
        return $this->hasMany(DeskComment::class);
    }

    /**
     * @return HasMany<DeskImage>
     */
    public function deskImages(): HasMany
    {
        return $this->hasMany(DeskImage::class);
    }

    /**
     * @return HasMany<DeskTask>
     */
    public function deskTasks(): HasMany
    {
        return $this->hasMany(DeskTask::class);
    }

    /**
     * @return HasMany<EnterRequest>
     */
    public function enterRequests(): HasMany
    {
        return $this->hasMany(EnterRequest::class);
    }

    /**
     * @return HasMany<HelpOfferLink>
     */
    public function helpOfferLinks(): HasMany
    {
        return $this->hasMany(HelpOfferLink::class);
    }

    /**
     * @return HasOne<UserInfo>
     */
    public function info(): HasOne
    {
        return $this->hasOne(UserInfo::class);
    }

    /**
     * @return HasMany<InviteLink>
     */
    public function inviteLinks(): HasMany
    {
        return $this->hasMany(InviteLink::class);
    }

    /**
     * @return HasMany<User>
     */
    public function invited(): HasMany
    {
        return $this->hasMany(self::class, 'referal_id');
    }

    /**
     * @return BelongsToMany<MemberList>
     */
    public function memberLists(): BelongsToMany
    {
        return $this->belongsToMany(MemberList::class, 'member_list_user');
    }

    /**
     * @return BelongsToMany<Organization>
     */
    public function membership(): BelongsToMany
    {
        $query = $this->belongsToMany(Organization::class, 'membership');
        $query->withPivot([
            'position_id',
            'position_name',
            'description',
            'permissions',
            'comment',
            'points',
            'joined_at',
        ]);

        return $query;
    }

    /**
     * @return HasOne<Mfa>
     */
    public function mfa(): HasOne
    {
        return $this->hasOne(Mfa::class);
    }

    /**
     * @return HasMany<News>
     */
    public function news(): HasMany
    {
        return $this->hasMany(News::class);
    }

    /**
     * @return HasMany<NewsClick>
     */
    public function newsClicks(): HasMany
    {
        return $this->hasMany(NewsClick::class);
    }

    /**
     * @return HasMany<NewsImpression>
     */
    public function newsImpressions(): HasMany
    {
        return $this->hasMany(NewsImpression::class);
    }

    /**
     * @return HasMany<Organization>
     */
    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class);
    }

    /**
     * @return BelongsToMany<Organization>
     */
    public function organizationsAdminister(): BelongsToMany
    {
        return $this->membership()
            ->wherePivot('permissions', '>', 0);
    }

    /**
     * @return HasMany<Quiz>
     */
    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    /**
     * @return HasMany<QuizAnswer>
     */
    public function quizAnswers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class);
    }

    /**
     * @return BelongsTo<User, User>
     */
    public function referal(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referal_id');
    }

    /**
     * @return HasOne<UserSecure>
     */
    public function secure(): HasOne
    {
        return $this->hasOne(UserSecure::class);
    }

    /**
     * @return HasMany<Suggestion>
     */
    public function suggestions(): HasMany
    {
        return $this->hasMany(Suggestion::class);
    }

    public function saveUserFields(array $values): void
    {
        $info = $this->info()->firstOrNew();
        $infoFields = (new UserInfo())->getFillable();
        /** @var UserSecure $secure */
        $secure = $this->secure()->firstOrNew();

        $data = array_merge($info->toArray(), $secure->data, $values);
        $secureFields = [];

        foreach ($data as $name => $value) {
            if (in_array($name, $infoFields, true)) {
                if (in_array($name, $this->hiddens, true)) {
                    /** @noinspection PhpVariableVariableInspection */
                    $info->$name = null;
                    $secureFields[$name] = $value;
                } else {
                    /** @noinspection PhpVariableVariableInspection */
                    $info->$name = $value;
                }
            }
        }

        $info->save();

        $secure->data = $secureFields;
        $secure->save();
    }

    /**
     * @throws Exception
     */
    public function generatePublicName(bool $save = false, int $initSex = -1): array
    {
//        $lang = Str::lower($this->info->country) ?? 'by';
        // TODO: Force by lang
        $lang = 'by';

        $sex = $initSex === 0 || $initSex === 1 ? $initSex : $this->info->sex ?? 0;
        $root = resource_path("names/$lang");

        if (! file_exists($root)) {
            $root = resource_path('names/en');
        }

        if (file_exists($root."/$sex")) {
            $root .= "/$sex";
        }

        $fsList = array_map(static function ($item) {
            return trim($item);
        }, file($root.'/f') ?: []);
        $nsList = array_map(static function ($item) {
            return trim($item);
        }, file($root.'/n') ?: []);

        $fsCount = count($fsList);
        $nsCount = count($nsList);

        if ($fsCount === 0 || $nsCount === 0) {
            return [null, null];
        }

        $fsIndex = random_int(0, $fsCount - 1);
        $nsIndex = random_int(0, $nsCount - 1);

        $fsAdd = 0;
        $nsAdd = -1;

        do {
            $nsAdd++;

            if ($nsAdd === $nsCount) {
                $fsAdd++;
                $nsAdd = 0;
            }

            if ($fsAdd === $fsCount) {
                return [null, null];
            }

            $exist = self::query()
                ->where(['public_family' => $fsList[($fsIndex + $fsAdd) % $fsCount]])
                ->where(['public_name' => $nsList[($nsIndex + $nsAdd) % $nsCount]])
                ->exists();
        } while ($exist);

        if ($save) {
            $this->public_family = $fsList[($fsIndex + $fsAdd) % $fsCount];
            $this->public_name = $nsList[($nsIndex + $nsAdd) % $nsCount];
        }

        return [$fsList[($fsIndex + $fsAdd) % $fsCount], $nsList[($nsIndex + $nsAdd) % $nsCount]];
    }

    public function getPublicFamily(): string
    {
        if ($this->info->family) {
            if ($this->is_public) {
                return $this->info->family;
            }

            if (! in_array('family', $this->hiddens, true)) {
                return $this->info->family;
            }
        }

        return $this->public_family ?? '';
    }

    public function getPublicName(): string
    {
        if ($this->info->name) {
            if ($this->is_public) {
                return $this->info->name;
            }

            if (! in_array('name', $this->hiddens, true)) {
                return $this->info->name;
            }
        }

        return $this->public_name ?? '';
    }

    public function isSAdmin(): bool
    {
        return $this->is_admin;
    }

    public function getAvatar(): string
    {
        if ($this->public_avatar) {
            /**
             * @var FilesystemAdapter $storage
             */
            $storage = Storage::disk(config('filesystems.public'));

            return $storage->url("avatars/$this->public_avatar");
        }

        return '';
    }

    public function sendNotification(int $to, string $message): void
    {
        event(new NotificationEvent([$to], $message, 'user', $this->id));
    }

    public function routeNotificationForMail(?Notification $notification = null): string
    {
        if ($notification && method_exists($notification, 'getNotificationEmail')) {
            return $notification->getNotificationEmail($this);
        }

        return $this->email;
    }

    protected static function boot(): void
    {
        parent::boot();

        self::created(static function (User $user) {
            $supportID = config('app.organizations.support');

            if ($supportID === null) {
                return;
            }

            /** @var Organization|null $organization */
            $organization = Organization::find($supportID);

            if ($organization === null) {
                return;
            }

            $supportUser = $organization->members()->first();

            if ($supportUser === null) {
                return;
            }

            $conversation = new ChatConversation();
            $conversation->is_direct = true;
            $conversation->save();

            /** @var ChatParticipant $participantSupport */
            $participantSupport = $conversation->chatParticipants()->make();
            $participantSupport->user_id = $supportUser->id;
            $participantSupport->organization_id = $supportID;
            $participantSupport->save();

            $participant = $conversation->chatParticipants()->make();
            $participant->user_id = $user->id;
            $participant->organization_id = null;
            $participant->save();

            /** @var string $messageText */
            $messageText = __('common.support_welcome_message1');
            $message = $conversation->chatMessages()->make();
            $message->chat_participant_id = $participantSupport->id;
            $message->user_id = $participantSupport->user_id;
            $message->content = $messageText;
            $message->save();

            /** @var string $messageText */
            $messageText = __('common.support_welcome_message2');
            $message = $conversation->chatMessages()->make();
            $message->chat_participant_id = $participantSupport->id;
            $message->user_id = $participantSupport->user_id;
            $message->content = $messageText;
            $message->save();
        });
    }
}
