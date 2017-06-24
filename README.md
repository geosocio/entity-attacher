# Entity Attacher [![Build Status](https://travis-ci.org/geosocio/entity-attacher.svg?branch=develop)](https://travis-ci.org/geosocio/entity-attacher) [![Coverage Status](https://coveralls.io/repos/github/geosocio/entity-attacher/badge.svg)](https://coveralls.io/github/geosocio/entity-attacher)
Provides a method to attach _related_ entities to the current _unnatached_
entity.This library is the product of a missing API that was found in
[doctrine/doctrine2#6459](https://github.com/doctrine/doctrine2/issues/6459). If
you want to create a new entity with a lot of existing related entities, you
would have to manually go through each relation and attach the related entites.

This can be tedious for entities that have a large number of relationships.

## Configuration
Add something like this to your service configuration:
```yaml
app.entity_attacher:
        class: GeoSocio\EntityAttacher\EntityAttacher
        arguments:
            - '@doctrine.orm.entity_manager'
            - '@annotations.reader'
```

You may also need to add the `GeoSocio\EntityAttacher\Annotation\Attach`
annotation to your annotation reader.

## Usage

Add the `@Attach` annotation to to the relationships that _should_ be attached.

```php
/**
 * @ORM\Entity()
 * @ORM\Table(name="post")
 */
class Post
{

    /**
     * @ORM\ManyToOne(targetEntity="GeoSocio\Core\Entity\User\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="user_id")
     * @Attach()
     */
    private $user;
}
```

Then when a new `Post` is created, you can attach the entity:
```php
$post = $this->attacher->attach($post);
```
Doing this will retrieve the `$user` from the database and prevent the `A new
entity was found through the relationship` error. If you still receive that
error, it means the `$user` was not found (and thus, should be persisted).
