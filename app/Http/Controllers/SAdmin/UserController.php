<?php

declare(strict_types=1);

namespace App\Http\Controllers\SAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SAdmin\UserRequest;
use App\Http\Resources\SAdmin\UserResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;

class UserController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = User::query();
        $query->join('user_infos', 'users.id', '=', 'user_infos.user_id');

        $query->with('mfa');
        $query->withCount([
            'deskTasks',
            'news',
            'suggestions',
        ]);

        foreach ($request->all() as $id => $value) {
            switch ($id) {
                case 'id':
                    $query->where('id', $value);

                    break;
                case 'public_family':
                    $query->where(static function (Builder $q) use ($value) {
                        $q->where(static function (Builder $q) use ($value) {
                            $q->whereNull('user_infos.family')
                                ->where('public_family', 'LIKE', "%$value%");
                        });
                        $q->orWhere(static function (Builder $q) use ($value) {
                            $q->where('user_infos.family', 'LIKE', "%$value%");
                        });
                    });

                    break;
                case 'public_name':
                    $query->where(static function (Builder $q) use ($value) {
                        $q->where(static function (Builder $q) use ($value) {
                            $q->whereNull('user_infos.name')
                                ->where('public_name', 'LIKE', "%$value%");
                        });
                        $q->orWhere(static function (Builder $q) use ($value) {
                            $q->where('user_infos.name', 'LIKE', "%$value%");
                        });
                    });

                    break;
                case 'has_referal':
                    $value === 'true' ? $query->whereNotNull('referal_id') : $query->whereNull('referal_id');

                    break;
                case 'is_verified':
                    $query->where('is_verified', $value === 'true');

                    break;
                case 'is_admin':
                    $query->where('is_admin', $value === 'true');

                    break;
                case 'has_desk_tasks':
                    $query->having('desk_tasks_count', $value === 'true' ? '>' : '=', '0');

                    break;
                case 'has_news':
                    $query->having('news_count', $value === 'true' ? '>' : '=', '0');

                    break;
                case 'has_suggestions':
                    $query->having('suggestions_count', $value === 'true' ? '>' : '=', '0');

                    break;
            }
        }

        $sortBy = $request->get('sortBy', 'id');
        $sortDirection = $request->get('sortDirection', 'asc');

        $query->orderBy($sortBy, $sortDirection);

        $limit = (int) $request->get('limit', 0);

        if ($limit > 0) {
            return UserResource::collection($query->paginate($limit));
        }

        return UserResource::collection($query->get());
    }

    public function update(UserRequest $request, User $user): UserResource
    {
        $data = $request->validated();

        /** @var User $currentUser */
        $currentUser = $request->user();

        if (isset($data['is_admin']) && $data['is_admin'] === false) {
            if ($user->id === 1 || $user->id === $currentUser->id) {
                unset($data['is_admin']);
            }
        }

        $user->fill($data);
        $user->save();

        return new UserResource($user);
    }

    public function resetMfa(User $user): UserResource
    {
        $mfaModel = $user->mfa()->firstOrNew();
        $mfaModel->enabled = new Collection();
        $mfaModel->save();

        return new UserResource($user);
    }
}
