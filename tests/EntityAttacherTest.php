<?php

namespace GeoSocio\Tests\EntityAttacher;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Common\Annotations\Reader;
use GeoSocio\EntityAttacher\EntityAttacher;
use GeoSocio\EntityAttacher\Annotation\Attach;
use PHPUnit\Framework\TestCase;

class EntityAttacherTest extends TestCase
{

    public function testAttach()
    {
        $metadata = $this->getMockBuilder(ClassMetadataInfo::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn([]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($metadata);

        $reader = $this->createMock(Reader::class);

        $entityAttacher = new EntityAttacher($em, $reader);

        $unattached = new \stdClass;
        $unattached->id = 123;
        $attached = $entityAttacher->attach($unattached);

        $this->assertEquals($unattached->id, $attached->id);
    }

    public function testAttachArgumentFailure()
    {
        $this->expectException(\InvalidArgumentException::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $reader = $this->createMock(Reader::class);


        $entityAttacher = new EntityAttacher($em, $reader);
        $entityAttacher->attach(null);
    }

    public function testAttachFound()
    {
        $id = 123;
        $ids = [
            'id' => 123,
        ];

        $unattached = new \stdClass;
        $unattached->id = $id;

        $existing = new \stdClass;
        $existing->id = $id;

        $metadata = $this->getMockBuilder(ClassMetadataInfo::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->once())
            ->method('getIdentifierValues')
            ->willReturn($ids);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($metadata);
        $em->expects($this->once())
            ->method('find')
            ->with(\stdClass::class, $ids)
            ->willReturn($existing);

        $reader = $this->createMock(Reader::class);


        $entityAttacher = new EntityAttacher($em, $reader);

        $attached = $entityAttacher->attach($unattached);

        $this->assertSame($existing, $attached);
    }

    public function testAttachFindException()
    {
        $id = 123;
        $ids = [
            'id' => 123,
        ];

        $unattached = new \stdClass;
        $unattached->id = $id;

        $metadata = $this->getMockBuilder(ClassMetadataInfo::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->once())
            ->method('getIdentifierValues')
            ->willReturn($ids);
        $metadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn([]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($metadata);
        $em->expects($this->once())
            ->method('find')
            ->willThrowException(new ORMInvalidArgumentException());

        $reader = $this->createMock(Reader::class);

        $entityAttacher = new EntityAttacher($em, $reader);

        $attached = $entityAttacher->attach($unattached);

        $this->assertEquals($unattached->id, $attached->id);
    }

    public function testAttachUnattachedRelationship()
    {
        $related = new \stdClass();
        $related->id = 321;

        $unattached = new \stdClass();
        $unattached->id = 123;
        $unattached->related = $related;

        $property = $this->getMockBuilder(\ReflectionProperty::class)
            ->disableOriginalConstructor()
            ->getMock();

        $metadata = $this->getMockBuilder(ClassMetadataInfo::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn([
                [
                    'fieldName' => 'related',
                ],
            ]);
        $metadata->expects($this->once())
            ->method('getReflectionProperty')
            ->with('related')
            ->willReturn($property);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($metadata);

        $reader = $this->createMock(Reader::class);
        $reader->expects($this->once())
            ->method('getPropertyAnnotations')
            ->with($property)
            ->willReturn([]);

        $entityAttacher = new EntityAttacher($em, $reader);

        $attached = $entityAttacher->attach($unattached);

        $this->assertEquals($unattached->id, $attached->id);
        $this->assertEquals($unattached->related->id, $attached->related->id);
    }

    public function testAttachEmptyRelationship()
    {
        $related = new \stdClass();
        $related->id = 321;

        $unattached = new \stdClass();
        $unattached->id = 123;
        $unattached->related = $related;

        $property = $this->getMockBuilder(\ReflectionProperty::class)
            ->disableOriginalConstructor()
            ->getMock();

        $metadata = $this->getMockBuilder(ClassMetadataInfo::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn([
                [
                    'fieldName' => 'related',
                ],
            ]);
        $metadata->expects($this->once())
            ->method('getReflectionProperty')
            ->with('related')
            ->willReturn($property);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($metadata);

        $attach = $this->createMock(Attach::class);

        $reader = $this->createMock(Reader::class);
        $reader->expects($this->once())
            ->method('getPropertyAnnotations')
            ->with($property)
            ->willReturn([
                $attach,
            ]);

        $entityAttacher = new EntityAttacher($em, $reader);

        $attached = $entityAttacher->attach($unattached);

        $this->assertEquals($unattached->id, $attached->id);
        $this->assertEquals($unattached->related->id, $attached->related->id);
    }

    public function testAttachEmptyCollectionRelationship()
    {
        $related = new \stdClass();
        $related->id = 321;

        $unattached = new \stdClass();
        $unattached->id = 123;
        $unattached->related = $related;

        $property = $this->getMockBuilder(\ReflectionProperty::class)
            ->disableOriginalConstructor()
            ->getMock();

        $collection = $this->createMock(Collection::class);
        $collection->expects($this->once())
            ->method('isEmpty')
            ->willReturn(true);

        $metadata = $this->getMockBuilder(ClassMetadataInfo::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn([
                [
                    'fieldName' => 'related',
                ],
            ]);
        $metadata->expects($this->once())
            ->method('getReflectionProperty')
            ->with('related')
            ->willReturn($property);
        $metadata->expects($this->once())
            ->method('getFieldValue')
            ->with($unattached, 'related')
            ->willReturn($collection);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($metadata);

        $attach = $this->createMock(Attach::class);

        $reader = $this->createMock(Reader::class);
        $reader->expects($this->once())
            ->method('getPropertyAnnotations')
            ->with($property)
            ->willReturn([
                $attach,
            ]);

        $entityAttacher = new EntityAttacher($em, $reader);

        $attached = $entityAttacher->attach($unattached);

        $this->assertEquals($unattached->id, $attached->id);
        $this->assertEquals($unattached->related->id, $attached->related->id);
    }

    public function testAttacRelationship()
    {
        $related = new \stdClass();
        $related->id = 321;

        $unattached = new \stdClass();
        $unattached->id = 123;
        $unattached->related = $related;

        $property = $this->getMockBuilder(\ReflectionProperty::class)
            ->disableOriginalConstructor()
            ->getMock();

        $metadata = $this->getMockBuilder(ClassMetadataInfo::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->exactly(2))
            ->method('getIdentifierValues')
            ->willReturnOnConsecutiveCalls(
                [
                    'id' => 123,
                ],
                [
                    'id' => 321,
                ]
            );
        $metadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn([
                [
                    'fieldName' => 'related',
                    'targetEntity' => \stdClass::class,
                ],
            ]);
        $metadata->expects($this->once())
            ->method('getReflectionProperty')
            ->with('related')
            ->willReturn($property);
        $metadata->expects($this->exactly(2))
            ->method('getFieldValue')
            ->with($unattached, 'related')
            ->willReturn($related);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->exactly(2))
            ->method('getClassMetadata')
            ->with(\stdClass::class)
            ->willReturn($metadata);
        $em->expects($this->exactly(2))
            ->method('find')
            ->withConsecutive(
                [
                    \stdClass::class,
                    [
                        'id' => 123,
                    ]
                ],
                [
                    \stdClass::class,
                    [
                        'id' => 321,
                    ]
                ]
            )
            ->willReturnOnConsecutiveCalls(null, $related);

        $attach = $this->createMock(Attach::class);

        $reader = $this->createMock(Reader::class);
        $reader->expects($this->once())
            ->method('getPropertyAnnotations')
            ->with($property)
            ->willReturn([
                $attach,
            ]);

        $entityAttacher = new EntityAttacher($em, $reader);

        $attached = $entityAttacher->attach($unattached);

        $this->assertEquals($unattached->id, $attached->id);
        $this->assertEquals($unattached->related->id, $attached->related->id);
    }

    public function testAttacRelationshipRecursive()
    {
        $child = new \stdClass();
        $child->id = 'abc';

        $related = new \stdClass();
        $related->id = 321;
        $related->child = $child;

        $unattached = new \stdClass();
        $unattached->id = 123;
        $unattached->related = $related;

        $property = $this->getMockBuilder(\ReflectionProperty::class)
            ->disableOriginalConstructor()
            ->getMock();

        $metadata = $this->getMockBuilder(ClassMetadataInfo::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->exactly(3))
            ->method('getIdentifierValues')
            ->willReturnOnConsecutiveCalls(
                [
                    'id' => 123,
                ],
                [
                    'id' => 321,
                ],
                [
                    'id' => 'abc',
                ]
            );
        $metadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn([
                [
                    'fieldName' => 'related',
                    'targetEntity' => \stdClass::class,
                ],
            ]);
        $metadata->expects($this->once())
            ->method('getReflectionProperty')
            ->with('related')
            ->willReturn($property);
        $metadata->expects($this->exactly(2))
            ->method('getFieldValue')
            ->with($unattached, 'related')
            ->willReturn($related);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->exactly(3))
            ->method('getClassMetadata')
            ->with(\stdClass::class)
            ->willReturn($metadata);
        $em->expects($this->exactly(3))
            ->method('find')
            ->withConsecutive(
                [
                    \stdClass::class,
                    [
                        'id' => 123,
                    ]
                ],
                [
                    \stdClass::class,
                    [
                        'id' => 321,
                    ]
                ],
                [
                    \stdClass::class,
                    [
                        'id' => 'abc',
                    ]
                ]
            )
            ->willReturnOnConsecutiveCalls(null, null, $child);

        $attach = $this->createMock(Attach::class);

        $reader = $this->createMock(Reader::class);
        $reader->expects($this->once())
            ->method('getPropertyAnnotations')
            ->with($property)
            ->willReturn([
                $attach,
            ]);

        $entityAttacher = new EntityAttacher($em, $reader);

        $attached = $entityAttacher->attach($unattached);

        $this->assertEquals($unattached->id, $attached->id);
        $this->assertEquals($unattached->related->id, $attached->related->id);
        $this->assertEquals($unattached->related->child->id, $attached->related->child->id);
    }

    public function testAttacCollectionRelationship()
    {

        $related = new \stdClass();
        $related->id = 321;

        $collection = new ArrayCollection([
            $related,
        ]);

        $unattached = new \stdClass();
        $unattached->id = 123;
        $unattached->related = $collection;

        $property = $this->getMockBuilder(\ReflectionProperty::class)
            ->disableOriginalConstructor()
            ->getMock();

        $metadata = $this->getMockBuilder(ClassMetadataInfo::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->exactly(2))
            ->method('getIdentifierValues')
            ->willReturnOnConsecutiveCalls(
                [
                    'id' => 123,
                ],
                [
                    'id' => 321,
                ]
            );
        $metadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn([
                [
                    'fieldName' => 'related',
                    'targetEntity' => \stdClass::class,
                ],
            ]);
        $metadata->expects($this->once())
            ->method('getReflectionProperty')
            ->with('related')
            ->willReturn($property);
        $metadata->expects($this->exactly(2))
            ->method('getFieldValue')
            ->with($unattached, 'related')
            ->willReturn($collection);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->exactly(2))
            ->method('getClassMetadata')
            ->with(\stdClass::class)
            ->willReturn($metadata);
        $em->expects($this->exactly(2))
            ->method('find')
            ->withConsecutive(
                [
                    \stdClass::class,
                    [
                        'id' => 123,
                    ]
                ],
                [
                    \stdClass::class,
                    [
                        'id' => 321,
                    ]
                ]
            )
            ->willReturnOnConsecutiveCalls(null, $related);

        $attach = $this->createMock(Attach::class);

        $reader = $this->createMock(Reader::class);
        $reader->expects($this->once())
            ->method('getPropertyAnnotations')
            ->with($property)
            ->willReturn([
                $attach,
            ]);

        $entityAttacher = new EntityAttacher($em, $reader);

        $attached = $entityAttacher->attach($unattached);

        $this->assertEquals($unattached->id, $attached->id);
        $this->assertEquals($unattached->related->first()->id, $attached->related->first()->id);
    }

    public function testAttacCollectionRecursiveRelationship()
    {

        $child = new \stdClass();
        $child->id = 'abc';

        $related = new \stdClass();
        $related->id = 321;
        $related->child = $child;

        $collection = new ArrayCollection([
            $related,
        ]);

        $unattached = new \stdClass();
        $unattached->id = 123;
        $unattached->related = $collection;

        $property = $this->getMockBuilder(\ReflectionProperty::class)
            ->disableOriginalConstructor()
            ->getMock();

        $metadata = $this->getMockBuilder(ClassMetadataInfo::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->exactly(3))
            ->method('getIdentifierValues')
            ->willReturnOnConsecutiveCalls(
                [
                    'id' => 123,
                ],
                [
                    'id' => 321,
                ],
                [
                    'id' => 'abc',
                ]
            );
        $metadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn([
                [
                    'fieldName' => 'related',
                    'targetEntity' => \stdClass::class,
                ],
            ]);
        $metadata->expects($this->once())
            ->method('getReflectionProperty')
            ->with('related')
            ->willReturn($property);
        $metadata->expects($this->exactly(2))
            ->method('getFieldValue')
            ->with($unattached, 'related')
            ->willReturn($collection);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->exactly(3))
            ->method('getClassMetadata')
            ->with(\stdClass::class)
            ->willReturn($metadata);
        $em->expects($this->exactly(3))
            ->method('find')
            ->withConsecutive(
                [
                    \stdClass::class,
                    [
                        'id' => 123,
                    ]
                ],
                [
                    \stdClass::class,
                    [
                        'id' => 321,
                    ]
                ],
                [
                    \stdClass::class,
                    [
                        'id' => 'abc',
                    ]
                ]
            )
            ->willReturnOnConsecutiveCalls(null, null, $child);

        $attach = $this->createMock(Attach::class);

        $reader = $this->createMock(Reader::class);
        $reader->expects($this->once())
            ->method('getPropertyAnnotations')
            ->with($property)
            ->willReturn([
                $attach,
            ]);

        $entityAttacher = new EntityAttacher($em, $reader);

        $attached = $entityAttacher->attach($unattached);

        $this->assertEquals($unattached->id, $attached->id);
        $this->assertEquals($unattached->related->first()->id, $attached->related->first()->id);
        $this->assertEquals($unattached->related->first()->child->id, $attached->related->first()->child->id);
    }
}
