<?php declare(strict_types=1);
namespace SpareParts\Pillar\Mapper\Dibi;

use SpareParts\Pillar\Mapper\Annotation\Table;

/**
 * @internal
 */
class EntityMapping implements IEntityMapping
{
	/** @var TableInfo[][] */
	private array $tableInfoList = [];

	/** @var ColumnInfo[] */
	private array $columnInfoList = [];

	private string $entityClassName;

	private bool $isVirtualEntity;

	/**
	 * @param string $entityClassName
	 * @param TableInfo[] $tableInfoList
	 * @param ColumnInfo[] $columnInfoList
	 * @param bool $isVirtualEntity
	 */
	public function __construct($entityClassName, array $tableInfoList, array $columnInfoList, $isVirtualEntity = false)
	{
		$this->columnInfoList = $columnInfoList;
		$this->entityClassName = $entityClassName;
		$this->isVirtualEntity = $isVirtualEntity;

		foreach ($tableInfoList as $tableInfo) {
			$this->tableInfoList[$tableInfo->getIdentifier()][] = $tableInfo;
		}
	}

	/**
	 * @return string
	 */
	public function getEntityClassName(): string
	{
		return $this->entityClassName;
	}

	/**
	 * This method returns all possible table definitions for this entity.
	 * There might be a lot of additional logic involved in deciding which tables will be used to construct final data query.
	 *
	 * @return TableInfo[]
	 */
	public function getTables(?string $tag = null): array
	{
		$resultList = [];

		foreach ($this->tableInfoList as $identifier => $possibleTables) {

			// TAG INTERSECTION
			$resultList[] = (function() use ($tag, $possibleTables): TableInfo {

				foreach ($possibleTables as $possibleTable) {
					// for no tag present, choose table with no tags or with `default` tag
					if ($tag === null) {
						if ($possibleTable->getTags() === null) {
							return $possibleTable;
						}
						if (in_array(Table::DEFAULT_TAG, $possibleTable->getTags())) {
							return $possibleTable;
						}
						continue;
					}
					// if tag is present, choose first table with that tag
					if ($possibleTable->getTags() !== null && in_array($tag, $possibleTable->getTags())) {
						return $possibleTable;
					}
				}
				// if no table could be chosen, just take first one
				return reset ($possibleTables);

			})();
		}
		return $resultList;
	}

	/**
	 * @param string $tableIdentifier
	 * @return ColumnInfo[]
	 */
	public function getColumnsForTable($tableIdentifier): array
	{
		return array_filter($this->columnInfoList, function (ColumnInfo $columnInfo) use ($tableIdentifier) {
			return ($columnInfo->getTableIdentifier() === $tableIdentifier);
		});
	}

	/**
	 * @return bool
	 */
	public  function isVirtualEntity(): bool {
		return $this->isVirtualEntity;
	}
}
