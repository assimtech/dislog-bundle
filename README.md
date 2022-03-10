# DislogBundle

[![Latest Stable Version](https://poser.pugx.org/assimtech/dislog-bundle/v/stable)](https://packagist.org/packages/assimtech/dislog-bundle)
[![Total Downloads](https://poser.pugx.org/assimtech/dislog-bundle/downloads)](https://packagist.org/packages/assimtech/dislog-bundle)
[![Latest Unstable Version](https://poser.pugx.org/assimtech/dislog-bundle/v/unstable)](https://packagist.org/packages/assimtech/dislog-bundle)
[![License](https://poser.pugx.org/assimtech/dislog-bundle/license)](https://packagist.org/packages/assimtech/dislog-bundle)
[![Build Status](https://travis-ci.org/assimtech/dislog-bundle.svg?branch=master)](https://travis-ci.org/assimtech/dislog-bundle)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/assimtech/dislog-bundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/assimtech/dislog-bundle/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/assimtech/dislog-bundle/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/assimtech/dislog-bundle/?branch=master)

Dislog Bundle provides symfony ^3|^4|^5 integration for [assimtech/dislog](https://github.com/assimtech/dislog), an API Call logger.

## Installation

Install with composer:

```shell
composer require assimtech/dislog-bundle
```

Enable the bundle `Assimtech\DislogBundle\AssimtechDislogBundle`.

Configure the [handler](#handler-configuration):

```yaml
assimtech_dislog:
    handler:
        stream:
            resource: '/tmp/my.log'
```

Start logging your api calls:

```php
$request = '<request />';

/** @var \Assimtech\Dislog\ApiCallLoggerInterface $apiCallLogger */
$apiCall = $apiCallLogger->logRequest($request, $endpoint, $appMethod, $reference);

$response = $api->transmit($request);

$this->apiCallLogger->logResponse($apiCall, $response);
```

See [assimtech/dislog](https://github.com/assimtech/dislog) for more advanced usage.

Remove old api calls:

```sh
bin/console assimtech:dislog:remove
```

To log HTTP requests from a PSR-18 client, you may use the service `assimtech_dislog.logging_http_client`:

```php
/**
 * @var \Psr\Http\Message\RequestInterface $request
 * @var \Assimtech\Dislog\LoggingHttpClientInterface $httpClient
 * @var \Psr\Http\Message\ResponseInterface $response
 */
$response = $httpClient->sendRequest(
    $request,
    $appMethod,
    $reference,
    $requestProcessors,
    $responseProcessors,
    $deferredLogging
);
```

## Handler configuration

### Doctrine Object Managers

The doctrine mapping definitions included with the bundle are placed in non-default paths intentionally to prevent automapping from accidently loading into the wrong object manager.

E.g. If you have an application which uses both `Doctrine\ORM` (for your normal application entities) as well as `Doctrine\ODM` (for Dislog) we don't want `Doctrine\ORM` to detect and load the mapping information from `DislogBundle`'s `ApiCall.orm.xml`. If it did, you may end up with a table being created if you also use `doctrine:schema:update` or Doctrine Migrations.

This means mapping information for Dislog must be loaded manually.

**WARNING: It is advisable to avoid using your application's default entity manager as a `flush()` from dislog may interfere with your application**

#### Doctrine ODM

An example of adding the mapping information with DoctrineMongoDBBundle
```yaml
doctrine_mongodb:
    document_managers:
        default:
            # Your main application document manager config
        dislog:
            connection: default
            mappings:
                AssimtechDislogBundle:
                    type: xml
                    prefix: Assimtech\Dislog\Model
                    dir: Resources/config/doctrine/mongodb

assimtech_dislog:
    handler:
        doctrine_document_manager:
            document_manager: doctrine_mongodb.odm.dislog_document_manager
```

For more advanced setups please see [DoctrineMongoDBBundle Configuration](http://symfony.com/doc/current/bundles/DoctrineMongoDBBundle/config.html)

#### Doctrine ORM

An example of adding the mapping information with DoctrineBundle
```yaml
doctrine:
    orm:
        entity_managers:
            default:
                # Your main application entity manager config
            dislog:
                connection: default
                mappings:
                    AssimtechDislogBundle:
                        type: xml
                        prefix: Assimtech\Dislog\Model
                        dir: Resources/config/doctrine/orm

assimtech_dislog:
    handler:
        doctrine_entity_manager:
            entity_manager: doctrine.orm.dislog_entity_manager
```

For more advanced setups please see [DoctrineBundle Configuration](http://symfony.com/doc/master/bundles/DoctrineBundle/configuration.html)

### Service

You may use your own logger service which implements `Assimtech\Dislog\ApiCallLoggerInterface`.

```yaml
assimtech_dislog:
    handler:
        service:
            name: App\Dislog\ApiLogger
```

**Note:** You are responsible for passing request / response through any processors before persisting. The easiest way to implement a custom logger is to extend the default one.

```php
namespace App\Dislog;

use Assimtech\Dislog\Model\ApiCallInterface;
use Assimtech\Dislog\ApiCallLogger;

class ApiLogger extends ApiCallLogger
{
    public function logRequest(
        ?string $request,
        ?string $endpoint,
        ?string $appMethod,
        string $reference = null,
        /* callable[]|callable */ $processors = []
    ): ApiCallInterface {
        $processedRequest = $this->processPayload($processors, $request);

        $apiCall = $this->apiCallFactory->create();
        $apiCall
            ->setRequest($processedRequest)
            ->setEndpoint($endpoint)
            ->setMethod($appMethod)
            ->setReference($reference)
            ->setRequestTime(microtime(true))
        ;

        // Persist $apiCall somewhere

        return $apiCall;
    }

    public function logResponse(
        ApiCallInterface $apiCall,
        string $response = null,
        /* callable[]|callable */ $processors = []
    ): void {
        $duration = microtime(true) - $apiCall->getRequestTime();

        $processedResponse = $this->processPayload($processors, $response);

        $apiCall
            ->setResponse($processedResponse)
            ->setDuration($duration)
        ;

        // Update the persisted $apiCall
    }
}
```

### Stream

```yaml
assimtech_dislog:
    handler:
        stream:
            identity_generator: Assimtech\Dislog\Identity\UniqueIdGenerator
            resource: /tmp/dis.log
            serializer: Assimtech\Dislog\Serializer\StringSerializer
```

## Configuration reference

```yaml
assimtech_dislog:
    api_call_factory: assimtech_dislog.api_call.factory # Api Call Factory service name

    max_age: 2592000 # seconds to keep logs for

    handler:
        # *One* of the following sections must be configured, none are enable by default

        doctrine_document_manager:
            document_manager: ~ # document manager service name

        doctrine_entity_manager:
            entity_manager: ~ # entity manager service name

        service:
            name: ~ # Your custom handler service name

        stream:
            resource: ~ # Either a stream path ("/tmp/my.log", "php://stdout") or a stream resource (see fopen)
            identity_generator: Assimtech\Dislog\Identity\UniqueIdGenerator # Identity Generator service name
            serializer: Assimtech\Dislog\Serializer\StringSerializer # Serializer service name

    preferences:
        suppress_handler_exceptions: false # By default, api call logging exceptions are suppressed (they still get emitted as warnings to the psr_logger if any)
        endpoint_max_length: null # By default, limits endpoint max length. Recommended 255 if using a VARCHAR 255 in the storage layer
        method_max_length: null # By default, limits endpoint max length. Recommended 255 if using a VARCHAR 255 in the storage layer
        reference_max_length: null # By default, limits endpoint max length. Recommended 255 if using a VARCHAR 255 in the storage layer

    psr_logger: logger # (Optional) Psr-3 logger service name
```
