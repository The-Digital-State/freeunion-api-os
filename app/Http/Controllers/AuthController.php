<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Events\OrganizationAdminJoinEvent;
use App\Events\OrganizationJoinedEvent;
use App\Facades\SSI;
use App\Http\Requests\Auth\EmailVerificationRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResendEmailRequest;
use App\Http\Requests\Auth\UserRegisterRequest;
use App\Http\Resources\UserShortResource;
use App\Http\Response;
use App\Models\EnterRequest;
use App\Models\InviteLink;
use App\Models\Organization;
use App\Models\User;
use App\Services\Notifications\Centrifugo;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use JsonException;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response as ResponseCode;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;

class AuthController extends Controller
{
    public function invite(Request $request): JsonResponse
    {
        $inviteLink = InviteLink::query()->where('id', $request->get('id', 0))->first();

        if (! $inviteLink || $inviteLink->code !== $request->get('code') || $inviteLink->isExpired()) {
            throw new ModelNotFoundException();
        }

        return Response::success(
            [
                'data' => [
                    'user' => new UserShortResource($inviteLink->user),
                ],
            ]
        );
    }

    public function login(LoginRequest $request): JsonResponse
    {
        /** @var Route $route */
        $route = $request->route();
        $data = $request->validated();

        $query = User::query()->where(['email' => $data['email']]);

        if ($route->getName() === 'auth.slogin') {
            $query->where(['is_admin' => true]);
        }

        $user = $query->first();

        if (! $user || ! Hash::check($data['password'], $user->password) || ! $user->hasVerifiedEmail()) {
            return Response::error(__('auth.failed'));
        }

        if (($user->mfa !== null) && $user->mfa->enabled->isNotEmpty()) {
            if (isset($data['2fa'])) {
                if (! $user->mfa->verify($data['2fa']['method'], $data['2fa']['password'])) {
                    return Response::error(__('auth.failed'));
                }
            } else {
                return Response::success(['need_2fa' => $user->mfa->enabled]);
            }
        }

        $deviceName = $route->getName() === 'auth.slogin' ? "sa - {$data['device_name']}" : $data['device_name'];
        $token = $user->createToken($deviceName);

        try {
            $notificationToken = Centrifugo::generateConnectionToken(
                (string) $user->id,
                (int) Carbon::now()->addHour()->timestamp
            );
        } catch (Throwable) {
            $notificationToken = '';
        }

        return Response::success(
            [
                'token' => $token->plainTextToken,
                'notificationToken' => $notificationToken,
            ]
        );
    }

    /**
     * @throws Exception
     */
    public function loginSSI(Request $request): JsonResponse
    {
        $result = SSI::auth($request->all());

        if ($result === false) {
            return Response::error(__('auth.failed'));
        }

        /** @phpstan-ignore-next-line */
        $membership = SSI::membership($result->evidence);

        if ($membership === false) {
            return Response::error(__('auth.failed'));
        }

        [$group, $isOwner] = $membership;

        // TODO: Prevent send email for @ssi
        /** @phpstan-ignore-next-line */
        $email = hash('sha256', config('app.key').$result->credentialSubject->did).'@ssi';

        // TODO: Refactor user create
        $user = User::firstOrNew(['email' => $email]);

        if ($user->id === null) {
            $user->referal_id = null;
            $user->password = '';
            $user->is_public = 0;
            $user->hiddens = User::SECURE_FIELDS;

            $data = [
                'sex' => random_int(0, 1),
            ];

            $user->generatePublicName(true, $data['sex']);
            $user->save();
            $user->saveUserFields($data);
            $user->markEmailAsVerified();

            event(new Registered($user));
        }

        $organizationDid = hash('sha256', config('app.key').$group->id).'@ssi';
        $organization = Organization::where('did', $organizationDid)->first();

        if ($organization === null && $isOwner) {
            $object = $group->credentialSubject;

            // TODO: Refactor organization create
            $organization = $user->organizations()->create([
                'id' => $group->id,
                'did' => $organizationDid,
                'type_id' => (int) $object->type_name,
                'name' => $object->name,
                'short_name' => $object->name,
                'description' => $object->description,
            ]);

            if (isset($object->interests) && is_array($object->interests)) {
                $organization->interestScope()->sync($object->interests);
            }

            $organization->members()->syncWithoutDetaching([
                $organization->user_id => [
                    'position_id' => 1,
                    'permissions' => PHP_INT_MAX,
                ],
            ]);

            $enterRequest = $organization->enterRequests()->create(
                [
                    'user_id' => $organization->user_id,
                    'status' => EnterRequest::STATUS_ACTIVE,
                ]
            );

            $enterRequest->created_at = $organization->created_at;
            $enterRequest->updated_at = $organization->created_at;
            $enterRequest->save();
        }

        if ($organization !== null && $organization->user_id !== $user->id) {
            // TODO: Refactor add user to members
            $lastRequest = $user->enterRequests()
                ->where('organization_id', $organization->id)
                ->orderBy('created_at', 'desc')
                ->first();
            $canNewRequest = $lastRequest === null
                || $lastRequest->status >= EnterRequest::STATUS_REJECTED
                || $lastRequest->status === EnterRequest::STATUS_CANCEL;

            if ($canNewRequest) {
                $user->enterRequests()->create(
                    [
                        'organization_id' => $organization->id,
                        'status' => EnterRequest::STATUS_ACTIVE,
                    ]
                );
                $user->membership()->syncWithoutDetaching($organization);

                event(new OrganizationJoinedEvent($user->id, $organization));
                event(new OrganizationAdminJoinEvent($organization, $user));
            }
        }

        // TODO: Set UA
        $token = $user->createToken('ssi');

        try {
            $notificationToken = Centrifugo::generateConnectionToken(
                (string) $user->id,
                (int) Carbon::now()->addHour()->timestamp
            );
        } catch (Throwable) {
            $notificationToken = '';
        }

        return Response::success(
            [
                'token' => $token->plainTextToken,
                'notificationToken' => $notificationToken,
            ]
        );
    }

    /**
     * @throws JsonException
     */
    public function ssoCommento(Request $request): JsonResponse
    {
        $token = $request->get('token');
        $hmac = $request->get('hmac');
        $hmacSecret = hex2bin(config('app.sso.commento.secret_key'));

        if ($hmacSecret === false) {
            $hmacSecret = '';
        }

        if ($token === null) {
            return Response::error(__('validation.required', ['attribute' => 'token']));
        }

        if ($hmac === null) {
            return Response::error(__('validation.required', ['attribute' => 'hmac']));
        }

        try {
            if ($hmac !== hash_hmac('sha256', (string) hex2bin($token), $hmacSecret)) {
                return Response::error(__('errors.unknown'));
            }
        } catch (Throwable) {
            return Response::error(__('errors.unknown'));
        }

        /** @var User $user */
        $user = $request->user();
        $avatar = $user->getAvatar();

        $payload = [
            'token' => $token,
            'email' => $user->email,
            'name' => trim("{$user->getPublicFamily()} {$user->getPublicName()}"),
        ];

        if ($avatar !== '') {
            $payload['photo'] = $avatar;
        }

        $payload = json_encode($payload, JSON_THROW_ON_ERROR);
        $payloadHex = bin2hex($payload);
        $hmac = hash_hmac('sha256', $payload, $hmacSecret);

        return Response::success([
            'payload' => $payloadHex,
            'hmac' => $hmac,
            'redirect' => "https://commento.io/api/oauth/sso/callback?payload=$payloadHex&hmac=$hmac",
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var PersonalAccessToken $token */
        $token = $user->currentAccessToken();
        $token->delete();

        return Response::success();
    }

    /**
     * @throws Exception
     */
    public function register(UserRegisterRequest $request): JsonResponse
    {
        /** @var Route $route */
        $route = $request->route();

        if (App::environment('production') && $route->getName() === 'auth.register2') {
            return Response::notImplemented();
        }

        $data = $request->validated();

        if (isset($data['phone'])) {
            $data['phone'] = preg_replace('/\D/', '', $data['phone']);
        }

        $data['sex'] ??= random_int(0, 1);
        $data['password'] = Hash::make($data['password']);

        /**
         * @var User $user
         */
        $user = User::query()->make($data);

        if (! isset($data['is_public']) || $data['is_public'] === 0) {
            $user->hiddens = User::SECURE_FIELDS;
        }

        if (isset($data['invite_id'])) {
            $link = InviteLink::query()->where('id', $data['invite_id'])->first();

            if (! $link || $link->code !== $data['invite_code']) {
                return Response::error(__('auth.invite_missing'), ResponseCode::HTTP_FORBIDDEN);
            }

            if ($link->isExpired()) {
                return Response::error(__('auth.invite_expired'), ResponseCode::HTTP_FORBIDDEN);
            }

            $user->referal_id = $link->user_id;

            if (App::environment('production') || ! in_array($link->id, [1, 2], true)) {
                $link->delete();
            }
        } else {
            $user->referal_id = null;
        }

        $user->generatePublicName(true);
        $user->save();
        $user->saveUserFields($data);

        if ($route->getName() === 'auth.register2') {
            $user->markEmailAsVerified();
        }

        event(new Registered($user));

        return Response::success(
            [
                'public_family' => $user->public_family,
                'public_name' => $user->public_name,
            ]
        );
    }

    public function resend(ResendEmailRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::query()->where(['email' => $data['email']])->first();

        if (! $user) {
            throw new ModelNotFoundException();
        }

        if ($user->hasVerifiedEmail()) {
            return Response::error(__('auth.forbidden'), ResponseCode::HTTP_FORBIDDEN);
        }

        if (isset($data['new_email'])) {
            $exist = User::query()->whereKeyNot($user->id)->where(['email' => $data['new_email']])->count() > 0;

            if ($exist) {
                return Response::error(__('validation.unique', ['attribute' => __('validation.attributes.email')]));
            }

            $user->update(
                [
                    'email' => $data['new_email'],
                ]
            );
        }

        $user->sendEmailVerificationNotification();

        return Response::success();
    }

    public function verifyEmail(EmailVerificationRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var string $hash */
        $hash = $request->route('hash') ?? '';

        if (! $user->hasVerifiedEmail() && hash_equals($hash, sha1($user->email ?: ''))) {
            $user->markEmailAsVerified();
        }

        if ($user->hasNewEmail() && hash_equals($hash, sha1($user->new_email ?: ''))) {
            $newEmail = $user->new_email;
            $user->new_email = null;
            $user->save();

            if (User::query()->where('email', $newEmail)->exists()) {
                throw new BadRequestHttpException();
            }

            $user->email = $newEmail ?: '';
            $user->markEmailAsVerified();
        }

        return Response::success();
    }

    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return Response::success();
        }

        return Response::error(__($status));
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $credentials = [
            'email' => $request->route('email'),
            'password' => Str::random(),
            'token' => $request->route('token'),
        ];

        $status = Password::reset($credentials, static function (User $user, string $password) {
            if (! $user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();

                /** @noinspection PhpParamsInspection */
                /** @phpstan-ignore-next-line */
                event(new Verified($user));
            }

            $user->forceFill([
                'password' => Hash::make($password),
            ]);
            $user->save();

            event(new PasswordReset($user));
        });

        if ($status === Password::PASSWORD_RESET) {
            return Response::success(
                [
                    'password' => $credentials['password'],
                ]
            );
        }

        return Response::error(__($status));
    }

    public function centrifugoRefresh(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $notificationToken = Centrifugo::generateConnectionToken(
            (string) $user->id,
            (int) Carbon::now()->addMinute()->timestamp
        );

        return Response::success(
            [
                'token' => $notificationToken,
            ]
        );
    }
}
