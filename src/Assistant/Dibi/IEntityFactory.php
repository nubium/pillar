<?php declare(strict_types=1);
namespace SpareParts\Pillar\Assistant\Dibi;

use SpareParts\Pillar\Entity\IEntity;

interface IEntityFactory
{
	/**
	 * @param string $entityClassName
	 * @param mixed[] $data
	 * @return IEntity
	 */
	public function createEntity(string $entityClassName, array $data): IEntity;
}
