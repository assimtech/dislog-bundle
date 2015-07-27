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

Add the bundle to your `AppKernel`:

```php
$bundles = array(
    /** Your other bundles */
    new Assimtech\DislogBundle\AssimtechDislogBundle(),
);
```

Configure at least a handler in your `config.yml`:

```yaml
assimtech_dislog:
    handler:
        stream:
            resource: /tmp/my.log
```


Start logging your api calls:

```php
/** @var \Assimtech\Dislog\ApiCallLoggerInterface $apiCallLogger */
$apiCallLogger = $container->get('assimtech_dislog.logger');

$request = '<request />';

$apiCall = $apiCallLogger->logRequest($request, $endpoint, $method, $reference);

$response = $api->transmit($request);

$this->apiCallLogger->logResponse($apiCall, $response);
```

See [assimtech/dislog](https://github.com/assimtech/dislog) for more advanced usage.


## Handler configuration

### Stream

```yaml
assimtech_dislog:
    handler:
        stream:
            resource: /tmp/my.log
```


### Doctrine Object Manager

The doctrine mapping definitions included with the bundle are placed in non-default paths intentionally to prevent automapping from accidently loading into the wrong object manager.

E.g. If you have an application which uses both DoctrineORM (for your normal application entities) as well as DoctrineMongoDB (for Dislog) we don't want DoctrineORM to detect and load the mapping information from DislogBundle's ApiCall.orm.xml. If it did, you may end up with a table being created if you also use `doctrine:schema:update` or Doctrine Migrations.

This means mapping information for Dislog must be loaded manually.

#### Doctrine ORM

An example of adding the mapping information with DoctrineBundle to the default object manager
```yaml
doctrine:
    orm:
        entity_managers:
            default:
                mappings:
                    AssimtechDislogBundle:
                        type: xml
                        prefix: Assimtech\Dislog\Model
                        dir: Resources/config/doctrine/orm

assimtech_dislog:
    handler:
        doctrine_object_manager:
            object_manager: doctrine.orm.entity_manager
```

For more advanced setups please see [DoctrineBundle Configuration](http://symfony.com/doc/master/bundles/DoctrineBundle/configuration.html)


#### Doctrine MongoDB

An example of adding the mapping information with DoctrineMongoDBBundle to the default document manager
```yaml
doctrine_mongodb:
    document_managers:
        default:
            mappings:
                AssimtechDislogBundle:
                    type: xml
                    prefix: Assimtech\Dislog\Model
                    dir: Resources/config/doctrine/mongodb

assimtech_dislog:
    handler:
        doctrine_object_manager:
            object_manager: doctrine_mongodb.odm.default_document_manager
```

For more advanced setups please see [DoctrineMongoDBBundle Configuration](http://symfony.com/doc/current/bundles/DoctrineMongoDBBundle/config.html)


## Aliased Processors

[Aliased processors](https://github.com/assimtech/dislog/blob/master/README.md#aliasing-processors) can be automatically
registered with the `ApiCallLogger` by tagging them with `name: assimtech_dislog.processor` and an `alias`:

```yaml
my_bundle.my_service.dislog_processor.my_processor:
    public: false
    class: %assimtech_dislog.processor.regex_replace.class%
    arguments:
        - "/password=([\w]{2})[\w]+([\w]{2})/"
        - "password=$1***$2"
    tags:
        - { name: assimtech_dislog.processor, alias: my_service.password }
```

The above processor could then be used by simply referencing the alias `my_service.password`:

```php
$apiCallLogger->logRequest(
    $request,
    $endpoint,
    $method,
    $reference,
    array(
        'my_service.password',
    )
);
```


### Aliasing processors on demand using a factory

Adding aliased processors using the above method will result in all tagged processors being registered whenever the api call logger is constructed regardless of which api service is actually being used. Typically only one api is used in a single request. This means we only really want to construct and alias a subset of the available processors for an api when it is required. We can limit which processors are loaded into the `ApiCallLogger` by using a service factory.

```php
use Assimtech\Dislog\ApiCallLogger;

class MyApiFactory
{
    /**
     * @var ApiCallLogger $apiCallLogger
     */
    private $apiCallLogger;

    /**
     * @var callable[] $dislogProcessors
     * Associative array of callable
     *      array(
     *          'my_api.replace_password' => $passwordProcessor,
     *          'my_api.replace_secret' => $secretProcessor,
     *      )
     */
    private $dislogProcessors;

    public function __construct(ApiCallLogger $apiCallLogger, array $dislogProcessors)
    {
        $this->apiCallLogger = $apiCallLogger;
        $this->dislogProcessors = $dislogProcessors;
    }

    public function create()
    {
        // Add processors to API Call Logger
        foreach ($this->dislogProcessors as $alias => $dislogProcessor) {
            $this->apiCallLogger->setAliasedProcessor($alias, $dislogProcessor);
        }

        // Construct MyApi however it needs to be constructed
        return new MyApi($this->apiCallLogger);
    }
}
```

```yaml
services:
    my_api.factory:
        public: false
        class: MyApiFactory
        arguments:
            - "@assimtech_dislog.logger"
            -
                my_api.replace_password:    @my_api.replace_password
                my_api.replace_secret:      @my_api.replace_secret

    my_api.replace_password:
        public: false
        class: %assimtech_dislog.processor.regex_replace.class%
        arguments:
            - "/password=([\w]{2})[\w]+([\w]{2})/"
            - "password=$1***$2"

    my_api.replace_secret:
        public: false
        class: %assimtech_dislog.processor.string_replace.class%
        arguments:
            - %my_api.secret%
            - "***"

    my_api:
        class:   MyApi
        factory: ["@my_api.factory", create]
```

## Configuration reference

```yaml
assimtech_dislog:
    api_call_factory: assimtech_dislog.api_call.factory # Api Call Factory service name

    handler:
        # *One* of the following sections must be configured, none are enable by default

        stream:
            resource: ~ # Either a stream path ("/tmp/my.log", "php://stdout") or a stream resource (see fopen)
            identity_generator: assimtech_dislog.generator.unique_id # Identity Generator service name
            serializer: assimtech_dislog.serializer.string # Serializer service name

        doctrine_object_manager:
            object_manager: ~ # Object manager service name

        service:
            name: ~ # Your custom handler service name

    preferences:
        suppressHandlerExceptions: false # By default, api call logging exceptions are suppressed (they still get emitted as warnings to the psr_logger if any)

    psr_logger: logger # (Optional) Psr-3 logger service name
```

