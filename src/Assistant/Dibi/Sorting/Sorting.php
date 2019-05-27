<?php
namespace SpareParts\Pillar\Assistant\Dibi\Sorting;

class Sorting implements ISorting
{
	/**
	 * @var string
	 */
	private $property;

	/**
	 * @var SortingDirectionEnum
	 */
	private $direction;

	/**
	 * @param string $property
	 * @param SortingDirectionEnum $direction
	 */
	public function __construct($property, SortingDirectionEnum $direction)
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
