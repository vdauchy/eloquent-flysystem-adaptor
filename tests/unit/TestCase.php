<?php

declare(strict_types=1);

namespace VDauchy\EloquentFlysystemAdaptor\Tests\unit;

use CreateContentsTable;
use Illuminate\Foundation\Application;
use VDauchy\EloquentFlysystemAdaptor\ServiceProvider;
use VDauchy\SqlAnalyzer\frameworks\laravel\HasSqlAnalyzer;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use HasSqlAnalyzer;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        include_once __DIR__ . '/../../src/database/migrations/create_contents_table.php.stub';
        (new CreateContentsTable())->up();

        $this->analyzerSetUp();
    }

    /**
     * Define environment setup.
     *
     * @param  Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections', [
            'sqlite' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
        ]);
    }

    /**
     * Get package providers.
     *
     * @param Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return array_merge(parent::getPackageProviders($app), [
            ServiceProvider::class,
        ]);
    }
}
