<?php declare(strict_types=1);
namespace SpareParts\Pillar\Entity;

interface IEntity
{
	/**
	 * @param string[] $properties List of concerned properties
	 *
	 * @return string[]
	 */
	public function getChangedProperties(array $properties): array;
}
