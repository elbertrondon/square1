<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\FOSUserBundle;
use FOS\UserBundle\Form\Factory\FactoryInterface;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use FOS\UserBundle\Controller\SecurityController;
use AppBundle\Form\UserType;
use Symfony\Component\Security\Core\Security;
use AppBundle\Entity\User;
use AppBundle\Entity\Wishlist;
use AppBundle\Entity\Products;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\CssSelector;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DefaultController extends Controller {

    /**
     * @Route("/syncData", name="syncData")
     */
    public function syncData(Request $request) {

        $em = $this->getDoctrine()->getManager();
        $classMetaData = $em->getClassMetadata('AppBundle:Products');
        $connection = $em->getConnection();
        $dbPlatform = $connection->getDatabasePlatform();
        $connection->beginTransaction();
        try {
            $connection->query('SET FOREIGN_KEY_CHECKS=0');
            $q = $dbPlatform->getTruncateTableSql($classMetaData->getTableName());
            $connection->executeUpdate($q);
            $connection->query('SET FOREIGN_KEY_CHECKS=1');
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollback();
        }

        $baseUrl = 'https://www.appliancesdelivered.ie';
        $uri = [0 => '/small-appliances', 1 => '/dishwashers'];

        foreach ($uri as $uriValue) {

            $page1 = file_get_contents($baseUrl . $uriValue);
            $crawler = new Crawler($page1);
            $filter = '.search-results-footer > p > a';
            $getCantPage = $crawler
                            ->filter($filter)->last()->attr('href');
            unset($crawler);

            $cant = split('page=', $getCantPage);

            for ($i = 1; $i <= $cant[1]; $i++) {

                $newUrl = $baseUrl . $uriValue . '?&page=' . $i;
                $html = file_get_contents($newUrl);

                $crawler = new Crawler($html);
                $filter = '.search-results-product';
                $allProducts = $crawler
                        ->filter($filter)
                        ->each(function (Crawler $node) {
                    return $node->html();
                });
                unset($crawler);

                $productName = $price = $description = $image = $auxProducts = $finalProducts = $brand = array();
                $productNameFilter = '.product-description > div > div > h4';
                $descriptionFilter = 'ul > li';
                $priceFilter = '.product-description > div > .col-lg-4 > div > h3';
                $imageFilter = '.product-image > a';
                $brandFilter = '.product-description > div > div > a';

                foreach ($allProducts as $index => $HTML) {
                    $crawler = new Crawler($HTML);
                    $productName[] = $crawler
                            ->filter($productNameFilter)
                            ->text();
                    $description[$index] = array();
                    $description[$index] = $crawler
                            ->filter($descriptionFilter)
                            ->each(function (Crawler $node) {
                        return $node->html();
                    });
                    $price[] = $crawler
                            ->filter($priceFilter)
                            ->text();
                    $image[] = $crawler
                            ->filter($imageFilter)
                            ->filterXpath('//img')
                            ->extract(array('src'));
                    $brand[] = $crawler
                            ->filter($brandFilter)
                            ->filterXpath('//img')
                            ->extract(array('src'));
                    unset($crawler);
                    array_push($auxProducts, ['name' => $productName[$index], 'description' => $description[$index], 'price' => $price[$index], 'image' => $image[$index], 'brand' => $brand[$index]]);
                }

                foreach ($auxProducts as $value) {
                    
                    $priceAux = split('€', $value['price']);

                    $product = new Products();
                    $product->setName($value['name']);
                    $product->setImage($value['image'][0]);
                    $product->setPrice(str_replace(",", "", $priceAux[1]));
                    if ($value['brand']) {
                        $product->setBrand($value['brand'][0]);
                    }
                    $product->setDescription($value['description']);
                    $em->persist($product);
                }
                $em->flush();
            }
        }

        return new Response(json_encode('sync complete'));
    }

    /**
     * @Route("/wishList", name="wishList")
     */
    public function wishListAction(Request $request) {

        $em = $this->getDoctrine()->getManager();
        $securityContext = $this->container->get('security.authorization_checker');

        if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $user = $em->getRepository('AppBundle:User')->find($this->getUser()->getId());

            $getWishlist = $user->getWishlist();

            foreach ($getWishlist as $value) {
                $wishlistId = $value->getId();
            }

            $wishlist = $em->getRepository('AppBundle:Wishlist')->find($wishlistId);

            $token = $wishlist->getUrlToken();

            $products = $wishlist->getProducts();

            return $this->render('wishlist.html.twig', ['name' => $user->getName() . ' ' . $user->getLastname(), 'products' => $products, 'urlToken' => $token]);
        } else {
            return $this->render('wishlist.html.twig', ['products' => null]);
        }
    }

    /**
     * @Route("/wishListShared", name="wishListShared")
     */
    public function wishListSharedAction(Request $request) {

        $em = $this->getDoctrine()->getManager();

        $token = $request->query->get('token');

        $wishlist = $em->getRepository('AppBundle:Wishlist')->findOneBy(['token' => $token]);
        
        $getUser = $wishlist->getUsers();

        foreach ($getUser as $value) {
            $userId = $value->getId();
        }

        $user = $em->getRepository('AppBundle:User')->find($userId);

        $products = $wishlist->getProducts();

        return $this->render('wishlistShared.html.twig', ['products' => $products, 'user' => $user]);
    }

    /**
     * @Route("/productsGrid", name="productsGrid")
     */
    public function productsGridAction(Request $request) {

        $em = $this->getDoctrine()->getManager();

        $products = $em->getRepository('AppBundle:Products')->findAll();

        $dql = "SELECT p FROM AppBundle:Products p";
        $query = $em->createQuery($dql);

        $paginator = $this->get('knp_paginator');
        $products = $paginator->paginate(
                $query, /* query NOT result */ $request->query->getInt('page', 1)/* page number */, 20/* limit per page */
        );


        $securityContext = $this->container->get('security.authorization_checker');
        if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $user = $this->getUser();
            return $this->render('productsGrid.html.twig', ['name' => $user->getName() . ' ' . $user->getLastname(), 'products' => $products]);
        } else {
            return $this->render('productsGrid.html.twig', ['products' => $products]);
        }
    }

    /**
     * @Route("/createUser", name="createUser")
     */
    public function createUserAction(Request $request) {

        /** @var $userManager UserManagerInterface */
        $userManager = $this->get('fos_user.user_manager');
        /** @var $dispatcher EventDispatcherInterface */
        $dispatcher = $this->get('event_dispatcher');

        $user = $userManager->createUser();
        $user->setEnabled(true);

        $event = new GetResponseUserEvent($user, $request);
        $dispatcher->dispatch(FOSUserEvents::REGISTRATION_INITIALIZE, $event);

        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }

        $form = $this->createForm(UserType::class, $user);
        $form->setData($user);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {

                $event = new FormEvent($form, $request);
                $dispatcher->dispatch(FOSUserEvents::REGISTRATION_SUCCESS, $event);

                $userManager->updateUser($user);

                $this->container->get('logger')->info(
                        sprintf("New user registration: %s", $user)
                );

                if (null === $response = $event->getResponse()) {
                    $url = $this->generateUrl('productsGrid');
                    $response = new RedirectResponse($url);
                }

                $token = openssl_random_pseudo_bytes(8);
                $token = bin2hex($token);

                $em = $this->getDoctrine()->getManager();
                $wishlist = new Wishlist();
                $users = $em->getRepository('AppBundle:User')->findOneById($user->getId());
                $tokenUrl = $this->generateUrl('wishListShared', array('token' => $token), UrlGeneratorInterface::ABSOLUTE_URL);
                $wishlist->setName('My Wishlist');
                $wishlist->setToken($token);
                $wishlist->setUrlToken($tokenUrl);
                $wishlist->addUser($users);
                $em->persist($wishlist);
                $em->flush();


                $dispatcher->dispatch(FOSUserEvents::REGISTRATION_COMPLETED, new FilterUserResponseEvent($user, $request, $response));

                return $response;
            }

            $event = new FormEvent($form, $request);
            $dispatcher->dispatch(FOSUserEvents::REGISTRATION_FAILURE, $event);

            if (null !== $response = $event->getResponse()) {
                return $response;
            }
        }

        $securityContext = $this->container->get('security.authorization_checker');
        if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $user = $this->getUser();
            return $this->render('createUser.html.twig', ['form' => $form->createView(), 'name' => $user->getName() . ' ' . $user->getLastname()]);
        } else {
            return $this->render('createUser.html.twig', array('form' => $form->createView()));
        }
    }

    /**
     * @Route("/ajax/addToWishList", name="addToWishList")
     * @Method({"POST"})
     */
    public function addToWishList(Request $request) {

        if ($request->isXmlHttpRequest()) {
            $user = $this->getUser();
            $em = $this->getDoctrine()->getManager();
            $productId = $request->get('productId');
            $wish = $user->getWishlist();
            foreach ($wish as $value) {
                $wishlistId = $value->getId();
            }
            $product = $em->getRepository('AppBundle:Products')->find($productId);

            $wishlist = $em->getRepository('AppBundle:Wishlist')->find($wishlistId);

//            $wishlist->addProduct($product);

            $product->addWishlist($wishlist);

            $em->flush();

            return new Response(json_encode(array('value' => true)));
        }
    }

    /**
     * @Route("/ajax/removeProducts", name="removeProducts")
     * @Method({"POST"})
     */
    public function removeProducts(Request $request) {

        if ($request->isXmlHttpRequest()) {
            $userId = $request->get('userId');
            $productId = $request->get('productId');
            $em = $this->getDoctrine()->getManager();

            $user = $em->getRepository('AppBundle:User')->find($userId);

            $wish = $user->getWishlist();
            foreach ($wish as $value) {
                $wishlistId = $value->getId();
            }
            $product = $em->getRepository('AppBundle:Products')->find($productId);

            $wishlist = $em->getRepository('AppBundle:Wishlist')->find($wishlistId);

            $product->removeWishlist($wishlist);

            $em->flush();

            return new Response(json_encode(array('value' => true)));
        }
    }

    /**
     * @Route("/ajax/shareWishList", name="shareWishList")
     * @Method({"POST"})
     */
    public function shareWishList(Request $request) {

        if ($request->isXmlHttpRequest()) {
            $user = $this->getUser();
            $em = $this->getDoctrine()->getManager();

            $wish = $user->getWishlist();
            foreach ($wish as $value) {
                $wishlistId = $value->getId();
            }

            $wishlist = $em->getRepository('AppBundle:Wishlist')->find($wishlistId);

            $email = $request->request->get('email');

            $emailHR = $this->getParameter('mailer_user');
            $sender = $this->getParameter('sender_name');
            $message = \Swift_Message::newInstance();
            $message->setSubject($user->getName() . ' wants to share its product list with you')
                    ->setFrom(array($emailHR => $sender))
                    ->setTo($email)
                    ->setBody(
                            $this->renderView('email.html.twig', array('url' => $wishlist->getToken())), 'text/html');
            $this->get('mailer')->send($message);

            return new Response(json_encode(array('value' => true)));
        }
    }

}
