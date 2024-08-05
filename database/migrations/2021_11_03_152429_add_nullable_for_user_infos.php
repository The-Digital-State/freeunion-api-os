<?php

declare(strict_types=1);

use App\Models\UserInfo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddNullableForUserInfos extends Migration
{
    public function up(): void
    {
        $saveFields = [];
        UserInfo::each(static function (UserInfo $userInfo) use (&$saveFields) {
            $saveFields[$userInfo->user_id] = [
                'sex' => $userInfo->sex,
                'country' => $userInfo->country,
                'worktype' => $userInfo->worktype,
                'work_place' => $userInfo->work_place,
            ];
        });

        Schema::table('user_infos', static function (Blueprint $table) {
            $table->dropColumn(['sex', 'country', 'worktype', 'work_place']);
        });

        Schema::table('user_infos', static function (Blueprint $table) {
            $table->unsignedTinyInteger('sex')->nullable()
                ->after('patronymic');
            $table->string('country', 2)->nullable()
                ->after('birthday');
            $table->integer('worktype')->nullable()
                ->after('country');
            $table->string('work_place')->nullable()
                ->after('scope');
        });

        DB::beginTransaction();

        foreach ($saveFields as $userId => $fields) {
            UserInfo::where('user_id', $userId)->update($fields);
        }

        DB::commit();
    }

    public function down(): void
    {
        $saveFields = [];
        UserInfo::each(static function (UserInfo $userInfo) use (&$saveFields) {
            $saveFields[$userInfo->user_id] = [
                'sex' => $userInfo->sex ?? 0,
                'country' => $userInfo->country ?? 'BY',
                'worktype' => $userInfo->worktype ?? 0,
                'work_place' => $userInfo->work_place ?? '',
            ];
        });

        Schema::table('user_infos', static function (Blueprint $table) {
            $table->dropColumn(['sex', 'country', 'worktype', 'work_place']);
        });

        Schema::table('user_infos', static function (Blueprint $table) {
            $table->unsignedTinyInteger('sex')
                ->after('patronymic');
            $table->string('country', 2)
                ->after('birthday');
            $table->integer('worktype')
                ->after('country');
            $table->string('work_place')
                ->after('scope');
        });

        DB::beginTransaction();

        foreach ($saveFields as $userId => $fields) {
            UserInfo::where('user_id', $userId)->update($fields);
        }

        DB::commit();
    }
}
