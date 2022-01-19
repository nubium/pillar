<?php declare(strict_types=1);
namespace SpareParts\Pillar\Test\Fixtures;

use SpareParts\Pillar\Entity\Entity;
use SpareParts\Pillar\Entity\IEntity;
use SpareParts\Pillar\Mapper\Annotation as Pillar;

/**
 * @Pillar\Table(name="products", identifier="p")
 * @Pillar\Table(name="products", identifier="p", code="`products` `p` USE INDEX (`idx_try_me_out`)",
 *     tags={"idx"})
 * @Pillar\Table(name="images", identifier="img", code="LEFT JOIN `images` `img` ON `img`.`id` = `p`.`image_id`")
 * @Pillar\Table(name="images", identifier="img", code="LEFT JOIN `images` `img` ON `p`.`image_id` = `img`.`id`",
 *     tags={"slightly_diff_join"})
 */
class GridProduct extends Entity implements IEntity
{
	/**
	 * @var string
	 * @Pillar\Column(table="p", primary=true)
	 */
	protected string $id;

	/**
	 * @var string
	 * @Pillar\Column(table="p")
	 */
	protected string $name;

	/**
	 * @var int
	 * @Pillar\Column(name="image_id", table="p")
	 * @Pillar\Column(name="id", table="img", primary=true)
	 */
	protected int $imageId;

	/**
	 * @var string
	 * @Pillar\Column(table="img", name="path")
	 */
	protected string $image;

	/**
	 * @var float
	 * @Pillar\Column(table="p")
	 */
	protected float $price;


	public function getId(): string
	{
		return $this->id;
	}


	public function setId(string $id): void
	{
		$this->id = $id;
	}

	public function getName(): string
	{
		return $this->name;
	}


	public function setName(string $name): void
	{
		$this->name = $name;
	}


	public function getImageId(): int
	{
		return $this->imageId;
	}


	public function setImageId(int $imageId): void
	{
		$this->imageId = $imageId;
	}


	public function getImage(): string
	{
		return $this->image;
	}


	public function setImage(string $image): void
	{
		$this->image = $image;
	}


	public function getPrice(): float
	{
		return $this->price;
	}

	public function setPrice(float $price): void
	{
		$this->price = $price;
	}
}
