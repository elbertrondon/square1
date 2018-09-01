<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;
/**
 * User
 *
 * @ORM\Table(name="user")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserRepository")
 */
class User extends BaseUser
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="Name", type="string", length=50)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="Lastname", type="string", length=50)
     */
    private $lastname;
    
    /**
     * @ORM\ManyToMany(targetEntity="Wishlist", mappedBy="users")
     */
    private $wishlist;
    
    

    public function __construct() {
        $this->wishlist = new \Doctrine\Common\Collections\ArrayCollection();
    }


    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return User
     */
    public function setName($name)
    {
        $this->name = $name;
        $this->username = $name;
        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set lastname
     *
     * @param string $lastname
     *
     * @return User
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * Get lastname
     *
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    

    

    /**
     * Add wishlist
     *
     * @param \AppBundle\Entity\Wishlist $wishlist
     *
     * @return User
     */
    public function addWishlist(\AppBundle\Entity\Wishlist $wishlist)
    {
        $this->wishlist[] = $wishlist;

        return $this;
    }

    /**
     * Remove wishlist
     *
     * @param \AppBundle\Entity\Wishlist $wishlist
     */
    public function removeWishlist(\AppBundle\Entity\Wishlist $wishlist)
    {
        $this->wishlist->removeElement($wishlist);
    }

    /**
     * Get wishlist
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getWishlist()
    {
        return $this->wishlist;
    }
}
