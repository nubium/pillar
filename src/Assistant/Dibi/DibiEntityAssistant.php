<?php declare(strict_types=1);
namespace SpareParts\Pillar\Assistant\Dibi;

use Dibi\Result;
use SpareParts\Pillar\Entity\IEntity;
use SpareParts\Pillar\Mapper\Dibi\ColumnInfo;
use SpareParts\Pillar\Mapper\Dibi\IEntityMapping;
use SpareParts\Pillar\Mapper\Dibi\TableInfo;
use SpareParts\Pillar\Mapper\EntityMappingException;
use SpareParts\Pillar\Mapper\IMapper;

class DibiEntityAssistant
{
	protected IMapper $mapper;
	protected IConnectionProvider $connectionProvider;
	protected IEntityFactory $entityFactory;

	/**
	 * @var array<string, string[]>
	 */
	private array $entityPropertiesCache = [];


	public function __construct(IMapper $mapper, IEntityFactory $entityFactory, IConnectionProvider $connectionProvider)
	{
		$this->mapper = $mapper;
		$this->connectionProvider = $connectionProvider;
		$this->entityFactory = $entityFactory;
	}

	/**
	 * @param string|IEntity $entityClassOrInstance
	 * @param bool $returnEntities If set to false, does not try to format fetched data using EntityFactory
	 * @return Fluent
	 * @throws \SpareParts\Pillar\Mapper\EntityMappingException
	 */
	public function fluent($entityClassOrInstance, bool $returnEntities = true): Fluent
	{
		$mapping = $this->mapper->getEntityMapping($entityClassOrInstance);
		if ($mapping->isVirtualEntity()) {
			throw new UnableToSaveException('Virtual property cannot be persisted!');
		}

		$fluent = new Fluent(
			$this->connectionProvider->getConnection(),
			$mapping,
			$returnEntities ? $this->entityFactory : null
		);
		return $fluent;
	}


	/**
	 * This method returns "blank" fluent, does not force you to use IEntity resulting class (i.e. fetches plain array)
	 *
	 * @param string|IEntity $entityClassOrInstance
	 * @return Fluent
	 * @throws \SpareParts\Pillar\Mapper\EntityMappingException
	 */
	public function fluentForAggregateCalculations($entityClassOrInstance): Fluent
	{
		$fluent = new Fluent(
			$this->connectionProvider->getConnection(),
			$this->mapper->getEntityMapping($entityClassOrInstance)
		);
		return $fluent;
	}

	/**
	 * Update entity database representation to current entity values.
	 *
	 * @param IEntity $entity
	 * @param array<mixed>|null $tables
	 *
	 * @return int Number of affected rows. Can be higher than 1, if multiple tables were changed!
	 *
	 * @throws EntityMappingException
	 * @throws UnableToSaveException
	 * @throws \Dibi\Exception
	 * @throws \InvalidArgumentException
	 */
	public function update(IEntity $entity, array $tables = null): int
	{
		$mapping = $this->mapper->getEntityMapping($entity);
		if ($mapping->isVirtualEntity()) {
			throw new UnableToSaveException('Virtual property cannot be persisted!');
		}
		$tableInfos = $this->sanitizeTableInfos($entity, $tables);

		// we iterate over tables and update each of them that has changed
		$affectedRows = 0;
		foreach ($tableInfos as $tableInfo) {
			$columnValuesToStore = $this->getValuesToUpdate($entity, $mapping, $tableInfo);

			// nothing to update in this table
			if (!count($columnValuesToStore)) {
				continue;
			}

			$fluent = $this->connectionProvider->getConnection()
				->update($tableInfo->getName(), $columnValuesToStore)
				->limit(1);

			$columnInfos = $this->getColumnInfosForTable($mapping, $tableInfo);
			$fluent = $this->addPKToFluent($entity, $tableInfo->getIdentifier(), $columnInfos, $fluent);
			$affectedRows += $fluent->execute(\dibi::AFFECTED_ROWS);
		}
		return (int) $affectedRows;
	}

	/**
	 * @param IEntity $entity
	 * @param string $tableName
	 *
	 * @return int|null|string Primary key in case the new record was inserted, null otherwise
	 *
	 * @throws UnableToSaveException
	 * @throws \InvalidArgumentException
	 * @throws \Dibi\Exception
	 */
	public function insert(IEntity $entity, $tableName)
	{
		$mapping = $this->mapper->getEntityMapping($entity);
		if ($mapping->isVirtualEntity()) {
			throw new UnableToSaveException('Virtual property cannot be persisted!');
		}
		$tableInfo = $this->getTableInfoByTableName($entity, $mapping, $tableName);
		$columnValuesToStore = $this->getValuesToInsert($entity, $mapping, $tableInfo);

		if (!$columnValuesToStore) {
			return null;
		}

		$fluent = $this->connectionProvider->getConnection()
			->insert($tableInfo->getName(), $columnValuesToStore);

		try {
			/** @var int $result */
			$result = $fluent->execute(\dibi::IDENTIFIER);
			return $result;
		} catch (\Dibi\Exception $exception) {
			// let's assume this is because the PK wasn't AUTO_INCREMENT...
			// *waiting for pull request with better way to do this :)*
			if ($exception->getMessage() !== 'Cannot retrieve last generated ID.') {
				throw $exception;
			}
		}
		return null;
	}

	/**
	 * !!! This method "spends" values in the autoincrement columns (even when not inserting)), so use it wisely.
	 * This is caused by how mysql treats ON DUPLICATE KEY INSERT clause commands.
	 *
	 * @param IEntity $entity
	 * @param string $tableName
	 *
	 * @return int|string|null
	 * @throws \Dibi\Exception
	 * @internal param \string[] $tables
	 */
	public function insertOrUpdate(IEntity $entity, string $tableName)
	{
		$mapping = $this->mapper->getEntityMapping($entity);
		if ($mapping->isVirtualEntity()) {
			throw new UnableToSaveException('Virtual property cannot be persisted!');
		}
		$tableInfo = $this->getTableInfoByTableName($entity, $mapping, $tableName);
		$columnValuesToStore = $this->getValuesToInsert($entity, $mapping, $tableInfo);
		$columnValuesToUpdate = $this->getValuesToUpdate($entity, $mapping, $tableInfo);

		if (!$columnValuesToStore) {
			return null;
		}

		$fluent = $this->connectionProvider->getConnection()
			->insert($tableInfo->getName(), $columnValuesToStore);

		if ($columnValuesToUpdate) {
			$fluent = $fluent->onDuplicateKeyUpdate('%a', $columnValuesToUpdate);
		}

		try {
			return $fluent->execute(\dibi::IDENTIFIER);
		} catch (\Dibi\Exception $exception) {
			// let's assume this is because the PK wasn't AUTO_INCREMENT...
			// *waiting for pull request with better way to do this :)*
			if ($exception->getMessage() !== 'Cannot retrieve last generated ID.') {
				throw $exception;
			}
		}
		return null;
	}

	/**
	 * @throws EntityMappingException
	 * @throws UnableToSaveException
	 * @throws \Dibi\Exception
	 */
	public function delete(IEntity $entity, string $tableName): ?int
	{
		$mapping = $this->mapper->getEntityMapping($entity);

		if ($mapping->isVirtualEntity()) {
			throw new UnableToSaveException('Virtual property cannot be persisted!');
		}

		$tableInfo = $this->getTableInfoByTableName($entity, $mapping, $tableName);

		$fluent = $this->connectionProvider->getConnection()
			->delete($tableInfo->getName())
			->limit(1);

		// we need array key of ColumnInfo[] to be the column name for easy handling
		/** @var ColumnInfo[] $columnInfos */
		$columnInfos = $this->getColumnInfosForTable($mapping, $tableInfo);
		$fluent = $this->addPKToFluent($entity, $tableInfo->getName(), $columnInfos, $fluent);

		/** @var int $result */
		$result = $fluent->execute(\dibi::AFFECTED_ROWS);
		return $result;
	}

	/**
	 * @param IEntity $entity
	 * @param string $tableName
	 * @param ColumnInfo[] $columns
	 * @param \Dibi\Fluent $fluent
	 *
	 * @return \Dibi\Fluent
	 *
	 * @throws UnableToSaveException
	 */
	private function addPKToFluent(IEntity $entity, string $tableName, array $columns, \Dibi\Fluent $fluent): \Dibi\Fluent
	{
		/** @var ColumnInfo[] $pkColumns */
		$pkColumns = array_filter($columns, function (ColumnInfo $columnInfo) {
			return $columnInfo->isPrimaryKey();
		});
		if (!count($pkColumns)) {
			throw new UnableToSaveException(sprintf('No primary key exists for table %s of entity %s', $tableName,
				get_class($entity)));
		}
		// all columns marked as "primary" are considered to be a part of primary key
		foreach ($pkColumns as $pkColumnInfo) {
			$pkValue = $this->getEntityPropertyValue($entity, $pkColumnInfo);
			if (is_null($pkValue)) {
				throw new UnableToSaveException(sprintf('Entity: `%s` should have its table: `%s` saved, but primary key column\'s : `%s` value is empty (null),', get_class($entity), $tableName, $pkColumnInfo->getColumnName()));
			}

			$fluent->where(
				'%n = %i',
				$pkColumnInfo->getColumnName(),
				$pkValue
			);
		}
		return $fluent;
	}

	/**
	 * @return string[]
	 * @throws \SpareParts\Pillar\Mapper\EntityMappingException
	 */
	private function getEntityProperties(string $entityClassName): array
	{
		if (!isset($this->entityPropertiesCache[$entityClassName])) {
			$mapping = $this->mapper->getEntityMapping($entityClassName);
			$tables = $mapping->getTables();
			$properties = [];
			foreach ($tables as $tableInfo) {
				$columns = $mapping->getColumnsForTable($tableInfo->getIdentifier());
				foreach ($columns as $columnInfo) {
					$properties[] = $columnInfo->getPropertyName();
				}
			}
			$this->entityPropertiesCache[$entityClassName] = array_unique($properties);
		}
		return $this->entityPropertiesCache[$entityClassName];
	}


	/**
	 * @param IEntity $entity
	 * @param ColumnInfo[] $columnInfoList
	 * @return mixed[]
	 */
	protected function getEntityPropertyValuesMappedToColumns(IEntity $entity, array $columnInfoList)
	{
		$columnValues = [];
		foreach ($columnInfoList as $columnInfo) {
			if ($columnInfo->isDeprecated()) {
				continue;
			}
			$columnValues[$columnInfo->getColumnName()] = $this->getEntityPropertyValue($entity, $columnInfo);
		}
		return $columnValues;
	}

	/**
	 * @param IEntity $entity
	 * @param ColumnInfo $property
	 * @return mixed
	 */
	protected function getEntityPropertyValue(IEntity $entity, ColumnInfo $property)
	{
		$propName = $property->getPropertyName();
		$getterPk = \Closure::bind(function () use ($propName) {
			return $this->{$propName};
		}, $entity, get_class($entity));

		return $getterPk();
	}

	/**
	 * @param ColumnInfo[] $columnInfos
	 * @param string[] $changedProperties
	 * @return ColumnInfo[]
	 */
	private function prepareListToUpdate(array $columnInfos, array $changedProperties): array
	{
		$columnInfoListToUpdate = array_filter(
			$columnInfos,
			function (ColumnInfo $columnInfo) use ($changedProperties) {
				// never update a PK
				if ($columnInfo->isPrimaryKey()) {
					return false;
				}
				// if there is any `deprecated` column, ignore its value
				if ($columnInfo->isDeprecated()) {
					return false;
				}
				// update changed properties
				if (in_array($columnInfo->getPropertyName(), $changedProperties)) {
					return true;
				}

				return false;
			}
		);

		return $columnInfoListToUpdate;
	}

	/**
	 * @param IEntity $entity
	 * @param array<mixed>|null $tables
	 * @return TableInfo[]
	 * @throws EntityMappingException
	 */
	private function sanitizeTableInfos(IEntity $entity, array $tables = null): array
	{
		if ($tables !== null) {
			$tables = array_unique($tables);
		}

		$mapping = $this->mapper->getEntityMapping($entity);
		$tableInfos = $mapping->getTables();
		if ($tables === null) {
			return $tableInfos;
		}

		$tableInfos = array_filter(
			$tableInfos,
			fn (TableInfo $tableInfo) => in_array($tableInfo->getIdentifier(), $tables)
		);
		if (count($tableInfos) !== count ($tables)) {
			$foundNames = array_map(fn(TableInfo $tableInfo) => $tableInfo->getIdentifier(), $tableInfos);
			throw new UnableToSaveException(sprintf('Unable to save entity: `%s`, unknown tables `%s`',
				get_class($entity), implode(', ', array_diff($tables, $foundNames))));
		}

		return $tableInfos;
	}

	/**
	 * @param IEntityMapping $mapping
	 * @param TableInfo $tableInfo
	 * @return ColumnInfo[]
	 */
	private function getColumnInfosForTable(IEntityMapping $mapping, TableInfo $tableInfo): array
	{
		/** @var ColumnInfo[] $columnInfos */
		$columnInfos = [];
		foreach ($mapping->getColumnsForTable($tableInfo->getIdentifier()) as $columnInfo) {
			$columnInfos[ $columnInfo->getPropertyName() ] = $columnInfo;
		}

		return $columnInfos;
	}

	/**
	 * @param IEntity $entity
	 * @param IEntityMapping $entityMapping
	 * @param TableInfo $tableInfo
	 * @return mixed[]|null
	 */
	private function getValuesToInsert(
		IEntity $entity,
		IEntityMapping $entityMapping,
		TableInfo $tableInfo
	): ?array {
		// we need array key of ColumnInfo[] to be the column name for easy handling
		$columnInfos = $this->getColumnInfosForTable($entityMapping, $tableInfo);

		// entity will tell us which properties did change
		$changedProperties = $entity->getChangedProperties($this->getEntityProperties($entityMapping->getEntityClassName()));
		// ... then we can find DB mapping for those properties
		$changedColumnInfos = array_filter(
			$columnInfos,
			function (ColumnInfo $columnInfo) use ($changedProperties) {
				return in_array($columnInfo->getPropertyName(), $changedProperties);
			}
		);
		// now we know database columns and can prepare data for inserting into DB
		$columnValuesToStore = $this->getEntityPropertyValuesMappedToColumns($entity, $changedColumnInfos);

		// nothing to store in this table?
		if (!count($columnValuesToStore)) {
			return null;
		}

		// if there are any PK set by linking from another table (possible FK), we need to respect that values
		// if there are any `deprecated` columns, we need to insert their values
		foreach ($columnInfos as $columnInfo) {
			if (!$columnInfo->isPrimaryKey() && !$columnInfo->isDeprecated()) {
				continue;
			}
			$value = $this->getEntityPropertyValue($entity, $columnInfo);

			// we are not storing NULLs to PK...
			if (is_null($value)) {
				continue;
			}
			$columnValuesToStore[$columnInfo->getColumnName()] = $value;
		}

		return $columnValuesToStore;
	}

	/**
	 * @return mixed[]
	 */
	private function getValuesToUpdate(IEntity $entity, IEntityMapping $mapping, TableInfo $tableInfo): array
	{
		// we need array key of ColumnInfo[] to be the column name for easy handling
		$columnInfos = $this->getColumnInfosForTable($mapping, $tableInfo);
		$propertyList = array_keys($columnInfos);

		// properties that changed in this table
		$changedProperties = $entity->getChangedProperties($propertyList);

		// now that we have list of changed properties, we can make a list of DB columns that should be updated
		$columnInfoListToUpdate = $this->prepareListToUpdate($columnInfos, $changedProperties);

		// map property values to columns
		$columnValuesToStore = $this->getEntityPropertyValuesMappedToColumns($entity, $columnInfoListToUpdate);
		return $columnValuesToStore;
	}

	private function getTableInfoByTableName(IEntity $entity, IEntityMapping $mapping, string $tableName): TableInfo
	{
		$tableInfos = $mapping->getTables();

		$tableInfo = (function ($tableName) use ($tableInfos): ?TableInfo {
			foreach ($tableInfos as $tableInfo) {
				if ($tableInfo->getIdentifier() === $tableName) {
					return $tableInfo;
				}
			}
			return null;
		}) ($tableName);

		if ($tableInfo === null) {
			throw new UnableToSaveException(sprintf('Unable to save entity: `%s`, unknown table `%s`', get_class($entity), $tableName));
		}
		return $tableInfo;
	}
}
