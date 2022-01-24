<?php declare(strict_types=1);
namespace SpareParts\Pillar\Test\Unit;

use PHPUnit\Framework\TestCase;
use SpareParts\Pillar\Test\Fixtures\GridProduct;

class EntityTest extends TestCase
{

	/** @test */
	public function canGetChangedProperties(): void
	{
		$entity = new GridProduct();
		$values = $entity->getChangedProperties(['id', 'name']);
		$this->assertEquals([], $values);

		$entity->setName('lorem ipsum');
		$values = $entity->getChangedProperties(['id', 'name']);
		$this->assertEquals(['name'], $values);
	}

}