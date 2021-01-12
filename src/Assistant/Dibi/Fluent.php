<?php declare(strict_types=1);
namespace SpareParts\Pillar\Assistant\Dibi;

use SpareParts\Enum\Converter\MapConverter;
use SpareParts\Pillar\Assistant\Dibi\Sorting\ISorting;
use SpareParts\Pillar\Assistant\Dibi\Sorting\SortingDirectionEnum;
use SpareParts\Pillar\Mapper\Dibi\ColumnInfo;
use SpareParts\Pillar\Mapper\Dibi\IEntityMapping;
use SpareParts\Pillar\Mapper\Dibi\TableInfo;

/**
 * @method Fluent select(...$field)
 */
class Fluent extends \Dibi\Fluent
{
	/**
	 * @var IEntityMapping
	 */
	protected $entityMapping;

	/**
	 * @var IEntityFactory
	 */
	protected $entityFactory;

	public function __construct(\Dibi\Connection $connection, IEntityMapping $entityMapping, IEntityFactory $entityFactory = null)
	{
		parent::__construct($connection);
		if ($entityFactory) {
			$this->setupResult('setRowFactory', function (array $data) use ($entityFactory, $entityMapping) {
				return $entityFactory->createEntity($entityMapping->getEntityClassName(), $data);
			});
		}

		$this->entityMapping = $entityMapping;
		$this->entityFactory = $entityFactory;
	}

	/**
	 * @return $this
	 */
	public function selectEntityProperties()
	{
		$propertyList = [];
		foreach ($this->entityMapping->getTables() as $table) {
			foreach ($this->entityMapping->getColumnsForTable($table->getIdentifier()) as $column) {
				// each property may be mapped to multiple columns, we are using mapping to the FIRST ACTIVE table and ignoring the rest
				if (isset($propertyList[$column->getPropertyName()])) {
					continue;
				}
				// do not select columns marked as "deprecated"
				if ($column->isDeprecated()) {
					continue;
				}


				$propertyList[$column->getPropertyName()] = true;

				if ($column->getCustomSelectSql()) {
					$this->select($column->getCustomSelectSql())->as($column->getPropertyName());
				} else {
					$this->select('%n', sprintf(
						'%s.%s',
						$column->getTableInfo()->getIdentifier(),
						$column->getColumnName()
					))->as($column->getPropertyName());
				}
			}
		}
		return $this;
	}

	/**
	 * @param string[] $propertyList If present, Pillar will try to include only those tables that are needed to select these properties. This might not work 100% of the times, mostly if there is a `silent mid-table` in the joins. In this case use @see $additionalTableList
	 * @param string[] $additionalTableList If present, those tables will be used instead of (default) all tables.
	 *
	 * @return $this
	 */
	public function fromEntityDataSources(array $propertyList = null, array $additionalTableList = null)
	{
		$tables = $this->entityMapping->getTables();

		$primaryTable = array_shift($tables);

		$propertyTables = [];
		if ($propertyList !== null) {
			$propertyTables = $this->preparePropertyTables($propertyList);
		}

		$additionalTables = [];
		if ($additionalTableList !== null) {
			$additionalTables = array_filter($tables, function (TableInfo $tableInfo) use ($additionalTableList) {
				return in_array($tableInfo->getIdentifier(), $additionalTableList);
			});
		}

		$innerJoinTables = array_filter($tables, function (TableInfo $tableInfo) {
			return (strtolower(substr($tableInfo->getSqlJoinCode(), 0, 5)) === 'inner');
		});

		if ($propertyList || $additionalTableList) {
			// in case I wish to restrict the result
			$tables = array_unique(array_merge($innerJoinTables, $propertyTables, $additionalTables), SORT_REGULAR);
		}

		if ($primaryTable->getSqlJoinCode()) {
			$fromCodeParts = preg_split('/\\s+/', $primaryTable->getSqlJoinCode(), 2);
			if (count($fromCodeParts) == 2) {
				$this->__call($fromCodeParts[0], [$fromCodeParts[1]]);
			} else {
				$this->__call('FROM', [$primaryTable->getSqlJoinCode()]);
			}
		} else {
			$this->from(sprintf(
				'`%s` AS `%s`',
				$primaryTable->getName(),
				$primaryTable->getIdentifier()
			));
		}

		foreach ($tables as $table) {
			$this->__call('', [$table->getSqlJoinCode()]);
		}
		return $this;
	}

	/**
	 * @param string[] $propertyList
	 * @return TableInfo[]
	 */
	private function preparePropertyTables(array $propertyList): array
	{
		// tables that should not be important to select correct row (ie. left joins)
		/** @var TableInfo[] $optionalTables */
		$optionalTables = array_filter($this->entityMapping->getTables(), function (TableInfo $tableInfo) {
			return (strtolower(substr($tableInfo->getSqlJoinCode(), 0, 4)) === 'left');
		});

		$propertyTables = [];
		// find out which of those tables are important for the properties
		foreach ($optionalTables as $tableInfo) {
			$propertyInfoList = $this->entityMapping->getColumnsForTable($tableInfo->getIdentifier());

			$tablePropertyNames = array_map(function (ColumnInfo $columnInfo) {
				return $columnInfo->getPropertyName();
			}, $propertyInfoList);

			if (count(array_intersect($tablePropertyNames, $propertyList))) {
				$propertyTables[] = $tableInfo;
			}
		}
		return $propertyTables;
	}

	/**
	 * @param ISorting[] $sortingList
	 * @return $this
	 * @throws UnknownPropertyException
	 * @throws \SpareParts\Enum\Converter\UnableToConvertException
	 */
	public function applySorting(array $sortingList): self
	{
		if (count($sortingList) === 0) {
			// don't try to apply empty $sortingList
			return $this;
		}

		/** @var ColumnInfo[] $sortableProperties */
		$sortableProperties = [];
		foreach ($this->entityMapping->getTables() as $tableInfo) {
			foreach ($this->entityMapping->getColumnsForTable($tableInfo->getIdentifier()) as $columnInfo) {
				if (isset($sortableProperties[$columnInfo->getPropertyName()])) {
					continue;
				}
				$sortableProperties[$columnInfo->getPropertyName()] = $columnInfo;
			}
		}
		$directionMap = new MapConverter([
			'ASC' => SortingDirectionEnum::ASCENDING(),
			'DESC' => SortingDirectionEnum::DESCENDING(),
		]);

		foreach ($sortingList as $sorting) {
			if (!isset($sortableProperties[$sorting->getProperty()])) {
				throw new UnknownPropertyException(sprintf('Unable to map property: `%s` to entity: `%s`, please check whether the provided property name is correct.', $sorting->getProperty(), $this->entityMapping->getEntityClassName()));
			}
			$columnInfo = $sortableProperties[$sorting->getProperty()];
			$this->orderBy(
				$sorting->getDibiRepresentation(), sprintf(
					'%s.%s',
					$columnInfo->getTableInfo()->getIdentifier(),
					$columnInfo->getColumnName()
				),
				$directionMap->fromEnum($sorting->getDirection())
			);
		}
		return $this;
	}
}
