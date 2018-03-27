# php-redis-sampler

## Requirements

- php > 5.3
- [phpredis](https://github.com/phpredis/phpredis) extension

## Usage

```
php redis-sampler.php <host> <db> <number_of_keys> <key_type>
```

## Examples

```
#Sample from db 4 using a sample of 1000 keys
php redis-sampler.php redis-host.internal.example.net 4 1000

#Sample from db 0 using a sample of 10000 keys of hash type
php redis-sampler.php redis-host.internal.example.net 4 1000 hash
```
