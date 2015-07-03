# Dislog Bundle

[![Build Status](https://travis-ci.org/assimtech/dislog-bundle.svg?branch=master)](https://travis-ci.org/assimtech/dislog-bundle)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/assimtech/dislog-bundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/assimtech/dislog-bundle/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/assimtech/dislog-bundle/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/assimtech/dislog-bundle/?branch=master)


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

E.g. If you have an application which uses both DoctrineORM (for your normal application entities) as well as DoctrineMongoDB (for Dislog) we don't want DoctrineORM to dectect and load the mapping information from DislogBundle's ApiCall.orm.xml. If it did, you may end up with a table being created if you also use `doctrine:schema:update` or Doctrine Migrations.

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
                        prefix: Assimtech\Dislog
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
                    prefix: Assimtech\Dislog
                    dir: Resources/config/doctrine/mongodb

assimtech_dislog:
    handler:
        doctrine_object_manager:
            object_manager: doctrine_mongodb.odm.default_document_manager
```

For more advanced setups please see [DoctrineMongoDBBundle Configuration](http://symfony.com/doc/current/bundles/DoctrineMongoDBBundle/config.html)


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

