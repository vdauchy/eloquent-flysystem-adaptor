<?php

declare(strict_types=1);

namespace VDauchy\EloquentFlysystemAdaptor\Tests\unit;

use Ramsey\Uuid\UuidInterface;
use VDauchy\EloquentFlysystemAdaptor\models\Content;

class ModelTest extends TestCase
{
    public function testCreateFile()
    {
        $content = Content::create([
            'path' => 'a/b/c/foo.png',
            'is_file' => true,
            'is_public' => true,
            'created_at' => time(),
            'updated_at' => time(),
        ])->refresh();

        $this->instance(Content::class, $content);
        $this->assertSame('a/b/c/foo.png', $content->path);
        $this->assertSame('a/b/c', $content->dirname);
        $this->assertSame('foo.png', $content->filename);
        $this->assertTrue($content->is_file);
        $this->assertTrue($content->is_public);
        $this->assertInstanceOf(UuidInterface::class, $content->uuid);
    }

    public function testCreateDirectory()
    {
        $content = Content::create([
            'path' => 'a/b/c',
            'is_file' => false,
            'is_public' => true,
            'created_at' => time(),
            'updated_at' => time(),
        ])->refresh();

        $this->instance(Content::class, $content);
        $this->assertSame('a/b/c', $content->path);
        $this->assertSame('a/b/c', $content->dirname);
        $this->assertNull($content->filename);
        $this->assertFalse($content->is_file);
        $this->assertTrue($content->is_public);
        $this->assertInstanceOf(UuidInterface::class, $content->uuid);
    }
}
