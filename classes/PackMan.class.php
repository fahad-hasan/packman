<?php
/**
 * Created by PhpStorm.
 * User: floyd
 * Date: 28/11/15
 * Time: 10:59 AM
 *
 * This class handles the actual algorithm behind cost efficient packaging
 *
 * Construct: Cart $cart = required, $debug (optional, default false)
 *
 * Public: $packages, getPackages(), sortPackages($a, $b), sortCart($a, $b)
 * Private: $cart, arrangePackages(), moveThePackages(), getEmptyPackages()
 *
 */

namespace App\Classes;

class PackMan {

    private $packages = array();    //holds the packages
    private $cart;                  //the cart, current order
    public $debug;

    /*
     * Initialize the PackMan from the $cart
     * Returns: N/A
     */
    public function __construct(Cart $cart, $debug = false) {
        $this->cart = $cart;
        $this->debug = $debug;
    }

    /*
     * Calculates and balances the packages based on their price, weight and shipping cost using the following algorithm:
     *
     * - Step 01: We find out the minimum number of packages we need to ship the ordered items
     * - Step 02: We clear all the packages by taking out the items we had put inside them (same as initiating a new packages)
     * - Step 03: Sort the cart items by descending order of weight, heavier items first
     * - Step 04: Take the package with the lowest shipping cost per gram and put the item there
     *
     * Returns: array
     */
    public function getPackages() {

        //calculate the min number of boxes we need
        $total_boxes = $this->getPackageCount();
        if ($total_boxes > 0) {
            if ($this->debug) echo "Hmmm, we need a total of ".$total_boxes." packages...<br/>";
            //clear the box contents
            $this->getEmptyPackages($total_boxes);
            //put the items back in the packages balancing their weight as equally as possible
            $this->arrangePackages();
        }

        //this is te most efficient packaging combination, hopefully!
        return $this->packages;
    }

    private function getPackageCount() {
        $count_by_price = ceil($this->cart->price / 250);
        $count_by_weight = ceil($this->cart->weight / 5000);
        return max(array($count_by_price, $count_by_weight));
    }

    /*
     * Redistributes the cart items to the packages for maximum efficiency and lowest possible shipping cost
     * Returns: N/A
     */
    private function arrangePackages() {
        if ($this->debug) echo "Balancing the items...<br/>";
        $this->cart->sort(array($this, 'sortCart'));
        foreach($this->cart->getItems() as $item) {
            for($i = 0; $i < count($this->packages); $i++) {
                $package =& $this->packages[$i];
                //check if the package can hold the item
                if ($package->canHold($item)) {
                    $package->add($item);
                    if ($this->debug) echo $item['name'].", ".$item['weight']."g fits in Package<br/>";
                    break;
                } else {
                    //lets move the packages
                    if ($this->debug) echo "Move packages<br/>";
                    $this->moveThePackages();
                }
            }
        }
    }

    /*
     * Moves the most cost efficient package to the front of the list
     * Returns: N/A
     */
    private function moveThePackages() {
        usort($this->packages, array($this, 'sortPackages'));
    }

    /*
     * Closure for usort, sorts packages by cost per gram in ascending order
     * Returns: 0, 1, -1
     */
    public function sortPackages($a, $b) {
        if ($a->cost_per_gram == $b->cost_per_gram) {
            return 0;
        } else if ($a->cost_per_gram > $b->cost_per_gram) {
            return +1;
        } else {
            return -1;
        }
    }

    /*
     * Closure for usort, sorts cart items by weight in descending order, heaviest items first
     * Returns: 0, 1, -1
     */
    public function sortCart($a, $b) {
        if ($a['weight'] == $b['weight']) {
            return 0;
        } else if ($a['weight'] < $b['weight']) {
            return +1;
        } else {
            return -1;
        }
    }

    /*
     * Counts the number of packages, clears them (by recreating them)
     * Returns: 0, 1, -1
     */
    private function getEmptyPackages($count) {
        $this->packages = array();
        for($i = 0; $i < $count; $i++ ) {
            $this->packages[] = new Package();
        }
    }

}