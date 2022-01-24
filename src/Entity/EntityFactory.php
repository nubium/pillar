<?php declare(strict_types=1);
namespace SpareParts\Pillar\Entity;

use SpareParts\Pillar\Assistant\Dibi\IEntityFactory;
use SpareParts\Pillar\Mapper\IMapper;

class EntityFactory implements IEntityFactory
{
	/**
	 * @param class-string<IEntity> $entityClassName
	 * @param mixed[] $data
	 */
	public function createEntity(string $entityClassName, array $data): IEntity
	{
		// Try to fix strange Dibi datetime behaviour by using standard (and immutable!) datetime class
		$data = array_map(function($column) {
			return ($column instanceof \DateTime) ? \DateTimeImmutable::createFromMutable($column) : $column;
		}, $data);

		$created = new $entityClassName($data);
		assert($created instanceof IEntity);

		return $created;
	}
}
