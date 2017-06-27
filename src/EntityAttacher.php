<?php

namespace GeoSocio\EntityAttacher;

use Doctrine\ORM\ORMInvalidArgumentException;
use GeoSocio\EntityAttacher\Annotation\Attach;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;

class EntityAttacher implements EntityAttacherInterface
{

    protected $em;

    protected $reader;

    public function __construct(EntityManagerInterface $em, Reader $reader)
    {
        $this->em = $em;
        $this->reader = $reader;
    }

    public function attach($object)
    {
        if (!is_object($object)) {
            throw new \InvalidArgumentException('Can only attach objects');
        }

        $object = clone $object;

        $class = get_class($object);
        $metadata = $this->em->getClassMetadata($class);

        // Return the item if it's already in the database.
        if ($ids = $metadata->getIdentifierValues($object)) {
            try {
                $item = $this->em->find($class, $ids);
            } catch (ORMInvalidArgumentException $e) {
                $item = null;
            }

            if ($item) {
                return $item;
            }
        }

        $mappings = $metadata->getAssociationMappings();

        $associations = array_filter($mappings, function ($meta) use ($object, $metadata) {

            // Ensure the property is explicitly set to cascade the attach with
            // the GeoSocio\Core\Annotation\Attach annotation. It would be
            // better to use the cascade option, but an unknown cascade option
            // throws an exception.
            $annotations = $this->reader->getPropertyAnnotations($metadata->getReflectionProperty($meta['fieldName']));
            $annotations = array_filter($annotations, function ($annotation) {
                return $annotation instanceof Attach;
            });

            if (!count($annotations)) {
                return false;
            }

            $value = $metadata->getFieldValue($object, $meta['fieldName']);

            if (!$value) {
                return false;
            }

            if ($value instanceof Collection) {
                return !$value->isEmpty();
            }

            return true;
        });


        foreach ($associations as $data) {
            $meta = $this->em->getClassMetadata($data['targetEntity']);
            $value = $metadata->getFieldValue($object, $data['fieldName']);

            if ($value instanceof Collection) {
                $item = $value->map(function ($stub) use ($data, $meta) {
                    try {
                        $item = $this->em->find($data['targetEntity'], $meta->getIdentifierValues($stub));
                    } catch (ORMInvalidArgumentException $e) {
                        $item = null;
                    }

                    // If the item was not found in the database, recursively call this
                    // method.
                    if (!$item) {
                        return $this->attach($stub);
                    }

                    return $item;
                });
            } else {
                try {
                    $item = $this->em->find($data['targetEntity'], $meta->getIdentifierValues($value));
                } catch (ORMInvalidArgumentException $e) {
                    $item = null;
                }

                // If the item was not found in the database, recursively call this
                // method.
                if (!$item) {
                    $item = $this->attach($value);
                }
            }

            $metadata->setFieldValue($object, $data['fieldName'], $item);
        }

        return $object;
    }
}
