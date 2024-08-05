<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\APIRequest;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class EmailVerificationRequest extends APIRequest
{
    public function rules(): array
    {
        return [];
    }

    public function authorize(): bool
    {
        /** @var string $id */
        $id = $this->route('id') ?? '';
        /** @var string $hash */
        $hash = $this->route('hash') ?? '';
        $signature = hash_hmac(
            'sha256',
            "/email/verify/$id/$hash?".http_build_query([
                'expires' => $this->get('expires', 0),
            ]),
            config('app.key')
        );

        if (
            ! hash_equals($signature, $this->get('signature', ''))
            || now()->isAfter(Carbon::createFromTimestamp($this->get('expires', 0)))
        ) {
            return false;
        }

        /**
         * @var User|null $user
         */
        $user = User::query()->whereKey($id)->first();

        if (! $user) {
            return false;
        }

        Auth::setUser($user);

        return true;
    }

    protected function failedAuthorization(): void
    {
        throw new BadRequestHttpException();
    }
}
