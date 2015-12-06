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
 * Private: $cart, arrangePackages(), moveThePackages(), getEmptyPackages(), balanceWeight(), balance(&$a, &$b), getItemsCombinationByWeight($package, $weight)
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
            //put the items in the packages balancing their weight as equally as possible
            $this->arrangePackages();
            //balance weight
            if ($total_boxes > 1) {
                $this->balanceWeight();
            }
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
        $this->cart->sort(array($this, 'sortCartByWeightDesc'));
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
                    $this->moveThePackages('sortPackagesByCostGram');
                }
            }
        }
    }

    /*
     * Balances the weight of the packages by comparing two packages at a time
     * For example: for four packages 1, 2, 3, 4 the pairs to compare are
     * [1,2], [1,3], [1,4], [2,3], [2,4] and [3,4]
     */
    private function balanceWeight() {
        for($i = 0; $i < count($this->packages); $i++) {
            for($j = $i; $j < count($this->packages); $j++) {
                $this->balance($this->packages[$i], $this->packages[$j]);
            }
        }
    }

    /*
     * Compares two packages and weight-balance them. The steps are:
     * 1. Check if they have weight capacity without increasing shipping cost
     * 2. Sort two packages by weight to get the heavier (from package) and the lighter (to package)
     * 3. Calculate the weight difference and the weight to balance them equally
     * 4. Get a possible combination of items as close to the weight-to-balance as possible
     * 5. Remove these items from the from-package and add them to the to-package
     */
    private function balance(&$a, &$b) {
        //check if they have capacity without increasing shipping cost
        if ($a->weight_capacity_sh > 0 || $b->weight_capacity_sh > 0) {

            //sort the two packages by weight
            $sorted = array($a, $b);
            usort($sorted, function ($a, $b) {
                if ($a->weight == $b->weight) {
                    return 0;
                } else if ($a->weight < $b->weight) {
                    return +1;
                } else {
                    return -1;
                }
            });

            //first package is the heavier one
            $from_package = &$sorted[0];
            //and second one is the lighter one
            $to_package = &$sorted[1];

            //calculate the target weight to balance
            $weight_to_balance = ($from_package->weight - $to_package->weight) / 2;
            if ($weight_to_balance > 0) {
                //get a combination of items from the package which is as close to the weight-to-balance as possible
                $items = $this->getItemsCombinationByWeight($from_package, $weight_to_balance);
                foreach($items as $item) {
                    //move the items from one package to another
                    $from_package->remove($item);
                    $to_package->add($item);
                }
            }
        }
    }

    /*
     * Get a set of item combinations that is closest to the given weight
     * Returns: integer
     */
    private function getItemsCombinationByWeight($package, $weight) {
        //get all subsets of items array of the package
        $results = array(array());
        foreach ($package->items as $element) {
            foreach ($results as $combination) {
                $set = array_merge(array($element), $combination);
                $results[] = $set;
            }
        }

        //find the combination of items that weight as close to the target weight as possible
        $closest = array();
        foreach ($results as $items) {
            //put the weights of the items within the combination into a separate array
            $weights = array();
            foreach($items as $item) {
                $weights[] = $item['weight'];
            }

            //if we already have considered a few combinations to be the closest one, get their weights as well
            $closest_weight = array();
            if (!empty($closest)) {
                foreach ($closest as $item) {
                    $closest_weight[] = $item['weight'];
                }
            }

            //now we compare the two and only if there is a better combination, we replace it
            if (empty($closest) || abs($weight - array_sum($closest_weight)) > abs(array_sum($weights) - $weight)) {
                $closest = $items;
            }
        }

        //we hope, this set of items has the combined weight as close to the given weight as possible
        return $closest;

    }

    /*
     * Moves the most cost efficient package to the front of the list
     * Returns: N/A
     */
    private function moveThePackages($sort) {
        usort($this->packages, array($this, $sort));
    }

    /*
     * Closure for usort, sorts packages by cost per gram in ascending order
     * Returns: 0, 1, -1
     */
    public function sortPackagesByCostGram($a, $b) {
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
    public function sortCartByWeightDesc($a, $b) {
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