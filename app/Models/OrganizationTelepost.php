<?php

declare(strict_types=1);

namespace App\Models;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Class OrganizationTelepost
 *
 * @property int $id
 * @property int $organization_id
 * @property string|null $name
 * @property string $channel
 * @property string|null $verify_code
 * @property Organization $organization
 */
class OrganizationTelepost extends Model
{
    public const POST_MAX_LENGTH = 1024;

    public const NEWLINE_TAGS = [
        '<h1>',
        '<h2>',
        '<h3>',
        '<h4>',
        '<h5>',
        '<h6>',
        '</h1>',
        '</h2>',
        '</h3>',
        '</h4>',
        '</h5>',
        '</h6>',
        '<p>',
        '</p>',
        '<br>',
        '<br/>',
        '<br />',
    ];

    public const ALLOWED_TAGS = '<b><strong><i><em><a>';

    public $timestamps = false;

    protected $fillable = ['name', 'channel'];

    /**
     * @return BelongsTo<Organization, OrganizationTelepost>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function sendNews(News $news): void
    {
        $frontUrl = config('app.front_url');
        $footer = is_string($frontUrl) && $news->organization->public_status === Organization::PUBLIC_STATUS_SHOW ?
            "\n\n<a href='$frontUrl/news/$news->organization_id/$news->id'>Читать на Freeunion.online</a>" : '';
        $preview = $news->getPreview();

        if ($preview !== null && $preview !== '') {
            $this->sendPhoto($preview, $news->content, $news->title, $footer);
        } else {
            $this->sendMessage($news->content, $news->title, $footer);
        }
    }

    public function sendMessage(string $text, string $title = '', string $footer = ''): void
    {
        $botName = config('telegram.bots.post_bot.token');

        if (! $botName || ! is_string($botName)) {
            throw new RuntimeException('Bot API key not set');
        }

        $client = new Client();
        try {
            $client->post("https://api.telegram.org/bot$botName/sendMessage", [
                'form_params' => [
                    'chat_id' => $this->channel,
                    'text' => self::compose($text, $title, $footer),
                    'parse_mode' => 'HTML',
                ],
            ]);
        } catch (GuzzleException) {
        }
    }

    public function sendPhoto(string $imageUrl, string $text, string $title = '', string $footer = ''): void
    {
        $botName = config('telegram.bots.post_bot.token');

        if (! $botName || ! is_string($botName)) {
            throw new RuntimeException('Bot API key not set');
        }

        $client = new Client();
        try {
            $client->post("https://api.telegram.org/bot$botName/sendPhoto", [
                'form_params' => [
                    'chat_id' => $this->channel,
                    'photo' => $imageUrl,
                    'caption' => self::compose($text, $title, $footer),
                    'parse_mode' => 'HTML',
                ],
            ]);
        } catch (GuzzleException) {
        }
    }

    public static function compose(string $text, string $title = '', string $footer = ''): string
    {
        $result = '';

        if ($title !== '') {
            $result = "<b>$title</b>\n";
        }

        $maxLength = self::POST_MAX_LENGTH - mb_strlen($footer);
        $textParts = explode(
            "\n",
            strip_tags(
                str_ireplace(self::NEWLINE_TAGS, "\n", $text),
                self::ALLOWED_TAGS
            )
        );

        foreach ($textParts as $part) {
            $part = trim($part);

            if ($part === '') {
                continue;
            }

            if (mb_strlen($result."\n".$part) > $maxLength) {
                break;
            }

            $result .= "\n".$part;
        }

        return $result.$footer;
    }

    public static function normalizeChannelName(string $channel): string
    {
        return '@'.preg_replace('/[^\w_]/', '', str_replace('https://t.me/', '', $channel));
    }

    protected static function boot(): void
    {
        parent::boot();

        self::creating(static function (OrganizationTelepost $telepost) {
            $telepost->verify_code = Str::random(64);
        });

        self::saving(static function (OrganizationTelepost $telepost) {
            if ($telepost->isDirty('channel')) {
                $telepost->channel = self::normalizeChannelName($telepost->channel);
                $telepost->verify_code = Str::random(64);
            }
        });
    }
}
