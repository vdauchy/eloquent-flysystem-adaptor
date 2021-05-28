# Eloquent Flysystem Adaptor

<p>
<a href="https://github.com/vdauchy/eloquent-flysystem-adaptor/actions"><img src="https://github.com/vdauchy/eloquent-flysystem-adaptor/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/vdauchy/eloquent-flysystem-adaptor"><img src="https://img.shields.io/packagist/dt/vdauchy/eloquent-flysystem-adaptor" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/vdauchy/eloquent-flysystem-adaptor"><img src="https://img.shields.io/packagist/v/vdauchy/eloquent-flysystem-adaptor" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/vdauchy/eloquent-flysystem-adaptor"><img src="https://img.shields.io/packagist/l/vdauchy/eloquent-flysystem-adaptor" alt="License"></a>
</p>

## Introduction

This package is an Adaptor for Flysystem using Laravel's ORM (Eloquent).

In `config\filesystems.php` add your new disk as:

```php
return [
    'disks' => [
        /* Name your disk as you wish */
        'my_sql_disk' => [
            /* Use 'eloquent' driver that is registered by this package. */
            'driver' => 'eloquent',
            /* Optional: Set your custom model that extends the base Model to use different tables per disk */
            'model' => CustomContentModel::class,
            /* Optional: Set a custom callable to generate urls or just overwrite the static method 'getUrl()' */
            'getUrl' => [CustomContentModel::class, 'getSomeCustomUrl']
        ]   
    ]
];
```

Create as many models as you wish like:
```php 
use VDauchy\EloquentFlysystemAdaptor\models\Content;

class CustomContentModel extends Content 
{
    /**
     * Example of `getUrl` overwrite to generate URLs using the UUID.
     */
    static public function getUrl(string $path, ?array $metadata): string
    {
        return route('my.custom.content', ['uuid' => $metadata['uuid']]);
    }
}
```

Create controllers like:
```php
Route::get('my-custom-content/{uuid}', function (string $uuid) {
    $content = CustomContentModel::fromUuid($uuid);
    /* TODO: Add checks for public/private visibility and improve type/mime handling */
    return response($content->contents, 200, ['Content-Type' => $content->mimetype]);
})
->where('uuid', '^[a-z0-9\-]{36}$')
->name('my.custom.content');
```

## Maintenance

Here are the steps to develop/test this package using docker:

```shell
# Make sure no image with the same tag exists.
docker image rm php-cli-eloquent-flysystem-adaptor:latest -f;

# Build new image with the expected extensions.
docker build . -t php-cli-eloquent-flysystem-adaptor:latest;

# Update vendors.
docker run \
    --volume $(pwd):/usr/src/app \
    php-cli-eloquent-flysystem-adaptor:latest \
    composer update;
    
# Run unit tests.
docker run \
    --volume $(pwd):/usr/src/app \
    php-cli-eloquent-flysystem-adaptor:latest \
    composer unit;
    
# Run static analysis.
docker run \
    --volume $(pwd):/usr/src/app \
    php-cli-eloquent-flysystem-adaptor:latest \
    composer lint;
    
# Run style check.
docker run \
    --volume $(pwd):/usr/src/app \
    php-cli-eloquent-flysystem-adaptor:latest \
    composer fmt;
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.