<?php declare(strict_types=1);
namespace SpareParts\Pillar\Test\Integration;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\DbUnit\DataSet\ArrayDataSet;
use PHPUnit\DbUnit\TestCaseTrait;
use SpareParts\Pillar\Adapter\Dibi\DibiConnectionProvider;
use SpareParts\Pillar\Assistant\Dibi\DibiEntityAssistant;
use SpareParts\Pillar\Entity\EntityFactory;
use SpareParts\Pillar\Mapper\Dibi\AnnotationMapper;
use SpareParts\Pillar\Test\Fixtures\GridProduct;

class DibiEntityAssistantFluentTest extends \PHPUnit\Framework\TestCase
{
	use TestCaseTrait {
		setUp as dbunit_setUp;
	}

	/** @var DibiEntityAssistant */
	protected $entityAssistant;

	/** @var \Dibi\Connection */
	protected $connection;

	public function setUp()
	{
		$this->dbunit_setUp();

		AnnotationRegistry::registerLoader("class_exists");

		$mapper = new AnnotationMapper(new AnnotationReader());
		$entityFactory = new EntityFactory();
		$this->connection = new \Dibi\Connection([
			'host' => '127.0.0.1',
			'username' => 'travis',
			'password' => '',
			'database' => 'testdb',
			'charset' => 'utf8',
			'driver' => 'mysqli',
		]);
		$connectionProvider = new DibiConnectionProvider($this->connection);

		$this->entityAssistant = new DibiEntityAssistant(
			$mapper,
			$entityFactory,
			$connectionProvider
		);
	}

	/**
	 * @test
	 */
	public function fluentCanLoadEntityById()
	{
		/** @var GridProduct $product */
		$product = $this->entityAssistant
			->fluent(GridProduct::class)
			->selectEntityProperties()
			->fromEntityDataSources()
			->where('`p`.`id` = %i', 25)
			->fetch();
        ;

//		$product->fetch();

		$this->assertEquals(25, $product->getId());
		$this->assertEquals(1, $product->getImageId());
		$this->assertEquals('amazing bedsheet', $product->getName());
		$this->assertEquals(12.5, $product->getPrice());
		$this->assertEquals('/path/to/image', $product->getImage());
	}

	/**
	 * @test
	 */
	public function fluentCanUseAggregateSelectAndOmitJoinTable()
	{
		$fluent = $this->entityAssistant
			->fluentForAggregateCalculations(GridProduct::class)
			->select('name')
			->fromEntityDataSources($propertyList = ['name']);

		$data = $fluent->fetchAll();

		$this->assertEquals('amazing bedsheet', $data[0]['name']);

		// this is questionable way to ensure `images` table is not present in the sql query.
		$this->assertEquals('SELECT `name` FROM `products` AS `p`', $fluent->__toString());
	}

	/**
	 * @test
	 */
	public function fluentCanUseAggregateSelectAndNotOmitJoinTable()
	{
		$fluent = $this->entityAssistant
			->fluentForAggregateCalculations(GridProduct::class)
			->select('path')
			->fromEntityDataSources($propertyList = ['image']);

		$data = $fluent->fetchAll();

		$this->assertEquals('/path/to/image', $data[0]['path']);

		// this is questionable way to ensure `images` table is really joined
		$this->assertEquals('SELECT `path` FROM `products` AS `p`  LEFT JOIN `images` `img` ON `img`.`id` = `p`.`image_id`', $fluent->__toString());
	}

	/**
	 * @test
	 */
	public function fluentCanUseAggregateSelect()
	{
		$fluent = $this->entityAssistant
			->fluentForAggregateCalculations(GridProduct::class)
			->select('path')
			->fromEntityDataSources();

		$data = $fluent->fetchAll();

		$this->assertEquals('/path/to/image', $data[0]['path']);
		// this is questionable way to ensure `images` table is really joined
		$this->assertEquals('SELECT `path` FROM `products` AS `p`  LEFT JOIN `images` `img` ON `img`.`id` = `p`.`image_id`', $fluent->__toString());
	}

    /**
     * @test
     */
    public function fluentCanUseTagToChangePrimarySelectTable()
    {
        $fluent = $this->entityAssistant
            ->fluent(GridProduct::class)
            ->selectEntityProperties()
            ->fromEntityDataSources(null, null, 'idx')
            ->where('`p`.`id` = %i', 25);

        /** @var GridProduct $product */
        $product = $fluent->fetch();
        $this->assertEquals(25, $product->getId());
        $this->assertEquals(1, $product->getImageId());
        $this->assertEquals('amazing bedsheet', $product->getName());
        $this->assertEquals(12.5, $product->getPrice());
        $this->assertEquals('/path/to/image', $product->getImage());

        $this->assertEquals('SELECT `p`.`id` AS `id` , `p`.`name` AS `name` , `p`.`image_id` AS `imageId` , `p`.`price` AS `price` , `img`.`path` AS `image` FROM `products` `p` USE INDEX (`idx_try_me_out`)  LEFT JOIN `images` `img` ON `img`.`id` = `p`.`image_id` WHERE `p`.`id` = 25', $fluent->__toString());
    }


    /**
     * @test
     */
    public function fluentCanUseTagToChangeJoinSelectTable()
    {
        $fluent = $this->entityAssistant
            ->fluent(GridProduct::class)
            ->selectEntityProperties()
            ->fromEntityDataSources(null, null, 'slightly_diff_join')
            ->where('`p`.`id` = %i', 25);

        /** @var GridProduct $product */
        $product = $fluent->fetch();
        $this->assertEquals(25, $product->getId());
        $this->assertEquals(1, $product->getImageId());
        $this->assertEquals('amazing bedsheet', $product->getName());
        $this->assertEquals(12.5, $product->getPrice());
        $this->assertEquals('/path/to/image', $product->getImage());

        $this->assertEquals('SELECT `p`.`id` AS `id` , `p`.`name` AS `name` , `p`.`image_id` AS `imageId` , `p`.`price` AS `price` , `img`.`path` AS `image` FROM `products` AS `p`  LEFT JOIN `images` `img` ON `p`.`image_id` = `img`.`id` WHERE `p`.`id` = 25', $fluent->__toString());
    }


	/**
	 * @throws \Dibi\Exception
	 * @test
	 */
	public function insertCanCreateNewRow()
	{
		$product = new GridProduct();
		$product->setImage('path/to/image/i/guess');
		$product->setName('black mirror');
		$product->setPrice(11.1);

		$this->assertInternalType('int', $imgId = $this->entityAssistant->insert($product, 'img'));
		$product->setImageId($imgId);
		$this->assertInternalType('int', $id = $this->entityAssistant->insert($product, 'p'));

		$data = $this->connection->select('name, price, image_id')->from('products')->where('`id` = %i', $id)->fetch();
		$this->assertEquals('black mirror', $data['name']);
		$this->assertEquals(11.1, $data['price']);
		$this->assertEquals($imgId, $data['image_id']);

		$data = $this->connection->select('path')->from('images')->where('`id` = %i', $imgId)->fetch();
		$this->assertEquals('path/to/image/i/guess', $data['path']);
	}

	/**
	 * @test
	 */
	public function updateCanChangeRowsInMultipleTables()
	{
		// this is a sketchy way to prepare fixture enttiy - it depends on knowing of inner workings of pillar. Should probably use mock instead, but am too lazy to do so.
		$product = new GridProduct(['id' => 25, 'imageId' => 1]);
		$product->setName('really amazing bedsheet');
		$product->setImage('/new/path');

		$affectedRows = $this->entityAssistant->update($product, ['p', 'img']);

		$this->assertEquals(2, $affectedRows);

		$data = $this->connection->select('*')->from('products')->fetchAll();
		$this->assertCount(1, $data);
		$this->assertEquals([
			'id' => 25,
			'image_id' => 1,
			'price' => 12.5,
			'name' => 'really amazing bedsheet',
		], $data[0]->toArray());

		$data = $this->connection->select('*')->from('images')->fetchAll();
		$this->assertCount(1, $data);
		$this->assertEquals([
			'id' => 1,
			'path' => '/new/path',
		], $data[0]->toArray());
	}

	protected function getConnection()
	{
		$pdo = new \PDO('mysql:host=127.0.0.1;dbname=testdb', 'travis', '', [\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8']);
		return $this->createDefaultDBConnection($pdo);
	}

	protected function getDataSet()
	{
		return new ArrayDataSet([
			'images' => [
				[
					'id' => 1,
					'path' => '/path/to/image',
				],
			],
			'products' => [
				[
					'id' => 25,
					'image_id' => 1,
					'price' => 12.5,
					'name' => 'amazing bedsheet',
				]
			],
		]);
	}
}
