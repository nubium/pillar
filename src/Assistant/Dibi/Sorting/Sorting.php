<?php declare(strict_types=1);
namespace SpareParts\Pillar\Assistant\Dibi\Sorting;

class Sorting implements ISorting
{
	private string $property;

	private SortingDirectionEnum $direction;

	public function __construct(string $property, SortingDirectionEnum $direction)
	{
		$this->property = $property;
		$this->direction = $direction;
	}

	public function getProperty(): string
	{
		return $this->property;
	}

	public function getDirection(): SortingDirectionEnum
	{
		return $this->direction;
	}

	public function getDibiRepresentation(): string
	{
		return '%n';
	}
}
