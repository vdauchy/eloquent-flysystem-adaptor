<?php

declare(strict_types=1);

namespace VDauchy\EloquentFlysystemAdaptor\Tests\unit;

use Generator;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Filesystem;
use Ramsey\Uuid\UuidInterface;
use VDauchy\EloquentFlysystemAdaptor\EloquentAdapter;

/**
 * @see https://github.com/thephpleague/flysystem/blob/1.x/tests/ftp/FtpIntegrationTestCase.php
 */
class AdapterFromFtpTest extends TestCase
{
    /**
     * @var AdapterInterface
     */
    protected static AdapterInterface $adapter;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @before
     */
    public function setupFilesystem()
    {
        $this->filesystem = new Filesystem(new EloquentAdapter());
    }

    /**
     * @test
     */
    public function writingReadingDeleting()
    {
        $filesystem = $this->filesystem;
        $this->assertTrue($filesystem->put('path.txt', 'file contents'));
        $this->assertEquals('file contents', $filesystem->read('path.txt'));
        $this->assertTrue($filesystem->delete('path.txt'));
    }

    /**
     * @test
     * @dataProvider filenameProvider
     */
    public function writingAndReadingFilesWithSpecialPath(string $path): void
    {
        $this->setupFilesystem();
        $filesystem = $this->filesystem;

        $filesystem->write($path, 'contents');
        $filesystem->listContents('some');
        $contents = $filesystem->read($path);

        $this->assertEquals('contents', $contents);
    }

    public function filenameProvider(): Generator
    {
        yield "a path with square brackets in filename 1" => ["some/file[name].txt"];
        yield "a path with square brackets in filename 2" => ["some/file[0].txt"];
        yield "a path with square brackets in filename 3" => ["some/file[10].txt"];
        yield "a path with square brackets in dirname 1" => ["some[name]/file.txt"];
        yield "a path with square brackets in dirname 3" => ["some[10]/file.txt"];
        yield "a path with square brackets in dirname 2" => ["some[0]/file.txt"];
        yield "a path with curly brackets in filename 1" => ["some/file{name}.txt"];
        yield "a path with curly brackets in filename 2" => ["some/file{0}.txt"];
        yield "a path with curly brackets in filename 3" => ["some/file{10}.txt"];
        yield "a path with curly brackets in dirname 1" => ["some{name}/filename.txt"];
        yield "a path with curly brackets in dirname 2" => ["some{0}/filename.txt"];
        yield "a path with curly brackets in dirname 3" => ["some{10}/filename.txt"];
        yield "a path with plus sign in dirname" => ["some+dir/filename.txt"];
        yield "a path with plus sign in filename" => ["some/file+name.txt"];
    }

    /**
     * @test
     */
    public function creatingADirectory()
    {
        $this->filesystem->createDir('dirname/directory');
        $metadata = $this->filesystem->getMetadata('dirname/directory');
        $this->assertEquals('dir', $metadata['type']);
        $this->assertEquals('public', $metadata['visibility']);
        $this->assertInstanceOf(UuidInterface::class, $metadata['uuid']);
        $this->filesystem->deleteDir('dirname');
    }

    /**
     * @test
     */
    public function writingInADirectoryAndDeletingTheDirectory()
    {
        $filesystem = $this->filesystem;
        $this->assertTrue($filesystem->write('deeply/nested/path.txt', 'contents'));
        $this->assertTrue($filesystem->has('deeply/nested'));
        $this->assertTrue($filesystem->has('deeply'));
        $this->assertTrue($filesystem->has('deeply/nested/path.txt'));
        $this->assertTrue($filesystem->deleteDir('deeply/nested'));
        $this->assertFalse($filesystem->has('deeply/nested'));
        $this->assertFalse($filesystem->has('deeply/nested/path.txt'));
        $this->assertTrue($filesystem->has('deeply'));
        $this->assertTrue($filesystem->deleteDir('deeply'));
        $this->assertFalse($filesystem->has('deeply'));
    }

    /**
     * @test
     */
    public function listingFilesOfADirectory()
    {
        $filesystem = $this->filesystem;
        $filesystem->write('dirname/a.txt', 'contents');
        $filesystem->write('dirname/b/b.txt', 'contents');
        $filesystem->write('dirname/c.txt', 'contents');
        $files = $filesystem->listContents('', true);
        $files = array_map(fn ($i) => $i['path'], $files);
        $expected = ['dirname', 'dirname/a.txt', 'dirname/b', 'dirname/b/b.txt', 'dirname/c.txt'];
        $filesystem->deleteDir('dirname');
        $this->assertEquals($expected, $files);
    }
}
