<?php

declare(strict_types=1);

namespace VDauchy\EloquentFlysystemAdaptor\Tests\unit;

use Illuminate\Support\Facades\Route;
use League\Flysystem\Config;
use VDauchy\EloquentFlysystemAdaptor\EloquentAdapter;
use VDauchy\EloquentFlysystemAdaptor\models\Content;

class AdapterTest extends TestCase
{
    public function testWrite()
    {
        $path = '/to/path/file';
        $contents = random_bytes(1024);

        $savedFile = (new EloquentAdapter())->write($path, $contents, new Config());

        $this->assertNotFalse($savedFile);
        $this->assertSame(ltrim($path, '/'), $savedFile['path']);
        $this->assertSame($contents, $savedFile['contents']);
        $this->assertSame(strlen($contents), $savedFile['size']);
        $this->assertSame($savedFile['type'], 'file');
        $this->assertSame($savedFile['visibility'], 'public');
        $this->assertArrayHasKey('uuid', $savedFile);
    }

    public function testDoubleWriteSamePath()
    {
        $path = 'to/path/file';

        $savedFile = (new EloquentAdapter())->write($path, $content = random_bytes(1024), new Config());
        $this->assertNotFalse($savedFile);
        $this->assertSame($path, $savedFile['path']);
        $this->assertSame($content, $savedFile['contents']);

        $savedFile = (new EloquentAdapter())->write($path, $content = random_bytes(1024), new Config());
        $this->assertNotFalse($savedFile);
        $this->assertSame($path, $savedFile['path']);
        $this->assertSame($content, $savedFile['contents']);
    }

    public function testCreateDir()
    {
        $dirName = '/to/path/dir';

        $savedDir = (new EloquentAdapter())->createDir($dirName, new Config());

        $this->assertNotFalse($savedDir);
        $this->assertSame(ltrim($dirName, '/'), $savedDir['path']);
        $this->assertArrayNotHasKey('contents', $savedDir);
        $this->assertArrayNotHasKey('size', $savedDir);
        $this->assertSame($savedDir['type'], 'dir');
        $this->assertSame($savedDir['visibility'], 'public');
        $this->assertArrayHasKey('uuid', $savedDir);
    }

    public function testDefaultGetUrl()
    {
        $path = '/to/path/file';
        $contents = random_bytes(1024);
        $adapter = new EloquentAdapter();

        $adapter->write($path, $contents, new Config());

        $this->assertSame($path, $adapter->getUrl($path));
    }

    public function testCustomCallableGetUrl()
    {
        $path = '/to/path/file';
        $contents = random_bytes(1024);
        $adapter = new EloquentAdapter(
            Content::class,
            fn (string $path, ?array $metadata) => route('content', ['uuid' => $metadata['uuid']]),
        );

        $savedFile = $adapter->write($path, $contents, new Config(['mimetype' => 'application']));

        $this->assertSame("http://localhost/content/{$savedFile['uuid']}", $adapter->getUrl($path));
        $this->assertSame($contents, $this
            ->get("http://localhost/content/{$savedFile['uuid']}")
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application')
            ->content());
    }

    protected function defineWebRoutes($router)
    {
        Route::get('content/{uuid}', function (string $uuid) {
                $content = Content::fromUuid($uuid);
                return response($content->contents, 200, ['Content-Type' => $content->mimetype]);
        })
            ->where('uuid', '^[a-z0-9\-]{36}$')
            ->name('content');
    }
}
