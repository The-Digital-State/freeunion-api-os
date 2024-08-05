<?php

declare(strict_types=1);

use App\Models\Fundraising;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeDescriptionFundraisingToText extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $descriptions = Fundraising::query()->pluck('description', 'id')->toArray();
        Schema::table('fundraisings', static function (Blueprint $table) {
            $table->dropColumn(['description']);
        });

        Schema::table('fundraisings', static function (Blueprint $table) {
            $table->text('description')->nullable()
                ->after('title');
        });

        foreach ($descriptions as $id => $description) {
            /** @var Fundraising|null $fundraising */
            $fundraising = Fundraising::query()->find($id);

            if ($fundraising) {
                $fundraising->description = $description;
                $fundraising->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fundraisings', static function (Blueprint $table) {
            $table->dropColumn(['description']);
        });

        Schema::table('fundraisings', static function (Blueprint $table) {
            $table->string('description')->nullable()
                ->after('title');
        });
    }
}
