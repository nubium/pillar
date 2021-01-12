<?php declare(strict_types=1);
namespace SpareParts\Pillar\Assistant\Dibi;


interface IConnectionProvider
{
	public function getConnection(): \Dibi\Connection;
}
