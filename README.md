# DislogBundle

[![Latest Stable Version](https://poser.pugx.org/assimtech/dislog-bundle/v/stable)](https://packagist.org/packages/assimtech/dislog-bundle)
[![Total Downloads](https://poser.pugx.org/assimtech/dislog-bundle/downloads)](https://packagist.org/packages/assimtech/dislog-bundle)
[![Latest Unstable Version](https://poser.pugx.org/assimtech/dislog-bundle/v/unstable)](https://packagist.org/packages/assimtech/dislog-bundle)
[![License](https://poser.pugx.org/assimtech/dislog-bundle/license)](https://packagist.org/packages/assimtech/dislog-bundle)
[![Build Status](https://travis-ci.org/assimtech/dislog-bundle.svg?branch=master)](https://travis-ci.org/assimtech/dislog-bundle)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/assimtech/dislog-bundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/assimtech/dislog-bundle/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/assimtech/dislog-bundle/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/assimtech/dislog-bundle/?branch=master)

Dislog Bundle provides symfony 2 integration for [assimtech/dislog](https://github.com/assimtech/dislog), an API Call logger.

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
$apiCall = $apiCallLogger->logRequest($request, $endpoint, $method, $reference);

$response = $api->transmit($request);

$this->apiCallLogger->logResponse($apiCall, $response);
```

See [assimtech/dislog](https://github.com/assimtech/dislog) for more advanced usage.

Remove old api calls:

```sh
bin/console assimtech:dislog:remove
```

## Handler configuration

### Stream

```yaml
assimtech_dislog:
    handler:
        stream:
            resource: /tmp/my.log
```

### Doctrine Entity Manager

The doctrine mapping definitions included with the bundle are placed in non-default paths intentionally to prevent automapping from accidently loading into the wrong entity manager.

E.g. If you have an application which uses both DoctrineORM (for your normal application entities) as well as DoctrineMongoDB (for Dislog) we don't want DoctrineORM to detect and load the mapping information from DislogBundle's ApiCall.orm.xml. If it did, you may end up with a table being created if you also use `doctrine:schema:update` or Doctrine Migrations.

This means mapping information for Dislog must be loaded manually.

**WARNING: It is advisable to avoid using your application's default entity manager as a `flush()` from dislog may interfere with your application**

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

#### Doctrine MongoDB

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
        doctrine_entity_manager:
            entity_manager: doctrine_mongodb.odm.dislog_document_manager
```

For more advanced setups please see [DoctrineMongoDBBundle Configuration](http://symfony.com/doc/current/bundles/DoctrineMongoDBBundle/config.html)

## Configuration reference

```yaml
assimtech_dislog:
    api_call_factory: assimtech_dislog.api_call.factory # Api Call Factory service name

    max_age: 2592000 # seconds to keep logs for

    handler:
        # *One* of the following sections must be configured, none are enable by default

        stream:
            resource: ~ # Either a stream path ("/tmp/my.log", "php://stdout") or a stream resource (see fopen)
            identity_generator: Assimtech\Dislog\Identity\UniqueIdGenerator # Identity Generator service name
            serializer: Assimtech\Dislog\Serializer\StringSerializer # Serializer service name

        doctrine_entity_manager:
            entity_manager: ~ # entity manager service name

        service:
            name: ~ # Your custom handler service name

    preferences:
        suppress_handler_exceptions: false # By default, api call logging exceptions are suppressed (they still get emitted as warnings to the psr_logger if any)

    psr_logger: logger # (Optional) Psr-3 logger service name
```
