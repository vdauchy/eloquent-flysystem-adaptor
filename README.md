**Eloquent Flysystem**

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
            /* Optional: Set a custom callable to generate urls pointing to a controller able to render the file (or overwrite the static method 'getUrl()') */
            'getUrl' => [CustomContentModel::class, 'getSomeCustomUrl']
        ]   
    ]
];
```

```php 
use VDauchy\EloquentFlysystemAdaptor\models\Content;

class CustomContentModel extends Content 
{
    /**
     * Example of method used to resolve the path.
     */
    static public function getSomeCustomUrl(string $path, ?array $metadata): string
    {
        return route('my.content', ['uuid' => $metadata['uuid']]);
    }
}
```

***Maintenance***

Here are the steps to develop/test this package using docker.

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
```