<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\ChatConversationController as AdminChatConversationController;
use App\Http\Controllers\Admin\DeskTaskController as AdminDeskTaskController;
use App\Http\Controllers\Admin\EnterRequestController as AdminEnterRequestController;
use App\Http\Controllers\Admin\ExpenseController as AdminExpenseController;
use App\Http\Controllers\Admin\FundraisingController as AdminFundraisingController;
use App\Http\Controllers\Admin\HelpOfferController;
use App\Http\Controllers\Admin\MaterialController as AdminMaterialController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\MemberListController;
use App\Http\Controllers\Admin\NewsController as AdminNewsController;
use App\Http\Controllers\Admin\OrganizationController as AdminOrganizationController;
use App\Http\Controllers\Admin\PaymentSystemController as AdminPaymentSystemController;
use App\Http\Controllers\Admin\QuizController as AdminQuizController;
use App\Http\Controllers\Admin\QuizQuestionController as AdminQuizQuestionController;
use App\Http\Controllers\Admin\SubscriptionController as AdminSubscriptionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatConversationController;
use App\Http\Controllers\DeskTaskController;
use App\Http\Controllers\DictionaryController;
use App\Http\Controllers\EnterRequestController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FundraisingController;
use App\Http\Controllers\InviteLinkController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\MeController;
use App\Http\Controllers\MSectionController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\SAdmin\UserController as SAdminUserController;
use App\Http\Controllers\SuggestionController;
use App\Http\Controllers\UploadController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('auth')->as('auth.')->group(static function () {
    Route::get('invite', [AuthController::class, 'invite'])
        ->name('invite');
    Route::post('register', [AuthController::class, 'register'])
        ->name('register');
    Route::post('register2', [AuthController::class, 'register'])
        ->name('register2');
    Route::middleware('throttle:5,120,user_email')->group(static function () {
        Route::post('resend', [AuthController::class, 'resend'])
            ->name('resend');
        Route::post('forgot', [AuthController::class, 'forgot'])
            ->name('forgot');
    });
    Route::post('login', [AuthController::class, 'login'])
        ->name('login');
    Route::post('login/ssi', [AuthController::class, 'loginSSI'])
        ->name('login.ssi');
    Route::post('slogin', [AuthController::class, 'login'])
        ->name('slogin');
    Route::post('verify_email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->where('id', '[0-9]+')
        ->name('verify');
    Route::post('reset_password/{email}/{token}', [AuthController::class, 'resetPassword'])
        ->name('reset');
});

Route::middleware(['auth:sanctum', 'verified'])->group(static function () {
    Route::prefix('auth')->as('auth.')->group(static function () {
        Route::get('user', [MeController::class, 'user'])
            ->name('user');
        Route::post('ws_refresh', [AuthController::class, 'centrifugoRefresh'])
            ->name('ws.refresh');
        Route::post('logout', [AuthController::class, 'logout'])
            ->name('logout');

        Route::prefix('sso')->as('sso.')->group(static function () {
            Route::any('commento', [AuthController::class, 'ssoCommento'])
                ->name('commento');
        });
    });

    Route::prefix('me')->as('me.')->group(static function () {
        Route::get('user', [MeController::class, 'user'])
            ->name('user');
        Route::get('invited', [MeController::class, 'invited'])
            ->name('invited');
        Route::post('update', [MeController::class, 'update'])
            ->name('update');
        Route::post('change_visibility', [MeController::class, 'changeVisibility'])
            ->name('visibility');
        Route::post('update_email', [MeController::class, 'updateEmail'])
            ->name('email')
            ->middleware('throttle:5,120,user_email');
        Route::post('update_email/cancel', [MeController::class, 'updateEmailCancel'])
            ->name('email.cancel');
        Route::post('update_password', [MeController::class, 'updatePassword'])
            ->name('password');
        Route::post('update_avatar', [MeController::class, 'updateAvatar'])
            ->name('avatar');
        Route::post('settings', [MeController::class, 'saveSettings'])
            ->name('settings');

        Route::as('name.')->group(static function () {
            Route::get('get_name', [MeController::class, 'getName'])
                ->name('get');
            Route::post('new_name', [MeController::class, 'newName'])
                ->name('new');
            Route::post('save_name', [MeController::class, 'saveName'])
                ->name('save');
        });

        Route::as('totp.')->group(static function () {
            Route::post('register_totp', [MeController::class, 'registerMfa'])
                ->name('register');
            Route::post('unregister_totp', [MeController::class, 'unregisterMfa'])
                ->name('unregister');
            Route::post('enable_totp', [MeController::class, 'enableMfa'])
                ->name('enable');
        });

        Route::as('notifications.')->group(static function () {
            Route::get('notifications', [MeController::class, 'getNotifications'])
                ->name('index');
            Route::post('notifications', [MeController::class, 'sendNotification'])
                ->name('send');
            Route::get('notifications/{notification}', [MeController::class, 'getNotification'])
                ->name('show');
        });

        Route::get('enter_requests', [EnterRequestController::class, 'index'])
            ->name('enter-requests');

        Route::as('organization.')->group(static function () {
            Route::post('organization', [MeController::class, 'createOrganization'])
                ->name('store');
            Route::prefix('organization/{organization}')->group(static function () {
                Route::post('enter', [MeController::class, 'enterOrganization'])
                    ->name('enter');
                Route::post('leave', [MeController::class, 'leaveOrganization'])
                    ->name('leave');

                Route::as('request.')->group(static function () {
                    Route::post('cancel', [MeController::class, 'cancelRequest'])
                        ->name('cancel');
                    Route::get('status', [MeController::class, 'requestStatus'])
                        ->name('status');
                });

                Route::as('help_offers.')->group(static function () {
                    Route::get('help_offers', [MeController::class, 'helpOfferList'])
                        ->name('index');
                    Route::post('help_offers', [MeController::class, 'helpOffer'])
                        ->name('update');
                });

                Route::apiResource('suggestions', 'SuggestionController');

                Route::apiResource('quizzes', 'QuizController')
                    ->only('index', 'show');
                Route::post('quizzes/{quiz}', [QuizController::class, 'question'])
                    ->name('quizzes.question');
            });
        });

        Route::as('chat.')->group(static function () {
            Route::apiResource('conversations', 'ChatConversationController');
            Route::prefix('conversations/{conversation}')->as('conversations.')->group(static function () {
                Route::post('clear', [ChatConversationController::class, 'clear'])
                    ->name('clear');

                Route::post('block', [ChatConversationController::class, 'block'])
                    ->name('block');
                Route::post('unblock', [ChatConversationController::class, 'unblock'])
                    ->name('unblock');
                Route::post('mute', [ChatConversationController::class, 'mute'])
                    ->name('mute');
                Route::post('unmute', [ChatConversationController::class, 'unmute'])
                    ->name('unmute');

                Route::post('add', [ChatConversationController::class, 'add'])
                    ->name('add');
                Route::post('remove', [ChatConversationController::class, 'remove'])
                    ->name('remove');

                Route::apiResource('messages', 'ChatMessageController');
            });
        });
    });

    Route::as('invite.')->group(static function () {
        Route::get('invite_link', [InviteLinkController::class, 'show'])
            ->name('show');
        Route::post('invite_link', [InviteLinkController::class, 'generate'])
            ->name('generate');
    });

    Route::prefix('desk_tasks/{desk_task}')->as('desk_tasks.')->group(static function () {
        Route::as('comments.')->group(static function () {
            Route::get('comments', [DeskTaskController::class, 'commentsIndex'])
                ->name('index');
            Route::post('comments', [DeskTaskController::class, 'commentsStore'])
                ->name('store');
            Route::put('comments/{comment}', [DeskTaskController::class, 'commentsUpdate'])
                ->name('update');
            Route::delete('comments/{comment}', [DeskTaskController::class, 'commentsDestroy'])
                ->name('destroy');

            Route::post('comments/{comment}/reaction', [DeskTaskController::class, 'setCommentReaction'])
                ->name('reaction');
        });
    });

    Route::prefix('suggestions/{suggestion}')->as('suggestions.')->group(static function () {
        Route::post('reaction', [SuggestionController::class, 'setReaction'])
            ->name('reaction');

        Route::as('comments.')->group(static function () {
            Route::get('comments', [SuggestionController::class, 'commentsIndex'])
                ->name('index');
            Route::post('comments', [SuggestionController::class, 'commentsStore'])
                ->name('store');
            Route::put('comments/{comment}', [SuggestionController::class, 'commentsUpdate'])
                ->name('update');
            Route::delete('comments/{comment}', [SuggestionController::class, 'commentsDestroy'])
                ->name('destroy');

            Route::post('comments/{comment}/reaction', [SuggestionController::class, 'setCommentReaction'])
                ->name('reaction');
        });
    });

    Route::prefix('admin_org')->middleware(AdminMiddleware::class)->as('admin.')->group(static function () {
        Route::get('', [AdminOrganizationController::class, 'index'])
            ->name('index');
        Route::get('{organization}', [AdminOrganizationController::class, 'show'])
            ->name('show');
        Route::put('{organization}', [AdminOrganizationController::class, 'update'])
            ->name('update');
        Route::delete('{organization}', [AdminOrganizationController::class, 'destroy'])
            ->name('destroy');

        Route::prefix('{organization}')->group(static function () {
            Route::post('delegate', [AdminOrganizationController::class, 'delegate'])
                ->name('delegate');

            Route::post('scopes', [AdminOrganizationController::class, 'updateScopes'])
                ->name('update.scopes');
            Route::post('interests', [AdminOrganizationController::class, 'updateInterests'])
                ->name('update.interests');
            Route::post('update_avatar', [AdminOrganizationController::class, 'updateAvatar'])
                ->name('update.avatar');

            Route::prefix('requests')->as('requests.')->group(static function () {
                Route::post('apply_all', [AdminEnterRequestController::class, 'applyAll'])
                    ->name('apply-all');
                Route::get('', [AdminEnterRequestController::class, 'index'])
                    ->name('index');
                Route::get('{enter_request}', [AdminEnterRequestController::class, 'show'])
                    ->name('show');
                Route::post('{enter_request}/apply', [AdminEnterRequestController::class, 'apply'])
                    ->name('apply');
                Route::post('{enter_request}/reject', [AdminEnterRequestController::class, 'reject'])
                    ->name('reject');
            });

            Route::apiResource('banners', 'Admin\BannerController');
            Route::prefix('banners')->as('banners.')->group(static function () {
                Route::post('{banner}/large', [BannerController::class, 'uploadLarge'])
                    ->name('update.large');
                Route::post('{banner}/small', [BannerController::class, 'uploadSmall'])
                    ->name('update.small');
            });

            Route::apiResource('doc_templates', 'Admin\DocTemplateController');

            Route::prefix('members')->as('members.')->group(static function () {
                Route::get('', [MemberController::class, 'index'])
                    ->name('index');
                Route::get('admins', [MemberController::class, 'admins'])
                    ->name('admins');
                Route::get('{user}', [MemberController::class, 'show'])
                    ->name('show');
                Route::put('{user}', [MemberController::class, 'update'])
                    ->name('update');
                Route::delete('{user}', [MemberController::class, 'kick'])
                    ->name('kick');
            });

            Route::apiResource('member_lists', 'Admin\MemberListController');
            Route::prefix('member_lists')->as('member_lists.')->group(static function () {
                Route::as('members.')->group(static function () {
                    Route::get('{member_list}/members', [MemberListController::class, 'showMembers'])
                        ->name('index');
                    Route::put('{member_list}/members', [MemberListController::class, 'addMembers'])
                        ->name('add');
                    Route::delete('{member_list}/members', [MemberListController::class, 'removeMembers'])
                        ->name('remove');
                });
            });

            Route::post('notifications', [AdminOrganizationController::class, 'sendNotification'])
                ->name('notifications');
            Route::post('send_announcement', [AdminOrganizationController::class, 'sendAnnouncement'])
                ->name('send-announcement');
            Route::post('send_message', [AdminOrganizationController::class, 'sendMessage'])
                ->name('send-message');

            Route::apiResource('desk_tasks', 'Admin\DeskTaskController');
            Route::prefix('desk_tasks/{desk_task}')->as('desk_tasks.')->group(static function () {
                Route::post('attach', [AdminDeskTaskController::class, 'attachUsers'])
                    ->name('attach');
                Route::post('detach', [AdminDeskTaskController::class, 'detachUsers'])
                    ->name('detach');
                Route::post('drag/{after}', [AdminDeskTaskController::class, 'drag'])
                    ->name('drag');
                Route::post('move/{column}', [AdminDeskTaskController::class, 'move'])
                    ->where('column', '[0-4]')
                    ->name('move');
                Route::post('image', [AdminDeskTaskController::class, 'uploadImage'])
                    ->name('image.upload');
                Route::delete('image/{desk_image}', [AdminDeskTaskController::class, 'removeImage'])
                    ->name('image.remove');

                Route::as('comments.')->group(static function () {
                    Route::get('comments', [AdminDeskTaskController::class, 'commentsIndex'])
                        ->name('index');
                    Route::post('comments', [AdminDeskTaskController::class, 'commentsStore'])
                        ->name('store');
                    Route::put('comments/{comment}', [AdminDeskTaskController::class, 'commentsUpdate'])
                        ->name('update');
                    Route::delete('comments/{comment}', [AdminDeskTaskController::class, 'commentsDestroy'])
                        ->name('destroy');

                    Route::post('comments/{comment}/reaction', [AdminDeskTaskController::class, 'setCommentReaction'])
                        ->name('reaction');
                });

                // TODO: Remove old comments
                Route::apiResource('desk_comments', 'Admin\DeskCommentController');
            });

            Route::apiResource('help_offers', 'Admin\HelpOfferController');
            Route::put('help_offers', [HelpOfferController::class, 'updateAll'])
                ->name('help-offers.update-all');

            Route::apiResource('organization_chats', 'Admin\OrganizationChatController');

            Route::apiResource('news', 'Admin\NewsController');
            Route::prefix('news')->as('news.')->group(static function () {
                Route::post('upload', [AdminNewsController::class, 'uploadImage'])
                    ->name('upload');
                Route::post('{news}/publish', [AdminNewsController::class, 'publish'])
                    ->name('publish');
                Route::post('{news}/unpublish', [AdminNewsController::class, 'unpublish'])
                    ->name('unpublish');

                Route::prefix('abuses')->as('abuses.')->group(static function () {
                    Route::get('', [AdminNewsController::class, 'abuses'])
                        ->name('index');
                    Route::get('{news_abuse}', [AdminNewsController::class, 'abuseShow'])
                        ->name('show');
                    Route::delete('{news_abuse}', [AdminNewsController::class, 'abuseDestroy'])
                        ->name('destroy');
                });

                Route::post('{news}/telepost', [AdminNewsController::class, 'telepost'])
                    ->name('telepost');
            });

            Route::prefix('kbase')->as('kbase.')->group(static function () {
                Route::apiResource('sections', 'Admin\MSectionController');

                Route::apiResource('materials', 'Admin\MaterialController');
                Route::prefix('materials')->as('materials.')->group(static function () {
                    Route::post('{material}/drag/{after}', [AdminMaterialController::class, 'drag'])
                        ->name('drag');
                    Route::post('upload', [AdminMaterialController::class, 'uploadImage'])
                        ->name('upload');
                    Route::post('{material}/publish', [AdminMaterialController::class, 'publish'])
                        ->name('publish');
                    Route::post('{material}/unpublish', [AdminMaterialController::class, 'unpublish'])
                        ->name('unpublish');
                });
            });

            Route::apiResource('organization_teleposts', 'Admin\OrganizationTelepostController');

            Route::apiResource('quizzes', 'Admin\QuizController');
            Route::prefix('quizzes/{quiz}')->as('quizzes.')->group(static function () {
                Route::post('publish', [AdminQuizController::class, 'publish'])
                    ->name('publish');
                Route::post('close', [AdminQuizController::class, 'close'])
                    ->name('close');

                Route::apiResource('questions', 'Admin\QuizQuestionController');

                Route::prefix('questions/{question}')->as('questions.')->group(static function () {
                    Route::post('drag/{after}', [AdminQuizQuestionController::class, 'drag'])
                        ->name('drag');
                });
            });

            Route::as('chat.')->group(static function () {
                Route::apiResource('conversations', 'Admin\ChatConversationController');
                Route::prefix('conversations/{conversation}')->as('conversations.')->group(static function () {
                    Route::post('clear', [AdminChatConversationController::class, 'clear'])
                        ->name('clear');

                    Route::post('block', [AdminChatConversationController::class, 'block'])
                        ->name('block');
                    Route::post('unblock', [AdminChatConversationController::class, 'unblock'])
                        ->name('unblock');
                    Route::post('mute', [AdminChatConversationController::class, 'mute'])
                        ->name('mute');
                    Route::post('unmute', [AdminChatConversationController::class, 'unmute'])
                        ->name('unmute');

                    Route::post('add', [AdminChatConversationController::class, 'add'])
                        ->name('add');
                    Route::post('remove', [AdminChatConversationController::class, 'remove'])
                        ->name('remove');

                    Route::apiResource('messages', 'Admin\ChatMessageController');
                });
            });

            Route::post('fundraisings/upload', [AdminFundraisingController::class, 'uploadImage'])
                ->name('fundraising.upload');
            Route::get('fundraisings/payments', [AdminFundraisingController::class, 'payments'])
                ->name('fundraising.payments');
            Route::apiResource('fundraisings', 'Admin\FundraisingController');

            Route::apiResource('subscriptions', 'Admin\SubscriptionController');
            Route::post('subscriptions/upload', [AdminSubscriptionController::class, 'uploadImage'])
                ->name('subscriptions.upload');

            Route::get('payment_systems/{system}', [AdminPaymentSystemController::class, 'showByName'])
                ->where('system', '[A-Za-z]+');
            Route::put('payment_systems/{system}', [AdminPaymentSystemController::class, 'updateByName'])
                ->where('system', '[A-Za-z]+');
            Route::apiResource('payment_systems', 'Admin\PaymentSystemController');

            Route::get('finance', [AdminFundraisingController::class, 'all'])
                ->name('finance.all');

            Route::post('expenses', [AdminExpenseController::class, 'update'])->name('expenses.update');
            Route::get('expenses', [AdminExpenseController::class, 'index'])->name('expenses.show');
        });
    });

    Route::prefix('sadmin')->middleware(SuperAdminMiddleware::class)->as('sadmin.')->group(static function () {
        Route::apiResource('users', 'SAdmin\UserController')
            ->only(['index', 'update']);
        Route::post('users/{user}/reset_2fa', [SAdminUserController::class, 'resetMfa']);

        Route::apiResource('organizations', 'SAdmin\OrganizationController')
            ->only(['index', 'update', 'destroy']);

        Route::apiResource('news', 'SAdmin\NewsController')
            ->only(['index', 'update', 'destroy']);

        Route::prefix('dictionaries')->group(static function () {
            Route::apiResource('activity_scopes', 'SAdmin\ActivityScopeController');
            // TODO: Remove
            Route::apiResource('help_offers', 'SAdmin\HelpOfferController');
            Route::apiResource('interest_scopes', 'SAdmin\InterestScopeController');
            Route::apiResource('organization_types', 'SAdmin\OrganizationTypeController');
            Route::apiResource('positions', 'SAdmin\PositionsController');
        });
    });
});

Route::middleware(['auth.optional'])->group(static function () {
    Route::prefix('organizations')->as('organizations.')->group(static function () {
        Route::get('', [OrganizationController::class, 'index'])
            ->name('index');

        Route::prefix('ssi')->as('ssi.')->group(static function () {
            Route::get('list', [OrganizationController::class, 'listSSI'])
                ->name('list');
        });

        Route::prefix('{organization}')->group(static function () {
            Route::get('', [OrganizationController::class, 'show'])
                ->name('show');
            Route::get('hierarchy', [OrganizationController::class, 'hierarchy'])
                ->name('hierarchy');
            Route::get('members', [OrganizationController::class, 'members'])
                ->name('members');

            Route::get('get_chat/{organization_chat}', [OrganizationController::class, 'getChat'])
                ->name('chat')->middleware(['auth:sanctum', 'verified']);

            Route::as('desk_tasks.')->group(static function () {
                Route::get('desk_tasks', [DeskTaskController::class, 'index'])
                    ->name('index');
                Route::get('desk_tasks/{desk_task}', [DeskTaskController::class, 'show'])
                    ->name('show');
                Route::post('desk_tasks/{desk_task}/assign', [DeskTaskController::class, 'assign'])
                    ->name('assign');

                // TODO: Remove old comments
                Route::get('desk_tasks/{desk_task}/desk_comments', [DeskTaskController::class, 'comments'])
                    ->name('desk-comments.index');
            });

            Route::prefix('news')->as('news.')->group(static function () {
                Route::get('', [NewsController::class, 'orgIndex'])
                    ->name('index');
                Route::post('', [NewsController::class, 'orgStore'])
                    ->name('store')->middleware(['auth:sanctum', 'verified']);
                Route::get('{news}', [NewsController::class, 'orgShow'])
                    ->name('show');
                Route::post('upload', [NewsController::class, 'uploadImage'])
                    ->name('upload')->middleware(['auth:sanctum', 'verified']);
            });

            Route::prefix('kbase')->as('kbase.')->group(static function () {
                Route::prefix('sections')->as('sections.')->group(static function () {
                    Route::get('', [MSectionController::class, 'orgIndex'])
                        ->name('index');
                    Route::get('{section}', [MSectionController::class, 'orgShow'])
                        ->name('show');
                });

                Route::prefix('materials')->as('materials.')->group(static function () {
                    Route::get('', [MaterialController::class, 'orgIndex'])
                        ->name('index');
                    Route::get('{material}', [MaterialController::class, 'orgShow'])
                        ->name('show');
                });
            });

            Route::post('fundraisings/{fundraising}/link', [FundraisingController::class, 'link'])
                ->name('fundraisings.link');

            Route::get('fundraisings', [FundraisingController::class, 'index'])
                ->name('fundraising.index');

            Route::get('subscriptions', [FundraisingController::class, 'subscriptions'])
                ->name('subscriptions.index');

            Route::get('finance', [FundraisingController::class, 'all'])
                ->name('finance.all');
            Route::post('fundraisings/stripe/webhook', [FundraisingController::class, 'stripeWebhook'])
                ->name('stripe.webhook');

            Route::get('expenses', [ExpenseController::class, 'index'])->name('expenses.update');
        });
    });

    Route::apiResource('news', 'NewsController')
        ->only(['index', 'show']);
    Route::prefix('news')->as('news.')->group(static function () {
        Route::post('{news}/abuse', [NewsController::class, 'abuse'])
            ->name('abuse');
    });

    Route::prefix('kbase')->as('kbase.')->group(static function () {
        Route::apiResource('sections', 'MSectionController')
            ->only(['index', 'show']);
        Route::apiResource('materials', 'MaterialController')
            ->only(['index', 'show']);
    });

    Route::post('upload', UploadController::class)
        ->name('upload')
        ->middleware('throttle:20,10,upload');
});

Route::prefix('dictionaries')->as('dictionaries.')->group(static function () {
    Route::get('activity_scopes', [DictionaryController::class, 'activityScopes'])
        ->name('activity-scopes');
    Route::get('countries', [DictionaryController::class, 'countries'])
        ->name('countries');
    // TODO: Remove
    Route::get('help_offers', [DictionaryController::class, 'helpOffers'])
        ->name('help-offers');
    Route::get('interest_scopes', [DictionaryController::class, 'interestScopes'])
        ->name('interest-scopes');
    Route::get('organization_types', [DictionaryController::class, 'organizationTypes'])
        ->name('organization-types');
    Route::get('reactions', [DictionaryController::class, 'reactions'])
        ->name('reactions');
    Route::get('request_statuses', [DictionaryController::class, 'requestStatuses'])
        ->name('request-statuses');
    Route::get('positions', [DictionaryController::class, 'positions'])
        ->name('positions');
    Route::get('search_place', [DictionaryController::class, 'searchPlace'])
        ->name('search-place');
    Route::get('tags', [DictionaryController::class, 'tags'])
        ->name('tags');
});
