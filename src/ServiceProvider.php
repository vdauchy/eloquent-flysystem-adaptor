<?php

declare(strict_types=1);

namespace VDauchy\EloquentFlysystemAdaptor;

use Illuminate\Database\Grammar;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Support\Fluent;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ServiceProvider extends PackageServiceProvider
{
    public function packageRegistered(): void
    {
        Grammar::macro('typeSizeableBinary', function (Fluent $column) {
            $length = intval($column->size ?? 16);
            return "binary({$length})";
        });

        Blueprint::macro('sizeableBinary', function (string $column, int $size): ColumnDefinition {
            assert($size > 0);
            assert($this instanceof Blueprint);
            return $this->addColumn('sizeableBinary', $column, ['size' => $size]);
        });

        Grammar::macro('typeSizeableBlob', function (Fluent $column) {
            $length = strtoupper($column->size ?? 'medium');
            return "{$length}BLOB";
        });

        Blueprint::macro('sizeableBlob', function (string $column, string $size): ColumnDefinition {
            assert(in_array($size, ['tiny', 'medium', 'long']));
            assert($this instanceof Blueprint);
            return $this->addColumn('sizeableBlob', $column, ['size' => $size]);
        });
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('eloquent-flysystem-adaptor')
            ->hasMigration('create_contents_table.php');
    }
}
