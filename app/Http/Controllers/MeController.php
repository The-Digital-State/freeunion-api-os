<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Events\OrganizationAdminJoinEvent;
use App\Events\OrganizationAdminRequestEvent;
use App\Events\OrganizationJoinedEvent;
use App\Events\OrganizationLeaveEvent;
use App\Events\OrganizationRequestEvent;
use App\Http\Requests\Me\HelpOfferRequest;
use App\Http\Requests\Me\NewNameRequest;
use App\Http\Requests\Me\PublicNameRequest;
use App\Http\Requests\Me\SendNotificationRequest;
use App\Http\Requests\Me\UpdateEmailRequest;
use App\Http\Requests\Me\UpdatePasswordRequest;
use App\Http\Requests\Me\UserUpdateRequest;
use App\Http\Requests\Organization\StoreRequest;
use App\Http\Resources\EnterRequestShortResource;
use App\Http\Resources\HelpOfferResource;
use App\Http\Resources\NotificationResource;
use App\Http\Resources\OrganizationResource;
use App\Http\Resources\UserFullResource;
use App\Http\Resources\UserInvitedResource;
use App\Http\Response;
use App\Models\EnterRequest;
use App\Models\HelpOffer;
use App\Models\HelpOfferLink;
use App\Models\Notification;
use App\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Constraint;
use Intervention\Image\Facades\Image;
use OTPHP\TOTP;
use Symfony\Component\HttpFoundation\Response as ResponseCode;
use Throwable;

class MeController extends Controller
{
    public function user(Request $request): UserFullResource
    {
        return new UserFullResource($request->user());
    }

    public function invited(Request $request): UserInvitedResource
    {
        return new UserInvitedResource($request->user());
    }

    public function update(UserUpdateRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->all();

        if (isset($data['birthday'])) {
            $birthday = Carbon::createFromFormat('Y-m-d', $data['birthday']);
            $data['birthday'] = $birthday !== false ? $birthday->toDateString() : null;
        }

        if (isset($data['phone'])) {
            $data['phone'] = preg_replace('/\D/', '', $data['phone']);
        }

        $user->saveUserFields($data);

        return Response::success();
    }

    public function changeVisibility(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $isPublic = $request->get('is_public', 0);
        $hiddens = array_intersect($request->get('hiddens', []), User::SECURE_FIELDS);
        $data = [
            'is_public' => $isPublic,
            'hiddens' => $hiddens,
        ];
        $user->update($data);
        $user->saveUserFields([]);

        return Response::success();
    }

    public function updateEmail(UpdateEmailRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->new_email = $request->get('email');
        $user->save();

        $user->sendNewEmailVerificationNotification();

        return Response::success();
    }

    public function updateEmailCancel(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->new_email = null;
        $user->save();

        return Response::success();
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->update(
            [
                'password' => Hash::make($request->get('password')),
            ]
        );

        return Response::success();
    }

    public function updateAvatar(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $image = null;
        $fileName = $user->id.'_'.time().'.jpg';

        try {
            $imageUrl = $request->get('image');

            if ($imageUrl) {
                $image = Image::make($imageUrl);
            } else {
                $file = $request->file('image');

                if ($file) {
                    $image = Image::make($file);
                }
            }
        } catch (Throwable $event) {
            return Response::error($event->getMessage());
        }

        if ($image) {
            if (! Str::startsWith($image->mime, 'image/')) {
                return Response::error(
                    __('validation.mimes', ['attribute' => __('validation.attributes.image'), 'values' => 'image/*']),
                    ResponseCode::HTTP_UNSUPPORTED_MEDIA_TYPE
                );
            }

            $image = $image->fit(256, 256, static function (Constraint $constraint) {
                $constraint->upsize();
            });

            $storage = Storage::disk(config('filesystems.public'));
            $fileWasUploaded = $storage->put("avatars/$fileName", (string) $image->stream('jpg'));

            if ($fileWasUploaded) {
                if ($storage->exists("avatars/$user->public_avatar")) {
                    $storage->delete("avatars/$user->public_avatar");
                }

                $user->public_avatar = $fileName;
                $user->save();

                return Response::success(
                    [
                        'url' => $user->getAvatar(),
                    ]
                );
            }
        }

        return Response::error(__('validation.required', ['attribute' => __('validation.attributes.image')]));
    }

    public function saveSettings(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->settings = array_filter(array_merge($user->settings ?? [], $request->all()), static function ($item) {
            return $item !== null;
        });
        $user->save();

        return Response::success();
    }

    public function getName(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return Response::success(
            [
                'data' => [
                    'public_family' => $user->public_family ?? '',
                    'public_name' => $user->public_name ?? '',
                ],
            ]
        );
    }

    /**
     * @throws Exception
     */
    public function newName(NewNameRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->change_public === 0) {
            return Response::error(__('validation.protected', ['attribute' => __('validation.attributes.name')]));
        }

        [$publicFamily, $publicName] = $user->generatePublicName(false, (int) $request->get('sex', -1));
        $timestamp = Carbon::now()->timestamp;
        $signature = hash_hmac(
            'sha256',
            implode(':', [
                $user->id,
                $publicFamily ?? '',
                $publicName ?? '',
                $timestamp,
            ]),
            config('app.key')
        );

        return Response::success(
            [
                'data' => [
                    'public_family' => $publicFamily ?? '',
                    'public_name' => $publicName ?? '',
                    'signature' => implode(':', [$timestamp, $signature]),
                ],
            ]
        );
    }

    public function saveName(PublicNameRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->change_public === 0) {
            return Response::error(__('validation.protected', ['attribute' => __('validation.attributes.name')]));
        }

        [$timestamp, $hash] = explode(':', $request->get('signature'), 2);
        $signature = hash_hmac(
            'sha256',
            implode(':', [
                $user->id,
                $request->get('public_family'),
                $request->get('public_name'),
                $timestamp,
            ]),
            config('app.key')
        );

        $hashNotEquals = ! hash_equals($signature, $hash);
        $isTimeout = Carbon::now()->subHour()->greaterThan(Carbon::createFromTimestamp($timestamp));

        if ($hashNotEquals || $isTimeout) {
            return Response::error(__('validation.operation_expired'));
        }

        if (count(array_intersect(['family', 'name'], $user->hiddens)) > 0) {
            $user->change_public--;
            $user->public_family = $request->get('public_family');
            $user->public_name = $request->get('public_name');
            $user->save();
        }

        return Response::success();
    }

    public function registerMfa(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $mfaModel = $user->mfa()->firstOrNew();

        if (! $mfaModel->exists) {
            $mfaModel->enabled = new Collection();
            $mfaModel->save();
        }

        if ($mfaModel->enabled->isNotEmpty()) {
            return Response::error(__('auth.register_2fa_failed'));
        }

        $mfaModel->generateTotpSecret();
        $otpObject = TOTP::create($mfaModel->totp_secret);
        $otpObject->setLabel($user->email);
        $otpObject->setIssuer(config('app.name'));

        return Response::success([
            'secret' => $otpObject->getSecret(),
            'qrcode' => $otpObject->getQrCodeUri(
                'https://api.qrserver.com/v1/create-qr-code/?data=[DATA]&size=300x300&ecc=M',
                '[DATA]'
            ),
        ]);
    }

    public function unregisterMfa(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $mfaModel = $user->mfa()->firstOrNew();
        $mfaModel->enabled = new Collection();
        $mfaModel->save();

        return Response::success();
    }

    public function enableMfa(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $mfaModel = $user->mfa()->firstOrNew();
        $password = $request->get('password');

        if (! isset($password) || ! $mfaModel->verify('totp', $password)) {
            return Response::error(__('errors.wrong_totp_password'));
        }

        $mfaModel->addType('otp');
        $mfaModel->addType('totp');
        $mfaModel->generateOtpPasswords();

        return Response::success([
            'otp_passwords' => $mfaModel->otp_passwords,
        ]);
    }

    public function createOrganization(StoreRequest $request): OrganizationResource
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->all();

        if (isset($data['type_id'], $data['type_name'])) {
            unset($data['type_name']);
        }

        if (isset($data['phone'])) {
            $data['phone'] = preg_replace('/\D/', '', $data['phone']);
        }

        $organization = $user->organizations()->create($data);
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

        return new OrganizationResource($organization);
    }

    public function enterOrganization(Request $request, Organization $organization): JsonResponse|JsonResource
    {
        /** @var User $user */
        $user = $request->user();

        if ($organization->user_id !== $user->id) {
            $lastRequest = $user->enterRequests()
                ->where('organization_id', $organization->id)
                ->orderBy('created_at', 'desc')
                ->first();
            $canNewRequest = $lastRequest === null
                || $lastRequest->status >= EnterRequest::STATUS_REJECTED
                || $lastRequest->status === EnterRequest::STATUS_CANCEL;

            if ($canNewRequest) {
                if ($organization->request_type === Organization::REQUEST_TYPE_SIMPLE) {
                    $enterRequest = $user->enterRequests()->create(
                        [
                            'organization_id' => $organization->id,
                            'status' => EnterRequest::STATUS_ACTIVE,
                        ]
                    );
                    $user->membership()->syncWithoutDetaching($organization);

                    event(new OrganizationJoinedEvent($user->id, $organization));
                    event(new OrganizationAdminJoinEvent($organization, $user));
                } else {
                    $enterRequest = $user->enterRequests()->create(
                        [
                            'organization_id' => $organization->id,
                            'status' => EnterRequest::STATUS_REQUESTED,
                            'comment' => $request->get('comment'),
                        ]
                    );

                    event(new OrganizationRequestEvent($user->id, $organization));
                    event(new OrganizationAdminRequestEvent($organization, $user));
                }

                return new EnterRequestShortResource($enterRequest);
            }
        }

        return Response::error(__('errors.request'));
    }

    public function leaveOrganization(Request $request, Organization $organization): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($organization->user_id === $user->id) {
            return Response::error(__('errors.organization_leave_owner'));
        }

        $lastRequest = $user->enterRequests()
            ->where('organization_id', $organization->id)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastRequest && $lastRequest->status === EnterRequest::STATUS_ACTIVE) {
            EnterRequest::create([
                'user_id' => $lastRequest->user_id,
                'organization_id' => $lastRequest->organization_id,
                'comment' => $request->get('message'),
                'status' => EnterRequest::STATUS_LEFT,
            ]);
        }

        $user->membership()->detach($organization);

        event(new OrganizationLeaveEvent($user->id, $organization));

        return Response::success();
    }

    public function cancelRequest(Request $request, Organization $organization): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $lastRequest = $user->enterRequests()
            ->where('organization_id', $organization->id)
            ->where('status', '<', 10)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastRequest) {
            $lastRequest->status = EnterRequest::STATUS_CANCEL;
            $lastRequest->comment = null;
            $lastRequest->save();

            return Response::success();
        }

        throw new ModelNotFoundException();
    }

    public function requestStatus(Request $request, Organization $organization): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $lastRequest = $user->enterRequests()
            ->where('organization_id', $organization->id)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastRequest) {
            return Response::success(
                [
                    'data' => [
                        'comment' => $lastRequest->comment ?? '',
                        'status' => $lastRequest->status,
                    ],
                ]
            );
        }

        throw new ModelNotFoundException();
    }

    public function helpOfferList(Organization $organization): JsonResource
    {
        $query = HelpOffer::query()->where('organization_id', $organization->id)
            ->where('enabled', true)
            ->orderBy('id');

        return HelpOfferResource::collection($query->get());
    }

    public function helpOffer(HelpOfferRequest $request, Organization $organization): JsonResource
    {
        /** @var User $user */
        $user = $request->user();

        $helpOffers = $request->get('help_offers', []);
        $existsOffers = $user->helpOfferLinks()->where('organization_id', $organization->id)->get()
            ->pluck('help_offer_id', 'id')->all();

        $toRemove = array_diff($existsOffers, $helpOffers);
        $toAdd = array_diff($helpOffers, $existsOffers);

        DB::beginTransaction();
        HelpOfferLink::whereIn('id', array_keys($toRemove))->delete();

        foreach ($toAdd as $helpOffer) {
            HelpOfferLink::create([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'help_offer_id' => $helpOffer,
            ]);
        }

        DB::commit();

        return new UserFullResource($user);
    }

    public function getNotifications(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $query = Notification::query()
            ->where('to_id', $user->id);

        if ((int) $request->get('status', 1) === Notification::STATUS_NOTREAD) {
            $query->notRead();
        }

        $query->orderBy('created_at', 'DESC');

        $limit = (int) $request->get('limit', 0);

        if ($limit > 0) {
            return NotificationResource::collection($query->paginate($limit));
        }

        return NotificationResource::collection($query->get());
    }

    public function getNotification(Request $request, Notification $notification): NotificationResource
    {
        /** @var User $user */
        $user = $request->user();

        if ($notification->to_id !== $user->id) {
            throw new ModelNotFoundException();
        }

        if ($notification->status === Notification::STATUS_NOTREAD) {
            $notification->setRead();
        }

        return new NotificationResource($notification);
    }

    public function sendNotification(SendNotificationRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $recipient = $request->get('to');

        if ($recipient !== $user->id) {
            $user->sendNotification($recipient, $request->get('message'));
        }

        return Response::success();
    }
}
