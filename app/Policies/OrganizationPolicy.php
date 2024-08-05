<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrganizationPolicy
{
    use HandlesAuthorization;

    public const ORGANIZATION = 'organization';

    public const ORGANIZATION_UPDATE = 'organization_update';

    public const ORGANIZATION_DESTROY = 'organization_destroy';

    public const MEMBERS = 'members';

    public const MEMBERS_VIEW = 'members_view';

    public const MEMBERS_UPDATE = 'members_update';

    public const MEMBERS_APPLY = 'members_apply';

    public const MEMBERS_KICK = 'members_kick';

    public const DOC_TEMPLATES = 'doc_templates';

    public const DOC_TEMPLATES_VIEW = 'doc_templates_view';

    public const DOC_TEMPLATES_STORE = 'doc_templates_store';

    public const DOC_TEMPLATES_UPDATE = 'doc_templates_update';

    public const DOC_TEMPLATES_DESTROY = 'doc_templates_destroy';

    public const BANNERS = 'banners';

    public const BANNERS_STORE = 'banners_store';

    public const BANNERS_UPDATE = 'banners_update';

    public const BANNERS_DESTROY = 'banners_destroy';

    public const ORG_COMMUNICATE = 'org_communicate';

    public const TASKS = 'tasks';

    public const TASKS_VIEW = 'tasks_view';

    public const TASKS_STORE = 'tasks_store';

    public const TASKS_UPDATE = 'tasks_update';

    public const TASKS_DESTROY = 'tasks_destroy';

    public const EVENTS = 'events';

    public const EVENTS_VIEW = 'events_view';

    public const EVENTS_STORE = 'events_store';

    public const EVENTS_UPDATE = 'events_update';

    public const EVENTS_DESTROY = 'events_destroy';

    public const NEWS = 'news';

    public const NEWS_VIEW_UNPUBLISH = 'news_view_unpublish';

    public const NEWS_STORE = 'news_store';

    public const NEWS_UPDATE = 'news_update';

    public const NEWS_DESTROY = 'news_destroy';

    public const NEWS_PUBLISH = 'news_publish';

    public const FINANCE = 'finance';

    public const FINANCE_VIEW = 'finance_view';

    public const FINANCE_MANAGE = 'finance_manage';

    public const CHAT = 'chat';

    public const CHAT_ALLOW = 'chat_allow';

    public const KBASE = 'kbase';

    public const KBASE_VIEW_UNPUBLISH = 'kbase_view_unpublish';

    public const KBASE_STORE = 'kbase_store';

    public const KBASE_UPDATE = 'kbase_update';

    public const KBASE_DESTROY = 'kbase_destroy';

    public const KBASE_PUBLISH = 'kbase_publish';

    public const QUIZ = 'quiz';

    public const QUIZ_VIEW_UNPUBLISH = 'quiz_view_unpublish';

    public const QUIZ_STORE = 'quiz_store';

    public const QUIZ_UPDATE = 'quiz_update';

    public const QUIZ_DESTROY = 'quiz_destroy';

    public const QUIZ_PUBLISH = 'quiz_publish';

    /* phpcs:disable SlevomatCodingStandard.Commenting.DisallowCommentAfterCode */
    public const PERMISSIONS = [
        self::ORGANIZATION_UPDATE, // 0
        self::ORGANIZATION_DESTROY, // 1

        self::MEMBERS_VIEW, // 2
        self::MEMBERS_UPDATE, // 3
        self::MEMBERS_APPLY, // 4
        self::MEMBERS_KICK, // 5

        self::DOC_TEMPLATES_VIEW, // 6
        self::DOC_TEMPLATES_STORE, // 7
        self::DOC_TEMPLATES_UPDATE, // 8
        self::DOC_TEMPLATES_DESTROY, // 9

        self::BANNERS_STORE, // 10
        self::BANNERS_UPDATE, // 11
        self::BANNERS_DESTROY, // 12

        self::ORG_COMMUNICATE, // 13

        self::TASKS_VIEW, // 14
        self::TASKS_STORE, // 15
        self::TASKS_UPDATE, // 16
        self::TASKS_DESTROY, // 17

        self::EVENTS_VIEW, // 18
        self::EVENTS_STORE, // 19
        self::EVENTS_UPDATE, // 20
        self::EVENTS_DESTROY, // 21

        self::NEWS_VIEW_UNPUBLISH, // 22
        self::NEWS_STORE, // 23
        self::NEWS_UPDATE, // 24
        self::NEWS_DESTROY, // 25
        self::NEWS_PUBLISH, // 26

        self::FINANCE_VIEW, // 27
        self::FINANCE_MANAGE, // 28

        self::CHAT_ALLOW, // 29

        self::KBASE_VIEW_UNPUBLISH, // 30
        self::KBASE_STORE, // 31
        self::KBASE_UPDATE, // 32
        self::KBASE_DESTROY, // 33
        self::KBASE_PUBLISH, // 34

        self::QUIZ_VIEW_UNPUBLISH, // 35
        self::QUIZ_STORE, // 36
        self::QUIZ_UPDATE, // 37
        self::QUIZ_DESTROY, // 38
        self::QUIZ_PUBLISH, // 39
    ];
    /* phpcs:enable */

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function before(User $user, string $ability, Organization $organization): ?bool
    {
        if ($organization->user_id === $user->id) {
            return true;
        }

        return null;
    }

    public function view(User $user, Organization $organization): bool
    {
        return in_array($organization->id, $user->membership->pluck('id')->toArray(), true);
    }

    public function administer(User $user, Organization $organization): bool
    {
        return in_array($organization->id, $user->organizationsAdminister()->get()->pluck('id')->toArray(), true);
    }

    /**
     * @throws AuthorizationException
     */
    private static function getPermissionShift(string $ability): int
    {
        $shift = array_search($ability, self::PERMISSIONS, true);

        if ($shift === false) {
            throw new AuthorizationException();
        }

        return $shift;
    }

    /**
     * @param  string  $name
     * @param  array<User|Organization>  $arguments
     * @return bool
     *
     * @throws AuthorizationException
     */
    public function __call(string $name, array $arguments)
    {
        if (count($arguments) === 2) {
            [$user, $organization] = $arguments;

            if ($user instanceof User && $organization instanceof Organization) {
                $user = $organization->members()->whereKey($user->id)->first();

                if ($user) {
                    // TODO: test permissions
                    $permissions = $user->getRelationValue('pivot')->permissions;

                    return (bool) (($permissions >> self::getPermissionShift($name)) & 1);
                }
            }
        }

        throw new AuthorizationException();
    }
}
