**Eloquent Flysystem**

This package is an Adaptor for Flysystem using Laravel's ORM (Eloquent).

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