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
	protected $id;

	/**
	 * @var string
	 * @Pillar\Column(table="p")
	 */
	protected $name;

	/**
	 * @var int
	 * @Pillar\Column(name="image_id", table="p")
	 * @Pillar\Column(name="id", table="img", primary=true)
	 */
	protected $imageId;

	/**
	 * @var string
	 * @Pillar\Column(table="img", name="path")
	 */
	protected $image;

	/**
	 * @var float
	 * @Pillar\Column(table="p")
	 */
	protected $price;



	/**
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param string $id
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * @return int
	 */
	public function getImageId()
	{
		return $this->imageId;
	}

	/**
	 * @param int $imageId
	 */
	public function setImageId($imageId)
	{
		$this->imageId = $imageId;
	}

	/**
	 * @return string
	 */
	public function getImage()
	{
		return $this->image;
	}

	/**
	 * @param string $image
	 */
	public function setImage($image)
	{
		$this->image = $image;
	}

	/**
	 * @return float
	 */
	public function getPrice()
	{
		return $this->price;
	}

	/**
	 * @param float $price
	 */
	public function setPrice($price)
	{
		$this->price = $price;
	}
}
