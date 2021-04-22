<?php

namespace Jtl\Connector\Modified\Tests\Mapper;

use jtl\Connector\Core\Database\Mysql;
use jtl\Connector\Modified\Mapper\Image;
use PHPUnit\Framework\TestCase;

/**
 * Class ImageTest
 * @package Jtl\Connector\Modified\Tests\Mapper
 */
class ImageTest extends TestCase
{
    /**
     * @dataProvider generateImageNameDataProvider
     *
     * @param \jtl\Connector\Model\Image $jtlImage
     * @param int $numberOfDuplicates
     * @param string $expectedName
     * @throws \ReflectionException
     */
    public function testGenerateImageName(\jtl\Connector\Model\Image $jtlImage, int $numberOfDuplicates, string $expectedName)
    {
        $imageMapperReflection = new \ReflectionClass(Image::class);
        $method = $imageMapperReflection->getMethod('generateImageName');
        $method->setAccessible(true);

        $imageMapperMock = $this->createMock(Image::class);

        $dbMock = $this->createMock(Mysql::class);

        $duplicateResults = [];
        for ($i = 0; $i < $numberOfDuplicates; $i++) {
            $duplicateResults[] = ['1'];
        }
        $duplicateResults[] = [];

        $dbMock->method('query')->willReturnOnConsecutiveCalls(...$duplicateResults);

        $imageMapperMock->method('getDb')->willReturn($dbMock);

        $result = $method->invoke($imageMapperMock, $jtlImage);

        $this->assertEquals($expectedName, $result);
    }

    /**
     * @return array[]
     */
    public function generateImageNameDataProvider(): array
    {
        return [
            [
                (new \jtl\Connector\Model\Image())
                    ->setName('foo')
                    ->setFilename('path/to/file/name/123edsakj.jpg'),
                2,
                'foo-2.jpg'
            ],
            [
                (new \jtl\Connector\Model\Image())
                    ->setName('foo.png')
                    ->setFilename('path/to/file/name/bar.jpg'),
                1,
                'foo-png-1.jpg'
            ],
            [
                (new \jtl\Connector\Model\Image())
                    ->setFilename('path/to/file/name/random.jpg'),
                0,
                'random.jpg'
            ],
            [
                (new \jtl\Connector\Model\Image())
                    ->setFilename('path/to/file/name/random.png.gif'),
                0,
                'random-png.gif'
            ],
        ];
    }
}
