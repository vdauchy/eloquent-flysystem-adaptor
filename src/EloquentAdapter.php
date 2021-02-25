<?php

declare(strict_types=1);

namespace VDauchy\EloquentFlysystemAdaptor;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use League\Flysystem\Util;
use RuntimeException;
use VDauchy\EloquentFlysystemAdaptor\models\Content;

class EloquentAdapter extends AbstractAdapter implements AdapterInterface
{
    public const METADATA_FIELDS = [
        'created_at',
        'is_file',
        'is_public',
        'mimetype',
        'path',
        'size',
        'updated_at',
        'uuid',
    ];

    /**
     * @var Content
     */
    private Content $model;

    /**
     * @var Closure
     */
    private Closure $getUrl;

    /**
     * @param  string  $model
     * @param  callable|null  $getUrl
     */
    public function __construct(string $model = Content::class, ?callable $getUrl = null)
    {
        $this->model = new $model();
        $this->getUrl = Closure::fromCallable($getUrl ?? fn (string $path, array $metadata): string => $path);
    }

    /**
     * @param  string  $path
     * @return string
     */
    public function getUrl(string $path): string
    {
        return ($this->getUrl)($path, $this->getMetadata($path) ?: []);
    }

    /**
     * @inheritDoc
     */
    public function write($path, $contents, Config $config)
    {
        return DB::transaction(function () use ($path, $contents, $config) {
            if (! $this->doCreateDir(Util::dirname($path), $config)) {
                return false;
            }
            return $this
                ->contents()
                ->updateOrCreate([
                    'is_file' => true,
                    'path' => $this->applyPathPrefix($path),
                ], [
                    'contents' => $contents,
                    'created_at' => $config->get('timestamp', time()),
                    'is_public' => ($config->get('visibility', Content::PUBLIC) === Content::PUBLIC),
                    'mimetype' => $config->get('mimetype', Util::guessMimeType($path, $contents)),
                    'size' => Util::contentSize($contents),
                    'updated_at' => $config->get('timestamp', time()),
                ])
                ->toArray();
        });
    }

    /**
     * @inheritDoc
     */
    public function writeStream($path, $resource, Config $config)
    {
        return $this->write($path, stream_get_contents($resource), $config);
    }

    /**
     * @inheritDoc
     */
    public function update($path, $contents, Config $config)
    {
        return DB::transaction(function () use ($path, $contents, $config) {
            $file = $this
                ->contents()
                ->where([
                    'is_file' => true,
                    'path' => $this->applyPathPrefix($path),
                ])
                ->first();
            if ($file instanceof Content) {
                $file->update([
                    'contents' => $contents,
                    'is_public' => ($config->get('visibility', $file->visibility) === Content::PUBLIC),
                    'mimetype' => $config->get('mimetype', Util::guessMimeType($path, $contents)),
                    'size' => Util::contentSize($contents),
                    'updated_at' => $config->get('timestamp', time()),
                ]);
                return $file->toArray();
            }
            return false;
        });
    }

    /**
     * @inheritDoc
     */
    public function updateStream($path, $resource, Config $config)
    {
        return $this->update($path, stream_get_contents($resource), $config);
    }

    /**
     * @inheritDoc
     */
    public function rename($path, $newpath)
    {
        return DB::transaction(function () use ($path, $newpath): bool {
            if (! $this->copy($path, $newpath)) {
                return false;
            }
            return $this->delete($path);
        });
    }

    /**
     * @inheritDoc
     */
    public function copy($path, $newpath)
    {
        return DB::transaction(function () use ($path, $newpath): bool {
            if (! $this->doCreateDir(Util::dirname($newpath), new Config())) {
                return false;
            }
            return $this
                ->contents()
                ->firstWhere('path', $this->applyPathPrefix($path))
                ->replicate([
                    'uuid',
                ])
                ->fill([
                    'path' => $this->applyPathPrefix($newpath),
                    'updated_at' => time(),
                ])
                ->save();
        });
    }

    /**
     * @inheritDoc
     */
    public function delete($path)
    {
        return DB::transaction(fn (): bool => boolval($this
            ->contents()
            ->where([
                'path' => $this->applyPathPrefix($path),
            ])
            ->delete()));
    }

    /**
     * @inheritDoc
     */
    public function deleteDir($dirname)
    {
        return DB::transaction(function () use ($dirname): bool {
            if (! $this->hasDirectory($dirname)) {
                return false;
            }
            return boolval($this
                ->contents()
                ->whereIn('path', collect($this->listContents($dirname, true))
                    ->pluck('path')
                    ->map(fn (string $path) => $this->applyPathPrefix($path))
                    ->add($this->applyPathPrefix($dirname)))
                ->delete());
        });
    }

    /**
     * @inheritDoc
     */
    public function createDir($dirname, Config $config)
    {
        return DB::transaction(function () use ($dirname, $config) {
            if (! $this->doCreateDir($dirname, $config)) {
                return false;
            }
            return $this->getMetadata($dirname);
        });
    }

    /**
     * @inheritDoc
     */
    public function setVisibility($path, $visibility)
    {
        return DB::transaction(function () use ($path, $visibility) {
            $file = $this
                ->contents()
                ->firstWhere('path', $this->applyPathPrefix($path));
            if ($file instanceof Content) {
                $file->update([
                    'is_public' => ($visibility === Content::PUBLIC),
                ]);
                return $file->toArray();
            }
            return false;
        });
    }

    /**
     * @inheritDoc
     */
    public function has($path)
    {
        return boolval($this
            ->contents()
            ->where('path', $this->applyPathPrefix($path))
            ->exists());
    }

    /**
     * @inheritDoc
     */
    public function read($path)
    {
        $file = $this
            ->contents()
            ->firstWhere('path', $this->applyPathPrefix($path));
        return ($file instanceof Content)
            ? $file->toArray()
            : false;
    }

    /**
     * @inheritDoc
     */
    public function readStream($path)
    {
        $file = $this
            ->contents()
            ->firstWhere('path', $this->applyPathPrefix($path));
        if ($file instanceof Content) {
            $stream = fopen('php://memory', 'w+b');
            if (! is_resource($stream)) {
                throw new RuntimeException('Unable to create memory stream.');
            }
            fwrite($stream, $file->contents ?? '');
            rewind($stream);
            return array_merge($file->toArray(), ['stream' => $stream]);
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function listContents($directory = '', $recursive = false): array
    {
        return $this
            ->contents()
            ->select(self::METADATA_FIELDS)
            ->where('path', '!=', $this->applyPathPrefix($directory))
            ->where(fn (Builder $query) => $query
                ->where(fn (Builder $query) => $query
                    ->where('path', 'like', $this->applyPathPrefix($directory) . '%')
                    ->when(! $recursive, fn (Builder $query) => $query
                        ->Where('path', 'not like', $this->applyPathPrefix($directory) . '%/%')))
                ->orWhere(fn (Builder $query) => $query
                    ->Where('path', 'like', $this->applyPathPrefix($directory) . '/%')
                    ->when(! $recursive, fn (Builder $query) => $query
                        ->Where('path', 'not like', $this->applyPathPrefix($directory) . '/%/%'))))
            ->orderBy('path')
            ->chunkMap(fn (Content $file): array => $file->fill([
                    'path' => $this->removePathPrefix($file->path),
                ])->toArray())
            ->all();
    }

    /**
     * @inheritDoc
     */
    public function getMetadata($path)
    {
        $file = $this
            ->contents()
            ->select(self::METADATA_FIELDS)
            ->firstWhere('path', $this->applyPathPrefix($path));
        return ($file instanceof Content)
            ? array_filter($file->toArray(), fn ($metadata) => ! is_null($metadata))
            : false;
    }

    /**
     * @inheritDoc
     */
    public function getSize($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * @inheritDoc
     */
    public function getMimetype($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * @inheritDoc
     */
    public function getTimestamp($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * @inheritDoc
     */
    public function getVisibility($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * Creates a directory.
     *
     * @param string $dirname
     * @param Config $config
     * @return bool
     */
    protected function doCreateDir(string $dirname, Config $config)
    {
        $dirname = trim($dirname, DIRECTORY_SEPARATOR);

        if ($this->hasDirectory($dirname)) {
            return true;
        }

        if ($this->hasFile($dirname)) {
            return false;
        }

        if ($dirname && ! $this->doCreateDir(Util::dirname($dirname), $config)) {
            return false;
        }

        $this
            ->contents()
            ->firstOrCreate([
                'path' => $this->applyPathPrefix($dirname),
            ], [
                'created_at' => $config->get('timestamp', time()),
                'is_file' => false,
                'is_public' => ($config->get('visibility', Content::PUBLIC) === Content::PUBLIC),
                'updated_at' => $config->get('timestamp', time()),
            ]);

        return true;
    }

    /**
     * Checks whether a directory exists.
     *
     * @param string $path The directory.
     * @return bool True if it exists, and is a directory, false if not.
     */
    protected function hasDirectory(string $path): bool
    {
        return (((array)$this->read($path))['type'] ?? null) === Content::DIRECTORY;
    }

    /**
     * Checks whether a file exists.
     *
     * @param string $path The file.
     * @return bool True if it exists, and is a file, false if not.
     */
    protected function hasFile(string $path): bool
    {
        return (((array)$this->read($path))['type'] ?? null) === Content::FILE;
    }

    /**
     * Determines if the path is inside the directory.
     *
     * @param string $path
     * @param string $directory
     * @return bool
     */
    protected function pathIsInDirectory(string $path, string $directory)
    {
        return $directory === '' || strpos($path, $directory . DIRECTORY_SEPARATOR) === 0;
    }

    /**
     * @return Builder
     */
    protected function contents(): Builder
    {
        return $this->model->newQuery();
    }
}
