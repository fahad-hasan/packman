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
 * Private: $cart, balanceWeight(), moveThePackages(), resetPackages()
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
     * - Step 01: We find out the minimum number of packages we need to ship the order
     * - Step 02: We clear the package contents by taking out the items we had put inside them (same as initiating a new package)
     * - Step 03: Sort the cart items by descending order of weight, heavier items first
     * - Step 04: Take the package with the lowest shipping cost per gram and put the item there
     *
     * Returns: array
     */
    public function getPackages() {

        //traverse all the cart items, in their original order
        foreach($this->cart->getItems() as $item) {

            if ($this->debug) echo "Putting down ".$item['name'].", ".$item['weight']."g<br/>";
            //Creating the first package because we have none!
            if (count($this->packages) == 0) {
                $package = new Package();
                $this->packages[] = $package;
                if ($this->debug) echo "Opening the Package ...<br/>";
            }

            //Lets see what can we put in each of the boxes
            $added = false;
            foreach ($this->packages as $package) {
                //check if the package has capacity to hold this item
                if ($package->canHold($item)) {
                    //Add the item to the package
                    $package->add($item);
                    $added = true;
                    if ($this->debug) echo $item['name'].", ".$item['weight']."g fits in Package<br/>";
                    break;  //goto the next item
                } else {
                    if ($this->debug) echo $item['name'].", ".$item['weight']."g doesn't fit in Package<br/>";
                }
            }

            //This means we don't have any existing package which can hold this item
            if (!$added) {
                //Time to open a new BOX!
                $package = new Package();
                $package->add($item);
                $this->packages[] = $package;
                if ($this->debug) echo "Opening a new package...<br/>";
                if ($this->debug) echo "Putting down ".$item['name'].", ".$item['weight']."g in Package<br/>";
            }

        }

        //calculate the min number of boxes we need
        $total_boxes = count($this->packages);
        if ($total_boxes > 0) {
            if ($this->debug) echo "Hmmm, we need a total of ".$total_boxes." packages...<br/>";
            //clear the box contents
            $this->resetPackages();
            //put the items back in the packages balancing their weight as equally as possible
            $this->balanceWeight();
        }

        //this is te most efficient packaging combination, hopefully!
        return $this->packages;
    }

    /*
     * Redistributes the cart items to the packages for maximum efficiency and lowest possible shipping cost
     * Returns: N/A
     */
    private function balanceWeight() {
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
    private function resetPackages() {
        $count = count($this->packages);
        $this->packages = array();
        for($i = 0; $i < $count; $i++ ) {
            $this->packages[] = new Package();
        }
    }

}