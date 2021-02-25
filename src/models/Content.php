<?php

declare(strict_types=1);

namespace VDauchy\EloquentFlysystemAdaptor\models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Util;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use VDauchy\EloquentFlysystemAdaptor\casts\UuidBinaryCast;

/**
 * @property int $id
 * @property string $path
 * @property UuidInterface $uuid
 * @property string $dirname
 * @property string|null $filename
 * @property bool $is_file
 * @property bool $is_directory
 * @property bool $is_public
 * @property bool $is_private
 * @property string $contents
 * @property int $size
 * @property string $visibility
 * @property string $mimetype
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Content extends Model
{
    public const CREATED_AT = null;
    public const UPDATED_AT = null;

    public const PUBLIC = AdapterInterface::VISIBILITY_PUBLIC;
    public const PRIVATE = AdapterInterface::VISIBILITY_PRIVATE;

    public const FILE = 'file';
    public const DIRECTORY = 'dir';

    protected $dateFormat = 'U';

    protected $fillable = [
        'mimetype',
        'path',
        'contents',
        'size',
        'is_file',
        'is_public',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'size' => 'int',
        'is_file' => 'bool',
        'is_public' => 'bool',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'uuid' => UuidBinaryCast::class,
    ];

    protected $appends = [
        'type',
        'timestamp',
        'visibility',
    ];

    protected $hidden = [
        'id',
        'is_file',
        'is_public',
        'created_at',
        'updated_at',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::creating(function (Content $file): void {
            $file->uuid ??= Uuid::uuid4();
        });
    }

    /**
     * @return bool
     */
    public function getIsDirectoryAttribute(): bool
    {
        return ! $this->is_file;
    }

    /**
     * @return bool
     */
    public function getIsPrivateAttribute(): bool
    {
        return ! $this->is_public;
    }

    /**
     * @return string
     */
    public function getDirnameAttribute(): string
    {
        return $this->is_file
            ? Util::dirname($this->path)
            : Util::normalizeDirname($this->path);
    }

    /**
     * @return string|null
     */
    public function getFilenameAttribute(): ?string
    {
        return $this->is_file
            ? basename($this->path)
            : null;
    }

    /**
     * @return string
     */
    public function getTypeAttribute(): string
    {
        return $this->is_file
            ? self::FILE
            : self::DIRECTORY;
    }

    /**
     * @return string
     */
    public function getVisibilityAttribute(): string
    {
        return $this->is_public
            ? self::PUBLIC
            : self::PRIVATE;
    }

    /**
     * @return int
     */
    public function getTimestampAttribute(): int
    {
        return $this->updated_at->unix();
    }
}
