<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace AppBundle\Services;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
/**
 * Description of SynchronizeData
 *
 * @author Elbert
 */
class SynchronizeData extends Controller {
    
    public function synchronize(){
        
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
        
        $html = file_get_contents('https://www.appliancesdelivered.ie/dishwashers');
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

            $product = new Products();
            $product->setName($value['name']);
            $product->setImage($value['image'][0]);
            $product->setPrice($value['price']);
            $product->setBrand($value['brand'][0]);
            $product->setDescription($value['description']);
            $em->persist($product);
        }
        $em->flush();
        
        return "Hola desde el servicio";
    }
}
