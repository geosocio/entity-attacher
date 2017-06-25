<?php

namespace GeoSocio\Tests\EntityAttacher;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Common\Annotations\Reader;
use GeoSocio\EntityAttacher\EntityAttacher;
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
        $attached = $entityAttacher->attach($unattached);

        $this->assertEquals($unattached, $attached);
    }
}
