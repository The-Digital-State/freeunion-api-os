<?php

declare(strict_types=1);

namespace App\Models;

use App\Events\AnnouncementEvent;
use App\Events\NotificationEvent;
use App\Models\Casts\OrgHiddensCast;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/* TODO: delete status */

/**
 * Class Organization
 *
 * @property int $id
 * @property string $did
 * @property int $user_id
 * @property int|null $type_id
 * @property string|null $type_name
 * @property int $request_type
 * @property string $name
 * @property string $short_name
 * @property string|null $avatar
 * @property string $description
 * @property string|null $site
 * @property string|null $email
 * @property string|null $address
 * @property string|null $phone
 * @property array|null $social
 * @property string|null $status
 * @property int $registration
 * @property int $public_status
 * @property bool $is_verified
 * @property array|null $hiddens
 * @property int $sort
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property Collection<int, ActivityScope> $activityScope
 * @property Collection<int, Material> $materials
 * @property Collection<int, MSection> $mSections
 * @property Collection<int, Banner> $banners
 * @property Collection<int, Banner> $bannersEnabled
 * @property Collection<int, ChatNotification> $chatNotifications
 * @property Collection<int, ChatParticipant> $chatParticipants
 * @property Collection<int, DeskTask> $deskTasks
 * @property Collection<int, DocTemplate> $docTemplates
 * @property Collection<int, EnterRequest> $enterRequests
 * @property Expense|null $expense
 * @property Collection<int, Fundraising> $fundraisings
 * @property Collection<int, Fundraising> $fundraisingsAndSubscriptions
 * @property Collection<int, HelpOfferLink> $helpOfferLinks
 * @property Collection<int, InterestScope> $interestScope
 * @property Collection<int, InviteLink> $inviteLinks
 * @property Collection<int, MemberList> $memberLists
 * @property Collection<int, User> $members
 * @property Collection<int, User> $membersAdmin
 * @property Collection<int, News> $news
 * @property Collection<int, OrganizationChat> $organizationChats
 * @property Collection<int, Organization> $organizationChildren
 * @property Collection<int, Organization> $organizationParents
 * @property Collection<int, OrganizationTelepost> $organizationTeleposts
 * @property OrganizationType|null $organizationType
 * @property User|null $owner
 * @property Collection<int, PaymentSystem> $paymentSystems
 * @property Collection<int, Quiz> $quizzes
 * @property Collection<int, Fundraising> $subscriptions
 * @property Collection<int, Suggestion> $suggestions
 * @property int $desk_tasks_count
 * @property int $materials_count
 * @property int $members_count
 * @property int $news_count
 * @property int $suggestions_count
 */
class Organization extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const REQUEST_TYPE_SIMPLE = 0;

    public const REQUEST_TYPE_APPROVE = 1;

    public const REQUEST_TYPE_DOC = 2;

    public const REQUEST_TYPE_DOC_PAPER = 3;

    public const REGISTRATION_NO = 0;

    public const REGISTRATION_YES = 1;

    public const REGISTRATION_INPROGRESS = 2;

    public const PUBLIC_STATUS_SHOW = 0;

    public const PUBLIC_STATUS_HIDDEN = 1;

    public const PUBLIC_STATUS_PARTIALLY_HIDDEN = 2;

    public const BLOCK_MEMBERS = 'members';

    public const BLOCK_ADMINS = 'admins';

    public const BLOCK_BANNERS = 'banners';

    public const BLOCK_TASKS = 'tasks';

    public const BLOCK_EVENTS = 'events';

    public const BLOCK_NEWS = 'news';

    public const BLOCK_FINANCE = 'finance';

    public const BLOCK_KBASE = 'kbase';

    protected $fillable = [
        'did',
        'type_id',
        'type_name',
        'request_type',
        'name',
        'short_name',
        'description',
        'site',
        'email',
        'address',
        'phone',
        'social',
        'chat',
        'status',
        'registration',
        'public_status',
        'is_verified',
        'hiddens',
    ];

    protected $casts = [
        'social' => 'array',
        'is_verified' => 'bool',
        'hiddens' => OrgHiddensCast::class,
    ];

    /**
     * @param  Builder<Organization>  $query
     * @param  User|null  $user
     * @param  string  $block
     * @return void
     *
     * @noinspection PhpUnusedParameterInspection
     */
    public function scopeVisibled(Builder $query, User|null $user, string $block): void
    {
        $query->where(static function (Builder $q) use ($user) {
            $q->where('public_status', self::PUBLIC_STATUS_SHOW);

            // TODO: Use block name
            $q->orWhere('public_status', self::PUBLIC_STATUS_PARTIALLY_HIDDEN);

            if ($user) {
                $q->orWhereIn('organization_id', $user->membership->pluck('id'));
            }
        });
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function isVisibled(User|null $user, string $block): bool
    {
        return ($this->public_status === self::PUBLIC_STATUS_SHOW)
            || ($this->public_status === self::PUBLIC_STATUS_PARTIALLY_HIDDEN)
            || ($user && $user->membership->pluck('id')->contains($this->id));
    }

    /**
     * @return BelongsToMany<ActivityScope>
     */
    public function activityScope(): BelongsToMany
    {
        return $this->belongsToMany(ActivityScope::class);
    }

    /**
     * @return HasMany<Material>
     */
    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }

    /**
     * @return HasMany<MSection>
     */
    public function mSections(): HasMany
    {
        return $this->hasMany(MSection::class);
    }

    /**
     * @return HasMany<Banner>
     */
    public function banners(): HasMany
    {
        $query = $this->hasMany(Banner::class);
        $query->orderBy('index')
            ->orderBy('id');

        return $query;
    }

    /**
     * @return HasMany<Banner>
     */
    public function bannersEnabled(): HasMany
    {
        $query = $this->hasMany(Banner::class);
        $query->where('enabled', true)
            ->orderBy('index')
            ->orderBy('id');

        return $query;
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
     * @return HasMany<DeskTask>
     */
    public function deskTasks(): HasMany
    {
        return $this->hasMany(DeskTask::class);
    }

    /**
     * @return HasMany<DocTemplate>
     */
    public function docTemplates(): HasMany
    {
        return $this->hasMany(DocTemplate::class);
    }

    /**
     * @return HasMany<EnterRequest>
     */
    public function enterRequests(): HasMany
    {
        return $this->hasMany(EnterRequest::class);
    }

    /**
     * @return HasOne<Expense>
     */
    public function expense(): HasOne
    {
        return $this->hasOne(Expense::class);
    }

    /**
     * @return HasMany<Fundraising>
     */
    public function fundraisings(): HasMany
    {
        $query = $this->hasMany(Fundraising::class);
        $query->where('is_subscription', false);

        return $query;
    }

    /**
     * @return HasMany<Fundraising>
     */
    public function fundraisingsAndSubscriptions(): HasMany
    {
        return $this->hasMany(Fundraising::class);
    }

    /**
     * @return HasMany<HelpOfferLink>
     */
    public function helpOfferLinks(): HasMany
    {
        return $this->hasMany(HelpOfferLink::class);
    }

    /**
     * @return BelongsToMany<InterestScope>
     */
    public function interestScope(): BelongsToMany
    {
        return $this->belongsToMany(InterestScope::class);
    }

    /**
     * @return HasMany<InviteLink>
     */
    public function inviteLinks(): HasMany
    {
        return $this->hasMany(InviteLink::class);
    }

    /**
     * @return HasMany<MemberList>
     */
    public function memberLists(): HasMany
    {
        return $this->hasMany(MemberList::class);
    }

    /**
     * @return BelongsToMany<User>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'membership')
            ->withPivot([
                'position_id',
                'position_name',
                'description',
                'permissions',
                'comment',
                'points',
                'joined_at',
            ]);
    }

    /**
     * @return BelongsToMany<User>
     */
    public function membersAdmin(): BelongsToMany
    {
        return $this->members()
            ->wherePivot('permissions', '>', 0);
    }

    /**
     * @return HasMany<News>
     */
    public function news(): HasMany
    {
        return $this->hasMany(News::class);
    }

    /**
     * @return HasMany<OrganizationChat>
     */
    public function organizationChats(): HasMany
    {
        return $this->hasMany(OrganizationChat::class);
    }

    /**
     * @return BelongsToMany<Organization>
     */
    public function organizationChildren(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'organization_hierarchy', 'parent_id', 'child_id');
    }

    /**
     * @return BelongsToMany<Organization>
     */
    public function organizationParents(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'organization_hierarchy', 'child_id', 'parent_id');
    }

    /**
     * @return HasMany<OrganizationTelepost>
     */
    public function organizationTeleposts(): HasMany
    {
        return $this->hasMany(OrganizationTelepost::class);
    }

    /**
     * @return BelongsTo<OrganizationType, Organization>
     */
    public function organizationType(): BelongsTo
    {
        return $this->belongsTo(OrganizationType::class, 'type_id');
    }

    /**
     * @return BelongsTo<User, Organization>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<PaymentSystem>
     */
    public function paymentSystems(): HasMany
    {
        return $this->hasMany(PaymentSystem::class);
    }

    /**
     * @return HasMany<Quiz>
     */
    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    /**
     * @return HasMany<Fundraising>
     */
    public function subscriptions(): HasMany
    {
        $query = $this->hasMany(Fundraising::class);
        $query->where('is_subscription', true);

        return $query;
    }

    /**
     * @return HasMany<Suggestion>
     */
    public function suggestions(): HasMany
    {
        return $this->hasMany(Suggestion::class);
    }

    public function getLogo(): string
    {
        if ($this->avatar) {
            /**
             * @var FilesystemAdapter $storage
             */
            $storage = Storage::disk(config('filesystems.public'));

            return $storage->url("logo/$this->avatar");
        }

        return '';
    }

    public function sendNotification(array $to, string $message): void
    {
        event(new NotificationEvent($to, $message, 'organization', $this->id));
    }

    public function sendAnnouncement(array $to, string $title, string $message): void
    {
        event(new AnnouncementEvent($to, $title, $message, 'organization', $this->id));
    }

    public static function requestTypes(): array
    {
        return [
            self::REQUEST_TYPE_SIMPLE,
            self::REQUEST_TYPE_APPROVE,
            self::REQUEST_TYPE_DOC,
            self::REQUEST_TYPE_DOC_PAPER,
        ];
    }

    public static function registrationTypes(): array
    {
        return [
            self::REGISTRATION_NO,
            self::REGISTRATION_YES,
            self::REGISTRATION_INPROGRESS,
        ];
    }

    public static function publicStatuses(): array
    {
        return [
            self::PUBLIC_STATUS_SHOW,
            self::PUBLIC_STATUS_HIDDEN,
            self::PUBLIC_STATUS_PARTIALLY_HIDDEN,
        ];
    }

    public static function availableBlocks(): array
    {
        return [
            self::BLOCK_TASKS,
            self::BLOCK_MEMBERS,
            self::BLOCK_ADMINS,
            self::BLOCK_EVENTS,
            self::BLOCK_FINANCE,
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        self::created(static function (Organization $organization) {
            $organization->members()->attach($organization->user_id, [
                'position_id' => 1,
                'permissions' => PHP_INT_MAX,
            ]);

            DB::beginTransaction();

            foreach (HelpOffer::defaultHelpOffers() as $item) {
                DB::table('help_offers')->insert([
                    [
                        'text' => $item,
                        'organization_id' => $organization->id,
                        'enabled' => true,
                    ],
                ]);
            }

            DB::commit();
        });
    }
}
