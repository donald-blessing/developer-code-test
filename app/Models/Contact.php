<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Contact extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $appends = [
        'attachment',
    ];

    protected $fillable = [
        'name',
        'email',
        'message',
    ];

    protected $table = 'contacts';

    public const MEDIA_COLLECTION = 'attachment';

    protected array $mimeTypes = [
        'PNG' => 'image/png',
        'SVG' => 'image/svg+xml',
        'SVG2' => 'application/svg+xml',
        'CSV' => 'text/csv',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_COLLECTION)
            ->useDisk('media');
    }

    /**
     * @return array
     */
    public function getAttachmentAttribute(): array
    {
        return $this->getMedia(self::MEDIA_COLLECTION)->map(function ($item) {
            return $item->getUrl();
        })->toArray();
    }


}
