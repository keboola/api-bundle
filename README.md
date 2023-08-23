# Keboola API Bundle
Symfony bundle providing common functionality for Keboola API applications.

Features:
* authentication using Storage and Manage API tokens

## Installation
Install the package with Composer:
```shell
composer require keboola/api-bundle
```

To use authentication using attributes, add the following to your `config/packages/security.yaml`:
```yaml 
security:
  firewalls:
    attribute:
        lazy: true
        stateless: true
        custom_authenticators:
          - keboola.api_bundle.security.attribute_authenticator
```

## Configuration
No configuration is required.

Authentication attributes are configured automatically based on API clients installed:
* to use `StorageApiTokenAuth`, install `keboola/storage-api-client`
* to use `ManageApiTokenAuth`, install `keboola/kbc-manage-api-php-client`

> [!NOTE]
> If you forget to install appropriate client, you will get exception like
> `Service "Keboola\ApiBundle\Attribute\ManageApiTokenAuth" not found: the container inside "Symfony\Component\DependencyInjection\Argument\ServiceLocator" is a smaller service locator`

## License

MIT licensed, see [LICENSE](./LICENSE) file.
