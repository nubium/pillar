<?php
namespace SpareParts\Pillar\Assistant\Dibi\Sorting;

interface ISorting
{
	public function getProperty(): string;

	public function getDirection(): SortingDirectionEnum;

	public function getDibiRepresentation(): string;
}
