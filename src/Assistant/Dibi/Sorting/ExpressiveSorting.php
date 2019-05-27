<?php declare(strict_types=1);
namespace SpareParts\Pillar\Assistant\Dibi\Sorting;

class ExpressiveSorting implements ISorting
{
	/** @var string */
	private $property;

	/** @var SortingDirectionEnum */
	private $direction;

	/** @var string */
	private $dibiExpression;

	public function __construct(string $dibiExpression, string $property, SortingDirectionEnum $direction)
	{
		$this->property = $property;
		$this->direction = $direction;
		$this->dibiExpression = $dibiExpression;
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
		return $this->dibiExpression;
	}
}
