<?php

namespace GeoSocio\Tests\EntityAttacher;

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

    public function testAttachRelationship()
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
}
