<?php declare(strict_types=1);
namespace SpareParts\Pillar\Mapper\Dibi;

interface IEntityMapping
{
	/**
	 * @return string
	 */
	public function getEntityClassName(): string;

	/**
	 * @return TableInfo[]
	 */
	public function getTables();

	/**
	 * @param string $tableIdentifier
	 * @return ColumnInfo[]
	 */
	public function getColumnsForTable($tableIdentifier): array;

	public function isVirtualEntity(): bool;
}
