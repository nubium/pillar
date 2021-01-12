<?php declare(strict_types=1);
namespace SpareParts\Pillar\Adapter\Dibi;

use SpareParts\Pillar\Assistant\Dibi\IConnectionProvider;

class DibiConnectionProvider implements IConnectionProvider
{
	private \Dibi\Connection $connection;

	public function __construct(\Dibi\Connection $connection)
	{
		$this->connection = $connection;
	}

	public function getConnection(): \Dibi\Connection
	{
		return $this->connection;
	}
}
