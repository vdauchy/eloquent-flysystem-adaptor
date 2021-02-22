<?php

declare(strict_types=1);

namespace VDauchy\EloquentFlysystemAdaptor\Tests\unit;

use League\Flysystem\Config;
use VDauchy\EloquentFlysystemAdaptor\EloquentAdapter;

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
    }
}
