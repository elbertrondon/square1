<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Wishlist
 *
 * @ORM\Table(name="wishlist")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\WishlistRepository")
 */
class Wishlist
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=50, nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", length=255, nullable=true, unique=true)
     */
    private $token;
    
    /**
     * @var string
     *
     * @ORM\Column(name="url_token", type="string", length=255, nullable=true, unique=true)
     */
    private $urlToken;
    
    /**
     * @ORM\ManyToMany(targetEntity="User", inversedBy="wishlist")
     * @ORM\JoinTable(name="users_wishlist")
     */
    private $users;
    
    /**
     * @ORM\ManyToMany(targetEntity="Products", mappedBy="wishlist")
     */
    private $products;

    public function __construct() {
        $this->users = new ArrayCollection();
        $this->products = new ArrayCollection();
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
     * @return Wishlist
     */
    public function setName($name)
    {
        $this->name = $name;

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
     * Set token
     *
     * @param string $token
     *
     * @return Wishlist
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    
    

    /**
     * Add user
     *
     * @param \AppBundle\Entity\User $user
     *
     * @return Wishlist
     */
    public function addUser(\AppBundle\Entity\User $user)
    {
        $this->users[] = $user;

        return $this;
    }

    /**
     * Remove user
     *
     * @param \AppBundle\Entity\User $user
     */
    public function removeUser(\AppBundle\Entity\User $user)
    {
        $this->users->removeElement($user);
    }

    /**
     * Get users
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * Add product
     *
     * @param \AppBundle\Entity\Products $product
     *
     * @return Wishlist
     */
    public function addProduct(\AppBundle\Entity\Products $product)
    {
        $this->products[] = $product;

        return $this;
    }

    /**
     * Remove product
     *
     * @param \AppBundle\Entity\Products $product
     */
    public function removeProduct(\AppBundle\Entity\Products $product)
    {
        $this->products->removeElement($product);
    }

    /**
     * Get products
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * Set urlToken
     *
     * @param string $urlToken
     *
     * @return Wishlist
     */
    public function setUrlToken($urlToken)
    {
        $this->urlToken = $urlToken;

        return $this;
    }

    /**
     * Get urlToken
     *
     * @return string
     */
    public function getUrlToken()
    {
        return $this->urlToken;
    }
}
