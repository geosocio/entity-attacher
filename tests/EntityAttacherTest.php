<?php

namespace GeoSocio\Tests\EntityAttacher;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
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
}
